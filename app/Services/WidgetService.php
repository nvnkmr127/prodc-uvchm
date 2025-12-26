<?php

namespace App\Services;

use App\Models\Widget;
use Illuminate\Support\Collection;

class WidgetService
{
    /**
     * Get all available widgets
     */
    public function getAllWidgets(): Collection
    {
        return Widget::all();
    }

    /**
     * Get widget by ID
     */
    public function getWidget(int $id): ?Widget
    {
        return Widget::find($id);
    }

    /**
     * Get widgets by category
     */
    public function getWidgetsByCategory(string $category): Collection
    {
        return Widget::where('category', $category)->get();
    }

    /**
     * Create a new widget
     */
    public function createWidget(array $data): Widget
    {
        return Widget::create($data);
    }

    /**
     * Update widget
     */
    public function updateWidget(int $id, array $data): bool
    {
        $widget = Widget::find($id);
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
        $widget = Widget::find($id);
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
        $widget = Widget::find($id);
        return $widget ? $widget->config ?? [] : [];
    }

    /**
     * Update widget configuration
     */
    public function updateWidgetConfig(int $id, array $config): bool
    {
        $widget = Widget::find($id);
        if (!$widget) {
            return false;
        }

        return $widget->update(['config' => $config]);
    }
}