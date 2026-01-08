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
        // This command will now be scheduled correctly.
        $schedule->command('etimeoffice:auto-sync --range=today')
            ->everyFiveMinutes()
            ->withoutOverlapping(10)
            ->runInBackground()
            ->onSuccess(function () {
                \Log::info('ETimeOffice auto-sync completed successfully');
            })
            ->onFailure(function () {
                \Log::error('ETimeOffice auto-sync failed');
            });

        // Schedule Daily Absent Webhook (Runs every 30 mins, checks cutoff time internally)
        $schedule->command('attendance:send-daily-absent-webhook')
            ->everyThirtyMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/scheduler.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }

    /**
     * Get the timezone that should be used by default for scheduled events.
     */
    protected function scheduleTimezone(): ?string
    {
        return 'Asia/Kolkata';
    }
}