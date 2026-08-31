<?php

namespace App\Services;

use App\Models\Import;
use App\Jobs\ImportUsersJob;
use Illuminate\Http\UploadedFile;


class ImportService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }
    public function createImport(UploadedFile $file, int $userId): Import{
        $filePath = $file->store('imports', 'local');
        $import=Import::create([
            'user_id' => $userId,
            'file_name'=>$file->getClientOriginalName(),
            'file_path' => $filePath,
            'status'=>'pending',
            'total_records'=>0,
            'processed_records'=>0,
            'failed_records'=>0            
        ]);
        ImportUsersJob::dispatch($import->id);
        return $import;
    }

}
