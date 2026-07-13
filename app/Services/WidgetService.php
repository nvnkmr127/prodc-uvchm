<?php

namespace App\Services;

use Illuminate\Support\Collection;

class WidgetService
{
    /**
     * Get all available widgets
     */
    public function getAllWidgets(): Collection
    {
        if (! class_exists('App\\Models\\Widget')) {
            return collect();
        }

        $widgetClass = 'App\\Models\\Widget';

        return $widgetClass::all();
    }
}
