<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BackupService;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ManualBackupTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:test-now {--upload-gdrive : Also test Google Drive upload}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test backup creation immediately (for debugging)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Testing Backup System');
        $this->info('========================');
        $this->newLine();

        try {
            // Create backup service
            $backupService = new BackupService();
            
            $this->info('1. Creating database backup...');
            $result = $backupService->createDatabaseBackup('manual_test');
            
            if ($result['success']) {
                $this->line('✅ Success! Created: ' . $result['filename']);
                $this->line('   Path: ' . $result['path']);
                $this->line('   Size: ' . $this->formatBytes($result['size']));
                $this->line('   Created: ' . $result['created_at']->format('Y-m-d H:i:s'));
                
                // Test Google Drive upload if requested
                if ($this->option('upload-gdrive')) {
                    $this->newLine();
                    $this->info('2. Testing Google Drive upload...');
                    
                    $gdriveEnabled = Setting::where('key', 'backup_gdrive_enabled')->value('value') ?? '0';
                    
                    if ($gdriveEnabled !== '1') {
                        $this->warn('⚠️  Google Drive is disabled in settings');
                    } else {
                        $uploadResult = $backupService->uploadToGoogleDrive(
                            $result['path'], 
                            $result['filename']
                        );
                        
                        if ($uploadResult['success']) {
                            $this->line('✅ Google Drive upload successful!');
                            if (isset($uploadResult['google_drive_id'])) {
                                $this->line('   Google Drive File ID: ' . $uploadResult['google_drive_id']);
                            }
                            if (isset($uploadResult['skipped']) && $uploadResult['skipped']) {
                                $this->line('   Note: File already existed in Google Drive');
                            }
                        } else {
                            $this->error('❌ Google Drive upload failed:');
                            $this->line('   Error: ' . $uploadResult['error']);
                            if (isset($uploadResult['error_type'])) {
                                $this->line('   Type: ' . $uploadResult['error_type']);
                            }
                            
                            // Provide specific troubleshooting advice
                            $this->newLine();
                            $this->warn('Troubleshooting Google Drive Upload:');
                            switch ($uploadResult['error_type'] ?? '') {
                                case 'authentication':
                                    $this->line('• Check OAuth setup in Admin > Settings > Backup');
                                    $this->line('• You may need to re-authorize Google Drive access');
                                    break;
                                case 'folder_access':
                                    $this->line('• Check if the backup folder exists in Google Drive');
                                    $this->line('• Verify folder permissions');
                                    break;
                                case 'api_error':
                                    $this->line('• Check Google Drive API quotas and limits');
                                    $this->line('• Verify Google Cloud project is properly configured');
                                    break;
                                default:
                                    $this->line('• Check logs for more details: storage/logs/database-backups.log');
                                    break;
                            }
                        }
                    }
                }
                
                // Show backup in context
                $this->newLine();
                $this->info('3. Current backup status:');
                $this->showRecentBackups($backupService);
                
                // Ask if user wants to keep the test backup
                if ($this->confirm('Keep this test backup?', false)) {
                    $this->line('Test backup kept: ' . $result['filename']);
                } else {
                    if (file_exists($result['path'])) {
                        unlink($result['path']);
                        $this->line('Test backup deleted');
                    }
                }
                
            } else {
                $this->error('❌ Backup creation failed:');
                $this->line('Error: ' . $result['error']);
                
                // Show troubleshooting steps
                $this->newLine();
                $this->warn('Troubleshooting Steps:');
                $this->line('1. Check database connection: php artisan db:show');
                $this->line('2. Check backup directory permissions: storage/app/backups/');
                $this->line('3. Check disk space: df -h');
                $this->line('4. Check logs: storage/logs/laravel.log');
                
                return 1;
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Test failed with exception:');
            $this->line('Error: ' . $e->getMessage());
            $this->line('File: ' . $e->getFile() . ':' . $e->getLine());
            
            return 1;
        }
        
        $this->newLine();
        $this->info('✅ Backup test completed successfully!');
        
        return 0;
    }
    
    private function showRecentBackups(BackupService $backupService)
    {
        try {
            $backups = $backupService->getBackupsList();
            $recent = array_slice($backups, 0, 3);
            
            if (empty($recent)) {
                $this->line('No existing backups found');
                return;
            }
            
            $this->line('Recent backups:');
            foreach ($recent as $backup) {
                $age = $backup['created_at']->diffForHumans();
                $size = $this->formatBytes($backup['size']);
                $this->line("  • {$backup['filename']} ({$size}) - {$age}");
            }
            
        } catch (\Exception $e) {
            $this->line('Could not list existing backups: ' . $e->getMessage());
        }
    }
    
    private function formatBytes($size)
    {
        if ($size < 1024) return $size . ' B';
        if ($size < 1048576) return round($size / 1024, 2) . ' KB';
        if ($size < 1073741824) return round($size / 1048576, 2) . ' MB';
        return round($size / 1073741824, 2) . ' GB';
    }
}