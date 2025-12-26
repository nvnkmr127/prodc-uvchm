<?php

namespace App\Services;

use App\Models\User;
use App\Models\Dashboard;
use App\Models\DashboardView;

class DashboardAnalyticsService
{
    public function trackDashboardView(User $user, Dashboard $dashboard): void
    {
        DashboardView::create([
            'user_id' => $user->id,
            'dashboard_id' => $dashboard->id,
            'viewed_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
    }

    public function trackWidgetInteraction(User $user, Widget $widget, string $action): void
    {
        WidgetInteraction::create([
            'user_id' => $user->id,
            'widget_id' => $widget->id,
            'action' => $action,
            'occurred_at' => now()
        ]);
    }

    public function getDashboardUsageStats(Dashboard $dashboard, string $period = '30d'): array
    {
        $startDate = $this->getPeriodStartDate($period);
        
        return [
            'total_views' => DashboardView::where('dashboard_id', $dashboard->id)
                ->where('viewed_at', '>=', $startDate)
                ->count(),
            'unique_users' => DashboardView::where('dashboard_id', $dashboard->id)
                ->where('viewed_at', '>=', $startDate)
                ->distinct('user_id')
                ->count(),
            'avg_session_duration' => $this->calculateAverageSessionDuration($dashboard, $startDate),
            'most_used_widgets' => $this->getMostUsedWidgets($dashboard, $startDate),
            'usage_by_hour' => $this->getUsageByHour($dashboard, $startDate)
        ];
    }

    public function getPerformanceMetrics(): array
    {
        return [
            'avg_load_time' => Cache::remember('dashboard_avg_load_time', 300, function () {
                return DashboardMetric::where('metric', 'load_time')
                    ->where('created_at', '>=', now()->subDays(7))
                    ->avg('value');
            }),
            'error_rate' => Cache::remember('dashboard_error_rate', 300, function () {
                $total = DashboardView::where('created_at', '>=', now()->subDays(7))->count();
                $errors = DashboardError::where('created_at', '>=', now()->subDays(7))->count();
                return $total > 0 ? ($errors / $total) * 100 : 0;
            }),
            'widget_response_times' => $this->getWidgetResponseTimes()
        ];
    }
}