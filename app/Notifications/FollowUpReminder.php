<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class FollowUpReminder extends Notification
{
    use Queueable;

    protected $enquiries;

    /**
     * Create a new notification instance.
     * @param Collection $enquiries A collection of Enquiry models due for follow-up.
     */
    public function __construct(Collection $enquiries)
    {
        $this->enquiries = $enquiries;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mailMessage = (new MailMessage)
                    ->subject('You have ' . $this->enquiries->count() . ' Follow-ups Today')
                    ->line('This is a reminder for your scheduled follow-ups for today, ' . now()->format('d-M-Y') . '.');
        
        foreach ($this->enquiries as $enquiry) {
            $mailMessage->line(
                '-> ' . $enquiry->student_name . ' (' . ($enquiry->course->name ?? 'N/A') . ')'
            );
        }

        $mailMessage->action('View Calendar', route('admin.follow-ups.calendar'))
                    ->line('Thank you for your attention to these leads!');
        
        return $mailMessage;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'You have ' . $this->enquiries->count() . ' follow-ups scheduled for today.',
            'link' => route('admin.follow-ups.calendar')
        ];
    }
}
