<?php

namespace App\Notifications;

use App\Models\Enquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewEnquiryAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    public $enquiry;

    /**
     * Create a new notification instance.
     */
    public function __construct(Enquiry $enquiry)
    {
        $this->enquiry = $enquiry;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // We will send an email and also save it to the database
        // for an in-app notification center.
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = route('admin.enquiries.edit', $this->enquiry);

        return (new MailMessage)
            ->subject('New Enquiry Assigned: '.$this->enquiry->student_name)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('A new enquiry has been assigned to you for follow-up.')
            ->line('Student Name: '.$this->enquiry->student_name)
            ->line('Course Interest: '.($this->enquiry->course->name ?? 'Not Specified'))
            ->action('View Enquiry Details', $url)
            ->line('Please follow up at your earliest convenience.');
    }

    /**
     * Get the array representation of the notification for the database.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'enquiry_id' => $this->enquiry->id,
            'message' => "A new enquiry for {$this->enquiry->student_name} has been assigned to you.",
            'link' => route('admin.enquiries.edit', $this->enquiry),
        ];
    }
}
