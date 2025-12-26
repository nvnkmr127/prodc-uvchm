<?php
// Complete Google Drive diagnosis - run this in php artisan tinker

use App\Models\Setting;
use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleDrive;
use Illuminate\Support\Facades\Log;

echo "=== COMPLETE GOOGLE DRIVE DIAGNOSIS ===\n\n";

// 1. Check Setting model and database connection
echo "1. Checking Setting model and database...\n";
try {
    $settingsCount = Setting::count();
    echo "   ✅ Settings table accessible ({$settingsCount} settings found)\n";
    
    // Show a few sample settings to verify the model works
    $sampleSettings = Setting::take(3)->get(['key', 'value']);
    echo "   Sample settings:\n";
    foreach ($sampleSettings as $setting) {
        $value = strlen($setting->value) > 30 ? substr($setting->value, 0, 30) . '...' : $setting->value;
        echo "     - {$setting->key}: {$value}\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Setting model error: " . $e->getMessage() . "\n";
    return;
}

echo "\n2. Checking Google Drive specific settings...\n";

// Check each Google Drive setting individually
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
    try {
        $value = Setting::get($key);
        if ($value !== null && $value !== '') {
            $existingSettings[$key] = $value;
            if ($key === 'gdrive_client_secret' || $key === 'gdrive_access_token' || $key === 'gdrive_refresh_token') {
                echo "   ✅ {$key}: EXISTS (" . strlen($value) . " chars)\n";
            } else {
                $preview = strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value;
                echo "   ✅ {$key}: {$preview}\n";
            }
        } else {
            echo "   ❌ {$key}: MISSING or EMPTY\n";
        }
    } catch (\Exception $e) {
        echo "   ❌ {$key}: ERROR - " . $e->getMessage() . "\n";
    }
}

// 3. Validate Client ID format if it exists
echo "\n3. Validating credentials format...\n";
if (isset($existingSettings['gdrive_client_id'])) {
    $clientId = $existingSettings['gdrive_client_id'];
    if (preg_match('/^[0-9]+-[a-zA-Z0-9_]+\.apps\.googleusercontent\.com$/', $clientId)) {
        echo "   ✅ Client ID format is valid\n";
    } else {
        echo "   ❌ Client ID format is INVALID (should end with .apps.googleusercontent.com)\n";
        echo "      Current: {$clientId}\n";
    }
} else {
    echo "   ❌ Cannot validate - Client ID is missing\n";
}

// 4. Test Google Client creation
echo "\n4. Testing Google Client creation...\n";
if (isset($existingSettings['gdrive_client_id']) && isset($existingSettings['gdrive_client_secret'])) {
    try {
        $client = new GoogleClient();
        $client->setClientId($existingSettings['gdrive_client_id']);
        $client->setClientSecret($existingSettings['gdrive_client_secret']);
        $client->setRedirectUri(route('admin.backups.gdrive.callback'));
        $client->addScope(GoogleDrive::DRIVE_FILE);
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        
        // Test auth URL generation
        $authUrl = $client->createAuthUrl();
        echo "   ✅ Google Client created successfully\n";
        echo "   ✅ Auth URL generated (" . strlen($authUrl) . " chars)\n";
        echo "   📍 Redirect URI: " . route('admin.backups.gdrive.callback') . "\n";
        
    } catch (\Exception $e) {
        echo "   ❌ Google Client creation failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ❌ Cannot test - Client ID or Secret missing\n";
}

// 5. Check routes
echo "\n5. Checking required routes...\n";
try {
    $callbackRoute = route('admin.backups.gdrive.callback');
    echo "   ✅ Callback route exists: {$callbackRoute}\n";
} catch (\Exception $e) {
    echo "   ❌ Callback route missing: " . $e->getMessage() . "\n";
}

// 6. Test BackupService method
echo "\n6. Testing BackupService methods...\n";
try {
    $backupService = app(\App\Services\BackupService::class);
    
    // Test the specific method that's failing
    $client = $backupService->getGoogleDriveClient();
    if ($client) {
        echo "   ✅ BackupService can create Google Client\n";
    } else {
        echo "   ❌ BackupService returns null client\n";
    }
    
    // Test the connection test method
    $result = $backupService->testGoogleDriveConnection();
    echo "   📊 Connection test result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
    
} catch (\Exception $e) {
    echo "   ❌ BackupService test failed: " . $e->getMessage() . "\n";
}

// 7. Show exact fix steps
echo "\n=== DIAGNOSIS COMPLETE - RECOMMENDED ACTIONS ===\n\n";

if (empty($existingSettings)) {
    echo "🔧 ISSUE: No Google Drive settings found\n";
    echo "SOLUTION: Set up credentials using one of these methods:\n\n";
    
    echo "METHOD 1 - Manual setup in tinker:\n";
    echo "Setting::set('gdrive_client_id', 'YOUR_CLIENT_ID_FROM_GOOGLE_CONSOLE');\n";
    echo "Setting::set('gdrive_client_secret', 'YOUR_CLIENT_SECRET_FROM_GOOGLE_CONSOLE');\n\n";
    
    echo "METHOD 2 - Use the admin interface:\n";
    echo "- Go to Admin > Settings > Backup tab\n";
    echo "- Enter your Google Drive credentials\n\n";
    
    echo "METHOD 3 - Use the diagnostic command:\n";
    echo "php artisan gdrive:fix\n\n";
    
} elseif (!isset($existingSettings['gdrive_client_id']) || !isset($existingSettings['gdrive_client_secret'])) {
    echo "🔧 ISSUE: Partial Google Drive setup (missing Client ID or Secret)\n";
    echo "SOLUTION: Complete the setup with missing credentials\n\n";
    
} elseif (!isset($existingSettings['gdrive_access_token'])) {
    echo "🔧 ISSUE: Credentials exist but no access token (not authorized)\n";
    echo "SOLUTION: Authorize the application:\n";
    echo "1. Go to Admin > Settings > Backup tab\n";
    echo "2. Click 'Authorize Google Drive'\n";
    echo "3. Complete the OAuth flow\n\n";
    
} else {
    echo "🔧 ISSUE: All settings exist but connection test still fails\n";
    echo "SOLUTION: Check Google Cloud Console configuration:\n";
    echo "1. Ensure redirect URI matches: " . route('admin.backups.gdrive.callback') . "\n";
    echo "2. Verify OAuth consent screen is configured\n";
    echo "3. Check that Google Drive API is enabled\n";
    echo "4. Try re-authorizing the application\n\n";
}

echo "GOOGLE CLOUD CONSOLE CHECKLIST:\n";
echo "1. Go to: https://console.cloud.google.com/\n";
echo "2. APIs & Services > Credentials\n";
echo "3. Find your OAuth 2.0 Client ID\n";
echo "4. Authorized redirect URIs must include:\n";
echo "   " . route('admin.backups.gdrive.callback') . "\n";
echo "5. APIs & Services > Library: Enable Google Drive API\n";
echo "6. APIs & Services > OAuth consent screen: Configure properly\n";

// 8. Show current error context
if (isset($existingSettings['gdrive_client_id'])) {
    echo "\nYOUR CURRENT ERROR CONTEXT:\n";
    echo "- Client ID used: " . $existingSettings['gdrive_client_id'] . "\n";
    echo "- Redirect URI: " . route('admin.backups.gdrive.callback') . "\n";
    echo "- Error: invalid_client / Unauthorized\n";
    echo "- This typically means the Client ID/Secret don't match Google Console\n";
    echo "  OR the redirect URI isn't registered correctly\n";
}