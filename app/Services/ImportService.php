<?php

namespace App\Services;

use App\Jobs\ProcessImportChunkJob;
use App\Models\Import;
use Illuminate\Bus\Batch;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ImportService
{
    private const CHUNK_SIZE = 1000;

    public function createImport(
        UploadedFile $file,
        int $userId
    ): Import {
        $filePath = $file->store('imports', 'local');

        $import = Import::create([
            'user_id' => $userId,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'status' => 'pending',
            'total_records' => 0,
            'processed_records' => 0,
            'failed_records' => 0,
        ]);

        try {
            $totalRecords = $this->countRecords($filePath);

            $import->update([
                'total_records' => $totalRecords,
            ]);

            if ($totalRecords === 0) {
                $import->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

                return $import->fresh();
            }

            $jobs = [];

            for (
                $startRow = 2;
                $startRow <= $totalRecords + 1;
                $startRow += self::CHUNK_SIZE
            ) {
                $jobs[] = new ProcessImportChunkJob(
                    importId: $import->id,
                    startRow: $startRow,
                    chunkSize: self::CHUNK_SIZE
                );
            }

            $batch = Bus::batch($jobs)
                ->name("Import #{$import->id}")

                ->before(function (Batch $batch) use ($import) {
                    $import->update([
                        'status' => 'processing',
                        'started_at' => now(),
                    ]);

                    Log::info('Import batch started', [
                        'import_id' => $import->id,
                        'batch_id' => $batch->id,
                    ]);
                })

                ->then(function (Batch $batch) use ($import) {
                    $currentImport = $import->fresh();

                    /*
                     * A cancelled import must never become completed.
                     */
                    if ($currentImport->status === 'cancelled') {
                        Log::info(
                            'Import batch completed but import is cancelled',
                            [
                                'import_id' => $import->id,
                                'batch_id' => $batch->id,
                            ]
                        );

                        return;
                    }

                    $currentImport->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                    ]);

                    Log::info('Import completed', [
                        'import_id' => $import->id,
                        'batch_id' => $batch->id,
                        'total_records' => $currentImport->total_records,
                        'processed_records' =>
                            $currentImport->processed_records,
                        'failed_records' =>
                            $currentImport->failed_records,
                    ]);
                })

                ->catch(function (
                    Batch $batch,
                    Throwable $exception
                ) use ($import) {
                    $currentImport = $import->fresh();

                    /*
                     * Do not overwrite cancellation with failed.
                     */
                    if ($currentImport->status === 'cancelled') {
                        Log::info(
                            'Import batch failed after cancellation',
                            [
                                'import_id' => $import->id,
                                'batch_id' => $batch->id,
                            ]
                        );

                        return;
                    }

                    $currentImport->update([
                        'status' => 'failed',
                        'completed_at' => now(),
                        'error_message' => $exception->getMessage(),
                    ]);

                    Log::error('Import batch failed', [
                        'import_id' => $import->id,
                        'batch_id' => $batch->id,
                        'error' => $exception->getMessage(),
                    ]);
                })

                ->finally(function (Batch $batch) use ($import) {
                    Log::info('Import batch finished', [
                        'import_id' => $import->id,
                        'batch_id' => $batch->id,
                        'cancelled' => $batch->cancelled(),
                        'failed_jobs' => $batch->failedJobs,
                    ]);
                })

                ->dispatch();

            $import->update([
                'batch_id' => $batch->id,
            ]);

            Log::info('Import batch dispatched', [
                'import_id' => $import->id,
                'batch_id' => $batch->id,
                'total_records' => $totalRecords,
                'chunk_size' => self::CHUNK_SIZE,
                'chunks' => count($jobs),
            ]);

            return $import->fresh();
        } catch (Throwable $e) {
            $import->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => $e->getMessage(),
            ]);

            Log::error('Import creation failed', [
                'import_id' => $import->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function countRecords(string $filePath): int
    {
        $disk = Storage::disk('local');

        if (! $disk->exists($filePath)) {
            throw new RuntimeException(
                "Import file not found: {$filePath}"
            );
        }

        $handle = fopen(
            $disk->path($filePath),
            'r'
        );

        if ($handle === false) {
            throw new RuntimeException(
                'Unable to open CSV file.'
            );
        }

        try {
            $header = fgetcsv($handle);

            if ($header === false) {
                throw new RuntimeException(
                    'CSV file is empty.'
                );
            }

            $header = array_map(
                fn ($value) =>
                    strtolower(trim((string) $value)),
                $header
            );

            if ($header !== ['name', 'email']) {
                throw new RuntimeException(
                    'CSV header must be: name,email'
                );
            }

            $count = 0;

            while (($row = fgetcsv($handle)) !== false) {
                if ($this->isEmptyRow($row)) {
                    continue;
                }

                $count++;
            }

            return $count;
        } finally {
            fclose($handle);
        }
    }

    private function isEmptyRow(array $row): bool
    {
        return count(
            array_filter(
                $row,
                fn ($value) =>
                    trim((string) $value) !== ''
            )
        ) === 0;
    }
}

