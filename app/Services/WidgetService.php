<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class WidgetService
{
    /**
     * Get all available widgets
     */
    public function getAllWidgets(): Collection
    {
        if (!class_exists('App\\Models\\Widget')) {
            return collect();
        }

        $widgetClass = 'App\\Models\\Widget';
        return $widgetClass::all();
    }

    /**
     * Get widget by ID
     */
    public function getWidget(int $id): ?Model
    {
        if (!class_exists('App\\Models\\Widget')) {
            return null;
        }

        $widgetClass = 'App\\Models\\Widget';
        return $widgetClass::find($id);
    }

    /**
     * Get widgets by category
     */
    public function getWidgetsByCategory(string $category): Collection
    {
        if (!class_exists('App\\Models\\Widget')) {
            return collect();
        }

        $widgetClass = 'App\\Models\\Widget';
        return $widgetClass::where('category', $category)->get();
    }

    /**
     * Create a new widget
     */
    public function createWidget(array $data): Model
    {
        if (!class_exists('App\\Models\\Widget')) {
            throw new \RuntimeException('Widget model is not available');
        }

        $widgetClass = 'App\\Models\\Widget';
        return $widgetClass::create($data);
    }

    /**
     * Update widget
     */
    public function updateWidget(int $id, array $data): bool
    {
        if (!class_exists('App\\Models\\Widget')) {
            return false;
        }

        $widgetClass = 'App\\Models\\Widget';
        $widget = $widgetClass::find($id);
        if (!$widget) {
            return false;
        }

        return $widget->update($data);
    }

    /**
     * Delete widget
     */
    public function deleteWidget(int $id): bool
    {
        if (!class_exists('App\\Models\\Widget')) {
            return false;
        }

        $widgetClass = 'App\\Models\\Widget';
        $widget = $widgetClass::find($id);
        if (!$widget) {
            return false;
        }

        return $widget->delete();
    }

    /**
     * Get widget configuration
     */
    public function getWidgetConfig(int $id): array
    {
        if (!class_exists('App\\Models\\Widget')) {
            return [];
        }

        $widgetClass = 'App\\Models\\Widget';
        $widget = $widgetClass::find($id);
        return $widget ? $widget->config ?? [] : [];
    }

    /**
     * Update widget configuration
     */
    public function updateWidgetConfig(int $id, array $config): bool
    {
        if (!class_exists('App\\Models\\Widget')) {
            return false;
        }

        $widgetClass = 'App\\Models\\Widget';
        $widget = $widgetClass::find($id);
        if (!$widget) {
            return false;
        }

        return $widget->update(['config' => $config]);
    }
}
