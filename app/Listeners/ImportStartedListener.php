<?php

namespace App\Listeners;

use App\Events\ImportStarted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ImportStartedListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ImportStarted $event): void
    {
        Log::info('CSV import started.', [
            'import_id' => $event->import->id,
            'file_name' => $event->import->file_name,
            'user_id' => $event->import->user_id,
        ]);
    }
}
