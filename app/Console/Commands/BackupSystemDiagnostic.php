<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BackupService;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BackupSystemDiagnostic extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:diagnose {--fix : Attempt to fix common issues}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnose and fix backup system issues';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 UVCHM Portal - Backup System Diagnostic');
        $this->info('==========================================');
        $this->newLine();

        $this->checkBackupSettings();
        $this->checkBackupDirectory();
        $this->checkExistingBackups();
        $this->checkGoogleDriveConfiguration();
        $this->checkCronConfiguration();
        $this->checkLogFiles();
        $this->checkDatabaseConnection();
        
        if ($this->option('fix')) {
            $this->newLine();
            $this->info('🔧 ATTEMPTING FIXES...');
            $this->attemptFixes();
        }

        $this->newLine();
        $this->info('✅ Diagnostic completed');
        $this->showRecommendations();
    }

    private function checkBackupSettings()
    {
        $this->info('1. BACKUP SETTINGS');
        $this->info('==================');

        $autoBackup = Setting::where('key', 'auto_backup')->value('value') ?? '0';
        $frequency = Setting::where('key', 'backup_frequency')->value('value') ?? 'daily';
        $maintenanceWindow = Setting::where('key', 'maintenance_window')->value('value') ?? '02:00';
        $gdriveEnabled = Setting::where('key', 'backup_gdrive_enabled')->value('value') ?? '0';
        $retention = Setting::where('key', 'backup_retention_days')->value('value') ?? '30';

        $this->line('Auto Backup: ' . ($autoBackup === '1' ? '<fg=green>ENABLED</fg=green>' : '<fg=red>DISABLED</fg=red>'));
        $this->line('Frequency: ' . $frequency);
        $this->line('Maintenance Window: ' . $maintenanceWindow);
        $this->line('Google Drive: ' . ($gdriveEnabled === '1' ? '<fg=green>ENABLED</fg=green>' : '<fg=yellow>DISABLED</fg=yellow>'));
        $this->line('Retention Days: ' . $retention);

        if ($autoBackup !== '1') {
            $this->error('❌ Auto backup is disabled! This is likely why backups aren\'t running daily.');
        }

        $this->newLine();
    }

    private function checkBackupDirectory()
    {
        $this->info('2. BACKUP DIRECTORY');
        $this->info('==================');

        $backupPath = storage_path('app/backups');
        
        if (!file_exists($backupPath)) {
            $this->error('❌ Backup directory does not exist: ' . $backupPath);
        } else {
            $this->line('✅ Backup directory exists: ' . $backupPath);
            
            // Check permissions
            if (!is_writable($backupPath)) {
                $this->error('❌ Backup directory is not writable');
            } else {
                $this->line('✅ Backup directory is writable');
            }
            
            // Check disk space
            $freeSpace = disk_free_space($backupPath);
            $totalSpace = disk_total_space($backupPath);
            $usedPercent = round((($totalSpace - $freeSpace) / $totalSpace) * 100, 2);
            
            $this->line('Disk Usage: ' . $usedPercent . '%');
            if ($usedPercent > 90) {
                $this->error('❌ Disk usage is critically high!');
            } elseif ($usedPercent > 80) {
                $this->warn('⚠️  Disk usage is getting high');
            }
        }

        $this->newLine();
    }

    private function checkExistingBackups()
    {
        $this->info('3. EXISTING BACKUPS');
        $this->info('==================');

        try {
            $backupService = new BackupService();
            $backups = $backupService->getBackupsList();
            
            if (empty($backups)) {
                $this->error('❌ No backup files found');
                return;
            }

            $this->line('Total backups found: ' . count($backups));
            
            // Show recent backups
            $recent = array_slice($backups, 0, 5);
            $this->line('Recent backups:');
            
            foreach ($recent as $backup) {
                $age = $backup['created_at']->diffForHumans();
                $size = $this->formatBytes($backup['size']);
                $status = $backup['created_at']->isToday() ? '<fg=green>TODAY</fg=green>' : 
                         ($backup['created_at']->isYesterday() ? '<fg=yellow>YESTERDAY</fg=yellow>' : $age);
                
                $this->line("  - {$backup['filename']} ({$size}) - {$status}");
            }
            
            // Check for daily backups in last week
            $lastWeek = collect($backups)->filter(function ($backup) {
                return $backup['created_at']->isAfter(now()->subWeek());
            });
            
            $this->line('Backups in last 7 days: ' . $lastWeek->count());
            
            if ($lastWeek->count() < 3) {
                $this->error('❌ Very few recent backups! Backup system may not be running properly.');
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Failed to check backups: ' . $e->getMessage());
        }

        $this->newLine();
    }

    private function checkGoogleDriveConfiguration()
    {
        $this->info('4. GOOGLE DRIVE CONFIGURATION');
        $this->info('=============================');

        $gdriveEnabled = Setting::where('key', 'backup_gdrive_enabled')->value('value') ?? '0';
        
        if ($gdriveEnabled !== '1') {
            $this->line('Google Drive backup is disabled');
            $this->newLine();
            return;
        }

        $clientId = Setting::where('key', 'gdrive_client_id')->value('value');
        $clientSecret = Setting::where('key', 'gdrive_client_secret')->value('value');
        $accessToken = Setting::where('key', 'gdrive_access_token')->value('value');
        
        $this->line('Client ID: ' . ($clientId ? '✅ SET' : '❌ MISSING'));
        $this->line('Client Secret: ' . ($clientSecret ? '✅ SET' : '❌ MISSING'));
        $this->line('Access Token: ' . ($accessToken ? '✅ SET' : '❌ MISSING'));

        if (!$clientId || !$clientSecret || !$accessToken) {
            $this->error('❌ Google Drive is enabled but credentials are incomplete!');
            $this->line('Please complete OAuth setup in Admin > Settings > Backup');
        }

        $this->newLine();
    }

    private function checkCronConfiguration()
    {
        $this->info('5. CRON CONFIGURATION');
        $this->info('=====================');

        // Check if Laravel scheduler is likely running
        $lastRun = cache('last_schedule_run');
        if ($lastRun) {
            $minutesAgo = $lastRun->diffInMinutes(now());
            if ($minutesAgo <= 2) {
                $this->line('✅ Laravel Scheduler: RUNNING (last run ' . $minutesAgo . ' minutes ago)');
            } else {
                $this->warn('⚠️  Laravel Scheduler: May not be running (last run ' . $minutesAgo . ' minutes ago)');
            }
        } else {
            $this->error('❌ Laravel Scheduler: No recent activity detected');
            $this->line('Make sure you have added this to your crontab:');
            $this->line('* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1');
        }

        // Show next scheduled backup time
        $frequency = Setting::where('key', 'backup_frequency')->value('value') ?? 'daily';
        $maintenanceWindow = Setting::where('key', 'maintenance_window')->value('value') ?? '02:00';
        
        $this->line('Next backup scheduled: ' . $frequency . ' at ' . $maintenanceWindow);

        $this->newLine();
    }

    private function checkLogFiles()
    {
        $this->info('6. LOG FILES');
        $this->info('============');

        $logFiles = [
            'scheduler.log' => 'Scheduler Activity',
            'full-backups.log' => 'Full Backups',
            'backup-health.log' => 'Backup Health Check'
        ];

        foreach ($logFiles as $filename => $description) {
            $path = storage_path('logs/' . $filename);
            
            if (file_exists($path)) {
                $size = filesize($path);
                $modified = Carbon::createFromTimestamp(filemtime($path));
                $this->line("✅ {$description}: {$this->formatBytes($size)} (modified {$modified->diffForHumans()})");
                
                // Check for recent activity
                if ($filename === 'scheduler.log' && $modified->isAfter(now()->subDay())) {
                    $content = file_get_contents($path);
                    if (str_contains($content, 'failed') || str_contains($content, 'error')) {
                        $this->error("   ❌ Recent errors found in {$description}");
                    }
                }
            } else {
                $this->error("❌ {$description}: File not found");
            }
        }

        $this->newLine();
    }

    private function checkDatabaseConnection()
    {
        $this->info('7. DATABASE CONNECTION');
        $this->info('======================');

        try {
            $pdo = DB::connection()->getPdo();
            $this->line('✅ Database connection: OK');
            
            // Test backup creation
            $this->line('Testing database backup creation...');
            $backupService = new BackupService();
            $testResult = $backupService->createDatabaseBackup('diagnostic');
            
            if ($testResult['success']) {
                $this->line('✅ Test backup created successfully: ' . $testResult['filename']);
                $this->line('   Size: ' . $this->formatBytes($testResult['size']));
                
                // Clean up test backup
                if (file_exists($testResult['path'])) {
                    unlink($testResult['path']);
                    $this->line('   Test backup cleaned up');
                }
            } else {
                $this->error('❌ Test backup failed: ' . $testResult['error']);
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Database connection failed: ' . $e->getMessage());
        }

        $this->newLine();
    }

    private function attemptFixes()
    {
        $fixes = 0;

        // Fix 1: Create backup directory if missing
        $backupPath = storage_path('app/backups');
        if (!file_exists($backupPath)) {
            mkdir($backupPath, 0755, true);
            $this->line('✅ Created backup directory');
            $fixes++;
        }

        // Fix 2: Enable auto backup if disabled
        $autoBackup = Setting::where('key', 'auto_backup')->value('value') ?? '0';
        if ($autoBackup !== '1') {
            Setting::updateOrCreate(['key' => 'auto_backup'], ['value' => '1']);
            $this->line('✅ Enabled automatic backups');
            $fixes++;
        }

        // Fix 3: Set default backup frequency if missing
        $frequency = Setting::where('key', 'backup_frequency')->value('value');
        if (!$frequency) {
            Setting::updateOrCreate(['key' => 'backup_frequency'], ['value' => 'daily']);
            $this->line('✅ Set backup frequency to daily');
            $fixes++;
        }

        // Fix 4: Set default maintenance window if missing
        $maintenanceWindow = Setting::where('key', 'maintenance_window')->value('value');
        if (!$maintenanceWindow) {
            Setting::updateOrCreate(['key' => 'maintenance_window'], ['value' => '02:00']);
            $this->line('✅ Set maintenance window to 02:00');
            $fixes++;
        }

        if ($fixes === 0) {
            $this->line('No automatic fixes needed');
        } else {
            $this->info("Applied {$fixes} fixes");
        }
    }

    private function showRecommendations()
    {
        $this->info('📋 RECOMMENDATIONS');
        $this->info('==================');

        $recommendations = [];

        // Check if auto backup is disabled
        $autoBackup = Setting::where('key', 'auto_backup')->value('value') ?? '0';
        if ($autoBackup !== '1') {
            $recommendations[] = 'Enable automatic backups in Admin > Settings > Backup';
        }

        // Check cron setup
        $lastRun = cache('last_schedule_run');
        if (!$lastRun || $lastRun->diffInMinutes(now()) > 5) {
            $recommendations[] = 'Set up Laravel cron job: * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1';
        }

        // Check Google Drive
        $gdriveEnabled = Setting::where('key', 'backup_gdrive_enabled')->value('value') ?? '0';
        $clientId = Setting::where('key', 'gdrive_client_id')->value('value');
        if ($gdriveEnabled === '1' && !$clientId) {
            $recommendations[] = 'Complete Google Drive OAuth setup for cloud backups';
        }

        // Check disk space
        $freeSpace = disk_free_space(storage_path());
        $totalSpace = disk_total_space(storage_path());
        $usedPercent = round((($totalSpace - $freeSpace) / $totalSpace) * 100, 2);
        if ($usedPercent > 80) {
            $recommendations[] = 'Free up disk space or increase backup retention settings';
        }

        if (empty($recommendations)) {
            $this->line('✅ No specific recommendations at this time');
        } else {
            foreach ($recommendations as $i => $recommendation) {
                $this->line(($i + 1) . '. ' . $recommendation);
            }
        }
    }

    private function formatBytes($size)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen($size) - 1) / 3);
        return sprintf("%.2f", $size / pow(1024, $factor)) . ' ' . $units[$factor];
    }
}