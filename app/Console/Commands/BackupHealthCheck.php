<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class BackupHealthCheck extends Command
{
    protected $signature = 'backup:health-check {--notify : Send email notification}';
    protected $description = 'Check backup system health and status';

    public function handle()
    {
        $this->info('Checking backup system health...');
        
        $issues = [];
        
        // Check if auto backup is enabled
        $autoBackup = setting('auto_backup', false, 'bool');
        if (!$autoBackup) {
            $issues[] = 'Auto backup is disabled';
        }
        
        // Check last backup age
        $lastBackup = $this->getLastBackupDate();
        if ($lastBackup) {
            $daysSinceBackup = now()->diffInDays($lastBackup);
            $retentionDays = setting('backup_retention_days', 30);
            
            if ($daysSinceBackup > 1) {
                $issues[] = "Last backup was {$daysSinceBackup} days ago";
            }
        } else {
            $issues[] = 'No backups found';
        }
        
        // Check disk space
        $diskUsage = $this->getDiskUsage();
        if ($diskUsage['percentage'] > 80) {
            $issues[] = "Backup disk usage high: {$diskUsage['percentage']}%";
        }
        
        // Report results
        if (empty($issues)) {
            $this->info('✅ Backup system is healthy');
            return Command::SUCCESS;
        } else {
            $this->warn('⚠️ Backup system issues found:');
            foreach ($issues as $issue) {
                $this->line("  - {$issue}");
            }
            
            if ($this->option('notify')) {
                $this->sendHealthAlert($issues);
            }
            
            return Command::FAILURE;
        }
    }
    
    private function getLastBackupDate()
    {
        // Check Spatie backups
        $disks = config('backup.backup.destination.disks', ['local']);
        $disk = Storage::disk($disks[0]);
        $appName = config('backup.backup.name', config('app.name', 'Laravel'));
        
        $files = $disk->files($appName);
        $lastModified = null;
        
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'zip') {
                $modified = $disk->lastModified($file);
                if (!$lastModified || $modified > $lastModified) {
                    $lastModified = $modified;
                }
            }
        }
        
        return $lastModified ? Carbon::createFromTimestamp($lastModified) : null;
    }
    
    private function getDiskUsage()
    {
        $path = storage_path('app');
        $total = disk_total_space($path);
        $free = disk_free_space($path);
        $used = $total - $free;
        
        return [
            'used' => $used,
            'total' => $total,
            'percentage' => round(($used / $total) * 100, 2)
        ];
    }
    
    private function sendHealthAlert($issues)
    {
        $email = setting('notification_email');
        if ($email) {
            $message = "Backup system health issues detected:\n\n" . implode("\n", $issues);
            
            \Mail::raw($message, function($mail) use ($email) {
                $mail->to($email)->subject('Backup Health Alert - ' . config('app.name'));
            });
            
            $this->info('Health alert sent to: ' . $email);
        }
    }
}