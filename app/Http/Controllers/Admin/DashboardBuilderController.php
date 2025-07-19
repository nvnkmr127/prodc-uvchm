<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Dashboard, Widget, DashboardWidget, WidgetCategory};
use App\Services\{DashboardService, WidgetDataService};
use Illuminate\Http\{Request, JsonResponse};
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;

class DashboardBuilderController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService,
        private WidgetDataService $widgetDataService
    ) {}

    // ADD THIS METHOD - This was missing!
    public function index()
    {
        // Get roles for the dropdown
        $roles = Role::whereNotIn('name', ['super-admin'])->get();
        
        // Get widget categories for the sidebar
        $widgetCategories = WidgetCategory::with(['widgets' => function ($query) {
            $query->where('is_active', true);
        }])->where('is_active', true)->orderBy('order')->get();

        return view('admin.dashboard-builder.index', compact('roles', 'widgetCategories'));
    }

    public function getRoles(): JsonResponse
    {
        $roles = Role::whereNotIn('name', ['super-admin'])
            ->get()
            ->map(fn($role) => [
                'id' => $role->id,
                'name' => Str::title($role->name),
                'slug' => $role->name
            ]);

        return response()->json(['roles' => $roles]);
    }

    public function loadDashboard(Role $role): JsonResponse
    {
        $dashboard = $this->dashboardService->getDashboardForRole($role->id);
        
        $widgets = $dashboard->widgets->map(function ($dashboardWidget) {
            $widget = $dashboardWidget->widget;
            $data = $this->widgetDataService->getWidgetData($widget);
            
            return [
                'instanceId' => $dashboardWidget->instance_id,
                'id' => $widget->id,
                'name' => $widget->name,
                'component' => $widget->component,
                'grid_x' => $dashboardWidget->grid_x,
                'grid_y' => $dashboardWidget->grid_y,
                'grid_w' => $dashboardWidget->grid_w,
                'grid_h' => $dashboardWidget->grid_h,
                'config' => $dashboardWidget->config,
                'data' => $data
            ];
        });

        return response()->json([
            'dashboard' => $dashboard,
            'widgets' => $widgets
        ]);
    }

    public function saveDashboard(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'dashboard_id' => 'required|exists:dashboards,id',
            'widgets' => 'array'
        ]);

        $dashboard = Dashboard::findOrFail($validated['dashboard_id']);
        $this->dashboardService->updateLayout($dashboard, $validated['widgets']);

        return response()->json(['message' => 'Dashboard saved successfully']);
    }

    public function addWidget(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'dashboard_id' => 'required|exists:dashboards,id',
            'widget_id' => 'required|exists:widgets,id',
            'position' => 'required|array'
        ]);

        $dashboard = Dashboard::findOrFail($validated['dashboard_id']);
        $widget = Widget::findOrFail($validated['widget_id']);

        $dashboardWidget = DashboardWidget::create([
            'dashboard_id' => $dashboard->id,
            'widget_id' => $widget->id,
            'instance_id' => Str::uuid(),
            'grid_x' => $validated['position']['x'] ?? 0,
            'grid_y' => $validated['position']['y'] ?? 0,
            'grid_w' => $widget->default_width,
            'grid_h' => $widget->default_height,
            'config' => $widget->default_config ?? [],
            'order' => $dashboard->widgets()->count()
        ]);

        $data = $this->widgetDataService->getWidgetData($widget);

        return response()->json([
            'widget' => [
                'instanceId' => $dashboardWidget->instance_id,
                'id' => $widget->id,
                'name' => $widget->name,
                'component' => $widget->component,
                'grid_x' => $dashboardWidget->grid_x,
                'grid_y' => $dashboardWidget->grid_y,
                'grid_w' => $dashboardWidget->grid_w,
                'grid_h' => $dashboardWidget->grid_h,
                'config' => $dashboardWidget->config,
                'data' => $data
            ]
        ]);
    }

    public function removeWidget(string $instanceId): JsonResponse
    {
        $dashboardWidget = DashboardWidget::where('instance_id', $instanceId)->first();
        
        if (!$dashboardWidget) {
            return response()->json(['error' => 'Widget not found'], 404);
        }

        $dashboardWidget->delete();

        return response()->json(['message' => 'Widget removed successfully']);
    }
    public function getWidgetCategories(): JsonResponse
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
    try {
        $data = $this->widgetDataService->getWidgetData($widget);
        return response()->json($data);
    } catch (\Exception $e) {
        return response()->json([
            'error' => true,
            'message' => 'Failed to load widget data',
            'data' => []
        ], 500);
    }
}
public function getTemplates(): JsonResponse
{
    $templates = DashboardTemplate::where('is_public', true)
        ->orWhere('created_by', auth()->id())
        ->with('creator:id,name')
        ->orderBy('usage_count', 'desc')
        ->get()
        ->map(function ($template) {
            return [
                'id' => $template->id,
                'name' => $template->name,
                'description' => $template->description,
                'category' => $template->category,
                'preview_image' => $template->preview_image,
                'widget_count' => count($template->layout),
                'usage_count' => $template->usage_count,
                'created_by' => $template->creator?->name ?? 'System',
                'is_own' => $template->created_by === auth()->id(),
                'created_at' => $template->created_at->diffForHumans()
            ];
        });

    return response()->json(['templates' => $templates]);
}

public function saveTemplate(Request $request): JsonResponse
{
    $validated = $request->validate([
        'dashboard_id' => 'required|exists:dashboards,id',
        'name' => 'required|string|max:255',
        'description' => 'nullable|string|max:1000',
        'category' => 'required|string|max:50',
        'is_public' => 'boolean'
    ]);

    $dashboard = Dashboard::findOrFail($validated['dashboard_id']);
    
    // Export current dashboard layout
    $layout = $dashboard->widgets->map(function ($widget) {
        return [
            'type' => $widget->widget->type,
            'x' => $widget->grid_x,
            'y' => $widget->grid_y,
            'w' => $widget->grid_w,
            'h' => $widget->grid_h,
            'config' => $widget->config
        ];
    })->toArray();

    $template = DashboardTemplate::create([
        'name' => $validated['name'],
        'description' => $validated['description'],
        'category' => $validated['category'],
        'layout' => $layout,
        'config' => $dashboard->config,
        'is_public' => $validated['is_public'] ?? false,
        'created_by' => auth()->id()
    ]);

    return response()->json([
        'template' => $template,
        'message' => 'Template saved successfully!'
    ]);
}

public function applyTemplate(Request $request): JsonResponse
{
    $validated = $request->validate([
        'template_id' => 'required|exists:dashboard_templates,id',
        'dashboard_id' => 'required|exists:dashboards,id'
    ]);

    $template = DashboardTemplate::findOrFail($validated['template_id']);
    $dashboard = Dashboard::findOrFail($validated['dashboard_id']);

    // Clear existing widgets
    $dashboard->widgets()->delete();

    // Apply template layout
    foreach ($template->layout as $index => $widgetData) {
        $widget = Widget::where('type', $widgetData['type'])->first();
        
        if ($widget) {
            DashboardWidget::create([
                'dashboard_id' => $dashboard->id,
                'widget_id' => $widget->id,
                'instance_id' => Str::uuid(),
                'grid_x' => $widgetData['x'],
                'grid_y' => $widgetData['y'],
                'grid_w' => $widgetData['w'],
                'grid_h' => $widgetData['h'],
                'config' => $widgetData['config'] ?? [],
                'order' => $index
            ]);
        }
    }

    // Update dashboard config
    $dashboard->update(['config' => $template->config]);
    
    // Increment template usage
    $template->incrementUsage();

    return response()->json(['message' => 'Template applied successfully!']);
}

public function getWidgetConfig(Widget $widget): JsonResponse
{
    $configSchema = [
        'chart' => [
            'title' => ['type' => 'text', 'label' => 'Chart Title', 'default' => 'Chart Widget'],
            'chartType' => [
                'type' => 'select',
                'label' => 'Chart Type',
                'options' => ['line' => 'Line', 'bar' => 'Bar', 'pie' => 'Pie', 'doughnut' => 'Doughnut'],
                'default' => 'line'
            ],
            'showLegend' => ['type' => 'boolean', 'label' => 'Show Legend', 'default' => true],
            'colors' => ['type' => 'color-array', 'label' => 'Colors', 'default' => ['#3B82F6', '#10B981']],
            'height' => ['type' => 'number', 'label' => 'Height (px)', 'default' => 300, 'min' => 200, 'max' => 600]
        ],
        'kpi' => [
            'title' => ['type' => 'text', 'label' => 'KPI Title', 'default' => 'KPI Metric'],
            'subtitle' => ['type' => 'text', 'label' => 'Subtitle', 'default' => ''],
            'format' => [
                'type' => 'select',
                'label' => 'Number Format',
                'options' => ['number' => 'Number', 'currency' => 'Currency', 'percentage' => 'Percentage'],
                'default' => 'number'
            ],
            'showTrend' => ['type' => 'boolean', 'label' => 'Show Trend', 'default' => true],
            'showSparkline' => ['type' => 'boolean', 'label' => 'Show Sparkline', 'default' => false],
            'variant' => [
                'type' => 'select',
                'label' => 'Style Variant',
                'options' => ['default' => 'Default', 'success' => 'Success', 'warning' => 'Warning', 'danger' => 'Danger'],
                'default' => 'default'
            ]
        ],
        'table' => [
            'title' => ['type' => 'text', 'label' => 'Table Title', 'default' => 'Data Table'],
            'pageSize' => ['type' => 'number', 'label' => 'Page Size', 'default' => 10, 'min' => 5, 'max' => 100],
            'searchable' => ['type' => 'boolean', 'label' => 'Enable Search', 'default' => true],
            'exportable' => ['type' => 'boolean', 'label' => 'Enable Export', 'default' => true],
            'sortable' => ['type' => 'boolean', 'label' => 'Enable Sorting', 'default' => true]
        ]
    ];

    $schema = $configSchema[$widget->type] ?? [];
    $currentConfig = $widget->default_config ?? [];

    return response()->json([
        'schema' => $schema,
        'current_config' => $currentConfig,
        'widget' => [
            'id' => $widget->id,
            'name' => $widget->name,
            'type' => $widget->type,
            'description' => $widget->description
        ]
    ]);
}

public function updateWidgetConfig(Request $request): JsonResponse
{
    $validated = $request->validate([
        'instance_id' => 'required|string',
        'config' => 'required|array'
    ]);

    $dashboardWidget = DashboardWidget::where('instance_id', $validated['instance_id'])->first();
    
    if (!$dashboardWidget) {
        return response()->json(['error' => 'Widget instance not found'], 404);
    }

    $dashboardWidget->update(['config' => $validated['config']]);

    return response()->json(['message' => 'Widget configuration updated successfully!']);
}
}