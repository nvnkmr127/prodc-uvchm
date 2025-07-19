<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // ===============================================
        // PAYMENT REMINDER SYSTEM
        // ===============================================

        // Process payment reminders every 15 minutes during business hours
        $schedule->command('reminders:process')
            ->everyFifteenMinutes()
            ->between('08:00', '18:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/payment-reminders.log'));

        // Send daily payment reminders
        $schedule->command('payments:enhanced-reminders')
            ->dailyAt(\App\Models\Setting::get('reminder_time', '09:00'))
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/fee-reminders.log'));

        // Generate defaulter analysis daily
        $schedule->command('defaulters:analyze')
            ->dailyAt('08:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/defaulters-analysis.log'));

        // Send urgent overdue reminders twice daily
        $schedule->command('payments:enhanced-reminders --fee-type=urgent')
            ->twiceDaily(10, 16) // 10 AM and 4 PM
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/urgent-reminders.log'));

        // Process escalated cases daily
        $schedule->command('defaulters:escalate')
            ->dailyAt('10:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/escalations.log'));
            
        // Process pending reminders every hour
        $schedule->call(function () {
            $service = app(\App\Services\PaymentReminderService::class);
            $service->processPendingReminders();
        })->hourly()
          ->withoutOverlapping()
          ->name('process-pending-reminders');

        // Update defaulter records daily
        $schedule->call(function () {
            $service = app(\App\Services\PaymentReminderService::class);
            $service->updateDefaulterRecords();
        })->daily()
          ->at('06:00')
          ->withoutOverlapping()
          ->name('update-defaulter-records');

        // Cleanup old records weekly
        $schedule->call(function () {
            $service = app(\App\Services\PaymentReminderService::class);
            $service->cleanupOldRecords();
        })->weekly()
          ->saturdays()
          ->at('02:00')
          ->withoutOverlapping()
          ->name('cleanup-old-reminder-records');
          
          // Process pending reminders every hour
    $schedule->job(new \App\Jobs\ProcessPendingReminders())->hourly();
    
    // Update defaulter records daily at 2 AM
    $schedule->call(function () {
        app(\App\Services\PaymentReminderService::class)->updateDefaulterRecords();
    })->dailyAt('02:00');
    
    // Cleanup old reminder records weekly
    $schedule->call(function () {
        app(\App\Services\PaymentReminderService::class)->cleanupOldRecords(90);
    })->weeklyOn(1, '03:00'); // Monday at 3 AM


        // ===============================================
        // FINANCIAL REPORTING
        // ===============================================

        // Weekly financial summary every Monday
        $schedule->command('reports:weekly-collection')
            ->weeklyOn(1, '08:00') // Monday 8 AM
            ->name('weekly-collection-summary')
            ->appendOutputTo(storage_path('logs/weekly-reports.log'));

        // Monthly collection report on 1st of each month
        $schedule->command('reports:monthly-collection')
            ->monthlyOn(1, '09:00')
            ->name('monthly-collection-report')
            ->appendOutputTo(storage_path('logs/monthly-reports.log'));

        // Daily collection dashboard update
        $schedule->command('reports:daily-collection')
            ->dailyAt('07:00')
            ->withoutOverlapping()
            ->runInBackground();

        // ===============================================
        // FEE-TYPE SPECIFIC REMINDERS
        // ===============================================

        // Tuition fee reminders - High priority, daily
        $schedule->command('payments:enhanced-reminders --fee-type=tuition_fee')
            ->dailyAt('09:30')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/tuition-reminders.log'));

        // Uniform fee reminders - Weekly on Wednesdays
        $schedule->command('payments:enhanced-reminders --fee-type=uniform_fee')
            ->weeklyOn(3, '10:00') // Wednesday 10 AM
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/uniform-reminders.log'));

        // Library fee reminders - Monthly on 15th
        $schedule->command('payments:enhanced-reminders --fee-type=library_fee')
            ->monthlyOn(15, '11:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/library-reminders.log'));

        // ===============================================
        // SYSTEM MAINTENANCE
        // ===============================================

        // Generate risk assessment weekly
        $schedule->command('analytics:risk-assessment')
            ->weeklyOn(1, '07:00') // Monday 7 AM
            ->appendOutputTo(storage_path('logs/risk-assessment.log'));

        // ===============================================
        // COMMUNICATION TASKS
        // ===============================================

        // Send follow-up reminders for enquiries
        $schedule->command('app:send-follow-up-reminders')
            ->dailyAt('10:00')
            ->appendOutputTo(storage_path('logs/follow-up-reminders.log'));

        // Process failed notification retries
        $schedule->command('notifications:retry-failed')
            ->hourly()
            ->withoutOverlapping();

        // Send SMS delivery reports
        $schedule->command('sms:delivery-reports')
            ->dailyAt('20:00')
            ->appendOutputTo(storage_path('logs/sms-delivery.log'));

        // ===============================================
        // SEASONAL TASKS
        // ===============================================

        // Academic year-end processes (July 1st)
        $schedule->command('academic:year-end-process')
            ->yearlyOn(7, 1, '05:00')
            ->name('academic-year-end');

        // Admission season reminders (May-July)
        $schedule->command('admissions:seasonal-reminders')
            ->dailyAt('11:00')
            ->when(function () {
                $month = date('n');
                return in_array($month, [5, 6, 7]); // May, June, July
            });

        // Fee structure updates (April 1st)
        $schedule->command('fees:annual-update-check')
            ->yearlyOn(4, 1, '06:00')
            ->name('annual-fee-structure-check');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        // Register all payment-related commands
        $this->commands([
            Commands\BackupSettings::class,
            Commands\ManageSettings::class,
            Commands\RunHealthCheck::class,
            Commands\FixSystemIssues::class,
            Commands\SendFeeReminders::class,
            Commands\ProcessPaymentReminders::class,
            Commands\EnhancedPaymentReminders::class,
            Commands\AnalyzeDefaulters::class,
            Commands\EscalateDefaulters::class,
            Commands\CleanupPaymentReminders::class,
            Commands\SendFollowUpReminders::class,
        ]);

        require base_path('routes/console.php');
    }

    /**
     * Get the timezone that should be used by default for scheduled events.
     */
    protected function scheduleTimezone(): ?string
    {
        return \App\Models\Setting::get('timezone', config('app.timezone', 'UTC'));
    }
}