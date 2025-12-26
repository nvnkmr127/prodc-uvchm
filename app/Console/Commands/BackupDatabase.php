<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BackupService;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database {--storage=* : Storage options (local, gdrive)} {--type=daily : Backup type}';
    protected $description = 'Create automatic database backup';

    public function handle()
    {
        try {
            // Check if backups are enabled
            $backupEnabled = Setting::where('key', 'auto_backup')->value('value') ?? '0';
            if ($backupEnabled !== '1') {
                $this->info('Automatic backups are disabled.');
                return 0;
            }

            $backupType = $this->option('type') ?? 'daily';
            $this->info("Starting {$backupType} database backup...");
            
            // Handle storage options (support comma-separated values)
            $rawStorageOption = $this->option('storage');
            $storageOptions = [];
            
            if (empty($rawStorageOption)) {
                $storageOptions = ['local'];
            } else {
                foreach ($rawStorageOption as $option) {
                    if (strpos($option, ',') !== false) {
                        $storageOptions = array_merge($storageOptions, explode(',', $option));
                    } else {
                        $storageOptions[] = $option;
                    }
                }
            }
            
            $storageOptions = array_unique(array_map('trim', $storageOptions));
            $this->line("Storage options: " . implode(', ', $storageOptions));
            
            $backupService = new BackupService();
            $results = [];
            
            // Step 1: Create local backup
            $this->info("Creating database backup...");
            $result = $backupService->createDatabaseBackup($backupType);
            
            if ($result['success']) {
                $this->info("✓ Local backup created: {$result['filename']}");
                $results['local'] = 'SUCCESS';
                
                // Step 2: Handle Google Drive upload if requested
                if (in_array('gdrive', $storageOptions)) {
                    $gdriveEnabled = Setting::where('key', 'backup_gdrive_enabled')->value('value') ?? '0';
                    
                    if ($gdriveEnabled === '1') {
                        $this->info("Uploading to Google Drive...");
                        
                        // Use the same BackupService method that works in your other commands
                        $uploadResult = $backupService->uploadToGoogleDrive(
                            $result['path'], 
                            $result['filename']
                        );
                        
                        if (is_array($uploadResult) && isset($uploadResult['success'])) {
                            if ($uploadResult['success']) {
                                if (isset($uploadResult['skipped']) && $uploadResult['skipped']) {
                                    $this->info("↪ File already exists in Google Drive");
                                    $results['gdrive'] = 'SKIPPED';
                                } else {
                                    $this->info("✓ Google Drive upload successful");
                                    $results['gdrive'] = 'SUCCESS';
                                }
                            } else {
                                $error = $uploadResult['error'] ?? 'Unknown error';
                                $this->error("✗ Google Drive upload failed: {$error}");
                                $results['gdrive'] = 'FAILED';
                            }
                        } elseif ($uploadResult) {
                            // Legacy format (direct Google Drive ID)
                            $this->info("✓ Google Drive upload successful");
                            $results['gdrive'] = 'SUCCESS';
                        } else {
                            $this->error("✗ Google Drive upload failed: No response");
                            $results['gdrive'] = 'FAILED';
                        }
                        
                    } else {
                        $this->warn("⚠ Google Drive upload skipped - disabled in settings");
                        $this->line("  Enable in settings: backup_gdrive_enabled = '1'");
                        $results['gdrive'] = 'DISABLED';
                    }
                }
                
            } else {
                $error = $result['error'] ?? 'Unknown error occurred';
                $this->error("✗ Local backup failed: {$error}");
                $results['local'] = 'FAILED';
                return 1;
            }
            
            // Display summary
            $this->newLine();
            $this->info('Backup process completed');
            foreach ($results as $storage => $status) {
                $this->line("  {$storage}: {$status}");
            }
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("Backup process failed: {$e->getMessage()}");
            Log::error('Backup process failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
}