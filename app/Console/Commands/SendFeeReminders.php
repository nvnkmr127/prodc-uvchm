<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\Admin\InvoiceController;
use App\Services\NotificationService;
use Carbon\Carbon;

class SendFeeReminders extends Command
{
    protected $signature = 'fees:send-reminders {--dry-run : Show what would be sent without actually sending}';
    protected $description = 'Send automated fee reminder notifications to students and staff';

    protected $invoiceController;
    protected $notificationService;

    public function __construct(InvoiceController $invoiceController, NotificationService $notificationService)
    {
        parent::__construct();
        $this->invoiceController = $invoiceController;
        $this->notificationService = $notificationService;
    }

    public function handle()
    {
        $this->info('🔔 Starting Fee Reminder Process...');
        
        if ($this->option('dry-run')) {
            $this->warn('DRY RUN MODE - No notifications will be sent');
            return $this->dryRun();
        }

        try {
            $result = $this->invoiceController->sendFeeReminders();
            $data = $result->getData();

            $this->info("✅ Fee reminders sent successfully!");
            $this->line("📧 Upcoming due reminders: " . $data->reminders_sent);
            $this->line("⚠️  Overdue alerts sent: " . $data->overdue_alerts_sent);
            
            if ($data->reminders_sent === 0 && $data->overdue_alerts_sent === 0) {
                $this->comment("📝 No reminders needed - all students are up to date!");
            }

        } catch (\Exception $e) {
            $this->error("❌ Failed to send fee reminders: " . $e->getMessage());
            
            // Send system alert about the failure
            $this->notificationService->sendSystemAlert(
                "Automated fee reminder command failed: " . $e->getMessage(),
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
        
        $this->info("Using reminder days: {$reminderDays}");
        
        try {
            $upcoming = \App\Models\Invoice::where('due_date', '>=', now())
                ->where('due_date', '<=', now()->addDays($reminderDays))
                ->where('status', 'unpaid')
                ->with('student')
                ->get();
                
            $overdue = \App\Models\Invoice::where('due_date', '<', now())
                ->where('status', 'unpaid')
                ->with('student')
                ->get();

            if ($upcoming->isEmpty() && $overdue->isEmpty()) {
                $this->comment("📝 No invoices found that need reminders");
                return 0;
            }

            $this->table(
                ['Type', 'Student', 'Amount', 'Due Date', 'Days', 'Invoice'],
                array_merge(
                    $upcoming->map(function($inv) {
                        return [
                            'Reminder',
                            $inv->student->name ?? 'Unknown',
                            '₹' . number_format($inv->due_amount, 2),
                           Carbon::parse($inv->due_date)->format('d-m-Y'),
                            now()->diffInDays($inv->due_date) . ' days left',
                            $inv->invoice_number ?? 'N/A'
                        ];
                    })->toArray(),
                    $overdue->map(function($inv) {
                        return [
                            'OVERDUE',
                            $inv->student->name ?? 'Unknown',
                            '₹' . number_format($inv->due_amount, 2),
                            $inv->due_date->format('d-m-Y'),
                            now()->diffInDays($inv->due_date) . ' days overdue',
                            $inv->invoice_number ?? 'N/A'
                        ];
                    })->toArray()
                )
            );

            $this->info("Would send {$upcoming->count()} reminders and {$overdue->count()} overdue alerts");
            
        } catch (\Exception $e) {
            $this->error("Error during dry run: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}