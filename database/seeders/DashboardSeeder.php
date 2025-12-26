<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Dashboard, Widget, DashboardWidget};
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;

class DashboardSeeder extends Seeder
{
    public function run()
    {
        $roles = Role::all();
        
        foreach ($roles as $role) {
            $this->createDashboardForRole($role);
        }
    }

    private function createDashboardForRole(Role $role)
    {
        // Create default dashboard for each role
        $dashboard = Dashboard::updateOrCreate(
            [
                'role_id' => $role->id,
                'is_default' => true
            ],
            [
                'name' => ucfirst($role->name) . ' Dashboard',
                'slug' => $role->name . '-dashboard',
                'is_active' => true,
                'config' => $this->getDefaultConfig($role->name)
            ]
        );

        // Add widgets based on role
        $this->addWidgetsToRole($dashboard, $role->name);
    }

    private function getDefaultConfig($roleName)
    {
        $configs = [
            'super-admin' => [
                'theme' => 'admin',
                'layout' => 'grid',
                'refresh_interval' => 300,
                'show_export' => true,
                'show_filters' => true
            ],
            'college-admin' => [
                'theme' => 'admin',
                'layout' => 'grid',
                'refresh_interval' => 600,
                'show_export' => true,
                'show_filters' => true
            ],
            'accountant' => [
                'theme' => 'financial',
                'layout' => 'grid',
                'refresh_interval' => 300,
                'show_export' => true,
                'show_filters' => false
            ],
            'staff' => [
                'theme' => 'academic',
                'layout' => 'vertical',
                'refresh_interval' => 600,
                'show_export' => false,
                'show_filters' => false
            ],
            'student' => [
                'theme' => 'student',
                'layout' => 'card',
                'refresh_interval' => 900,
                'show_export' => false,
                'show_filters' => false
            ]
        ];

        return $configs[$roleName] ?? $configs['student'];
    }

    private function addWidgetsToRole(Dashboard $dashboard, $roleName)
    {
        // Clear existing widgets
        $dashboard->widgets()->delete();

        $widgetConfigs = $this->getWidgetConfigsForRole($roleName);
        
        foreach ($widgetConfigs as $config) {
            $widget = Widget::where('slug', $config['widget_slug'])->first();
            
            if ($widget) {
                DashboardWidget::create([
                    'dashboard_id' => $dashboard->id,
                    'widget_id' => $widget->id,
                    'instance_id' => (string) Str::uuid(),
                    'row' => $config['y'], // Add the required row field
                    'grid_x' => $config['x'],
                    'grid_y' => $config['y'],
                    'grid_w' => $config['w'],
                    'grid_h' => $config['h'],
                    'order' => $config['order'],
                    'config' => $config['config'] ?? null
                ]);
            }
        }
    }

    private function getWidgetConfigsForRole($roleName)
    {
        $configs = [
            'super-admin' => [
                ['widget_slug' => 'revenue-chart', 'x' => 0, 'y' => 0, 'w' => 8, 'h' => 400, 'order' => 1],
                ['widget_slug' => 'attendance-analytics', 'x' => 8, 'y' => 0, 'w' => 4, 'h' => 400, 'order' => 2],
                ['widget_slug' => 'student-performance-chart', 'x' => 0, 'y' => 1, 'w' => 6, 'h' => 350, 'order' => 3],
                ['widget_slug' => 'fee-collection-chart', 'x' => 6, 'y' => 1, 'w' => 6, 'h' => 350, 'order' => 4],
                ['widget_slug' => 'student-enrollment-stats', 'x' => 0, 'y' => 2, 'w' => 4, 'h' => 200, 'order' => 5],
                ['widget_slug' => 'fee-summary-card', 'x' => 4, 'y' => 2, 'w' => 4, 'h' => 200, 'order' => 6],
                ['widget_slug' => 'daily-revenue', 'x' => 8, 'y' => 2, 'w' => 4, 'h' => 200, 'order' => 7]
            ],
            'college-admin' => [
                ['widget_slug' => 'student-enrollment-stats', 'x' => 0, 'y' => 0, 'w' => 4, 'h' => 200, 'order' => 1],
                ['widget_slug' => 'course-progress-tracker', 'x' => 4, 'y' => 0, 'w' => 4, 'h' => 200, 'order' => 2],
                ['widget_slug' => 'recent-admissions', 'x' => 8, 'y' => 0, 'w' => 4, 'h' => 200, 'order' => 3],
                ['widget_slug' => 'attendance-analytics', 'x' => 0, 'y' => 1, 'w' => 6, 'h' => 350, 'order' => 4],
                ['widget_slug' => 'student-performance-chart', 'x' => 6, 'y' => 1, 'w' => 6, 'h' => 350, 'order' => 5],
                ['widget_slug' => 'staff-directory', 'x' => 0, 'y' => 2, 'w' => 6, 'h' => 300, 'order' => 6]
            ],
            'accountant' => [
                ['widget_slug' => 'fee-summary-card', 'x' => 0, 'y' => 0, 'w' => 4, 'h' => 200, 'order' => 1],
                ['widget_slug' => 'daily-revenue', 'x' => 4, 'y' => 0, 'w' => 4, 'h' => 200, 'order' => 2],
                ['widget_slug' => 'revenue-chart', 'x' => 8, 'y' => 0, 'w' => 4, 'h' => 200, 'order' => 3],
                ['widget_slug' => 'fee-collection-chart', 'x' => 0, 'y' => 1, 'w' => 8, 'h' => 400, 'order' => 4],
                ['widget_slug' => 'outstanding-fees', 'x' => 8, 'y' => 1, 'w' => 4, 'h' => 400, 'order' => 5],
                ['widget_slug' => 'quick-fee-collection', 'x' => 0, 'y' => 2, 'w' => 6, 'h' => 250, 'order' => 6]
            ],
            'staff' => [
                ['widget_slug' => 'student-enrollment-stats', 'x' => 0, 'y' => 0, 'w' => 6, 'h' => 200, 'order' => 1],
                ['widget_slug' => 'attendance-analytics', 'x' => 6, 'y' => 0, 'w' => 6, 'h' => 200, 'order' => 2],
                ['widget_slug' => 'course-progress-tracker', 'x' => 0, 'y' => 1, 'w' => 8, 'h' => 300, 'order' => 3],
                ['widget_slug' => 'recent-admissions', 'x' => 8, 'y' => 1, 'w' => 4, 'h' => 300, 'order' => 4]
            ],
            'student' => [
                ['widget_slug' => 'course-progress-tracker', 'x' => 0, 'y' => 0, 'w' => 8, 'h' => 300, 'order' => 1],
                ['widget_slug' => 'attendance-analytics', 'x' => 8, 'y' => 0, 'w' => 4, 'h' => 300, 'order' => 2]
            ]
        ];

        return $configs[$roleName] ?? [];
    }
}