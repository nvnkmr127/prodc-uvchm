<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CollegeBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'college:backup 
                            {type : Type of backup (database, files, full)} 
                            {--notify : Send notification after backup}
                            {--clean : Clean old backups after creating new one}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Handle college management system backups with specific scheduling';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->argument('type');
        $notify = $this->option('notify');
        $clean = $this->option('clean');

        $this->info("Starting {$type} backup...");

        try {
            switch ($type) {
                case 'database':
                    $this->handleDatabaseBackup();
                    break;
                case 'files':
                    $this->handleFilesBackup();
                    break;
                case 'full':
                    $this->handleFullBackup();
                    break;
                default:
                    $this->error('Invalid backup type. Use: database, files, or full');

                    return 1;
            }

            if ($clean) {
                $this->cleanOldBackups();
            }

            if ($notify) {
                $this->sendNotification($type);
            }

            $this->info("{$type} backup completed successfully!");

            return 0;

        } catch (\Exception $e) {
            $this->error('Backup failed: '.$e->getMessage());
            \Log::error('College backup failed', [
                'type' => $type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }

    /**
     * Handle database backup
     */
    private function handleDatabaseBackup()
    {
        $this->info('Creating database backup...');

        // Check database connection
        try {
            DB::connection()->getPdo();
            $this->info('Database connection verified.');
        } catch (\Exception $e) {
            throw new \Exception('Database connection failed: '.$e->getMessage());
        }

        // Run spatie backup for database only
        $exitCode = Artisan::call('backup:run', [
            '--only-db' => true,
            '--disable-notifications' => true,
        ]);

        if ($exitCode !== 0) {
            throw new \Exception('Database backup command failed');
        }

        $this->logBackupInfo('database');
    }

    /**
     * Handle files backup
     */
    private function handleFilesBackup()
    {
        $this->info('Creating files backup...');

        // Check available disk space
        $this->checkDiskSpace();

        // Run spatie backup for files only
        $exitCode = Artisan::call('backup:run', [
            '--only-files' => true,
            '--disable-notifications' => true,
        ]);

        if ($exitCode !== 0) {
            throw new \Exception('Files backup command failed');
        }

        $this->logBackupInfo('files');
    }

    /**
     * Handle full backup
     */
    private function handleFullBackup()
    {
        $this->info('Creating full backup (database + files)...');

        // Check database connection and disk space
        try {
            DB::connection()->getPdo();
            $this->checkDiskSpace();
        } catch (\Exception $e) {
            throw new \Exception('Pre-backup checks failed: '.$e->getMessage());
        }

        // Run full spatie backup
        $exitCode = Artisan::call('backup:run', [
            '--disable-notifications' => true,
        ]);

        if ($exitCode !== 0) {
            throw new \Exception('Full backup command failed');
        }

        $this->logBackupInfo('full');
    }

    /**
     * Check available disk space
     */
    private function checkDiskSpace()
    {
        $path = storage_path('app');
        $freeBytes = disk_free_space($path);
        $totalBytes = disk_total_space($path);
        $usedPercentage = (($totalBytes - $freeBytes) / $totalBytes) * 100;

        if ($usedPercentage > 90) {
            $this->warn('Warning: Disk usage is over 90%. Consider cleaning old backups.');
        }

        if ($usedPercentage > 95) {
            throw new \Exception('Disk space critically low. Cannot proceed with backup.');
        }

        $this->info('Disk usage: '.round($usedPercentage, 2).'%');
    }

    /**
     * Clean old backups based on retention policy
     */
    private function cleanOldBackups()
    {
        $this->info('Cleaning old backups...');

        try {
            $retentionDays = setting('backup_retention_days', 30);
            $cutoffDate = Carbon::now()->subDays($retentionDays);
            $deletedCount = 0;

            // Clean spatie backups
            $disks = config('backup.backup.destination.disks', ['local']);
            $disk = Storage::disk($disks[0]);
            $appName = config('backup.backup.name', config('app.name', 'Laravel'));

            if ($disk->exists($appName)) {
                $files = $disk->files($appName);

                foreach ($files as $file) {
                    if (pathinfo($file, PATHINFO_EXTENSION) === 'zip') {
                        $fileDate = Carbon::createFromTimestamp($disk->lastModified($file));
                        if ($fileDate->lt($cutoffDate)) {
                            $disk->delete($file);
                            $deletedCount++;
                            $this->info('Deleted old backup: '.basename($file));
                        }
                    }
                }
            }

            $this->info("Cleaned up {$deletedCount} old backup files.");

        } catch (\Exception $e) {
            $this->warn('Backup cleanup failed: '.$e->getMessage());
        }
    }

    /**
     * Log backup information
     */
    private function logBackupInfo($type)
    {
        $stats = [
            'type' => $type,
            'timestamp' => Carbon::now()->toISOString(),
            'database_tables' => $this->getDatabaseTableCount(),
            'database_size' => $this->getDatabaseSize(),
            'files_count' => $this->getFilesCount(),
        ];

        \Log::info('College backup completed', $stats);

        // Update last backup info in settings
        setting(['last_backup_'.$type => Carbon::now()->toDateTimeString()]);
    }

    /**
     * Get database table count
     */
    private function getDatabaseTableCount()
    {
        try {
            $database = config('database.connections.'.config('database.default').'.database');
            $tables = DB::select('SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = ?', [$database]);

            return $tables[0]->count ?? 0;
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    /**
     * Get database size
     */
    private function getDatabaseSize()
    {
        try {
            $database = config('database.connections.'.config('database.default').'.database');
            $result = DB::select('
                SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS size_mb 
                FROM information_schema.tables 
                WHERE table_schema = ?
            ', [$database]);

            return ($result[0]->size_mb ?? 0).' MB';
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    /**
     * Get application files count
     */
    private function getFilesCount()
    {
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(base_path(), \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            return iterator_count($iterator);
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    /**
     * Send backup notification
     */
    private function sendNotification($type)
    {
        try {
            $notificationEmail = setting('notification_email');

            if ($notificationEmail && setting('backup_notifications', false)) {
                // Here you can implement email notification
                // For example, using Laravel's Mail facade
                $this->info("Notification would be sent to: {$notificationEmail}");

                // Uncomment and customize as needed:
                /*
                \Mail::to($notificationEmail)->send(new \App\Mail\BackupNotification([
                    'type' => $type,
                    'timestamp' => Carbon::now(),
                    'status' => 'success'
                ]));
                */
            }
        } catch (\Exception $e) {
            $this->warn('Failed to send notification: '.$e->getMessage());
        }
    }
}
