<?php

namespace App\Listeners;

use App\Events\ImportFailed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Notifications\ImportFailedNotification;

class ImportFailedListener
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
    public function handle(ImportFailed $event): void
    {
        $user = $event->import->user;

        $user->notify(
            new ImportFailedNotification(
                $event->import,
                $event->errorMessage
            )
        );
    }
}
