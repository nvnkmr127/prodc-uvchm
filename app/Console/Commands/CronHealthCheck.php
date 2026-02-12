<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class CronHealthCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:health-check {--detailed : Show detailed information}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the health and status of all cron jobs for UVCHM Portal';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('===========================================');
        $this->info('UVCHM Portal - Cron Job Health Check');
        $this->info('Timestamp: ' . now()->format('Y-m-d H:i:s'));
        $this->info('===========================================');
        $this->newLine();

        $this->checkSchedulerStatus();
        $this->checkQueueStatus();
        $this->checkLogFiles();
        $this->checkDatabaseConnection();
        $this->checkFilePermissions();

        if ($this->option('detailed')) {
            $this->showDetailedDiagnostics();
        }

        $this->newLine();
        $this->info('Health check completed at ' . now()->format('Y-m-d H:i:s'));
    }

    private function checkSchedulerStatus()
    {
        $this->info('1. SCHEDULER STATUS');
        $this->info('===================');

        try {
            // Check if we can list scheduled commands
            $scheduledCommands = \Artisan::call('schedule:list');
            $this->line('✓ Laravel Scheduler: <fg=green>ACCESSIBLE</fg=green>');

            // Check last run time from cache/database if you store it
            $lastScheduleRun = cache('last_schedule_run');
            if ($lastScheduleRun) {
                $diffInMinutes = now()->diffInMinutes($lastScheduleRun);
                if ($diffInMinutes <= 5) {
                    $this->line("✓ Last run: <fg=green>{$diffInMinutes} minutes ago</fg=green>");
                } else {
                    $this->line("⚠ Last run: <fg=yellow>{$diffInMinutes} minutes ago</fg=yellow>");
                }
            } else {
                $this->line('⚠ Last run: <fg=yellow>Unknown (no cache entry)</fg=yellow>');
            }

        } catch (\Exception $e) {
            $this->line('✗ Laravel Scheduler: <fg=red>FAILED</fg=red>');
            $this->line('  Error: ' . $e->getMessage());
        }

        $this->newLine();
    }

    private function checkQueueStatus()
    {
        $this->info('2. QUEUE STATUS');
        $this->info('===============');

        try {
            // Check failed jobs
            $failedJobs = DB::table('failed_jobs')->count();
            if ($failedJobs == 0) {
                $this->line('✓ Failed Jobs: <fg=green>None</fg=green>');
            } else {
                $this->line("⚠ Failed Jobs: <fg=yellow>{$failedJobs} found</fg=yellow>");
            }

            // Check recent jobs (if you have jobs table)
            if (\Schema::hasTable('jobs')) {
                $pendingJobs = DB::table('jobs')->count();
                $this->line("📋 Pending Jobs: {$pendingJobs}");
            }

            // Try to check if queue worker is responding
            $testJob = new \App\Jobs\TestCronHealthJob();
            $this->line('✓ Queue Configuration: <fg=green>Available</fg=green>');

        } catch (\Exception $e) {
            $this->line('✗ Queue System: <fg=red>FAILED</fg=red>');
            $this->line('  Error: ' . $e->getMessage());
        }

        $this->newLine();
    }

    private function checkLogFiles()
    {
        $this->info('3. LOG FILES STATUS');
        $this->info('===================');

        $logPath = storage_path('logs');
        $logs = [
            'queue-worker.log' => 24,          // Should update within 24 hours
            'db-maintenance.log' => 48,        // Daily at 2 AM
            'backup.log' => 48,                // Daily at 1 AM  
            'health-check.log' => 12,          // Every 6 hours
            'payment-reminders.log' => 48,     // Daily at 8 AM
            'settings-backup.log' => 48,       // Daily at 1:30 AM
            'reminder-processing.log' => 24,   // Every 30 min during work hours
            'queue-cleanup.log' => 168,        // Weekly on Sunday
            'reminder-cleanup.log' => 168,     // Weekly on Monday
        ];

        foreach ($logs as $logFile => $maxAgeHours) {
            $fullPath = $logPath . '/' . $logFile;

            if (File::exists($fullPath)) {
                $lastModified = File::lastModified($fullPath);
                $ageHours = (time() - $lastModified) / 3600;

                if ($ageHours <= $maxAgeHours) {
                    $this->line("✓ {$logFile}: <fg=green>Recent (" . round($ageHours, 1) . "h ago)</fg=green>");
                } else {
                    $this->line("⚠ {$logFile}: <fg=yellow>Old (" . round($ageHours, 1) . "h ago)</fg=yellow>");
                }
            } else {
                $this->line("✗ {$logFile}: <fg=red>Missing</fg=red>");
            }
        }

        // Check Laravel application logs
        $laravelLog = storage_path('logs/laravel.log');
        if (File::exists($laravelLog)) {
            $size = File::size($laravelLog);
            $sizeFormatted = $this->formatBytes($size);
            $this->line("📄 Laravel Log: {$sizeFormatted}");
        }

        $this->newLine();
    }

    private function checkDatabaseConnection()
    {
        $this->info('4. DATABASE CONNECTION');
        $this->info('======================');

        try {
            DB::connection()->getPdo();
            $this->line('✓ Database: <fg=green>Connected</fg=green>');

            // Check some key tables
            $tables = ['users', 'students', 'roles', 'permissions'];
            foreach ($tables as $table) {
                if (\Schema::hasTable($table)) {
                    $count = DB::table($table)->count();
                    $this->line("  📊 {$table}: {$count} records");
                }
            }

        } catch (\Exception $e) {
            $this->line('✗ Database: <fg=red>Connection Failed</fg=red>');
            $this->line('  Error: ' . $e->getMessage());
        }

        $this->newLine();
    }

    private function checkFilePermissions()
    {
        $this->info('5. FILE PERMISSIONS');
        $this->info('===================');

        $paths = [
            base_path('artisan') => '755',
            storage_path() => '755',
            storage_path('logs') => '755',
            storage_path('app') => '755',
            base_path('bootstrap/cache') => '755',
        ];

        foreach ($paths as $path => $expectedPerm) {
            if (File::exists($path)) {
                $permissions = substr(sprintf('%o', fileperms($path)), -3);
                if ($permissions >= $expectedPerm) {
                    $this->line("✓ " . basename($path) . ": <fg=green>{$permissions}</fg=green>");
                } else {
                    $this->line("⚠ " . basename($path) . ": <fg=yellow>{$permissions} (needs {$expectedPerm})</fg=yellow>");
                }
            } else {
                $this->line("✗ " . basename($path) . ": <fg=red>Not Found</fg=red>");
            }
        }

        $this->newLine();
    }

    private function showDetailedDiagnostics()
    {
        $this->info('6. DETAILED DIAGNOSTICS');
        $this->info('========================');

        // PHP Version
        $this->line('📋 PHP Version: ' . phpversion());

        // Laravel Version
        $this->line('📋 Laravel Version: ' . app()->version());

        // Environment
        $this->line('📋 Environment: ' . app()->environment());

        // Memory Usage
        $this->line('📋 Memory Usage: ' . $this->formatBytes(memory_get_usage(true)));

        // Server time
        $this->line('📋 Server Time: ' . now()->format('Y-m-d H:i:s T'));

        // Timezone
        $this->line('📋 Timezone: ' . config('app.timezone'));

        // Cache status
        try {
            cache()->put('health_check_test', 'ok', 60);
            $test = cache()->get('health_check_test');
            if ($test === 'ok') {
                $this->line('✓ Cache: <fg=green>Working</fg=green>');
            } else {
                $this->line('⚠ Cache: <fg=yellow>Issues detected</fg=yellow>');
            }
        } catch (\Exception $e) {
            $this->line('✗ Cache: <fg=red>Failed</fg=red>');
        }

        $this->newLine();
    }

    private function formatBytes($size)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen($size) - 1) / 3);
        return sprintf("%.2f %s", $size / pow(1024, $factor), $units[$factor]);
    }
}

// Job class for testing queue connectivity
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TestCronHealthJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        // Simple test job for health check
        \Log::info('Cron health check test job executed at ' . now());
    }
}