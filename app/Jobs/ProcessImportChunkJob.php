<?php

namespace App\Jobs;

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
use Illuminate\Support\Facades\Str;
use RuntimeException;
use Throwable;

class ProcessImportChunkJob implements ShouldQueue
{
    use Queueable;
    use Dispatchable;
    use InteractsWithQueue;
    use SerializesModels;
    use Batchable;


    public int $tries=3;

    public int $timeout=120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $importId ,
        public int $startRow , 
        public int $chunkSize=1000
    )
    {
        //
    }
    public function backoff():array
    {
        return [10,30,60];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->batch()?->cancelled()){
            Log::info('Import chunk skipped because batch was cancelled',[
            'import_id'=>$this->importId,
            'start_row'=>$this->startRow
            ]);
            return ;
        }
        $import =Import::findOrFail($this->importId);

        if ($import->status === 'cancelled'){
            LOg::info('Import chunk skipped because import was cancelled',[
                'import_id'=>$import->id,
                'start_row' =>$this->startRow
            ]);
            return ;
        }
        $disk=Storage::disk('local');
        if (! $disk->exists($import->file_path)) {
            throw new RuntimeException(
                    "Import file not found: {$filePath}"
            );
        }
        $filePath=$disk->path($import->file_path);
        $handle = fopen($filePath, 'r');
        if ($handle ===false){
            throw new \RuntimeException(
                'Unable to open CSV file.'
            );
        }
        $processedInChunk=0;
        $failedInChunk=0;
        $skippedINChunk=0;
        
        try {
            $rowNumber = 0;
            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;
                if ($rowNumber === 1) {
                    continue;
                }

                if ($rowNumber < $this->startRow) {
                    continue;
                }

                if ($rowNumber >= $this->startRow + $this->chunkSize) {
                    break;
                }

                if ($this->isEmptyRow($row)) {
                    continue;
                }

                $currentImport=$import->fresh();
                if ($currentImport->status === 'cancelled') {
                    Log::info(
                        'Import chunk stopped because import was cancell',[
                            'import_id'=>$import->id,
                            'row_number'=>$rowNumber
                        ]
                    );
                    break;
                }
                $result = $this->processRow(
                    $import,
                    $row,
                    $rowNumber
                );

                if ($result === 'processed') {
                    $processedInChunk ++;
                }elseif($result === 'failed') {
                    $failedInChunk ++;
                }elseif($result ==='skipped'){
                    $skippedINChunk ++;
                } 
            }  
        }    
        finally {
            fclose($handle);
        }
        Log:info('Import chunk processed',[
            'import_id'=>$import->id,
            'start_row'=>$this->startRow,
            'chunk_size'=>$this->chunksize,
            'processed'=>$processedInChunk,
            'failed'=>$failedInChunk,
            'skipped'=>$skippedINChunk
        ]);
    }
    private function processRow(
        Import $import,
        array $row,
        int $rowNumber ): 
        string {
        $name = trim($row[0] ?? '');
        $email = trim($row[1] ?? '');

        $data = [
            'name' => $name,
            'email' => $email,
        ];

        $record = ImportRecord::firstOrCreate(
            [
                'import_id' => $import->id,
                'row_number' => $rowNumber,
            ],
            [
                'status' => 'processing',
            ]
        );

        if($record->status === 'processed'){
            return 'skipped';
        }

        if($record->status === 'failed'){
            return 'skipped';
        }

        $validator = Validator::make(
            $data,
            [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                ],
                'email' => [
                    'required',
                    'email',
                    'max:255',
                ],
            ]
        );

        if ($validator->fails()) {
            return $this->markRowAsFailed(
                $import,
                $record,
                $data,
                implode( ' ', $validator->errors()->all() )
            );
        }

        try {
            DB::transaction(function () use (
                $record,
                $data
            ) 
            {
                $user = User::firstOrCreate(
                    [
                        'email' => $data['email'],
                    ],
                    [
                        'name' => $data['name'],
                        'password' => Hash::make(
                            Str::random(32)
                        ),
                    ]
                );
                $record->update([ 'status' => 'processed', ]);
            });
            $import->increment('processed_records');
            return 'processed';

        } catch (Throwable $e) {
            Log::error('Import row failed unexpectedly', [
                'import_id' => $import->id,
                'row_number' => $rowNumber,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function markRowAsFailed(Import $import, ImportRecord $record,  array $data,  String $errorMessage):string
        {
            DB::transaction(function() use(
                $import,
                $record,
                $data,
                $errorMessage
            )
            {
                ImportError::firstOrCreate(
                [ 
                'import_id' => $import->id,
                'row_number' => $record->row_number
                ],
                [
                    'row_data'=>$data,
                    'error_message'=>$errorMessage
                ]
            );
            $record->update(['status'=>'failed']);

            $import->increment('failed_recored');

            });
            Log::warning('Import row validation failed',[
                'import_id'=>$import->id,
                'row_number'=>$rowNumber
            ]);
            return 'failed';
        }

    private function isEmptyRow(array $row): bool
    {
        return count(
            array_filter(
                $row,
                fn ($value) => trim((string) $value) !== ''
            )
        ) === 0;
    }
}
