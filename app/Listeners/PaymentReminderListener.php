<?php

namespace App\Listeners;

// ✅ CHANGED: Imported new component-based events
use App\Events\StudentFeePaid;
use App\Events\StudentFeeCreated;
// ✅ CHANGED: Using the new component-based reminder service
use App\Services\ComponentPaymentReminderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class PaymentReminderListener implements ShouldQueue
{
    use InteractsWithQueue;

    protected $reminderService;

    /**
     * Create the event listener.
     *
     * @param ComponentPaymentReminderService $reminderService
     */
    // ✅ CHANGED: Injected the new component-based service
    public function __construct(ComponentPaymentReminderService $reminderService)
    {
        $this->reminderService = $reminderService;
    }

    /**
     * Handle the StudentFeePaid event.
     *
     * @param StudentFeePaid $event
     * @return void
     */
    // ✅ CHANGED: Renamed handler and updated to use StudentFeePaid event
    public function handleStudentFeePaid(StudentFeePaid $event)
    {
        // Cancel pending reminders for the now-paid fee component
        $this->reminderService->cancelRemindersForStudentFee($event->studentFee);
    }

    /**
     * Handle the StudentFeeCreated event.
     *
     * @param StudentFeeCreated $event
     * @return void
     */
    // ✅ CHANGED: Renamed handler and updated to use StudentFeeCreated event
    public function handleStudentFeeCreated(StudentFeeCreated $event)
    {
        // Setup a new reminder schedule for the newly created fee component
        $this->reminderService->setupComponentReminderSchedule($event->student, $event->studentFee);
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param \Illuminate\Events\Dispatcher $events
     */
    public function subscribe($events)
    {
        // ✅ CHANGED: Subscribing to the new component-based events
        $events->listen(
            StudentFeePaid::class,
            [self::class, 'handleStudentFeePaid']
        );

        $events->listen(
            StudentFeeCreated::class,
            [self::class, 'handleStudentFeeCreated']
        );
    }
}