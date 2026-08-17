<?php

namespace App\Listeners;

use App\Events\ImportCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Notifications\ImportCompletedNotification;


class ImportCompletedListener
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
    public function handle(ImportCompleted $event): void
    {
        
        $user = $event->import->user;

        $user->notify(
            new ImportCompletedNotification($event->import)
        );
    }
}
