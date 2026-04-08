<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SystemHealthController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Perform comprehensive system health check
     */
    public function performHealthCheck()
    {
        try {
            $healthData = [
                'timestamp' => now()->toISOString(),
                'database' => $this->checkDatabaseHealth(),
                'storage' => $this->checkStorageHealth(),
                'cache' => $this->checkCacheHealth(),
                'memory' => $this->checkMemoryUsage(),
                'disk' => $this->checkDiskUsage(),
                'queue' => $this->checkQueueHealth(),
            ];

            $overallStatus = $this->determineOverallHealth($healthData);
            $healthData['overall_status'] = $overallStatus;

            // Send notifications for critical issues
            $this->sendHealthNotifications($healthData);

            return response()->json([
                'success' => true,
                'health_data' => $healthData,
                'overall_status' => $overallStatus,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Health check failed: '.$e->getMessage(),
                'health_data' => ['error' => $e->getMessage()],
                'overall_status' => 'critical',
            ], 500);
        }
    }

    private function checkDatabaseHealth()
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $connectionTime = (microtime(true) - $start) * 1000;

            $start = microtime(true);
            $userCount = DB::table('users')->count();
            $queryTime = (microtime(true) - $start) * 1000;

            return [
                'status' => 'healthy',
                'connection_time_ms' => round($connectionTime, 2),
                'query_time_ms' => round($queryTime, 2),
                'user_count' => $userCount,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function checkStorageHealth()
    {
        try {
            $path = storage_path();
            $totalSpace = disk_total_space($path);
            $freeSpace = disk_free_space($path);

            if (! $totalSpace || ! $freeSpace) {
                return [
                    'status' => 'warning',
                    'error' => 'Could not determine disk space',
                ];
            }

            $usedSpace = $totalSpace - $freeSpace;
            $usagePercentage = ($usedSpace / $totalSpace) * 100;

            return [
                'status' => $usagePercentage > 90 ? 'critical' : ($usagePercentage > 80 ? 'warning' : 'healthy'),
                'total_space_gb' => round($totalSpace / 1024 / 1024 / 1024, 2),
                'free_space_gb' => round($freeSpace / 1024 / 1024 / 1024, 2),
                'used_space_gb' => round($usedSpace / 1024 / 1024 / 1024, 2),
                'usage_percentage' => round($usagePercentage, 2),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function checkCacheHealth()
    {
        try {
            $start = microtime(true);
            Cache::put('health_check_test', 'test_value', 60);
            $value = Cache::get('health_check_test');
            $responseTime = (microtime(true) - $start) * 1000;

            return [
                'status' => $value === 'test_value' ? 'healthy' : 'warning',
                'response_time_ms' => round($responseTime, 2),
                'driver' => config('cache.default'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function checkMemoryUsage()
    {
        try {
            $memoryUsage = memory_get_usage(true);
            $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
            $usagePercentage = $memoryLimit > 0 ? ($memoryUsage / $memoryLimit) * 100 : 0;

            return [
                'status' => $usagePercentage > 90 ? 'critical' : ($usagePercentage > 80 ? 'warning' : 'healthy'),
                'usage_mb' => round($memoryUsage / 1024 / 1024, 2),
                'limit_mb' => round($memoryLimit / 1024 / 1024, 2),
                'usage_percentage' => round($usagePercentage, 2),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'warning',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function checkDiskUsage()
    {
        try {
            $path = base_path();
            $totalSpace = disk_total_space($path);
            $freeSpace = disk_free_space($path);

            if (! $totalSpace || ! $freeSpace) {
                return [
                    'status' => 'warning',
                    'error' => 'Could not determine disk space',
                ];
            }

            $usedSpace = $totalSpace - $freeSpace;
            $usagePercentage = ($usedSpace / $totalSpace) * 100;

            return [
                'status' => $usagePercentage > 95 ? 'critical' : ($usagePercentage > 85 ? 'warning' : 'healthy'),
                'total_space_gb' => round($totalSpace / 1024 / 1024 / 1024, 2),
                'free_space_gb' => round($freeSpace / 1024 / 1024 / 1024, 2),
                'usage_percentage' => round($usagePercentage, 2),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'warning',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function checkQueueHealth()
    {
        try {
            // Check if we have any failed jobs
            $failedJobs = 0;

            // Only check failed_jobs table if it exists
            if (Schema::hasTable('failed_jobs')) {
                $failedJobs = DB::table('failed_jobs')->count();
            }

            return [
                'status' => $failedJobs > 10 ? 'warning' : 'healthy',
                'failed_jobs' => $failedJobs,
                'driver' => config('queue.default'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'warning',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function determineOverallHealth($healthData)
    {
        $statuses = collect($healthData)->pluck('status')->filter();

        if ($statuses->contains('critical')) {
            return 'critical';
        }
        if ($statuses->contains('warning')) {
            return 'warning';
        }

        return 'healthy';
    }

    private function sendHealthNotifications($healthData)
    {
        try {
            $criticalIssues = collect($healthData)->filter(function ($item) {
                return is_array($item) && isset($item['status']) && $item['status'] === 'critical';
            });

            if ($criticalIssues->isNotEmpty()) {
                $this->notificationService->sendSystemAlert(
                    'CRITICAL: System health issues detected',
                    'urgent',
                    [
                        'critical_issues' => $criticalIssues->keys()->toArray(),
                        'health_data' => $healthData,
                        'requires_immediate_attention' => true,
                    ]
                );
            }

            $warningIssues = collect($healthData)->filter(function ($item) {
                return is_array($item) && isset($item['status']) && $item['status'] === 'warning';
            });

            if ($warningIssues->isNotEmpty()) {
                $this->notificationService->sendSystemAlert(
                    'System performance warnings detected',
                    'high',
                    [
                        'warning_issues' => $warningIssues->keys()->toArray(),
                        'health_data' => $healthData,
                    ]
                );
            }
        } catch (\Exception $e) {
            \Log::error('Failed to send health notifications: '.$e->getMessage());
        }
    }

    private function parseMemoryLimit($memoryLimit)
    {
        if (is_numeric($memoryLimit)) {
            return (int) $memoryLimit;
        }

        $value = (int) $memoryLimit;
        $unit = strtolower(substr($memoryLimit, -1));

        switch ($unit) {
            case 'g':
                return $value * 1024 * 1024 * 1024;
            case 'm':
                return $value * 1024 * 1024;
            case 'k':
                return $value * 1024;
            default:
                return $value;
        }
    }

    /**
     * Simple health check endpoint for command line
     */
    public function simpleHealthCheck()
    {
        try {
            $checks = [
                'database' => $this->checkDatabase(),
                'storage' => $this->checkStorage(),
                'cache' => $this->checkCache(),
            ];

            $allHealthy = ! in_array('critical', array_column($checks, 'status'));

            return response()->json([
                'success' => true,
                'healthy' => $allHealthy,
                'checks' => $checks,
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ], 500);
        }
    }

    // Simplified check methods for command line usage
    private function checkDatabase()
    {
        try {
            DB::connection()->getPdo();
            DB::table('users')->count();

            return ['status' => 'healthy', 'message' => 'Database connection successful'];
        } catch (\Exception $e) {
            return ['status' => 'critical', 'message' => 'Database connection failed'];
        }
    }

    private function checkStorage()
    {
        try {
            $path = storage_path();
            $freeSpace = disk_free_space($path);
            $totalSpace = disk_total_space($path);

            if (! $freeSpace || ! $totalSpace) {
                return ['status' => 'warning', 'message' => 'Could not determine storage space'];
            }

            $usagePercent = (($totalSpace - $freeSpace) / $totalSpace) * 100;

            if ($usagePercent > 90) {
                return ['status' => 'critical', 'message' => 'Storage space critical'];
            } elseif ($usagePercent > 80) {
                return ['status' => 'warning', 'message' => 'Storage space low'];
            } else {
                return ['status' => 'healthy', 'message' => 'Storage space adequate'];
            }
        } catch (\Exception $e) {
            return ['status' => 'warning', 'message' => 'Storage check failed'];
        }
    }

    private function checkCache()
    {
        try {
            Cache::put('health_test', 'ok', 60);
            $value = Cache::get('health_test');

            return $value === 'ok' ?
                ['status' => 'healthy', 'message' => 'Cache working'] :
                ['status' => 'warning', 'message' => 'Cache not working'];
        } catch (\Exception $e) {
            return ['status' => 'warning', 'message' => 'Cache check failed'];
        }
    }
}
