<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Models\Widget;
use Exception;

class SyncDashboardWidgets extends Command
{
    protected $signature = 'dashboard:sync-widgets {--clean : Remove widgets that no longer have blade files}';
    protected $description = 'Sync dashboard widgets from Blade template files';

    public function handle()
    {
        $this->info('🔄 Syncing dashboard widgets from Blade files...');

        $widgetPath = resource_path('views/dashboard/widgets');
        
        if (!File::isDirectory($widgetPath)) {
            $this->warn("Directory not found. Creating: {$widgetPath}");
            File::makeDirectory($widgetPath, 0755, true, true);
        }

        $widgetFiles = File::glob("{$widgetPath}/*.blade.php");
        $foundViewPaths = [];
        $syncedCount = 0;

        $this->info("Found " . count($widgetFiles) . " widget files to process.");

        foreach ($widgetFiles as $file) {
            $fileName = Str::before(basename($file), '.blade.php');
            $viewPath = 'dashboard.widgets.' . $fileName;
            $widgetName = Str::title(str_replace(['-', '_'], ' ', $fileName));
            
            $foundViewPaths[] = $viewPath;

            try {
                $widget = Widget::updateOrCreate(
                    ['component' => $viewPath],
                    [
                        'name' => $widgetName,
                        'slug' => Str::slug($fileName),
                        'type' => $this->guessWidgetType($fileName),
                        'description' => "Auto-generated widget: {$widgetName}",
                        'is_active' => true
                    ]
                );
                
                $this->line("  [OK] Synced: {$widgetName} ({$viewPath})");
                $syncedCount++;
            } catch (Exception $e) {
                $this->error("  [ERROR] Failed to sync {$widgetName}: " . $e->getMessage());
            }
        }

        // Clean up obsolete widgets if requested
        if ($this->option('clean')) {
            $this->info("\n🧹 Checking for obsolete widgets to remove...");
            $obsoleteWidgets = Widget::whereNotIn('component', $foundViewPaths)->get();
            $removedCount = 0;

            if ($obsoleteWidgets->isEmpty()) {
                $this->info("No obsolete widgets found.");
            } else {
                foreach ($obsoleteWidgets as $widget) {
                    if ($this->confirm("Remove obsolete widget: {$widget->name} ({$widget->component})?")) {
                        $widget->delete();
                        $this->warn("  [REMOVED] {$widget->name}");
                        $removedCount++;
                    }
                }
            }
        }

        $this->info("\n✅ Sync Complete!");
        $this->info("Summary: {$syncedCount} widgets synced" . 
                   ($this->option('clean') ? ", {$removedCount} widgets removed" : ""));
        
        return Command::SUCCESS;
    }

    private function guessWidgetType($fileName)
    {
        $fileName = strtolower($fileName);
        
        if (Str::contains($fileName, ['chart', 'graph', 'analytics'])) {
            return 'chart';
        }
        
        if (Str::contains($fileName, ['total', 'count', 'kpi', 'metric'])) {
            return 'kpi';
        }
        
        if (Str::contains($fileName, ['list', 'table', 'recent'])) {
            return 'list';
        }
        
        if (Str::contains($fileName, ['action', 'quick', 'button'])) {
            return 'action';
        }
        
        if (Str::contains($fileName, ['status', 'progress', 'health'])) {
            return 'status';
        }
        
        return 'general';
    }
}