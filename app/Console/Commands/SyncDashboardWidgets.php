<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Models\Widget;
use Exception;

class SyncDashboardWidgets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'widgets:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Discovers, syncs, and cleans up dashboard widgets from their Blade files.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting dashboard widget sync...');

        $widgetPath = resource_path('views/admin/dashboard/widgets');
        
        if (!File::isDirectory($widgetPath)) {
            $this->warn("Directory not found. Creating: {$widgetPath}");
            File::makeDirectory($widgetPath, 0755, true, true);
        }

        $widgetFiles = File::glob("{$widgetPath}/*.blade.php");
        $foundViewPaths = [];
        $syncedCount = 0;

        $this->info("Found " . count($widgetFiles) . " widget files to process.");

        foreach ($widgetFiles as $file) {
            // ** THE FIX IS HERE **
            // This robustly gets the filename before the ".blade.php" extension.
            $fileName = Str::before(basename($file), '.blade.php');
            
            $viewPath = 'admin.dashboard.widgets.' . $fileName;
            $widgetName = Str::title(str_replace('_', ' ', $fileName));
            
            $foundViewPaths[] = $viewPath;

            try {
                Widget::updateOrCreate(
                    ['view_path' => $viewPath],
                    [
                        'name' => $widgetName,
                        'description' => "Displays the {$widgetName}.",
                    ]
                );
                $this->line("  [OK] Synced: {$widgetName}");
                $syncedCount++;
            } catch (Exception $e) {
                $this->error("  [ERROR] Failed to sync {$widgetName}: " . $e->getMessage());
            }
        }

        // --- Automatic Cleanup ---
        $this->info("\nChecking for obsolete widgets to remove...");
        $obsoleteWidgets = Widget::whereNotIn('view_path', $foundViewPaths)->get();
        $removedCount = 0;

        if ($obsoleteWidgets->isEmpty()) {
            $this->info("No obsolete widgets found.");
        } else {
            foreach ($obsoleteWidgets as $widget) {
                $this->warn("  [REMOVING] Obsolete widget: {$widget->name} ({$widget->view_path})");
                $widget->delete();
                $removedCount++;
            }
        }

        $this->info("\n✅ Sync Complete!");
        $this->info("Summary: {$syncedCount} widgets synced, {$removedCount} obsolete widgets removed.");
        
        return Command::SUCCESS;
    }
}
