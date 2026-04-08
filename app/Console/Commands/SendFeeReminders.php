<?php

namespace App\Console\Commands;

use App\Models\StudentFee;
use App\Services\ComponentPaymentReminderService; // MODIFIED: Use the new component-based reminder service
use App\Services\NotificationService;
use Carbon\Carbon; // MODIFIED: Use the StudentFee model
use Illuminate\Console\Command;

class SendFeeReminders extends Command
{
    protected $signature = 'fees:send-reminders {--dry-run : Show what would be sent without actually sending}';

    protected $description = 'Send automated fee reminder notifications for individual fee components to students and staff';

    // MODIFIED: Injected the new component-based reminder service
    protected $reminderService;

    protected $notificationService;

    public function __construct(ComponentPaymentReminderService $reminderService, NotificationService $notificationService)
    {
        parent::__construct();
        $this->reminderService = $reminderService;
        $this->notificationService = $notificationService;
    }

    public function handle()
    {
        $this->info('🔔 Starting Component-Based Fee Reminder Process...');

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN MODE - No notifications will be sent');

            return $this->dryRun();
        }

        try {
            // MODIFIED: Call the method that processes pending reminders from the component service
            $result = $this->reminderService->processPendingReminders();

            $this->info('✅ Fee reminders processed successfully!');
            $this->line('🚀 Reminders sent: '.($result['sent'] ?? 0));
            $this->line('❌ Reminders failed: '.($result['failed'] ?? 0));

            if (($result['sent'] ?? 0) === 0 && ($result['failed'] ?? 0) === 0) {
                $this->comment('📝 No pending reminders needed at this time.');
            }

        } catch (\Exception $e) {
            $this->error('❌ Failed to send fee reminders: '.$e->getMessage());

            // Send system alert about the failure
            $this->notificationService->sendSystemAlert(
                'Automated fee reminder command failed: '.$e->getMessage(),
                'high',
                ['command' => 'fees:send-reminders', 'error' => $e->getMessage()]
            );

            return 1; // Exit with error code
        }

        return 0; // Success
    }

    // Helper method to safely get numeric settings
    private function getNumericSetting(string $key, int|float $default = 0): int|float
    {
        try {
            $value = setting($key, $default);

            if (is_numeric($value)) {
                return is_float($default) ? (float) $value : (int) $value;
            }

            return $default;
        } catch (\Exception $e) {
            $this->warn("Could not retrieve setting '{$key}', using default: {$default}");

            return $default;
        }
    }

    private function dryRun()
    {
        $reminderDays = $this->getNumericSetting('fee_reminder_days', 7);

        $this->info("Scanning for fee components due within the next {$reminderDays} days and those that are overdue.");

        try {
            // MODIFIED: Query StudentFee model for upcoming and overdue components
            $upcoming = StudentFee::where('due_date', '>=', now())
                ->where('due_date', '<=', now()->addDays($reminderDays))
                ->whereIn('status', ['unpaid', 'partial'])
                ->with('student', 'feeCategory')
                ->get();

            $overdue = StudentFee::where('due_date', '<', now())
                ->whereIn('status', ['unpaid', 'partial', 'overdue'])
                ->with('student', 'feeCategory')
                ->get();

            if ($upcoming->isEmpty() && $overdue->isEmpty()) {
                $this->comment('📝 No fee components found that need reminders.');

                return 0;
            }

            $this->table(
                ['Type', 'Student', 'Fee Category', 'Amount Due', 'Due Date', 'Days'],
                array_merge(
                    $upcoming->map(function ($fee) {
                        return [
                            'Reminder',
                            $fee->student->name ?? 'Unknown',
                            $fee->feeCategory->name ?? 'N/A',
                            '₹'.number_format($fee->getRemainingAmount(), 2),
                            Carbon::parse($fee->due_date)->format('d-m-Y'),
                            now()->diffInDays($fee->due_date).' days left',
                        ];
                    })->toArray(),
                    $overdue->map(function ($fee) {
                        return [
                            'OVERDUE',
                            $fee->student->name ?? 'Unknown',
                            $fee->feeCategory->name ?? 'N/A',
                            '₹'.number_format($fee->getRemainingAmount(), 2),
                            $fee->due_date->format('d-m-Y'),
                            now()->diffInDays($fee->due_date).' days overdue',
                        ];
                    })->toArray()
                )
            );

            $this->info("Would process {$upcoming->count()} upcoming reminders and {$overdue->count()} overdue alerts.");

        } catch (\Exception $e) {
            $this->error('Error during dry run: '.$e->getMessage());

            return 1;
        }

        return 0;
    }
}
