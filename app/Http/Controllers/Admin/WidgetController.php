<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Widget, WidgetCategory};
use App\Services\WidgetDataService;
use Illuminate\Http\JsonResponse;

class WidgetController extends Controller
{
    public function __construct(
        private WidgetDataService $widgetDataService
    ) {}

    public function getCategories(): JsonResponse
    {
        $categories = WidgetCategory::with(['widgets' => function ($query) {
            $query->where('is_active', true);
        }])
        ->where('is_active', true)
        ->orderBy('order')
        ->get()
        ->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'icon' => $category->icon,
                'color' => $category->color,
                'widgets' => $category->widgets->map(function ($widget) {
                    return [
                        'id' => $widget->id,
                        'name' => $widget->name,
                        'type' => $widget->type,
                        'component' => $widget->component,
                        'icon' => $widget->icon,
                        'description' => $widget->description,
                        'default_width' => $widget->default_width,
                        'default_height' => $widget->default_height
                    ];
                })
            ];
        });

        return response()->json(['categories' => $categories]);
    }

    public function getWidgetData(Widget $widget): JsonResponse
    {
        $data = $this->widgetDataService->getWidgetData($widget);
        return response()->json($data);
    }
}