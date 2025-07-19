<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PaymentReminderService;

class ProcessPaymentReminders extends Command
{
    protected $signature = 'reminders:process {--dry-run : Show what would be sent}';
    protected $description = 'Process all pending payment reminders';

    protected $reminderService;

    public function __construct(PaymentReminderService $reminderService)
    {
        parent::__construct();
        $this->reminderService = $reminderService;
    }

    public function handle()
    {
        $this->info('🔔 Processing Payment Reminders...');

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN MODE - No reminders will be sent');
            // Show what would be sent
            return 0;
        }

        $results = $this->reminderService->processPendingReminders();

        $this->info("✅ Reminders processed!");
        $this->line("📧 Successfully sent: {$results['sent']}");
        $this->line("❌ Failed: {$results['failed']}");

        if (!empty($results['errors'])) {
            $this->error("Errors occurred:");
            foreach ($results['errors'] as $error) {
                $this->line("- {$error['student']}: {$error['error']}");
            }
        }

        return 0;
    }
}