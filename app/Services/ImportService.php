<?php

namespace App\Services;

use App\Models\Import;
use App\Jobs\PrepareImportJob;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ImportService
{
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

        /*
         * Prepare the import.
         *
         * PrepareImportJob will read the CSV
         * and create the chunk jobs using Bus::chain().
         */
        PrepareImportJob::dispatch($import->id);

        return $import;
    }

    public function cancelImport(Import $import): Import
    {
        return DB::transaction(function () use ($import) {

            $currentImport = Import::lockForUpdate()
                ->findOrFail($import->id);

            /*
             * Only pending or processing imports
             * can be cancelled.
             */
            if (! in_array(
                $currentImport->status,
                ['pending', 'processing'],
                true
            )) {
                throw new RuntimeException(
                    'Only pending or processing imports can be cancelled.'
                );
            }

            /*
             * We do not need Bus::findBatch()
             * because we are using Bus::chain().
             *
             * The queued jobs will check the import
             * status and stop when they see "cancelled".
             */
            $currentImport->update([
                'status' => 'cancelled',
                'completed_at' => now(),
            ]);

            Log::info('Import cancelled', [
                'import_id' => $currentImport->id,
            ]);

            return $currentImport->fresh();
        });
    }

    public function retryImport(Import $import): Import
    {
        return DB::transaction(function () use ($import) {

            $currentImport = Import::lockForUpdate()
                ->findOrFail($import->id);

            /*
             * Only failed imports can be retried.
             */
            if ($currentImport->status !== 'failed') {
                throw new RuntimeException(
                    'Only failed imports can be retried.'
                );
            }

            /*
             * Check that the CSV file still exists.
             */
            if (! Storage::disk('local')->exists(
                $currentImport->file_path
            )) {
                throw new RuntimeException(
                    'Import file no longer exists.'
                );
            }

            /*
             * Reset import state.
             */
            $currentImport->update([
                'status' => 'pending',
                'processed_records' => 0,
                'failed_records' => 0,
                'started_at' => null,
                'completed_at' => null,
                'error_message' => null,
            ]);

            /*
             * Delete previous errors.
             */
            $currentImport->errors()->delete();

            /*
             * Delete previous processed records.
             */
            $currentImport->records()->delete();

            /*
             * Prepare the import again.
             *
             * PrepareImportJob will create a new
             * Bus::chain().
             */
            PrepareImportJob::dispatch(
                $currentImport->id
            );

            Log::info('Import retry dispatched', [
                'import_id' => $currentImport->id,
                'total_records' => $currentImport->total_records,
            ]);

            return $currentImport->fresh();
        });
    }
}
