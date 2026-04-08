<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class UploadDatabaseBackups extends Command
{
    protected $signature = 'backup:upload-database {--recent : Upload only files from last 24 hours} {--all : Upload all database backups}';

    protected $description = 'Upload database backup files (.sql) to Google Drive';

    public function handle()
    {
        $this->info('📤 Uploading Database Backups to Google Drive');
        $this->info('==============================================');

        try {
            $backupService = new BackupService;
            $backupDir = storage_path('app/backups');

            if (! is_dir($backupDir)) {
                $this->error('Backup directory not found: '.$backupDir);

                return 1;
            }

            // Get SQL files
            $files = glob($backupDir.'/*.sql');

            if (empty($files)) {
                $this->warn('No database backup files (.sql) found');

                return 0;
            }

            // Filter by time if --recent option
            if ($this->option('recent')) {
                $cutoff = time() - (24 * 60 * 60); // 24 hours ago
                $files = array_filter($files, function ($file) use ($cutoff) {
                    return filemtime($file) > $cutoff;
                });

                if (empty($files)) {
                    $this->warn('No recent database backup files found (last 24 hours)');

                    return 0;
                }
            }

            $this->info('Found '.count($files).' database backup file(s) to upload:');

            $uploaded = 0;
            $failed = 0;

            foreach ($files as $filePath) {
                $filename = basename($filePath);
                $size = filesize($filePath);
                $sizeMB = round($size / 1024 / 1024, 2);
                $date = date('Y-m-d H:i:s', filemtime($filePath));

                $this->line("📁 {$filename} ({$sizeMB} MB) - {$date}");

                $result = $backupService->uploadToGoogleDrive($filePath, $filename);

                if ($result['success']) {
                    if (isset($result['skipped']) && $result['skipped']) {
                        $this->line('   ⚠️  Already exists in Google Drive');
                    } else {
                        $this->line('   ✅ Uploaded successfully');
                        $uploaded++;
                    }
                } else {
                    $this->line('   ❌ Failed: '.($result['error'] ?? 'Unknown error'));
                    $failed++;

                    // Stop if authentication error
                    if (isset($result['error_type']) && $result['error_type'] === 'authentication') {
                        $this->error('Authentication failed. Please re-authorize Google Drive in admin panel.');
                        break;
                    }
                }
            }

            $this->newLine();
            if ($uploaded > 0) {
                $this->info("✅ Successfully uploaded {$uploaded} database backup(s)");
            }
            if ($failed > 0) {
                $this->error("❌ Failed to upload {$failed} database backup(s)");
            }

            return $failed > 0 ? 1 : 0;

        } catch (\Exception $e) {
            $this->error('Upload failed: '.$e->getMessage());

            return 1;
        }
    }
}
