<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Import;
use App\Models\ImportError;
use App\Models\User;
use App\Events\ImportStarted;
use App\Events\ImportCompleted;
use App\Events\ImportFailed;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;


class ImportUsersJob implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $importId)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $import = Import::findOrFail($this->importId);
        $import->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
        ImportStarted::dispatch($import);
        try {
           $filePath = Storage::disk('local')->path($import->file_path);
           if (! Storage::disk('local')->exists($import->file_path)) {
            throw new \RuntimeException(
                "Import file not found: {$filePath}" );
    }
  
            $handle = fopen($filePath, 'r');
            if ($handle === false) {
                throw new \RuntimeException(
                    'Unable to open CSV file.'
                );
            }
            $header = fgetcsv($handle);
            if ($header === false) {
                fclose($handle);
                throw new \RuntimeException(
                    'CSV file is empty.'
                );
            }
            $header = array_map(
                fn ($value) => strtolower(trim($value)),
                $header
            );
            if ($header !== ['name', 'email']) {
                fclose($handle);
                throw new \RuntimeException(
                    'CSV header must be: name,email'
                );
            }
            $totalRecords = 0;
            while (($row = fgetcsv($handle)) !== false) {
                if ($this->isEmptyRow($row)) {
                    continue;
                }

                $totalRecords++;
            }
            $import->update([
                'total_records' => $totalRecords,
            ]);
            rewind($handle);
            fgetcsv($handle);
            $rowNumber = 1;
            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;
                if ($this->isEmptyRow($row)) {
                    continue;
                }
                $name = trim($row[0] ?? '');
                $email = trim($row[1] ?? '');
                $data = [
                    'name' => $name,
                    'email' => $email,
                ];
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
                            'unique:users,email',
                        ],
                    ]
                );
                if ($validator->fails()) {
                    ImportError::create([
                        'import_id' => $import->id,
                        'row_number' => $rowNumber,
                        'row_data' => $data,
                        'error_message' => implode(
                            ' ',
                            $validator->errors()->all()
                        ),
                    ]);
                    $import->increment('failed_records');
                    continue;
                }
                try {
                    User::create([
                        'name' => $name,
                        'email' => $email,
                        'password' => Hash::make(
                            Str::random(32)
                        ),
                    ]);
                    $import->increment('processed_records');
                    } 
                    catch (Throwable $e) {
                    ImportError::create([
                        'import_id' => $import->id,
                        'row_number' => $rowNumber,
                        'row_data' => $data,
                        'error_message' => $e->getMessage(),
                    ]);
                    $import->increment('failed_records');
                }
            }
            fclose($handle);
            $import->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        } catch (Throwable $e) {
            $import->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => $e->getMessage(),
            ]);
            ImportFailed::dispatch($import, $e->getMessage());
            throw $e;
        }
        ImportCompleted::dispatch($import);
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
