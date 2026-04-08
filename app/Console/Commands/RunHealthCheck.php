<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RunHealthCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'health:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs a system health check and logs a warning on failure.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Running system health check...');

        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            // Add other checks from your settings config here...
            'file_permissions' => $this->checkFilePermissions(),
        ];

        $isHealthy = ! in_array(false, array_column($checks, 'status'));

        if ($isHealthy) {
            $this->info('System health check passed.');
            \Log::info('System health check passed.');
        } else {
            $this->warn('System health check failed!');
            \Log::warning('System health check failed', ['results' => $checks]);

            // Optional: Send notification to admins if configured
            if (setting('health_check_notifications', false, 'bool')) {
                // Implement your notification logic here (e.g., mail or Slack)
            }
        }

        return $isHealthy ? Command::SUCCESS : Command::FAILURE;
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();

            return ['status' => true, 'message' => 'Database connection successful.'];
        } catch (\Exception $e) {
            return ['status' => false, 'message' => 'Database connection failed: '.$e->getMessage()];
        }
    }

    private function checkCache(): array
    {
        try {
            Cache::put('health_check', 'ok', 10);
            $value = Cache::get('health_check');
            if ($value === 'ok') {
                return ['status' => true, 'message' => 'Cache is functioning correctly.'];
            }

            return ['status' => false, 'message' => 'Failed to retrieve value from cache.'];
        } catch (\Exception $e) {
            return ['status' => false, 'message' => 'Cache system failed: '.$e->getMessage()];
        }
    }

    private function checkFilePermissions(): array
    {
        $path = storage_path('logs');
        if (is_writable($path)) {
            return ['status' => true, 'message' => 'Log directory is writable.'];
        }

        return ['status' => false, 'message' => 'Log directory is not writable.'];
    }
}
