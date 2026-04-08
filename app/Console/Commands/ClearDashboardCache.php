<?php

namespace App\Console\Commands;

use App\Models\Dashboard;
use App\Models\User;
use App\Models\Widget;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearDashboardCache extends Command
{
    protected $signature = 'dashboard:clear-cache 
                            {--user= : Clear cache for specific user ID}
                            {--widget= : Clear cache for specific widget ID}
                            {--all : Clear all dashboard caches}';

    protected $description = 'Clear dashboard and widget caches';

    public function handle()
    {
        $userId = $this->option('user');
        $widgetId = $this->option('widget');
        $clearAll = $this->option('all');

        if ($clearAll) {
            $this->clearAllDashboardCaches();
        } elseif ($userId) {
            $this->clearUserCache($userId);
        } elseif ($widgetId) {
            $this->clearWidgetCache($widgetId);
        } else {
            $this->clearCommonCaches();
        }

        $this->info('✅ Dashboard cache cleared successfully!');

        return Command::SUCCESS;
    }

    private function clearAllDashboardCaches()
    {
        $this->info('🧹 Clearing all dashboard caches...');

        // Clear all widget data caches
        $widgets = Widget::all();
        foreach ($widgets as $widget) {
            Cache::forget("widget_data_{$widget->id}");
        }
        $this->line("   - Cleared {$widgets->count()} widget caches");

        // Clear all user dashboard caches
        $users = User::all();
        foreach ($users as $user) {
            $roleName = $user->getRoleNames()->first();
            if ($roleName) {
                Cache::forget("dashboard_data_{$user->id}_{$roleName}");
                Cache::forget("staff_{$user->id}_accessible_students");
                Cache::forget("staff_{$user->id}_accessible_classes");
            }
        }
        $this->line("   - Cleared {$users->count()} user dashboard caches");

        // Clear general dashboard caches
        Cache::forget('dashboard_analytics');
        Cache::forget('dashboard_system_health');
        $this->line('   - Cleared general dashboard caches');
    }

    private function clearUserCache($userId)
    {
        $user = User::find($userId);
        if (! $user) {
            $this->error("User with ID {$userId} not found");

            return;
        }

        $this->info("🧹 Clearing cache for user: {$user->name} (ID: {$userId})");

        $roleName = $user->getRoleNames()->first();
        if ($roleName) {
            Cache::forget("dashboard_data_{$user->id}_{$roleName}");
            Cache::forget("staff_{$user->id}_accessible_students");
            Cache::forget("staff_{$user->id}_accessible_classes");
            $this->line("   - Cache cleared for role: {$roleName}");
        }
    }

    private function clearWidgetCache($widgetId)
    {
        $widget = Widget::find($widgetId);
        if (! $widget) {
            $this->error("Widget with ID {$widgetId} not found");

            return;
        }

        $this->info("🧹 Clearing cache for widget: {$widget->name} (ID: {$widgetId})");
        Cache::forget("widget_data_{$widget->id}");
    }

    private function clearCommonCaches()
    {
        $this->info('🧹 Clearing common dashboard caches...');

        $cacheKeys = [
            'dashboard_analytics',
            'dashboard_system_health',
            'widget_categories',
            'dashboard_templates',
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }

        $this->line('   - Cleared '.count($cacheKeys).' common cache keys');
    }
}
