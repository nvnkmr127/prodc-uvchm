<?php

namespace App\Services;

use App\Models\{Dashboard, DashboardWidget, Widget};
use Illuminate\Support\{Collection, Str};

class DashboardService
{
    public function getDashboardForRole(int $roleId): Dashboard
    {
        return Dashboard::with(['widgets.widget'])
            ->firstOrCreate(['role_id' => $roleId], [
                'name' => 'Default Dashboard',
                'is_default' => true,
                'config' => $this->getDefaultConfig()
            ]);
    }

    public function updateLayout(Dashboard $dashboard, array $widgets): void
    {
        \DB::transaction(function () use ($dashboard, $widgets) {
            // Remove existing widgets
            $dashboard->widgets()->delete();

            // Add new widget configurations
            foreach ($widgets as $index => $widgetData) {
                DashboardWidget::create([
                    'dashboard_id' => $dashboard->id,
                    'widget_id' => $widgetData['id'],
                    'instance_id' => $widgetData['instanceId'] ?? Str::uuid(),
                    'grid_x' => $widgetData['x'],
                    'grid_y' => $widgetData['y'],
                    'grid_w' => $widgetData['w'],
                    'grid_h' => $widgetData['h'],
                    'config' => $widgetData['config'] ?? [],
                    'order' => $index
                ]);
            }

            $dashboard->touch();
        });
    }

    private function getDefaultConfig(): array
    {
        return [
            'grid' => [
                'columns' => 12,
                'rowHeight' => 80,
                'gap' => 16
            ]
        ];
    }
}