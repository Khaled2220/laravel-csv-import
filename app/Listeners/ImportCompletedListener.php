<?php

namespace App\Listeners;

use App\Events\ImportCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Notifications\ImportCompletedNotification;
use App\Mail\ImportCompletedMail;
use Illuminate\Support\Facades\Mail;


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
        Mail::to($user->email)->send(new ImportCompletedMail ($event->import));
    }
}
