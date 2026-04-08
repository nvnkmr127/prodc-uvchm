<?php

namespace App\Services;

use App\Models\Dashboard;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    protected $widgetService;

    protected $analyticsService;

    // Make dependencies optional with default null values
    public function __construct(?WidgetService $widgetService = null, ?AnalyticsService $analyticsService = null)
    {
        $this->widgetService = $widgetService;
        $this->analyticsService = $analyticsService;
    }

    /**
     * Get dashboard for user's role with filtered widgets
     */
    public function getDashboardForUser(User $user): ?Dashboard
    {
        if (! class_exists('App\\Models\\DashboardWidget') || ! class_exists('App\\Models\\Widget')) {
            return null;
        }

        $dashboard = $user->getDefaultDashboard();

        if (! $dashboard) {
            return null;
        }

        // Load the dashboard widgets relationship
        $dashboard->load(['widgets.widget']);

        // Get the widgets collection from the loaded relationship
        $widgets = $dashboard->widgets;

        return $dashboard;
    }

    /**
     * Get dashboard data for user
     */
    public function getDashboardData(User $user): array
    {
        if (! class_exists('App\\Models\\DashboardWidget') || ! class_exists('App\\Models\\Widget')) {
            return [];
        }

        return Cache::remember("dashboard_data_user_{$user->id}", 300, function () use ($user) {
            $dashboard = $this->getDashboardForUser($user);

            if (! $dashboard) {
                return [];
            }

            $data = [
                'dashboard' => $dashboard,
                'widgets' => [],
                'user_preferences' => $this->getUserPreferences($user, $dashboard),
            ];

            // Load data for each widget
            foreach ($dashboard->widgets as $dashboardWidget) {
                $widgetData = $this->getWidgetData($user, $dashboardWidget->widget, $dashboardWidget->getMergedConfig());

                $data['widgets'][$dashboardWidget->instance_id] = [
                    'widget' => $dashboardWidget->widget,
                    'config' => $dashboardWidget->getMergedConfig(),
                    'data' => $widgetData,
                    'position' => [
                        'x' => $dashboardWidget->grid_x,
                        'y' => $dashboardWidget->grid_y,
                        'w' => $dashboardWidget->grid_w,
                        'h' => $dashboardWidget->grid_h,
                    ],
                ];
            }

            return $data;
        });
    }

    /**
     * Get widget data (with fallback if services not available)
     */
    protected function getWidgetData(User $user, $widget, array $config = []): array
    {
        if ($this->widgetService && method_exists($this->widgetService, 'getWidgetData')) {
            return $this->widgetService->getWidgetData($user, $widget, $config);
        }

        // Fallback implementation
        return [
            'widget_id' => is_object($widget) && isset($widget->id) ? $widget->id : null,
            'widget_name' => is_object($widget) && isset($widget->name) ? $widget->name : null,
            'data' => [],
            'last_updated' => now()->toISOString(),
        ];
    }

    /**
     * Get user preferences for dashboard
     */
    protected function getUserPreferences(User $user, Dashboard $dashboard): array
    {
        $preference = $user->dashboardPreferences()
            ->where('dashboard_id', $dashboard->id)
            ->first();

        return [
            'layout_preferences' => $preference?->layout_preferences ?? [],
            'widget_preferences' => $preference?->widget_preferences ?? [],
            'filter_preferences' => $preference?->filter_preferences ?? [],
            'is_customized' => $preference?->is_customized ?? false,
        ];
    }

    /**
     * Get widgets data (with fallback)
     */
    protected function getWidgets(): array
    {
        if ($this->widgetService && method_exists($this->widgetService, 'getAllWidgets')) {
            return $this->widgetService->getAllWidgets()->toArray();
        }

        // Fallback: return empty array or basic data
        return [];
    }

    /**
     * Get analytics data (with fallback)
     */
    protected function getAnalytics(): array
    {
        if ($this->analyticsService && method_exists($this->analyticsService, 'getDashboardAnalytics')) {
            return $this->analyticsService->getDashboardAnalytics();
        }

        // Fallback: return empty array or basic analytics
        return [
            'user_stats' => [],
            'student_stats' => [],
            'payment_stats' => [],
            'attendance_stats' => [],
        ];
    }

    /**
     * Clear user cache
     */
    public function clearUserCache(User $user): void
    {
        Cache::forget("dashboard_data_user_{$user->id}");
    }

    /**
     * Update dashboard layout for user
     */
    public function updateUserDashboardLayout(User $user, Dashboard $dashboard, array $layout): bool
    {
        try {
            if (! class_exists('App\\Models\\DashboardWidget')) {
                return false;
            }

            $dashboardWidgetClass = 'App\\Models\\DashboardWidget';

            // Update dashboard widget positions
            foreach ($layout as $widgetLayout) {
                $dashboardWidgetClass::where('dashboard_id', $dashboard->id)
                    ->where('instance_id', $widgetLayout['instance_id'])
                    ->update([
                        'grid_x' => $widgetLayout['x'],
                        'grid_y' => $widgetLayout['y'],
                        'grid_w' => $widgetLayout['w'],
                        'grid_h' => $widgetLayout['h'],
                        'order' => $widgetLayout['order'] ?? 0,
                    ]);
            }

            // Clear cache
            $this->clearUserCache($user);

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to update dashboard layout: '.$e->getMessage());

            return false;
        }
    }
}
