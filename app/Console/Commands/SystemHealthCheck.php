<?php

namespace App\Console\Commands;

use App\Http\Controllers\Admin\SystemHealthController;
use Illuminate\Console\Command;

class SystemHealthCheck extends Command
{
    protected $signature = 'system:health-check {--notify : Send notifications for issues}';

    protected $description = 'Perform comprehensive system health check';

    public function handle()
    {
        $this->info('🏥 Performing System Health Check...');

        try {
            // Check if we have the controller
            if (! class_exists(\App\Http\Controllers\Admin\SystemHealthController::class)) {
                return $this->simpleHealthCheck();
            }

            $controller = app(SystemHealthController::class);
            $result = $controller->performHealthCheck();

            // Safely get data as array
            $data = $result->getData(true);

            if (! isset($data['health_data'])) {
                $this->error('Invalid health data format');

                return 1;
            }

            $this->displayHealthSummary($data['health_data']);

            $status = $data['overall_status'] ?? 'unknown';
            match ($status) {
                'healthy' => $this->info('✅ System is healthy!'),
                'warning' => $this->warn('⚠️  System has some warnings'),
                'critical' => $this->error('❌ CRITICAL: System has critical issues!'),
                default => $this->warn('❓ Unknown system status: '.$status),
            };

            return $status === 'critical' ? 1 : 0;

        } catch (\Exception $e) {
            $this->error('❌ Health check failed: '.$e->getMessage());

            return $this->simpleHealthCheck();
        }
    }

    private function displayHealthSummary($healthData)
    {
        if (! is_array($healthData)) {
            $this->error('Invalid health data format received');

            return;
        }

        $rows = [];

        foreach ($healthData as $component => $data) {
            if (in_array($component, ['overall_status', 'timestamp'])) {
                continue;
            }

            // Convert object to array if needed
            if (is_object($data)) {
                $data = (array) $data;
            }

            // Skip if data is not an array
            if (! is_array($data)) {
                continue;
            }

            $status = $data['status'] ?? 'unknown';
            $statusIcon = match ($status) {
                'healthy' => '✅',
                'warning' => '⚠️',
                'critical' => '❌',
                default => '❓'
            };

            $details = $this->getHealthDetails($data);
            $rows[] = [ucfirst($component), $statusIcon.' '.ucfirst($status), $details];
        }

        if (! empty($rows)) {
            $this->table(['Component', 'Status', 'Details'], $rows);
        } else {
            $this->warn('No health data available to display');
        }
    }

    private function getHealthDetails($data)
    {
        if (isset($data['usage_percentage'])) {
            return $data['usage_percentage'].'% used';
        }

        if (isset($data['response_time_ms'])) {
            return $data['response_time_ms'].'ms';
        }

        if (isset($data['connection_time_ms'])) {
            return $data['connection_time_ms'].'ms connection';
        }

        if (isset($data['failed_jobs'])) {
            return $data['failed_jobs'].' failed jobs';
        }

        if (isset($data['error'])) {
            return substr($data['error'], 0, 50).(strlen($data['error']) > 50 ? '...' : '');
        }

        return '';
    }

    /**
     * Simple health check fallback
     */
    private function simpleHealthCheck()
    {
        $this->info('🏥 Running Simple Health Check...');

        $checks = [
            'Database' => $this->checkDatabase(),
            'Storage' => $this->checkStorage(),
            'Cache' => $this->checkCache(),
        ];

        $rows = [];
        $hasIssues = false;

        foreach ($checks as $component => $status) {
            $icon = match ($status) {
                'healthy' => '✅',
                'warning' => '⚠️',
                'critical' => '❌',
                default => '❓'
            };

            if (in_array($status, ['warning', 'critical'])) {
                $hasIssues = true;
            }

            $rows[] = [$component, $icon.' '.ucfirst($status)];
        }

        $this->table(['Component', 'Status'], $rows);

        if ($hasIssues) {
            $this->warn('⚠️  Some issues detected');

            return 1;
        } else {
            $this->info('✅ Basic health checks passed');

            return 0;
        }
    }

    private function checkDatabase()
    {
        try {
            \DB::connection()->getPdo();
            \DB::table('users')->count();

            return 'healthy';
        } catch (\Exception $e) {
            return 'critical';
        }
    }

    private function checkStorage()
    {
        try {
            $freeSpace = disk_free_space(storage_path());
            $totalSpace = disk_total_space(storage_path());

            if (! $freeSpace || ! $totalSpace) {
                return 'warning';
            }

            $usagePercent = (($totalSpace - $freeSpace) / $totalSpace) * 100;

            if ($usagePercent > 90) {
                return 'critical';
            }
            if ($usagePercent > 80) {
                return 'warning';
            }

            return 'healthy';
        } catch (\Exception $e) {
            return 'warning';
        }
    }

    private function checkCache()
    {
        try {
            \Cache::put('health_test', 'ok', 60);
            $value = \Cache::get('health_test');

            return $value === 'ok' ? 'healthy' : 'warning';
        } catch (\Exception $e) {
            return 'warning';
        }
    }
}
