<?php

namespace App\Console\Commands;

use App\Models\SystemNotification;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class NotificationSystemStatus extends Command
{
    protected $signature = 'notifications:status {--detailed : Show detailed status}';

    protected $description = 'Check the status of the notification system';

    public function handle()
    {
        $this->info('🔔 Notification System Status Check');
        $this->line('');

        // Check database tables
        $this->checkDatabaseTables();

        // Check service availability
        $this->checkServiceAvailability();

        // Check notification statistics
        $this->checkNotificationStatistics();

        // Check scheduled tasks
        $this->checkScheduledTasks();

        if ($this->option('detailed')) {
            $this->showDetailedStatus();
        }

        $this->line('');
        $this->info('✅ Notification System Status Check Complete');
    }

    private function checkDatabaseTables()
    {
        $this->info('📊 Database Tables:');

        $tables = [
            'system_notifications' => 'System Notifications',
            'notification_preferences' => 'User Preferences',
            'users' => 'Users',
            'students' => 'Students',
            'invoices' => 'Invoices',
            'payments' => 'Payments',
            'leave_applications' => 'Leave Applications',
        ];

        foreach ($tables as $table => $description) {
            $exists = Schema::hasTable($table);
            $icon = $exists ? '✅' : '❌';
            $this->line("  {$icon} {$description}: ".($exists ? 'EXISTS' : 'MISSING'));
        }
    }

    private function checkServiceAvailability()
    {
        $this->info('🔧 Service Availability:');

        try {
            $service = app(NotificationService::class);
            $this->line('  ✅ NotificationService: AVAILABLE');
        } catch (\Exception $e) {
            $this->line('  ❌ NotificationService: ERROR - '.$e->getMessage());
        }

        // Check if routes are registered
        $routes = ['admin.notifications.dashboard', 'notifications.index', 'test-notification'];
        foreach ($routes as $route) {
            $exists = \Route::has($route);
            $icon = $exists ? '✅' : '❌';
            $this->line("  {$icon} Route '{$route}': ".($exists ? 'REGISTERED' : 'MISSING'));
        }
    }

    private function checkNotificationStatistics()
    {
        $this->info('📈 Notification Statistics:');

        $stats = [
            'Total Notifications' => SystemNotification::count(),
            'Notifications Today' => SystemNotification::whereDate('created_at', today())->count(),
            'Unread Notifications' => SystemNotification::whereNull('read_by')->count(),
            'Critical Notifications (7 days)' => SystemNotification::where('priority', 'urgent')->where('created_at', '>=', now()->subDays(7))->count(),
        ];

        foreach ($stats as $label => $count) {
            $this->line("  📊 {$label}: {$count}");
        }
    }

    private function checkScheduledTasks()
    {
        $this->info('⏰ Scheduled Tasks:');

        $tasks = [
            'fees:send-reminders' => 'Fee Reminders',
            'finance:health-check' => 'Financial Health Check',
            'attendance:monitor' => 'Attendance Monitor',
            'system:health-check' => 'System Health Check',
        ];

        foreach ($tasks as $command => $description) {
            // Check if command exists
            try {
                \Artisan::call($command, ['--help' => true]);
                $this->line("  ✅ {$description}: AVAILABLE");
            } catch (\Exception $e) {
                $this->line("  ❌ {$description}: NOT AVAILABLE");
            }
        }
    }

    private function showDetailedStatus()
    {
        $this->info('🔍 Detailed Status:');

        // Check recent notifications by category
        $categories = SystemNotification::selectRaw('category, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('category')
            ->get();

        $this->line('  📊 Notifications by Category (Last 7 Days):');
        foreach ($categories as $category) {
            $this->line("    • {$category->category}: {$category->count}");
        }

        // Check notification priorities
        $priorities = SystemNotification::selectRaw('priority, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('priority')
            ->get();

        $this->line('  🎯 Notifications by Priority (Last 7 Days):');
        foreach ($priorities as $priority) {
            $this->line("    • {$priority->priority}: {$priority->count}");
        }

        // Check system health
        $this->line('  🏥 System Health:');
        $memoryUsage = memory_get_usage(true) / 1024 / 1024;
        $this->line('    • Memory Usage: '.round($memoryUsage, 2).' MB');

        $diskSpace = disk_free_space(base_path()) / 1024 / 1024 / 1024;
        $this->line('    • Available Disk Space: '.round($diskSpace, 2).' GB');
    }
}
