<?php

namespace App\Jobs;

use App\Events\ImportCompleted;
use App\Models\Import;
use App\Models\ImportError;
use App\Models\ImportRecord;
use App\Models\User;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ProcessImportChunkJob implements ShouldQueue
{
    use Dispatchable,InteractsWithQueue,Queueable,SerializesModels,Batchable;

    public int $tries = 3;
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $importId,
        public int $startRow,
        public int $chunkSize = 1000,
        public int $totalRecords = 0
    ){
        //
    }

    /**
     * Retry delays in seconds.
     */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            Log::info('Import chunk skipped because batch was cancelled',
                [
                    'import_id' => $this->importId,
                    'start_row' => $this->startRow,
                ]
            );
            return;
        }
        $import = Import::findOrFail($this->importId);
        if ($import->status === 'cancelled') {
            Log::info(
                'Import chunk skipped because import was cancelled',
                [
                    'import_id' => $import->id,
                    'start_row' => $this->startRow,
                ]
            );
            return;
        }
        $disk = Storage::disk('local');
        if (! $disk->exists($import->file_path)) {
            throw new RuntimeException(
                "Import file not found: {$import->file_path}"
            );
        }
        $filePath = $disk->path(
            $import->file_path
        );
        $handle = fopen($filePath,'r');
        if ($handle === false) {
            throw new RuntimeException(
                'Unable to open CSV file.'
            );
        }

        $processedInChunk = 0;
        $failedInChunk = 0;
        $skippedInChunk = 0;

        try {
            $rowNumber = 0;
            while (
                ($row = fgetcsv($handle)) !== false
            ) 
            {
                $rowNumber++;
                if ($rowNumber === 1) {
                    continue;
                }

                if (
                    $rowNumber <
                    $this->startRow
                ) {
                    continue;
                }

                if (
                    $rowNumber >=
                    $this->startRow +
                    $this->chunkSize
                ) {
                    break;
                }
                
                if (
                    $this->isEmptyRow($row)
                ) {
                    continue;
                }

                $currentImport =$import->fresh();
                if (
                    $currentImport->status ===
                    'cancelled'
                ) 
                {
                    Log::info(
                        'Import chunk stopped because import was cancelled',
                        [
                            'import_id' =>
                                $import->id,

                            'row_number' =>
                                $rowNumber,
                        ]
                    );
                    break;
                }
                $result = $this->processRow(
                    $import,
                    $row,
                    $rowNumber
                );
                if ($result === 'processed') 
                {
                    $processedInChunk++;
                } 
                elseif (
                    $result === 'failed') 
                {
                    $failedInChunk++;

                } elseif (
                    $result === 'skipped') 
                {
                    $skippedInChunk++;
                }
            }
        } 
        finally {
            fclose($handle);
        }
        Log::info(
            'Import chunk processed',
            [
                'import_id'=>$import->id,
                'start_row'=>$this->startRow,
                'chunk_size'=>$this->chunkSize,
                'processed'=>$processedInChunk,
                'failed'=>$failedInChunk,
                'skipped'=>$skippedInChunk,
            ]
        );

        $lastRowOfThisChunk =
            $this->startRow +
            $this->chunkSize -1;

        $lastDataRow =$this->totalRecords + 1;

        if ($lastRowOfThisChunk >=$lastDataRow) 
        {
            $this->completeImport(
                $import
            );
        }
    }

    private function processRow(Import $import,array $row,int $rowNumber): string 
    {
        $name = trim(
            (string) ($row[0] ?? '')
        );
        $email = trim(
            (string) ($row[1] ?? '')
        );

        $data = [
            'name' => $name,
            'email' => $email,
        ];

        $record = ImportRecord::firstOrCreate(
            [
                'import_id'=>$import->id,
                'row_number'=>$rowNumber,
            ],
            [
                'status'=>'processing',
            ]
        );
        if (
            in_array(
                $record->status,
                [
                    'processed',
                    'failed',
                ],
                true
            )
        ) {
            return 'skipped';
        }

        $validator = Validator::make(
            $data,
            [
                'name' => ['required','string','max:255',],
                'email' => ['required','email','max:255',],
            ]
        );

        if ($validator->fails()) 
        {
            return $this->markRowAsFailed(
                $import,
                $record,
                $data,
                implode(' ',$validator->errors()->all())
            );
        }
        try {
            DB::transaction(
                function () use (
                    $record,
                    $data
                ) {
                    User::firstOrCreate(
                        [
                            'email'=>$data['email'],
                        ],
                        [
                            'name'=>$data['name'],
                            'password'=>Hash::make(Str::random(32)),
                        ]
                    );
                    $record->update([
                        'status'=>'processed',
                    ]);
                }
            );
            $import->increment('processed_records');
            return 'processed';
        } 
        catch (Throwable $e) {
            Log::error(
                'Import row failed unexpectedly',
                [
                    'import_id' =>$import->id,
                    'row_number' =>$rowNumber,
                    'error' => $e->getMessage(),
                ]
            );
            throw $e;
        }
    }
    private function markRowAsFailed(Import $import,ImportRecord $record,array $data,string $errorMessage): string 
    {
        DB::transaction(
            function () use (
                $import,
                $record,
                $data,
                $errorMessage
            ) {
                ImportError::firstOrCreate(
                    [
                        'import_id' =>$import->id,
                        'row_number' =>$record->row_number,
                    ],
                    [
                        'row_data' =>$data,
                        'error_message' =>$errorMessage,
                    ]
                );
                if ($record->status !=='failed')
                {
                    $record->update([
                        'status' =>'failed',
                    ]);
                    $import->increment(
                        'failed_records'
                    );
                }
            }
        );
        Log::warning(
            'Import row validation failed',
            [
                'import_id' =>$import->id,
                'row_number' =>$record->row_number,
            ]
        );
        return 'failed';
    }
    private function completeImport(Import $import): void 
    {
        $currentImport=$import->fresh();
        if ($currentImport->status ==='cancelled') 
        {
            Log::info(
                'Import completion skipped because import was cancelled',
                [
                    'import_id' =>$currentImport->id,
                ]
            );
            return;
        }
        if ($currentImport->status ==='completed')
            {
            return;
        }

        $currentImport->update([
            'status' =>'completed',
            'completed_at' =>now(),
        ]);
        event(
            new ImportCompleted($currentImport->fresh()
            )
        );
        Log::info(
            'Import completed successfully',
            [
                'import_id' =>$currentImport->id,
                'total_records' =>$currentImport->total_records,
                'processed_records' => $currentImport->processed_records,
                'failed_records' =>$currentImport->failed_records,
            ]
        );
    }

    private function isEmptyRow(array $row): bool 
    {
        return count(
            array_filter(
                $row,fn ($value) =>trim((string) $value) !== ''
            )
        ) === 0;
    }
}
