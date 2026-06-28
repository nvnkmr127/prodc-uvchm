<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class LogsPruneCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:prune {--days=15 : The number of days to retain logs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune old log files from the storage/logs directory';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        
        if ($days <= 0) {
            $this->error('The --days option must be greater than 0.');
            return;
        }

        $logPath = storage_path('logs');
        
        if (!File::isDirectory($logPath)) {
            $this->error("Log directory does not exist at {$logPath}");
            return;
        }

        $files = File::files($logPath);
        $deletedCount = 0;
        $now = time();
        $cutoffTime = $now - ($days * 24 * 60 * 60);

        foreach ($files as $file) {
            // Only process .log files (avoids deleting .gitignore, etc.)
            if ($file->getExtension() === 'log') {
                $lastModified = $file->getMTime();
                
                if ($lastModified < $cutoffTime) {
                    $fileName = $file->getFilename();
                    if (File::delete($file->getPathname())) {
                        $this->line("Deleted: {$fileName}");
                        $deletedCount++;
                    } else {
                        $this->warn("Failed to delete: {$fileName}");
                    }
                }
            }
        }

        if ($deletedCount > 0) {
            $this->info("Successfully pruned {$deletedCount} log file(s) older than {$days} days.");
        } else {
            $this->info("No log files older than {$days} days found. Nothing to prune.");
        }
    }
}
