<?php

namespace App\Listeners;

use App\Events\PaymentReceived;
use App\Events\InvoiceGenerated;
use App\Services\PaymentReminderService;
use Illuminate\Contracts\Queue\ShouldQueue;

class PaymentReminderListener implements ShouldQueue
{
    protected $reminderService;

    public function __construct(PaymentReminderService $reminderService)
    {
        $this->reminderService = $reminderService;
    }

    public function handlePaymentReceived(PaymentReceived $event)
    {
        // Cancel pending reminders for paid invoice
        $this->reminderService->cancelRemindersForInvoice($event->invoice);
    }

    public function handleInvoiceGenerated(InvoiceGenerated $event)
    {
        // Setup reminder schedule for new invoice
        $this->reminderService->setupReminderSchedule($event->student, $event->invoice);
    }

    public function subscribe($events)
    {
        $events->listen(PaymentReceived::class, [self::class, 'handlePaymentReceived']);
        $events->listen(InvoiceGenerated::class, [self::class, 'handleInvoiceGenerated']);
    }
}