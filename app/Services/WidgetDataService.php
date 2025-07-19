<?php

namespace App\Services;

use App\Models\Widget;
use Illuminate\Support\Facades\Cache;

class WidgetDataService
{
    public function getWidgetData(Widget $widget, array $params = []): array
    {
        $cacheKey = "widget.{$widget->id}." . md5(serialize($params));
        
        return Cache::remember($cacheKey, 300, function () use ($widget, $params) {
            return $this->fetchWidgetData($widget, $params);
        });
    }

    private function fetchWidgetData(Widget $widget, array $params): array
    {
        if (!$widget->data_source || !class_exists($widget->data_source)) {
            return ['data' => [], 'error' => 'No data source configured'];
        }

        $dataProvider = app($widget->data_source);
        return $dataProvider->getData($params);
    }
}