<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use App\Models\{Widget, WidgetCategory};

class DashboardSyncWidgetsCommand extends Command
{
    protected $signature = 'dashboard:sync-widgets {--clean : Remove orphaned widgets}';
    protected $description = 'Sync widget definitions from Blade template files';

    public function handle()
    {
        $this->info('🔄 Syncing widgets from template files...');

        $widgetPath = resource_path('views/dashboard/widgets');
        
        if (!is_dir($widgetPath)) {
            $this->error("Widget templates directory not found: {$widgetPath}");
            return 1;
        }

        $templateFiles = File::glob("{$widgetPath}/*.blade.php");
        $this->info("Found " . count($templateFiles) . " widget files to process.");
        
        $synced = 0;
        $failed = 0;

        foreach ($templateFiles as $templateFile) {
            $widgetSlug = basename($templateFile, '.blade.php');
            
            try {
                $widgetConfig = $this->parseWidgetConfig($templateFile, $widgetSlug);
                
                Widget::updateOrCreate(
                    ['slug' => $widgetSlug],
                    $widgetConfig
                );
                
                $synced++;
                $this->line("  <info>[OK]</info> Synced: " . $widgetConfig['name'] . " ({$widgetConfig['template_path']})");
                
            } catch (\Exception $e) {
                $failed++;
                $this->line("  <error>[ERROR]</error> Failed to sync " . ucwords(str_replace(['-', '_'], ' ', $widgetSlug)) . ": " . 
$e->getMessage());
            }
        }

        $this->newLine();
        $this->info("✅ Sync Complete!");
        $this->line("Summary: {$synced} widgets synced, {$failed} failed");
        
        return $failed > 0 ? 1 : 0;
    }

    private function parseWidgetConfig($templateFile, $slug)
    {
        $content = File::get($templateFile);
        $name = $this->extractWidgetName($content, $slug);
        $type = $this->extractWidgetType($content, $slug);
        
        return [
            'name' => $name,
            'slug' => $slug,
            'type' => $type,
            'category_id' => $this->getOrCreateCategory($this->extractCategory($content, $slug)),
            'description' => $this->extractDescription($content, $name),
            'icon' => $this->extractIcon($content, $type),
            'component' => $this->generateComponentName($slug),
            'template_path' => "dashboard.widgets.{$slug}",
            'view_path' => "dashboard.widgets.{$slug}", // Add this line!
            'is_active' => true,
            'is_configurable' => $this->extractConfigurable($content),
            'default_width' => $this->extractDefaultWidth($content, $type),
            'default_height' => $this->extractDefaultHeight($content, $type),
            'min_width' => 2,
            'min_height' => 2,
            'max_width' => 12,
            'max_height' => 8,
            'default_config' => $this->extractDefaultConfig($content),
            'permissions' => $this->extractPermissions($content, $slug),
            'cache_duration' => $this->extractCacheDuration($content),
            'refresh_interval' => $this->extractRefreshInterval($content)
        ];
    }

    private function extractWidgetName($content, $slug)
    {
        // Look for widget title in HTML
        if (preg_match('/<h\d[^>]*class="[^"]*widget-title[^"]*"[^>]*>([^<]+)<\/h\d>/', $content, $matches)) {
            return trim($matches[1]);
        }
        
        // Look for @widget-name comment
        if (preg_match('/@widget-name\s+(.+)$/m', $content, $matches)) {
            return trim($matches[1]);
        }

        return ucwords(str_replace(['-', '_'], ' ', $slug));
    }

    private function extractWidgetType($content, $slug)
    {
        // Look for @widget-type comment
        if (preg_match('/@widget-type\s+(\w+)$/m', $content, $matches)) {
            return $matches[1];
        }

        // Auto-detect based on slug or content
        if (strpos($slug, 'chart') !== false || strpos($content, 'Chart') !== false || strpos($content, 'canvas') !== false) {
            return 'chart';
        }
        if (strpos($slug, 'kpi') !== false || strpos($slug, 'total') !== false || strpos($content, 'metric') !== false) {
            return 'kpi';
        }
        if (strpos($slug, 'list') !== false || strpos($content, 'table') !== false) {
            return 'list';
        }
        if (strpos($slug, 'status') !== false || strpos($slug, 'health') !== false) {
            return 'status';
        }
        if (strpos($slug, 'action') !== false) {
            return 'action';
        }

        return 'general';
    }

    private function extractCategory($content, $slug)
    {
        // Look for @widget-category comment
        if (preg_match('/@widget-category\s+(\w+)$/m', $content, $matches)) {
            return $matches[1];
        }

        // Auto-categorize based on slug
        if (strpos($slug, 'student') !== false || strpos($slug, 'academic') !== false || strpos($slug, 'attendance') !== false) {
            return 'academic';
        }
        if (strpos($slug, 'revenue') !== false || strpos($slug, 'fee') !== false || strpos($slug, 'financial') !== false || 
strpos($slug, 'payment') !== false) {
            return 'financial';
        }
        if (strpos($slug, 'system') !== false || strpos($slug, 'health') !== false) {
            return 'system';
        }
        if (strpos($slug, 'notification') !== false || strpos($slug, 'enquir') !== false || strpos($slug, 'communication') !== false) {
            return 'communication';
        }

        return 'general';
    }

    private function extractDescription($content, $name)
    {
        // Look for @widget-description comment
        if (preg_match('/@widget-description\s+(.+)$/m', $content, $matches)) {
            return trim($matches[1]);
        }

        // Look for description in HTML
        if (preg_match('/<small[^>]*class="[^"]*text-muted[^"]*"[^>]*>([^<]+)<\/small>/', $content, $matches)) {
            return trim($matches[1]);
        }

        return "Auto-generated widget: {$name}";
    }

    private function extractIcon($content, $type)
    {
        // Look for @widget-icon comment
        if (preg_match('/@widget-icon\s+(.+)$/m', $content, $matches)) {
            return trim($matches[1]);
        }

        // Look for Font Awesome icon in HTML
        if (preg_match('/<i[^>]*class="[^"]*fa-([^"\s]+)/', $content, $matches)) {
            return 'fas fa-' . $matches[1];
        }

        // Default icons based on type
        $defaultIcons = [
            'chart' => 'fas fa-chart-line',
            'kpi' => 'fas fa-tachometer-alt',
            'list' => 'fas fa-list',
            'status' => 'fas fa-heartbeat',
            'action' => 'fas fa-bolt',
            'financial' => 'fas fa-dollar-sign',
            'academic' => 'fas fa-graduation-cap',
            'system' => 'fas fa-cogs'
        ];

        return $defaultIcons[$type] ?? 'fas fa-widget';
    }

    private function generateComponentName($slug)
    {
        return ucwords(str_replace(['-', '_'], ' ', $slug)) . 'Widget';
    }

    private function extractConfigurable($content)
    {
        return preg_match('/@widget-configurable\s+(true|1|yes)$/mi', $content) === 1;
    }

    private function extractDefaultWidth($content, $type)
    {
        if (preg_match('/@widget-width\s+(\d+)$/m', $content, $matches)) {
            return (int) $matches[1];
        }
        
        // Default width based on type
        $defaultWidths = [
            'chart' => 6,
            'kpi' => 3,
            'list' => 4,
            'action' => 6
        ];
        
        return $defaultWidths[$type] ?? 4;
    }

    private function extractDefaultHeight($content, $type)
    {
        if (preg_match('/@widget-height\s+(\d+)$/m', $content, $matches)) {
            return (int) $matches[1];
        }
        
        // Default height based on type
        $defaultHeights = [
            'chart' => 400,
            'kpi' => 200,
            'list' => 350,
            'action' => 250
        ];
        
        return $defaultHeights[$type] ?? 300;
    }

    private function extractDefaultConfig($content)
    {
        if (preg_match('/@widget-config\s+(.+)$/m', $content, $matches)) {
            return json_decode($matches[1], true) ?: [];
        }
        return [];
    }

    private function extractPermissions($content, $slug)
    {
        if (preg_match('/@widget-permissions\s+(.+)$/m', $content, $matches)) {
            return array_map('trim', explode(',', $matches[1]));
        }

        // Auto-detect permissions based on slug
        $permissions = [];
        if (strpos($slug, 'student') !== false) {
            $permissions[] = 'view student data';
        }
        if (strpos($slug, 'financial') !== false || strpos($slug, 'revenue') !== false || strpos($slug, 'fee') !== false) {
            $permissions[] = 'view financial data';
        }
        if (strpos($slug, 'system') !== false) {
            $permissions[] = 'view system data';
        }

        return $permissions;
    }

    private function extractCacheDuration($content)
    {
        if (preg_match('/@widget-cache\s+(\d+)$/m', $content, $matches)) {
            return (int) $matches[1];
        }
        return 300; // 5 minutes default
    }

    private function extractRefreshInterval($content)
    {
        if (preg_match('/@widget-refresh\s+(\d+)$/m', $content, $matches)) {
            return (int) $matches[1];
        }
        return 60; // 1 minute default
    }

    private function getOrCreateCategory($categoryName)
    {
        $category = WidgetCategory::firstOrCreate(
            ['slug' => $categoryName],
            [
                'name' => ucfirst($categoryName),
                'slug' => $categoryName,
                'icon' => $this->getCategoryIcon($categoryName),
                'color' => $this->getCategoryColor($categoryName),
                'is_active' => true,
                'order' => WidgetCategory::count() + 1
            ]
        );

        return $category->id;
    }

    private function getCategoryIcon($categoryName)
    {
        $icons = [
            'academic' => 'fas fa-graduation-cap',
            'financial' => 'fas fa-dollar-sign',
            'system' => 'fas fa-cog',
            'analytics' => 'fas fa-chart-line',
            'communication' => 'fas fa-comments',
            'general' => 'fas fa-th-large'
        ];

        return $icons[$categoryName] ?? 'fas fa-widget';
    }

    private function getCategoryColor($categoryName)
    {
        $colors = [
            'academic' => '#007bff',
            'financial' => '#28a745',
            'system' => '#dc3545',
            'analytics' => '#ffc107',
            'communication' => '#17a2b8',
            'general' => '#6c757d'
        ];

        return $colors[$categoryName] ?? '#6c757d';
    }
}
