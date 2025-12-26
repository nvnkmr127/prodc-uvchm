<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Setting;
use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleDrive;

class GoogleDriveDiagnosis extends Command
{
    protected $signature = 'gdrive:diagnose';
    protected $description = 'Diagnose Google Drive backup configuration issues';

    public function handle()
    {
        $this->info('=== GOOGLE DRIVE DIAGNOSIS ===');
        $this->newLine();

        // 1. Check Setting model
        $this->info('1. Checking Setting model...');
        try {
            $settingsCount = Setting::count();
            $this->line("   ✅ Settings table accessible ({$settingsCount} settings found)");
        } catch (\Exception $e) {
            $this->error("   ❌ Setting model error: " . $e->getMessage());
            return 1;
        }

        // 2. Check Google Drive settings
        $this->newLine();
        $this->info('2. Checking Google Drive settings...');
        
        $gdriveSettings = [
            'gdrive_client_id',
            'gdrive_client_secret', 
            'gdrive_access_token',
            'gdrive_refresh_token',
            'google_drive_folder_id',
            'backup_gdrive_enabled'
        ];

        $existingSettings = [];
        foreach ($gdriveSettings as $key) {
            $value = Setting::get($key);
            if ($value !== null && $value !== '') {
                $existingSettings[$key] = $value;
                if (in_array($key, ['gdrive_client_secret', 'gdrive_access_token', 'gdrive_refresh_token'])) {
                    $this->line("   ✅ {$key}: EXISTS (" . strlen($value) . " chars)");
                } else {
                    $preview = strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value;
                    $this->line("   ✅ {$key}: {$preview}");
                }
            } else {
                $this->line("   ❌ {$key}: MISSING or EMPTY");
            }
        }

        // 3. Validate format
        $this->newLine();
        $this->info('3. Validating credentials...');
        if (isset($existingSettings['gdrive_client_id'])) {
            $clientId = $existingSettings['gdrive_client_id'];
            if (preg_match('/^[0-9]+-[a-zA-Z0-9_]+\.apps\.googleusercontent\.com$/', $clientId)) {
                $this->line("   ✅ Client ID format is valid");
            } else {
                $this->error("   ❌ Client ID format is INVALID");
                $this->line("      Should end with .apps.googleusercontent.com");
            }
        }

        // 4. Test Google Client
        $this->newLine();
        $this->info('4. Testing Google Client...');
        if (isset($existingSettings['gdrive_client_id']) && isset($existingSettings['gdrive_client_secret'])) {
            try {
                $client = new GoogleClient();
                $client->setClientId($existingSettings['gdrive_client_id']);
                $client->setClientSecret($existingSettings['gdrive_client_secret']);
                $client->setRedirectUri(route('admin.backups.gdrive.callback'));
                $client->addScope(GoogleDrive::DRIVE_FILE);
                
                $authUrl = $client->createAuthUrl();
                $this->line("   ✅ Google Client created successfully");
                $this->line("   📍 Redirect URI: " . route('admin.backups.gdrive.callback'));
                
            } catch (\Exception $e) {
                $this->error("   ❌ Google Client failed: " . $e->getMessage());
            }
        } else {
            $this->error("   ❌ Cannot test - credentials missing");
        }

        // 5. Test BackupService
        $this->newLine();
        $this->info('5. Testing BackupService...');
        try {
            $backupService = app(\App\Services\BackupService::class);
            $result = $backupService->testGoogleDriveConnection();
            
            if ($result['success']) {
                $this->line("   ✅ " . $result['message']);
            } else {
                $this->error("   ❌ " . $result['message']);
            }
        } catch (\Exception $e) {
            $this->error("   ❌ BackupService test failed: " . $e->getMessage());
        }

        // 6. Recommendations
        $this->newLine();
        $this->info('=== RECOMMENDATIONS ===');
        
        if (empty($existingSettings)) {
            $this->error('🔧 No Google Drive settings found');
            $this->line('Run: php artisan gdrive:fix');
        } elseif (!isset($existingSettings['gdrive_client_id']) || !isset($existingSettings['gdrive_client_secret'])) {
            $this->error('🔧 Missing credentials - complete setup in admin panel');
        } elseif (!isset($existingSettings['gdrive_access_token'])) {
            $this->error('🔧 Need authorization - click "Authorize Google Drive" in admin panel');
        } else {
            $this->warn('🔧 Check Google Cloud Console configuration');
            $this->line('Ensure redirect URI matches: ' . route('admin.backups.gdrive.callback'));
        }

        return 0;
    }
}