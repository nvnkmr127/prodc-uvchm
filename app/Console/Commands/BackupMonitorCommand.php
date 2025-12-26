<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class BackupMonitorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:monitor 
                            {--notify : Send notifications for issues}
                            {--detailed : Show detailed backup information}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor backup health and status for the college management system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $notify = $this->option('notify');
        $detailed = $this->option('detailed');

        $this->info('🔍 Monitoring backup system health...');
        $this->line('');

        $issues = [];
        $stats = [];

        // Check database backup status
        $dbStatus = $this->checkDatabaseBackups();
        $stats['database'] = $dbStatus;
        if (!$dbStatus['healthy']) {
            $issues[] = 'Database backup: ' . $dbStatus['issue'];
        }

        // Check files backup status
        $filesStatus = $this->checkFilesBackups();
        $stats['files'] = $filesStatus;
        if (!$filesStatus['healthy']) {
            $issues[] = 'Files backup: ' . $filesStatus['issue'];
        }

        // Check backup storage
        $storageStatus = $this->checkBackupStorage();
        $stats['storage'] = $storageStatus;
        if (!$storageStatus['healthy']) {
            $issues[] = 'Storage: ' . $storageStatus['issue'];
        }

        // Check backup configuration
        $configStatus = $this->checkBackupConfiguration();
        $stats['configuration'] = $configStatus;
        if (!$configStatus['healthy']) {
            $issues[] = 'Configuration: ' . $configStatus['issue'];
        }

        // Display results
        $this->displayResults($stats, $issues, $detailed);

        // Send notifications if requested and there are issues
        if ($notify && !empty($issues)) {
            $this->sendNotifications($issues);
        }

        // Log the monitoring results
        $this->logResults($stats, $issues);

        return empty($issues) ? 0 : 1;
    }

    /**
     * Check database backup status
     */
    private function checkDatabaseBackups()
    {
        $status = ['healthy' => true, 'issue' => null];
        
        try {
            // Check if database backup ran in the last 25 hours (allowing some flexibility)
            $lastExpected = Carbon::now()->subHours(25);
            $backupFound = false;
            
            // Check spatie backups
            $disks = config('backup.backup.destination.disks', ['local']);
            $disk = Storage::disk($disks[0]);
            $appName = config('backup.backup.name', config('app.name', 'Laravel'));
            
            if ($disk->exists($appName)) {
                $files = $disk->files($appName);
                
                foreach ($files as $file) {
                    if (pathinfo($file, PATHINFO_EXTENSION) === 'zip' && 
                        (str_contains($file, 'db') || str_contains($file, 'database'))) {
                        
                        $fileDate = Carbon::createFromTimestamp($disk->lastModified($file));
                        if ($fileDate->gt($lastExpected)) {
                            $backupFound = true;
                            $status['last_backup'] = $fileDate->format('Y-m-d H:i:s');
                            break;
                        }
                    }
                }
            }
            
            if (!$backupFound) {
                $status['healthy'] = false;
                $status['issue'] = 'No recent database backup found (expected within last 25 hours)';
            }
            
        } catch (\Exception $e) {
            $status['healthy'] = false;
            $status['issue'] = 'Error checking database backups: ' . $e->getMessage();
        }
        
        return $status;
    }

    /**
     * Check files backup status
     */
    private function checkFilesBackups()
    {
        $status = ['healthy' => true, 'issue' => null];
        
        try {
            // Files backup should run every 3 days, so check last 4 days
            $lastExpected = Carbon::now()->subDays(4);
            $backupFound = false;
            
            $disks = config('backup.backup.destination.disks', ['local']);
            $disk = Storage::disk($disks[0]);
            $appName = config('backup.backup.name', config('app.name', 'Laravel'));
            
            if ($disk->exists($appName)) {
                $files = $disk->files($appName);
                
                foreach ($files as $file) {
                    if (pathinfo($file, PATHINFO_EXTENSION) === 'zip' && 
                        (str_contains($file, 'files') || str_contains($file, 'only-files'))) {
                        
                        $fileDate = Carbon::createFromTimestamp($disk->lastModified($file));
                        if ($fileDate->gt($lastExpected)) {
                            $backupFound = true;
                            $status['last_backup'] = $fileDate->format('Y-m-d H:i:s');
                            break;
                        }
                    }
                }
            }
            
            if (!$backupFound) {
                $status['healthy'] = false;
                $status['issue'] = 'No recent files backup found (expected within last 4 days)';
            }
            
        } catch (\Exception $e) {
            $status['healthy'] = false;
            $status['issue'] = 'Error checking files backups: ' . $e->getMessage();
        }
        
        return $status;
    }

    /**
     * Check backup storage health
     */
    private function checkBackupStorage()
    {
        $status = ['healthy' => true, 'issue' => null];
        
        try {
            $path = storage_path('app');
            $freeBytes = disk_free_space($path);
            $totalBytes = disk_total_space($path);
            $usedPercentage = (($totalBytes - $freeBytes) / $totalBytes) * 100;
            
            $status['disk_usage'] = round($usedPercentage, 2) . '%';
            $status['free_space'] = $this->formatBytes($freeBytes);
            
            if ($usedPercentage > 95) {
                $status['healthy'] = false;
                $status['issue'] = 'Critical: Disk usage over 95% (' . round($usedPercentage, 2) . '%)';
            } elseif ($usedPercentage > 90) {
                $status['healthy'] = false;
                $status['issue'] = 'Warning: Disk usage over 90% (' . round($usedPercentage, 2) . '%)';
            }
            
            // Check backup directory permissions
            $backupPath = storage_path('app/backups');
            if (!is_dir($backupPath)) {
                mkdir($backupPath, 0755, true);
            }
            
            if (!is_writable($backupPath)) {
                $status['healthy'] = false;
                $status['issue'] = 'Backup directory is not writable';
            }
            
        } catch (\Exception $e) {
            $status['healthy'] = false;
            $status['issue'] = 'Error checking storage: ' . $e->getMessage();
        }
        
        return $status;
    }

    /**
     * Check backup configuration
     */
    private function checkBackupConfiguration()
    {
        $status = ['healthy' => true, 'issue' => null, 'warnings' => []];
        
        try {
            // Check if spatie/laravel-backup config exists
            if (!config('backup.backup.destination.disks')) {
                $status['healthy'] = false;
                $status['issue'] = 'Backup configuration missing';
                return $status;
            }
            
            // Check backup settings
            $autoBackup = setting('auto_backup', false);
            $retentionDays = setting('backup_retention_days', 30);
            
            if (!$autoBackup) {
                $status['warnings'][] = 'Auto backup is disabled';
            }
            
            if ($retentionDays < 7) {
                $status['warnings'][] = 'Backup retention period is very short (' . $retentionDays . ' days)';
            }
            
            if ($retentionDays > 90) {
                $status['warnings'][] = 'Backup retention period is very long (' . $retentionDays . ' days)';
            }
            
            // Check if notification email is configured
            $notificationEmail = setting('notification_email');
            if (setting('backup_notifications', false) && empty($notificationEmail)) {
                $status['warnings'][] = 'Backup notifications enabled but no email configured';
            }
            
            $status['auto_backup'] = $autoBackup;
            $status['retention_days'] = $retentionDays;
            $status['notifications'] = setting('backup_notifications', false);
            
        } catch (\Exception $e) {
            $status['healthy'] = false;
            $status['issue'] = 'Error checking configuration: ' . $e->getMessage();
        }
        
        return $status;
    }

    /**
     * Display monitoring results
     */
    private function displayResults($stats, $issues, $detailed)
    {
        // Overall status
        if (empty($issues)) {
            $this->info('✅ All backup systems are healthy!');
        } else {
            $this->error('❌ Found ' . count($issues) . ' backup issue(s):');
            foreach ($issues as $issue) {
                $this->line('   • ' . $issue);
            }
        }
        
        $this->line('');
        
        // Summary table
        $headers = ['Component', 'Status', 'Details'];
        $rows = [];
        
        foreach ($stats as $component => $data) {
            $status = $data['healthy'] ? '✅ Healthy' : '❌ Issue';
            $details = $data['healthy'] ? 'OK' : $data['issue'];
            
            if ($component === 'storage' && isset($data['disk_usage'])) {
                $details = $data['healthy'] ? 'Usage: ' . $data['disk_usage'] : $details;
            }
            
            $rows[] = [ucfirst($component), $status, $details];
        }
        
        $this->table($headers, $rows);
        
        // Detailed information
        if ($detailed) {
            $this->line('');
            $this->info('📊 Detailed Information:');
            
            if (isset($stats['database']['last_backup'])) {
                $this->line('Last database backup: ' . $stats['database']['last_backup']);
            }
            
            if (isset($stats['files']['last_backup'])) {
                $this->line('Last files backup: ' . $stats['files']['last_backup']);
            }
            
            if (isset($stats['storage']['free_space'])) {
                $this->line('Available storage: ' . $stats['storage']['free_space']);
            }
            
            if (isset($stats['configuration']['warnings']) && !empty($stats['configuration']['warnings'])) {
                $this->line('');
                $this->warn('Configuration warnings:');
                foreach ($stats['configuration']['warnings'] as $warning) {
                    $this->line('   • ' . $warning);
                }
            }
        }
    }

    /**
     * Send notifications for issues
     */
    private function sendNotifications($issues)
    {
        try {
            $notificationEmail = setting('notification_email');
            
            if ($notificationEmail && setting('backup_notifications', false)) {
                $this->info('📧 Notification would be sent to: ' . $notificationEmail);
                
                // Here you can implement actual email sending
                // Example implementation:
                /*
                \Mail::to($notificationEmail)->send(new \App\Mail\BackupHealthAlert([
                    'issues' => $issues,
                    'timestamp' => Carbon::now(),
                    'server' => config('app.url')
                ]));
                */
            } else {
                $this->warn('No notification email configured or notifications disabled');
            }
        } catch (\Exception $e) {
            $this->error('Failed to send notification: ' . $e->getMessage());
        }
    }

    /**
     * Log monitoring results
     */
    private function logResults($stats, $issues)
    {
        $logData = [
            'timestamp' => Carbon::now()->toISOString(),
            'overall_health' => empty($issues),
            'issues_count' => count($issues),
            'issues' => $issues,
            'stats' => $stats
        ];
        
        if (empty($issues)) {
            \Log::info('Backup system health check passed', $logData);
        } else {
            \Log::warning('Backup system health check found issues', $logData);
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}