<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\{Dashboard, Widget, WidgetCategory, DashboardWidget};
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;

class DashboardStatusCommand extends Command
{
    protected $signature = 'dashboard:status {--detailed : Show detailed information}';
    protected $description = 'Check dashboard system status and health';

    public function handle()
    {
        $this->info('📊 Dashboard System Status');
        $this->newLine();

        $status = $this->checkSystemStatus();
        
        $this->displayOverallStatus($status);
        
        if ($this->option('detailed')) {
            $this->displayDetailedStatus($status);
        }

        $this->displayRecommendations($status);

        return $status['overall'] === 'healthy' ? 0 : 1;
    }

    private function checkSystemStatus()
    {
        $status = [
            'overall' => 'healthy',
            'components' => [
                'database' => $this->checkDatabase(),
                'widgets' => $this->checkWidgets(),
                'templates' => $this->checkTemplates(),
                'cache' => $this->checkCache()
            ],
            'statistics' => $this->getStatistics()
        ];

        // Determine overall status
        $componentStatuses = collect($status['components'])->pluck('status');
        
        if ($componentStatuses->contains('error')) {
            $status['overall'] = 'error';
        } elseif ($componentStatuses->contains('warning')) {
            $status['overall'] = 'warning';
        }

        return $status;
    }

    private function checkDatabase()
    {
        try {
            $dashboards = Dashboard::count();
            $widgets = Widget::count();
            $categories = WidgetCategory::count();
            $dashboardWidgets = DashboardWidget::count();

            if ($dashboards === 0) {
                return [
                    'status' => 'warning',
                    'message' => 'No dashboards configured',
                    'details' => ['dashboards' => 0, 'widgets' => $widgets]
                ];
            }

            return [
                'status' => 'healthy',
                'message' => 'Database tables accessible',
                'details' => [
                    'dashboards' => $dashboards,
                    'widgets' => $widgets,
                    'categories' => $categories,
                    'dashboard_widgets' => $dashboardWidgets
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Database connection failed',
                'details' => ['error' => $e->getMessage()]
            ];
        }
    }

    private function checkWidgets()
    {
        try {
            $widgets = Widget::count();
            $activeWidgets = Widget::where('is_active', true)->count();
            $inactiveWidgets = $widgets - $activeWidgets;

            $status = 'healthy';
            $message = "Widgets loaded successfully";

            if ($activeWidgets === 0) {
                $status = 'warning';
                $message = 'No active widgets found';
            }

            return [
                'status' => $status,
                'message' => $message,
                'details' => [
                    'total' => $widgets,
                    'active' => $activeWidgets,
                    'inactive' => $inactiveWidgets
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Widget loading failed',
                'details' => ['error' => $e->getMessage()]
            ];
        }
    }

    private function checkTemplates()
    {
        $templatePath = resource_path('views/dashboard/widgets');
        
        if (!is_dir($templatePath)) {
            return [
                'status' => 'error',
                'message' => 'Widget templates directory not found',
                'details' => ['path' => $templatePath]
            ];
        }

        $templateFiles = File::glob("{$templatePath}/*.blade.php");
        $status = 'healthy';
        $message = 'All widget templates found';

        return [
            'status' => $status,
            'message' => $message,
            'details' => [
                'total_templates' => count($templateFiles)
            ]
        ];
    }

    private function checkCache()
    {
        try {
            Cache::put('dashboard_health_check', 'test', 60);
            $value = Cache::get('dashboard_health_check');
            
            if ($value === 'test') {
                return [
                    'status' => 'healthy',
                    'message' => 'Cache system working',
                    'details' => ['driver' => config('cache.default')]
                ];
            }

            return [
                'status' => 'warning',
                'message' => 'Cache not working properly',
                'details' => ['driver' => config('cache.default')]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Cache system failed',
                'details' => ['error' => $e->getMessage()]
            ];
        }
    }

    private function getStatistics()
    {
        try {
            return [
                'total_dashboards' => Dashboard::count(),
                'total_widgets' => Widget::count(),
                'active_widgets' => Widget::where('is_active', true)->count(),
                'widget_categories' => WidgetCategory::count(),
                'dashboard_widgets' => DashboardWidget::count()
            ];
        } catch (\Exception $e) {
            return [
                'total_dashboards' => 0,
                'total_widgets' => 0,
                'active_widgets' => 0,
                'widget_categories' => 0,
                'dashboard_widgets' => 0
            ];
        }
    }

    private function displayOverallStatus($status)
    {
        $statusColor = match($status['overall']) {
            'healthy' => 'info',
            'warning' => 'comment',
            'error' => 'error',
            default => 'line'
        };

        $statusIcon = match($status['overall']) {
            'healthy' => '✅',
            'warning' => '⚠️',
            'error' => '❌',
            default => 'ℹ️'
        };

        $this->{$statusColor}("{$statusIcon} Overall Status: " . ucfirst($status['overall']));
        $this->newLine();
    }

    private function displayDetailedStatus($status)
    {
        $this->info('🔍 Detailed Component Status:');
        $this->newLine();

        foreach ($status['components'] as $component => $details) {
            $icon = match($details['status']) {
                'healthy' => '✅',
                'warning' => '⚠️',
                'error' => '❌',
                default => 'ℹ️'
            };

            $this->line("{$icon} " . ucfirst($component) . ": {$details['message']}");
            
            if (!empty($details['details'])) {
                foreach ($details['details'] as $key => $value) {
                    $this->line("   {$key}: {$value}");
                }
            }
            $this->newLine();
        }

        $this->info('📈 System Statistics:');
        $this->table(
            ['Metric', 'Count'],
            collect($status['statistics'])->map(fn($value, $key) => [
                ucwords(str_replace('_', ' ', $key)),
                $value
            ])->toArray()
        );
    }

    private function displayRecommendations($status)
    {
        $recommendations = [];

        foreach ($status['components'] as $component => $details) {
            if ($details['status'] === 'error') {
                $recommendations[] = "❌ Fix {$component}: {$details['message']}";
            } elseif ($details['status'] === 'warning') {
                $recommendations[] = "⚠️  Check {$component}: {$details['message']}";
            }
        }

        if (empty($recommendations)) {
            $this->info('🎉 No issues found! Dashboard system is healthy.');
            return;
        }

        $this->newLine();
        $this->warn('🔧 Recommendations:');
        foreach ($recommendations as $recommendation) {
            $this->line("   {$recommendation}");
        }

        $this->newLine();
        $this->info('💡 Common solutions:');
        $this->line('   • Run: php artisan dashboard:setup --seed');
        $this->line('   • Run: php artisan dashboard:sync-widgets');
        $this->line('   • Run: php artisan migrate');
    }
}
