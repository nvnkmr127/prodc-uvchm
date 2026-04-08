<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ProcessPaymentReminders extends Command
{
    protected $signature = 'reminders:process {--dry-run : Show what would be sent}';

    protected $description = 'Process all pending payment reminders using ComponentPaymentReminderService';

    protected $reminderService;

    public function __construct()
    {
        parent::__construct();

        // ✅ FIXED: Use ComponentPaymentReminderService instead of PaymentReminderService
        if (class_exists('\App\Services\ComponentPaymentReminderService')) {
            $this->reminderService = app(\App\Services\ComponentPaymentReminderService::class);
        } else {
            $this->reminderService = null;
        }
    }

    public function handle()
    {
        $this->info('🔔 Processing Payment Reminders...');

        // Check if service is available
        if (! $this->reminderService) {
            $this->error('ComponentPaymentReminderService not available');
            $this->line('Please ensure the service class exists and is properly registered');

            return 1;
        }

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN MODE - No reminders will be sent');

            // Show what would be sent (if method exists)
            if (method_exists($this->reminderService, 'getPendingRemindersCount')) {
                $pendingCount = $this->reminderService->getPendingRemindersCount();
                $this->line("Would process {$pendingCount} pending reminders");
            } else {
                $this->line('Dry run functionality not available in current service');
            }

            return 0;
        }

        try {
            // Check if the service has the required method
            if (! method_exists($this->reminderService, 'processPendingReminders')) {
                $this->error('processPendingReminders method not found in ComponentPaymentReminderService');

                return 1;
            }

            $results = $this->reminderService->processPendingReminders();

            $this->info('✅ Reminders processed!');

            // Handle different result formats
            if (is_array($results)) {
                $sent = $results['sent'] ?? 0;
                $failed = $results['failed'] ?? 0;
                $errors = $results['errors'] ?? [];

                $this->line("📧 Successfully sent: {$sent}");
                $this->line("❌ Failed: {$failed}");

                if (! empty($errors)) {
                    $this->error('Errors occurred:');
                    foreach ($errors as $error) {
                        if (is_array($error)) {
                            $student = $error['student'] ?? 'Unknown';
                            $errorMsg = $error['error'] ?? 'Unknown error';
                            $this->line("- {$student}: {$errorMsg}");
                        } else {
                            $this->line("- {$error}");
                        }
                    }
                }
            } else {
                $this->line('Processing completed. Result: '.($results ? 'Success' : 'No reminders processed'));
            }

        } catch (\Exception $e) {
            $this->error('Error processing reminders: '.$e->getMessage());

            return 1;
        }

        return 0;
    }
}
