<?php

namespace App\Notifications;

use App\Models\Import;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ImportCompletedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Import $import)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
             'type' => 'import_completed',
            'import_id' => $this->import->id,
            'file_name' => $this->import->file_name,
            'status' => $this->import->status,
            'total_records' => $this->import->total_records,
            'processed_records' => $this->import->processed_records,
            'failed_records' => $this->import->failed_records,
            'message' => "Import {$this->import->file_name} completed successfully.",
        ];
    }
}
