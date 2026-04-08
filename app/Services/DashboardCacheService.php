<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class DashboardCacheService
{
    public function getCachedWidgetData(string $widgetId, array $params = []): array
    {
        $cacheKey = "widget.{$widgetId}.".md5(serialize($params));

        return Cache::remember($cacheKey, 300, function () use ($widgetId, $params) {
            return app(WidgetDataService::class)->getWidgetData($widgetId, $params);
        });
    }

    public function invalidateWidgetCache(string $widgetId): void
    {
        Cache::tags(["widget.{$widgetId}"])->flush();
    }
}
