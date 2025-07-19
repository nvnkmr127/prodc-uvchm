<?php
namespace App\Notifications;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Invoice;

class FeeReminderNotification extends Notification
{
    use Queueable;

    public $invoice;

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    public function via(object $notifiable): array
    {
        return ['database']; // We will store this in the database for now. We can add 'mail' later.
    }

    public function toArray(object $notifiable): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'amount_due' => $this->invoice->due_amount,
            'due_date' => $this->invoice->due_date->format('d M, Y'),
            'message' => "Reminder: Payment for invoice #{$this->invoice->invoice_number} of {$this->invoice->due_amount} is due on {$this->invoice->due_date->format('d M, Y')}."
        ];
    }
}