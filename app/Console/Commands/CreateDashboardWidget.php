<?php

namespace App\Console\Commands;

use App\Models\Widget;
use App\Models\WidgetCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CreateDashboardWidget extends Command
{
    protected $signature = 'dashboard:make-widget 
                            {name : The name of the widget}
                            {--type=general : Widget type (chart, kpi, list, action, status)}
                            {--category= : Widget category slug}
                            {--permissions= : Comma-separated required permissions}
                            {--roles= : Comma-separated allowed roles}';

    protected $description = 'Create a new dashboard widget with Blade template and database entry';

    public function handle()
    {
        $name = $this->argument('name');
        $type = $this->option('type');
        $categorySlug = $this->option('category');
        $permissions = $this->option('permissions');
        $roles = $this->option('roles');

        $slug = Str::slug($name);
        $fileName = str_replace('-', '_', $slug);

        // Create Blade template
        $this->createBladeTemplate($fileName, $name, $type);

        // Create database entry
        $this->createDatabaseEntry($name, $slug, $fileName, $type, $categorySlug, $permissions, $roles);

        $this->info("✅ Widget '{$name}' created successfully!");
        $this->line("📄 Blade template: resources/views/dashboard/widgets/{$fileName}.blade.php");
        $this->line('💾 Database entry created');
        $this->line("🔄 Run 'php artisan dashboard:sync-widgets' to refresh widget list");

        return Command::SUCCESS;
    }

    private function createBladeTemplate($fileName, $name, $type)
    {
        $widgetPath = resource_path('views/dashboard/widgets');

        if (! File::isDirectory($widgetPath)) {
            File::makeDirectory($widgetPath, 0755, true, true);
        }

        $filePath = "{$widgetPath}/{$fileName}.blade.php";

        if (File::exists($filePath)) {
            if (! $this->confirm('Widget template already exists. Overwrite?')) {
                return;
            }
        }

        $template = $this->getWidgetTemplate($name, $type);
        File::put($filePath, $template);

        $this->info("Created Blade template: {$filePath}");
    }

    private function createDatabaseEntry($name, $slug, $fileName, $type, $categorySlug, $permissions, $roles)
    {
        $category = null;
        if ($categorySlug) {
            $category = WidgetCategory::where('slug', $categorySlug)->first();
            if (! $category) {
                $this->warn("Category '{$categorySlug}' not found. Widget will be created without category.");
            }
        }

        $widget = Widget::updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'type' => $type,
                'component' => "dashboard.widgets.{$fileName}",
                'category_id' => $category?->id,
                'description' => "Custom widget: {$name}",
                'required_permissions' => $permissions ? explode(',', $permissions) : null,
                'allowed_roles' => $roles ? explode(',', $roles) : null,
                'default_width' => $this->getDefaultWidth($type),
                'default_height' => $this->getDefaultHeight($type),
                'is_active' => true,
            ]
        );

        $this->info("Created database entry for widget: {$widget->name}");
    }

    private function getWidgetTemplate($name, $type)
    {
        $templates = [
            'chart' => $this->getChartTemplate($name),
            'kpi' => $this->getKpiTemplate($name),
            'list' => $this->getListTemplate($name),
            'action' => $this->getActionTemplate($name),
            'status' => $this->getStatusTemplate($name),
            'general' => $this->getGeneralTemplate($name),
        ];

        return $templates[$type] ?? $templates['general'];
    }

    private function getChartTemplate($name)
    {
        return <<<BLADE
{{-- Dashboard Widget: {$name} --}}
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">{$name}</h6>
        <div class="dropdown no-arrow">
            <a class="dropdown-toggle" href="#" role="button" data-toggle="dropdown">
                <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-right shadow">
                <a class="dropdown-item" href="#" onclick="refreshWidget(this)">
                    <i class="fas fa-sync fa-sm fa-fw mr-2 text-gray-400"></i>
                    Refresh
                </a>
                <a class="dropdown-item" href="#" onclick="exportWidget(this)">
                    <i class="fas fa-download fa-sm fa-fw mr-2 text-gray-400"></i>
                    Export
                </a>
            </div>
        </div>
    </div>
    <div class="card-body">
        <canvas id="chart-{{ \$widget->instance_id ?? 'default' }}" width="400" height="200"></canvas>
    </div>
</div>

@push('scripts')
<script>
// Chart configuration for {$name}
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('chart-{{ \$widget->instance_id ?? "default" }}').getContext('2d');
    
    new Chart(ctx, {
        type: 'line', // Change as needed: 'bar', 'pie', 'doughnut', etc.
        data: {
            labels: @json(\$chartData['labels'] ?? []),
            datasets: [{
                label: '{$name}',
                data: @json(\$chartData['data'] ?? []),
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});
</script>
@endpush
BLADE;
    }

    private function getKpiTemplate($name)
    {
        return <<<BLADE
{{-- Dashboard Widget: {$name} --}}
<div class="card border-left-primary shadow h-100 py-2">
    <div class="card-body">
        <div class="row no-gutters align-items-center">
            <div class="col mr-2">
                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                    {$name}
                </div>
                <div class="h5 mb-0 font-weight-bold text-gray-800">
                    {{ \$kpiValue ?? '0' }}
                </div>
                @if(isset(\$kpiChange))
                <div class="small {{ \$kpiChange >= 0 ? 'text-success' : 'text-danger' }}">
                    <i class="fas fa-{{ \$kpiChange >= 0 ? 'arrow-up' : 'arrow-down' }}"></i>
                    {{ abs(\$kpiChange) }}% from last period
                </div>
                @endif
            </div>
            <div class="col-auto">
                <i class="fas fa-chart-line fa-2x text-gray-300"></i>
            </div>
        </div>
    </div>
</div>
BLADE;
    }

    private function getListTemplate($name)
    {
        return <<<BLADE
{{-- Dashboard Widget: {$name} --}}
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">{$name}</h6>
        <a href="#" class="btn btn-sm btn-primary">View All</a>
    </div>
    <div class="card-body" style="max-height: 400px; overflow-y: auto;">
        @forelse(\$listItems ?? [] as \$item)
        <div class="d-flex align-items-center py-2 border-bottom">
            <div class="mr-3">
                <div class="icon-circle bg-primary">
                    <i class="fas fa-{{ \$item['icon'] ?? 'circle' }} text-white"></i>
                </div>
            </div>
            <div class="flex-grow-1">
                <div class="font-weight-bold">{{ \$item['title'] ?? 'Item Title' }}</div>
                <div class="small text-gray-500">{{ \$item['description'] ?? 'Item description' }}</div>
                <div class="small text-muted">{{ \$item['timestamp'] ?? now()->format('M j, Y') }}</div>
            </div>
            @if(isset(\$item['action_url']))
            <div>
                <a href="{{ \$item['action_url'] }}" class="btn btn-sm btn-outline-primary">
                    View
                </a>
            </div>
            @endif
        </div>
        @empty
        <div class="text-center py-4">
            <i class="fas fa-inbox text-gray-300 fa-3x mb-3"></i>
            <p class="text-gray-500">No items found</p>
        </div>
        @endforelse
    </div>
</div>
BLADE;
    }

    private function getActionTemplate($name)
    {
        return <<<BLADE
{{-- Dashboard Widget: {$name} --}}
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">{$name}</h6>
    </div>
    <div class="card-body">
        <div class="d-grid gap-2">
            @foreach(\$quickActions ?? [] as \$action)
            <a href="{{ \$action['url'] ?? '#' }}" 
               class="btn btn-{{ \$action['style'] ?? 'primary' }} btn-block">
                <i class="fas fa-{{ \$action['icon'] ?? 'plus' }} mr-2"></i>
                {{ \$action['label'] ?? 'Action' }}
            </a>
            @endforeach
            
            {{-- Default actions if none provided --}}
            @if(empty(\$quickActions))
            <a href="#" class="btn btn-primary btn-block">
                <i class="fas fa-plus mr-2"></i>
                Primary Action
            </a>
            <a href="#" class="btn btn-outline-secondary btn-block">
                <i class="fas fa-cog mr-2"></i>
                Secondary Action
            </a>
            @endif
        </div>
    </div>
</div>
BLADE;
    }

    private function getStatusTemplate($name)
    {
        return <<<BLADE
{{-- Dashboard Widget: {$name} --}}
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">{$name}</h6>
    </div>
    <div class="card-body">
        @foreach(\$statusItems ?? [] as \$item)
        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="text-sm font-weight-bold">{{ \$item['label'] ?? 'Status Item' }}</span>
                <span class="badge badge-{{ \$item['status'] == 'success' ? 'success' : (\$item['status'] == 'warning' ? 'warning' : 'danger') }}">
                    {{ \$item['value'] ?? '0%' }}
                </span>
            </div>
            <div class="progress" style="height: 8px;">
                <div class="progress-bar bg-{{ \$item['status'] == 'success' ? 'success' : (\$item['status'] == 'warning' ? 'warning' : 'danger') }}" 
                     role="progressbar" 
                     style="width: {{ \$item['percentage'] ?? 0 }}%" 
                     aria-valuenow="{{ \$item['percentage'] ?? 0 }}" 
                     aria-valuemin="0" 
                     aria-valuemax="100">
                </div>
            </div>
        </div>
        @endforeach
        
        {{-- Default status if none provided --}}
        @if(empty(\$statusItems))
        <div class="text-center py-3">
            <div class="h4 mb-0 font-weight-bold text-success">✓</div>
            <p class="text-gray-500 small">System Operational</p>
        </div>
        @endif
    </div>
</div>
BLADE;
    }

    private function getGeneralTemplate($name)
    {
        return <<<BLADE
{{-- Dashboard Widget: {$name} --}}
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">{$name}</h6>
    </div>
    <div class="card-body">
        <p class="mb-0">This is a custom widget: {$name}</p>
        <p class="text-muted small">
            Edit this template at: resources/views/dashboard/widgets/{{ str_replace('-', '_', Str::slug('{$name}')) }}.blade.php
        </p>
        
        {{-- Add your custom widget content here --}}
        @if(isset(\$widgetData))
        <div class="mt-3">
            <pre><code>{{ json_encode(\$widgetData, JSON_PRETTY_PRINT) }}</code></pre>
        </div>
        @endif
    </div>
</div>
BLADE;
    }

    private function getDefaultWidth($type)
    {
        $widths = [
            'chart' => 8,
            'kpi' => 3,
            'list' => 6,
            'action' => 4,
            'status' => 6,
            'general' => 6,
        ];

        return $widths[$type] ?? 6;
    }

    private function getDefaultHeight($type)
    {
        $heights = [
            'chart' => 400,
            'kpi' => 150,
            'list' => 350,
            'action' => 300,
            'status' => 250,
            'general' => 300,
        ];

        return $heights[$type] ?? 300;
    }
}
