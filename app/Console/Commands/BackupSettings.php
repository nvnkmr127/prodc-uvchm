<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;

class BackupSettings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'settings:backup 
                            {--force : Force backup even if not scheduled}
                            {--clean : Clean old backups after creating new one}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a backup of all application settings';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting settings backup...');

        // Check if auto backup is enabled
        if (! $this->option('force') && ! setting('auto_backup', false, 'bool')) {
            $this->warn('Auto backup is disabled. Use --force to backup anyway.');

            return Command::FAILURE;
        }

        try {
            // Create backup
            $backupPath = backup_settings();

            if ($backupPath) {
                $this->info('Backup created successfully: '.basename($backupPath));

                // Clean old backups if requested
                if ($this->option('clean')) {
                    $this->cleanOldBackups();
                }

                return Command::SUCCESS;
            } else {
                $this->error('Failed to create backup');

                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error('Backup failed: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Clean old backup files based on retention settings
     */
    protected function cleanOldBackups()
    {
        $retentionDays = setting('backup_retention_days', 30);
        $backupPath = storage_path('app/backups');

        if (! is_dir($backupPath)) {
            return;
        }

        $cutoffDate = Carbon::now()->subDays($retentionDays);
        $files = glob($backupPath.'/settings-backup-*.json');
        $deletedCount = 0;

        foreach ($files as $file) {
            $fileDate = Carbon::createFromTimestamp(filemtime($file));

            if ($fileDate->lt($cutoffDate)) {
                if (unlink($file)) {
                    $deletedCount++;
                    $this->line('Deleted old backup: '.basename($file));
                }
            }
        }

        if ($deletedCount > 0) {
            $this->info("Cleaned {$deletedCount} old backup files");
        } else {
            $this->info('No old backup files to clean');
        }
    }
}
