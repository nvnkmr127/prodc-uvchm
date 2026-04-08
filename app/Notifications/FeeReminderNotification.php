<?php

namespace App\Notifications;

use App\Models\StudentFee;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification; // MODIFIED: Replaced Invoice with StudentFee

class FeeReminderNotification extends Notification
{
    use Queueable;

    /**
     * The student fee component instance.
     *
     * @var \App\Models\StudentFee
     */
    public $studentFee; // MODIFIED: Changed property from $invoice to $studentFee

    /**
     * Create a new notification instance.
     */
    public function __construct(StudentFee $studentFee)
    {
        // MODIFIED: The constructor now accepts a StudentFee object
        $this->studentFee = $studentFee;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database']; // We will store this in the database for now. We can add 'mail' later.
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        // MODIFIED: The data structure now reflects the details of a fee component
        $feeCategoryName = $this->studentFee->feeCategory->name ?? 'Fee';
        $remainingAmount = $this->studentFee->getRemainingAmount();
        $dueDateFormatted = $this->studentFee->due_date->format('d M, Y');

        return [
            'student_fee_id' => $this->studentFee->id,
            'fee_category' => $feeCategoryName,
            'amount_due' => $remainingAmount,
            'due_date' => $dueDateFormatted,
            'message' => "Reminder: Payment for {$feeCategoryName} of {$remainingAmount} is due on {$dueDateFormatted}.",
        ];
    }
}
