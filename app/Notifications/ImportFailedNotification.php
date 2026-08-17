<?php

namespace App\Notifications;


use App\Models\Import;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ImportFailedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct( public Import $import,public string $errorMessage)
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
     * Get the mail representation of the notification.
     */
  //  public function toMail(object $notifiable): MailMessage
  //  {
   //     return (new MailMessage)
   //         ->line('The introduction to the notification.')
 //           ->action('Notification Action', url('/'))
  //          ->line('Thank you for using our application!');
  //  }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
             'type' => 'import_failed',
            'import_id' => $this->import->id,
            'file_name' => $this->import->file_name,
            'status'=> $this->import->status,
            'total_records' => $this->import->total_records,
            'processed_records' => $this->import->processed_records,
            'failed_records' => $this->import->failed_records,
            'error_message' => $this->errorMessage,
            'message' => "Import {$this->import->file_name} failed.",
        ];
    }
}
