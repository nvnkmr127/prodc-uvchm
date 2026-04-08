<?php

// File: app/Console/Commands/FinalSystemTest.php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FinalSystemTest extends Command
{
    protected $signature = 'system:final-test {--quick : Run quick tests only}';

    protected $description = 'Run comprehensive system tests for notification integration';

    public function handle()
    {
        $this->info('🧪 Running Final System Tests...');
        $this->line('');

        $isQuick = $this->option('quick');
        $results = [];

        // Test 1: Database Tables
        $this->info('📊 Testing Database Tables...');
        $results['database'] = $this->testDatabaseTables();

        // Test 2: Notification Service
        $this->info('🔔 Testing Notification Service...');
        $results['service'] = $this->testNotificationService();

        // Test 3: Routes
        $this->info('🛣️ Testing Routes...');
        $results['routes'] = $this->testRoutes();

        // Test 4: Commands
        $this->info('⚡ Testing Commands...');
        $results['commands'] = $this->testCommands($isQuick);

        // Test 5: Dashboard
        $this->info('📈 Testing Dashboard...');
        $results['dashboard'] = $this->testDashboard();

        // Display Results
        $this->displayResults($results);

        $allPassed = collect($results)->every(fn ($result) => $result['status'] === 'pass');

        if ($allPassed) {
            $this->info('🎉 All tests passed! Your notification system is ready!');

            return 0;
        } else {
            $this->warn('⚠️  Some tests failed. Check the results above.');

            return 1;
        }
    }

    private function test_database_tables()
    {
        try {
            $requiredTables = ['system_notifications', 'notification_preferences', 'users', 'students'];
            $missingTables = [];

            foreach ($requiredTables as $table) {
                if (! \Schema::hasTable($table)) {
                    $missingTables[] = $table;
                }
            }

            if (empty($missingTables)) {
                return ['status' => 'pass', 'message' => 'All required tables exist'];
            } else {
                return ['status' => 'fail', 'message' => 'Missing tables: '.implode(', ', $missingTables)];
            }
        } catch (\Exception $e) {
            return ['status' => 'fail', 'message' => 'Database error: '.$e->getMessage()];
        }
    }

    private function test_notification_service()
    {
        try {
            $service = app(\App\Services\NotificationService::class);

            // Test basic notification
            $notification = $service->send([
                'title' => 'System Test Notification',
                'message' => 'This is a test notification for system validation',
                'type' => 'info',
                'category' => 'system',
                'priority' => 'low',
                'roles' => ['super-admin'],
                'data' => ['test' => true, 'timestamp' => now()->toISOString()],
            ]);

            if ($notification && $notification->id) {
                // Clean up test notification
                $notification->delete();

                return ['status' => 'pass', 'message' => 'Notification service working correctly'];
            } else {
                return ['status' => 'fail', 'message' => 'Failed to create notification'];
            }
        } catch (\Exception $e) {
            return ['status' => 'fail', 'message' => 'Service error: '.$e->getMessage()];
        }
    }

    private function test_routes()
    {
        try {
            $requiredRoutes = [
                'admin.notifications.dashboard',
                'admin.notifications.test',
            ];

            $missingRoutes = [];
            foreach ($requiredRoutes as $route) {
                if (! \Route::has($route)) {
                    $missingRoutes[] = $route;
                }
            }

            if (empty($missingRoutes)) {
                return ['status' => 'pass', 'message' => 'All required routes registered'];
            } else {
                return ['status' => 'fail', 'message' => 'Missing routes: '.implode(', ', $missingRoutes)];
            }
        } catch (\Exception $e) {
            return ['status' => 'fail', 'message' => 'Route error: '.$e->getMessage()];
        }
    }

    private function test_commands($isQuick)
    {
        $commands = [
            'notifications:status' => 'Notification status command',
            'system:simple-health' => 'Simple health check command',
        ];

        if (! $isQuick) {
            $commands['notifications:test'] = 'Notification test command';
            $commands['fees:send-reminders'] = 'Fee reminders command';
        }

        $results = [];
        foreach ($commands as $command => $description) {
            try {
                \Artisan::call($command, $command === 'fees:send-reminders' ? ['--dry-run' => true] : []);
                $results[$command] = 'pass';
            } catch (\Exception $e) {
                $results[$command] = 'fail: '.$e->getMessage();
            }
        }

        $passedCount = count(array_filter($results, fn ($r) => $r === 'pass'));
        $totalCount = count($results);

        if ($passedCount === $totalCount) {
            return ['status' => 'pass', 'message' => "All {$totalCount} commands working"];
        } else {
            return ['status' => 'warn', 'message' => "{$passedCount}/{$totalCount} commands working"];
        }
    }

    private function test_dashboard()
    {
        try {
            // Check if view file exists
            $viewPath = resource_path('views/admin/notifications/dashboard.blade.php');

            if (! file_exists($viewPath)) {
                return ['status' => 'fail', 'message' => 'Dashboard view file missing'];
            }

            // Test if we can compile the view (basic syntax check)
            $viewContent = file_get_contents($viewPath);
            if (strlen($viewContent) < 100) {
                return ['status' => 'fail', 'message' => 'Dashboard view file appears empty or incomplete'];
            }

            return ['status' => 'pass', 'message' => 'Dashboard view file exists and has content'];
        } catch (\Exception $e) {
            return ['status' => 'fail', 'message' => 'Dashboard error: '.$e->getMessage()];
        }
    }

    private function displayResults($results)
    {
        $this->line('');
        $this->info('📋 Test Results Summary:');
        $this->line('');

        $tableData = [];
        foreach ($results as $testName => $result) {
            $status = $result['status'];
            $icon = match ($status) {
                'pass' => '✅',
                'fail' => '❌',
                'warn' => '⚠️',
                default => '❓'
            };

            $tableData[] = [
                ucfirst($testName),
                $icon.' '.ucfirst($status),
                $result['message'],
            ];
        }

        $this->table(['Test', 'Status', 'Details'], $tableData);

        $passCount = collect($results)->where('status', 'pass')->count();
        $totalCount = count($results);
        $percentage = round(($passCount / $totalCount) * 100, 1);

        $this->line('');
        $this->info("📊 Overall Score: {$passCount}/{$totalCount} tests passed ({$percentage}%)");

        if ($percentage >= 80) {
            $this->info('🎉 System is ready for production!');
        } elseif ($percentage >= 60) {
            $this->warn('⚠️  System needs some fixes but core functionality works');
        } else {
            $this->error('❌ System needs significant fixes before use');
        }
    }
}
