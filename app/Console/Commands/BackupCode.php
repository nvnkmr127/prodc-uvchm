<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BackupService;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BackupCode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:code {--storage=* : Storage options (local, gdrive)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create automatic code backup';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $this->info('Starting automatic code backup...');
            
            $backupService = new BackupService();
            $storageOptions = $this->option('storage') ?: ['local'];
            
            // Ensure local is always included
            if (!in_array('local', $storageOptions)) {
                $storageOptions[] = 'local';
            }
            
            $results = [];
            
            foreach ($storageOptions as $storage) {
                $this->info("Creating code backup for storage: {$storage}");
                
                if ($storage === 'local') {
                    $result = $backupService->createCodeBackup();
                    $results[] = ['storage' => 'local', 'result' => $result];
                    
                    if ($result['success']) {
                        // Fix: Use 'filename' instead of 'file_name'
                        $this->info("✓ Local code backup created: {$result['filename']}");
                        
                        // Show file size and path
                        $sizeMB = round($result['size'] / 1024 / 1024, 2);
                        $this->line("  Size: {$sizeMB} MB");
                        $this->line("  Path: {$result['path']}");
                    } else {
                        // Fix: Use 'error' instead of 'message'
                        $errorMsg = $result['error'] ?? 'Unknown error';
                        $this->error("✗ Local code backup failed: {$errorMsg}");
                    }
                } elseif ($storage === 'gdrive') {
                    // Check if Google Drive is enabled
                    $gdriveEnabled = Setting::where('key', 'backup_gdrive_enabled')->value('value') ?? '0';
                    if ($gdriveEnabled !== '1') {
                        $this->warn('Google Drive backup is disabled, skipping...');
                        continue;
                    }
                    
                    // Create local backup first, then upload
                    $localResult = $backupService->createCodeBackup();
                    if ($localResult['success']) {
                        $this->info("✓ Local code backup created: {$localResult['filename']}");
                        
                        // Upload to Google Drive
                        $this->info("Uploading to Google Drive...");
                        $gdriveResult = $backupService->uploadToGoogleDrive($localResult['path'], $localResult['filename']);
                        $results[] = ['storage' => 'gdrive', 'result' => $gdriveResult];
                        
                        if ($gdriveResult['success']) {
                            $this->info("✓ Google Drive code backup uploaded successfully");
                        } else {
                            $errorMsg = $gdriveResult['error'] ?? 'Unknown error';
                            $this->error("✗ Google Drive code backup failed: {$errorMsg}");
                        }
                    } else {
                        $errorMsg = $localResult['error'] ?? 'Unknown error';
                        $this->error("✗ Cannot upload to Google Drive: local code backup failed - {$errorMsg}");
                    }
                }
            }
            
            // Clean up old backups if enabled
            $autoCleanup = Setting::where('key', 'auto_cleanup')->value('value') ?? '1';
            if ($autoCleanup === '1') {
                $this->info('Cleaning up old code backups...');
                $retentionDays = Setting::where('key', 'backup_retention_days')->value('value') ?? 30;
                
                // Clean up old code backup files (zip files)
                $backupDir = storage_path('app/backups');
                $deletedCount = 0;
                
                if (is_dir($backupDir)) {
                    $cutoff = now()->subDays($retentionDays)->timestamp;
                    $files = glob($backupDir . '/code_backup_*.zip');
                    
                    foreach ($files as $file) {
                        if (filemtime($file) < $cutoff) {
                            if (unlink($file)) {
                                $deletedCount++;
                            }
                        }
                    }
                }
                
                $this->info("Cleaned up {$deletedCount} old code backup files");
            }
            
            // Send notification email if configured
            $this->sendNotificationEmail($results);
            
            $successCount = count(array_filter($results, function($r) { return $r['result']['success']; }));
            $totalCount = count($results);
            
            $this->info("Code backup completed: {$successCount}/{$totalCount} operations successful");
            
            Log::info('Automatic code backup completed', [
                'success_count' => $successCount,
                'total_count' => $totalCount,
                'results' => $results
            ]);
            
            return $successCount > 0 ? 0 : 1;
            
        } catch (\Exception $e) {
            $this->error('Code backup failed: ' . $e->getMessage());
            Log::error('Automatic code backup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return 1;
        }
    }
    
    /**
     * Send notification email about backup status
     */
    private function sendNotificationEmail($results)
    {
        try {
            $notificationEmail = Setting::where('key', 'backup_notification_email')->value('value');
            if (!$notificationEmail) {
                return;
            }
            
            $successCount = count(array_filter($results, function($r) { return $r['result']['success']; }));
            $totalCount = count($results);
            
            $subject = $successCount === $totalCount 
                ? 'Code Backup Successful'
                : 'Code Backup Completed with Issues';
            
            $message = "Code backup completed at " . now()->format('Y-m-d H:i:s') . "\n\n";
            $message .= "Results: {$successCount}/{$totalCount} operations successful\n\n";
            
            foreach ($results as $result) {
                $status = $result['result']['success'] ? '✓' : '✗';
                $errorMsg = $result['result']['success'] ? 'Success' : ($result['result']['error'] ?? 'Unknown error');
                $message .= "{$status} {$result['storage']}: {$errorMsg}\n";
            }
            
            // Simple email sending (you may want to use a proper mail template)
            Mail::raw($message, function ($mail) use ($notificationEmail, $subject) {
                $mail->to($notificationEmail)
                     ->subject($subject);
            });
            
        } catch (\Exception $e) {
            Log::warning('Failed to send code backup notification email', [
                'error' => $e->getMessage()
            ]);
        }
    }
}