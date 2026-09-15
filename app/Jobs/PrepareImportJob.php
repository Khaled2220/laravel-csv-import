<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Events\ImportCompleted;
use App\Events\ImportFailed;
use App\Events\ImportStarted;
use App\Jobs\ProcessImportChunkJob;
use App\Models\Import;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class PrepareImportJob implements ShouldQueue
{
    use Queueable;
    use Dispatchable;
    use InteractsWithQueue;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

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

        if ($import->status === 'cancelled') {
            return;
        }
        try {
            $filePath = $import->file_path;
            $disk = Storage::disk('local');

            if (! $disk->exists($filePath)) {
                throw new RuntimeException(
                    "Import file not found: {$filePath}"
                );
            }
            $totalRecords = $this->countRecords($filePath);
            $import->update([
                'total_records' => $totalRecords,
            ]);

            if ($totalRecords === 0) {
                $import->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

                event(
                    new ImportCompleted(
                        $import->fresh()
                    )
                );
                return;
            }
            $import->update([
                'status' => 'processing',
                'started_at' => now(),
            ]);
            event(
                new ImportStarted($import->fresh())
            );
            Log::info('Import chain started', [
                'import_id' => $import->id,
            ]);

            $this->dispatchImportChain(
                $import->fresh(),
                $totalRecords
            );
        } 
        catch (Throwable $e) {
            $currentImport = $import->fresh();
            if ($currentImport->status === 'cancelled') {
                return;
            }
            $currentImport->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => $e->getMessage(),
            ]);
            event(
                new ImportFailed(
                    $currentImport->fresh(),
                    $e->getMessage()
                )
            );
            Log::error('Prepare import failed', [
                'import_id' => $import->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }


    private function dispatchImportChain(Import $import,int $totalRecords): void 
    {
        $chunkSize = 1000;
        $jobs = [];
        for (
            $startRow = 2;
            $startRow <= $totalRecords + 1;
            $startRow += $chunkSize
        ) 
        {
            $jobs[] = new ProcessImportChunkJob(
                importId: $import->id,
                startRow: $startRow,
                chunkSize: $chunkSize
            );
        }
        Bus::chain($jobs)
            ->catch(function (Throwable $exception) use ($import) {
                $currentImport = $import->fresh();
                if ($currentImport->status === 'cancelled') {
                    return;
                }
                $currentImport->update([
                    'status' => 'failed',
                    'completed_at' => now(),
                    'error_message' => $exception->getMessage(),
                ]);
                event(
                    new ImportFailed(
                        $currentImport->fresh(),
                        $exception->getMessage()
                    )
                );
                Log::error('Import chain failed', [
                    'import_id' => $import->id,
                    'error' => $exception->getMessage(),
                ]);
            })
            ->dispatch();
        Log::info('Import chain dispatched', [
            'import_id' => $import->id,
            'total_records' => $totalRecords,
            'chunk_size' => $chunkSize,
            'chunks' => count($jobs),
        ]);
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
            $disk->path($filePath),'r'
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
        } 
        finally {
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

    public function backoff(): array
    {
        return [10, 30, 60];
    }
}

