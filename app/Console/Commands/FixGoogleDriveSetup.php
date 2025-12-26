<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Setting;
use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleDrive;

class FixGoogleDriveSetup extends Command
{
    protected $signature = 'gdrive:fix {--reset : Reset all Google Drive settings}';
    protected $description = 'Fix Google Drive setup and validate configuration';

    public function handle()
    {
        $this->info('🔧 Google Drive Setup Fixer');
        $this->line(str_repeat('=', 50));
        
        if ($this->option('reset')) {
            $this->resetAllSettings();
        }
        
        $this->checkGoogleCloudConsoleSetup();
        $this->validateCredentials();
        $this->testApiAccess();
        $this->provideNextSteps();
    }
    
    private function resetAllSettings()
    {
        $this->info('🗑️  Resetting all Google Drive settings...');
        
        $keys = [
            'gdrive_client_id',
            'gdrive_client_secret',
            'gdrive_access_token', 
            'gdrive_refresh_token',
            'google_drive_folder_id',
            'backup_gdrive_enabled'
        ];
        
        foreach ($keys as $key) {
            Setting::remove($key);
        }
        
        \Illuminate\Support\Facades\Cache::flush();
        $this->info('✅ All Google Drive settings cleared');
        $this->newLine();
    }
    
    private function checkGoogleCloudConsoleSetup()
    {
        $this->info('🔍 Google Cloud Console Setup Check:');
        $this->line(str_repeat('-', 40));
        
        $this->warn('⚠️  You MUST complete these steps in Google Cloud Console:');
        $this->newLine();
        
        $this->line('1️⃣  Go to: https://console.cloud.google.com/');
        $this->line('2️⃣  Select your project (or create new one)');
        $this->line('3️⃣  APIs & Services > Library');
        $this->line('    📌 Enable "Google Drive API"');
        $this->line('    📌 Enable "Google Sheets API" (recommended)');
        $this->newLine();
        
        $this->line('4️⃣  APIs & Services > OAuth consent screen');
        $this->line('    📌 Configure app name and contact email');
        $this->line('    📌 Add scopes: https://www.googleapis.com/auth/drive.file');
        $this->line('    📌 Add your email as test user');
        $this->newLine();
        
        $this->line('5️⃣  APIs & Services > Credentials');
        $this->line('    📌 Create OAuth 2.0 Client ID (Web application)');
        $this->line('    📌 Add redirect URI:');
        $this->line('       https://uvchm.digicloudify.com/admin/backups/gdrive/callback');
        $this->newLine();
        
        if (!$this->confirm('Have you completed the Google Cloud Console setup?')) {
            $this->error('❌ Please complete Google Cloud Console setup first, then run this command again.');
            return false;
        }
        
        return true;
    }
    
    private function validateCredentials()
    {
        $this->info('🔑 Setting up credentials...');
        
        $clientId = $this->ask('Enter your Google Drive Client ID');
        $clientSecret = $this->secret('Enter your Google Drive Client Secret');
        
        if (!$clientId || !$clientSecret) {
            $this->error('❌ Both Client ID and Secret are required');
            return false;
        }
        
        // Validate Client ID format
        if (!preg_match('/^[0-9]+-[a-zA-Z0-9_]+\.apps\.googleusercontent\.com$/', $clientId)) {
            $this->error('❌ Invalid Client ID format. Should end with .apps.googleusercontent.com');
            return false;
        }
        
        // Store credentials
        Setting::set('gdrive_client_id', $clientId, [
            'group' => 'backup',
            'type' => 'text',
            'description' => 'Google Drive OAuth Client ID'
        ]);
        
        Setting::set('gdrive_client_secret', $clientSecret, [
            'group' => 'backup',
            'type' => 'password', 
            'description' => 'Google Drive OAuth Client Secret',
            'is_encrypted' => true
        ]);
        
        $this->info('✅ Credentials stored successfully');
        
        // Verify storage
        $storedId = Setting::get('gdrive_client_id');
        $storedSecret = Setting::get('gdrive_client_secret');
        
        $this->line("   Client ID: " . ($storedId ? "✓ Stored" : "✗ Failed"));
        $this->line("   Client Secret: " . ($storedSecret ? "✓ Stored" : "✗ Failed"));
        
        return $storedId && $storedSecret;
    }
    
    private function testApiAccess()
    {
        $this->info('🧪 Testing API Access...');
        
        try {
            $clientId = Setting::get('gdrive_client_id');
            $clientSecret = Setting::get('gdrive_client_secret');
            
            if (!$clientId || !$clientSecret) {
                $this->error('❌ No credentials found for testing');
                return false;
            }
            
            $client = new GoogleClient();
            $client->setClientId($clientId);
            $client->setClientSecret($clientSecret);
            $client->setRedirectUri(route('admin.backups.gdrive.callback'));
            $client->addScope(GoogleDrive::DRIVE_FILE);
            $client->setAccessType('offline');
            $client->setPrompt('consent');
            
            // Test auth URL generation
            $authUrl = $client->createAuthUrl();
            $this->info('✅ Authorization URL generated successfully');
            $this->line('   Length: ' . strlen($authUrl) . ' characters');
            
            return true;
            
        } catch (\Exception $e) {
            $this->error('❌ API test failed: ' . $e->getMessage());
            return false;
        }
    }
    
    private function provideNextSteps()
    {
        $this->info('📋 Next Steps:');
        $this->line(str_repeat('-', 40));
        
        $this->line('1️⃣  Go to your admin settings page:');
        $this->line('    https://uvchm.digicloudify.com/admin/settings?tab=backup');
        $this->newLine();
        
        $this->line('2️⃣  Click "Authorize Google Drive"');
        $this->line('3️⃣  Complete OAuth flow in popup window');
        $this->line('4️⃣  Click "Test Connection" to verify');
        $this->newLine();
        
        $this->warn('🚨 If you still get 403 errors:');
        $this->line('   - Double-check Google Drive API is ENABLED');
        $this->line('   - Verify you\'re using credentials from the SAME project');
        $this->line('   - Check OAuth consent screen is configured');
        $this->line('   - Wait 5-10 minutes for API enablement to propagate');
    }
}