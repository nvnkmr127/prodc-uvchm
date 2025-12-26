<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BackupService;

class UploadAllBackups extends Command
{
    protected $signature = 'backup:upload-all {--recent : Upload only files from last 24 hours}';
    protected $description = 'Upload all backup files from storage/app/backups to Google Drive';

    public function handle()
    {
        $this->info('📤 Uploading All Backups to Google Drive');
        $this->info('=====================================');
        
        try {
            $backupService = new BackupService();
            $backupDir = storage_path('app/backups');
            
            if (!is_dir($backupDir)) {
                $this->error('Backup directory not found: ' . $backupDir);
                return 1;
            }
            
            // Get all backup files (.sql, .zip, .json)
            $files = glob($backupDir . '/*.{sql,zip,json}', GLOB_BRACE);
            
            if (empty($files)) {
                $this->warn('No backup files found in ' . $backupDir);
                return 0;
            }
            
            // Filter by time if --recent option
            if ($this->option('recent')) {
                $cutoff = time() - (24 * 60 * 60); // 24 hours ago
                $files = array_filter($files, function($file) use ($cutoff) {
                    return filemtime($file) > $cutoff;
                });
                
                if (empty($files)) {
                    $this->warn('No recent backup files found (last 24 hours)');
                    return 0;
                }
            }
            
            $this->info('Found ' . count($files) . ' backup file(s) to upload:');
            
            $uploaded = 0;
            $failed = 0;
            $skipped = 0;
            
            foreach ($files as $filePath) {
                $filename = basename($filePath);
                $extension = pathinfo($filePath, PATHINFO_EXTENSION);
                $size = filesize($filePath);
                $sizeMB = round($size / 1024 / 1024, 2);
                $date = date('Y-m-d H:i:s', filemtime($filePath));
                
                $this->line("📁 {$filename} ({$extension}) - {$sizeMB} MB - {$date}");
                
                $result = $backupService->uploadToGoogleDrive($filePath, $filename);
                
                if ($result['success']) {
                    if (isset($result['skipped']) && $result['skipped']) {
                        $this->line("   ⚠️  Already exists in Google Drive");
                        $skipped++;
                    } else {
                        $this->line("   ✅ Uploaded successfully");
                        $uploaded++;
                    }
                } else {
                    $this->line("   ❌ Failed: " . ($result['error'] ?? 'Unknown error'));
                    $failed++;
                    
                    // Stop if authentication error
                    if (isset($result['error_type']) && $result['error_type'] === 'authentication') {
                        $this->error('Authentication failed. Please re-authorize Google Drive in admin panel.');
                        break;
                    }
                }
            }
            
            $this->newLine();
            $this->info("📊 Upload Summary:");
            $this->line("   ✅ Uploaded: {$uploaded}");
            $this->line("   ⚠️  Skipped: {$skipped}");
            $this->line("   ❌ Failed: {$failed}");
            
            return $failed > 0 ? 1 : 0;
            
        } catch (\Exception $e) {
            $this->error('Upload failed: ' . $e->getMessage());
            return 1;
        }
    }
}