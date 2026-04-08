<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\BackupService;
use Carbon\Carbon;
use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleDrive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BackupController extends Controller
{
    protected $backupService;

    public function __construct(BackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    /**
     * Calculate disk usage percentage
     */
    private function calculateDiskUsage()
    {
        try {
            $totalSpace = disk_total_space(storage_path());
            $freeSpace = disk_free_space(storage_path());
            $usedSpace = $totalSpace - $freeSpace;

            return round(($usedSpace / $totalSpace) * 100, 1);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get last backup information
     */
    private function getLastBackupInfo($spatieBackups, $settingsBackups)
    {
        $allBackups = array_merge($spatieBackups, $settingsBackups);

        if (empty($allBackups)) {
            return null;
        }

        // Sort by created_at and get the most recent
        usort($allBackups, function ($a, $b) {
            $dateA = isset($a['created_at']) ? $a['created_at']->getTimestamp() : 0;
            $dateB = isset($b['created_at']) ? $b['created_at']->getTimestamp() : 0;

            return $dateB - $dateA;
        });

        $lastBackup = $allBackups[0];

        return [
            'date' => isset($lastBackup['created_at']) ? $lastBackup['created_at']->format('M j, Y g:i A') : 'Unknown',
        ];
    }

    /**
     * Display backup management page
     */
    public function index()
    {
        try {
            $spatieBackups = $this->backupService->getLocalBackups() ?? [];
            $settingsBackups = $this->backupService->getGoogleDriveBackups() ?? [];

            // Get backup settings using your existing Setting model methods
            $backupConfig = [
                'auto_backup' => Setting::get('auto_backup', '1') === '1',
                'backup_frequency' => Setting::get('backup_frequency', 'daily'),
                'backup_retention_days' => Setting::get('backup_retention_days', '30'),
                'auto_cleanup' => Setting::get('auto_cleanup', '1') === '1',
                'backup_notifications' => Setting::get('backup_notifications', '0') === '1',
                'notification_email' => Setting::get('notification_email', ''),
                'backup_gdrive_enabled' => Setting::get('backup_gdrive_enabled', '0') === '1',
                'gdrive_client_id' => Setting::get('gdrive_client_id', ''),
                'gdrive_client_secret' => Setting::get('gdrive_client_secret', ''),
                'gdrive_folder_name' => Setting::get('gdrive_folder_name', 'College-Backups'),
                'disk_usage' => [
                    'percentage' => $this->calculateDiskUsage(),
                ],
                'last_backup' => $this->getLastBackupInfo($spatieBackups, $settingsBackups),
            ];

            return view('admin.backups.index', compact('spatieBackups', 'settingsBackups', 'backupConfig'));

        } catch (\Exception $e) {
            Log::error('Failed to load backup page', [
                'error' => $e->getMessage(),
            ]);

            // Set default values for error case
            $spatieBackups = [];
            $settingsBackups = [];
            $backupConfig = [
                'auto_backup' => false,
                'backup_frequency' => 'daily',
                'backup_retention_days' => '30',
                'auto_cleanup' => false,
                'backup_notifications' => false,
                'notification_email' => '',
                'backup_gdrive_enabled' => false,
                'gdrive_client_id' => '',
                'gdrive_client_secret' => '',
                'gdrive_folder_name' => 'College-Backups',
                'disk_usage' => ['percentage' => 0],
                'last_backup' => null,
            ];

            return view('admin.backups.index', compact('spatieBackups', 'settingsBackups', 'backupConfig'))
                ->with('error', 'Failed to load backup data: '.$e->getMessage());
        }
    }

    /**
     * Update backup settings
     */
    public function updateSettings(Request $request)
    {
        try {
            $request->validate([
                'auto_backup' => 'boolean',
                'backup_frequency' => 'required|in:daily,weekly,monthly',
                'backup_retention_days' => 'required|integer|min:1|max:365',
                'auto_cleanup' => 'boolean',
                'backup_notifications' => 'boolean',
                'notification_email' => 'nullable|email',
            ]);

            // Update settings using the Setting model
            Setting::set('auto_backup', $request->has('auto_backup') ? '1' : '0');
            Setting::set('backup_frequency', $request->input('backup_frequency'));
            Setting::set('backup_retention_days', $request->input('backup_retention_days'));
            Setting::set('auto_cleanup', $request->has('auto_cleanup') ? '1' : '0');
            Setting::set('backup_notifications', $request->has('backup_notifications') ? '1' : '0');

            if ($request->input('notification_email')) {
                Setting::set('notification_email', $request->input('notification_email'));
            }

            Log::info('Backup settings updated', [
                'user_id' => auth()->id(),
                'settings' => $request->only([
                    'auto_backup', 'backup_frequency', 'backup_retention_days',
                    'auto_cleanup', 'backup_notifications', 'notification_email',
                ]),
            ]);

            return redirect()->route('admin.backups.index')
                ->with('success', 'Backup settings updated successfully!');

        } catch (\Exception $e) {
            Log::error('Failed to update backup settings', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return redirect()->route('admin.backups.index')
                ->with('error', 'Failed to update backup settings: '.$e->getMessage());
        }
    }

    /**
     * Create a new backup
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'type' => 'required|in:database,code,settings,both',
            ]);

            $type = $request->input('type');

            switch ($type) {
                case 'database':
                    $result = $this->backupService->createDatabaseBackup('manual');
                    break;

                case 'code':
                    $result = $this->backupService->createCodeBackup();
                    break;

                case 'settings':
                    $backupPath = $this->createSettingsBackup();
                    $result = [
                        'success' => $backupPath !== false,
                        'message' => $backupPath ? 'Settings backup created successfully' : 'Settings backup failed',
                        'filename' => $backupPath ? basename($backupPath) : null,
                    ];
                    break;

                case 'both':
                    $dbResult = $this->backupService->createDatabaseBackup('manual');
                    $settingsPath = $this->createSettingsBackup();

                    $result = [
                        'success' => $dbResult['success'] && $settingsPath !== false,
                        'message' => 'Combined backup '.($dbResult['success'] && $settingsPath ? 'created successfully' : 'partially failed'),
                        'details' => [
                            'database' => $dbResult,
                            'settings' => $settingsPath ? 'Success' : 'Failed',
                        ],
                    ];
                    break;

                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid backup type specified',
                    ]);
            }

            // If backup was successful and Google Drive is enabled, upload to Drive
            if ($result['success'] && Setting::get('backup_gdrive_enabled', '0') === '1') {
                if (isset($result['path']) && file_exists($result['path'])) {
                    $uploadResult = $this->backupService->uploadToGoogleDrive(
                        $result['path'],
                        $result['filename']
                    );

                    if ($uploadResult['success']) {
                        $result['message'] .= ' and uploaded to Google Drive';
                        $result['google_drive_upload'] = true;
                    } else {
                        $result['message'] .= ' but Google Drive upload failed: '.$uploadResult['error'];
                        $result['google_drive_upload'] = false;
                        $result['google_drive_error'] = $uploadResult['error'];
                    }
                }
            }

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Backup creation failed', [
                'type' => $request->input('type'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Backup failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download a backup file
     */
    public function download($fileName)
    {
        try {
            $backupPath = storage_path("app/backups/{$fileName}");

            if (! file_exists($backupPath)) {
                abort(404, 'Backup file not found');
            }

            return response()->download($backupPath);

        } catch (\Exception $e) {
            Log::error('Backup download failed', [
                'filename' => $fileName,
                'error' => $e->getMessage(),
            ]);

            abort(500, 'Failed to download backup file');
        }
    }

    /**
     * Delete a backup file
     */
    public function destroy($id)
    {
        try {
            // If it's a local backup (filename format)
            if (strpos($id, '.') !== false) {
                $backupPath = storage_path("app/backups/{$id}");

                if (file_exists($backupPath)) {
                    unlink($backupPath);

                    return response()->json([
                        'success' => true,
                        'message' => 'Local backup deleted successfully',
                    ]);
                }
            } else {
                // It's a Google Drive backup (file ID format)
                $result = $this->backupService->deleteFromGoogleDrive($id);

                if ($result) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Google Drive backup deleted successfully',
                    ]);
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Backup file not found',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Backup deletion failed', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete backup: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Authorize Google Drive access - FIXED for 403 errors
     */
    public function authorizeGoogleDrive()
    {
        try {
            // Get credentials
            $clientId = Setting::get('gdrive_client_id');
            $clientSecret = Setting::get('gdrive_client_secret');

            Log::info('Google Drive Authorization Attempt', [
                'client_id_exists' => ! empty($clientId),
                'client_secret_exists' => ! empty($clientSecret),
                'client_id_preview' => $clientId ? substr($clientId, 0, 20).'...' : 'EMPTY',
                'client_secret_preview' => $clientSecret ? substr($clientSecret, 0, 10).'...' : 'EMPTY',
            ]);

            if (! $clientId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Google Drive Client ID is missing. Please configure it in settings first.',
                ]);
            }

            if (! $clientSecret) {
                return response()->json([
                    'success' => false,
                    'message' => 'Google Drive Client Secret is missing. Please configure it in settings first.',
                ]);
            }

            // Validate Client ID format (Google Client IDs have specific format)
            if (! preg_match('/^[0-9]+-[a-zA-Z0-9_]+\.apps\.googleusercontent\.com$/', $clientId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Google Drive Client ID format. Please check your Client ID from Google Cloud Console.',
                ]);
            }

            // Create Google Client with enhanced error handling
            $client = new GoogleClient;

            try {
                $client->setClientId($clientId);
                $client->setClientSecret($clientSecret);
                $client->setRedirectUri(route('admin.backups.gdrive.callback'));
                $client->addScope(GoogleDrive::DRIVE_FILE);
                $client->setAccessType('offline');
                $client->setPrompt('consent');

                // Test if we can generate auth URL (this validates basic setup)
                $authUrl = $client->createAuthUrl();

                Log::info('Authorization URL generated successfully', [
                    'redirect_uri' => route('admin.backups.gdrive.callback'),
                    'auth_url_length' => strlen($authUrl),
                ]);

                return response()->json([
                    'success' => true,
                    'auth_url' => $authUrl,
                    'message' => 'Please complete authorization in the popup window',
                ]);

            } catch (\Exception $clientError) {
                Log::error('Google Client setup failed', [
                    'error' => $clientError->getMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Google Client setup failed. Please verify your credentials are correct: '.$clientError->getMessage(),
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Authorization process failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Authorization process failed: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Handle Google Drive OAuth callback - DEBUG VERSION
     */
    public function handleGoogleDriveCallback(Request $request)
    {
        // Log everything for debugging
        Log::info('Google Drive OAuth Callback Debug', [
            'full_url' => $request->fullUrl(),
            'method' => $request->method(),
            'all_params' => $request->all(),
            'headers' => $request->headers->all(),
            'has_code' => $request->has('code'),
            'has_error' => $request->has('error'),
        ]);

        try {
            // Check for OAuth error first
            if ($request->has('error')) {
                $error = $request->get('error');
                $errorDescription = $request->get('error_description', 'No description provided');

                Log::error('OAuth error received', [
                    'error' => $error,
                    'description' => $errorDescription,
                ]);

                return redirect()->route('admin.settings.index', ['tab' => 'backup'])
                    ->with('error', "OAuth Error: {$error} - {$errorDescription}");
            }

            // Check for authorization code
            if (! $request->has('code')) {
                Log::error('No authorization code received', [
                    'all_params' => $request->all(),
                ]);

                return redirect()->route('admin.settings.index', ['tab' => 'backup'])
                    ->with('error', 'Authorization failed: No authorization code received');
            }

            $authCode = $request->get('code');
            Log::info('Authorization code received', [
                'code_length' => strlen($authCode),
                'code_preview' => substr($authCode, 0, 20).'...',
            ]);

            // Get stored credentials
            $clientId = Setting::get('gdrive_client_id');
            $clientSecret = Setting::get('gdrive_client_secret');

            Log::info('Retrieved credentials for token exchange', [
                'client_id_exists' => ! empty($clientId),
                'client_secret_exists' => ! empty($clientSecret),
                'client_id_preview' => $clientId ? substr($clientId, 0, 30).'...' : 'MISSING',
            ]);

            if (! $clientId || ! $clientSecret) {
                return redirect()->route('admin.settings.index', ['tab' => 'backup'])
                    ->with('error', 'Google Drive credentials not found in settings');
            }

            // Create client for token exchange
            $client = new GoogleClient;
            $client->setClientId($clientId);
            $client->setClientSecret($clientSecret);

            $redirectUri = route('admin.backups.gdrive.callback');
            $client->setRedirectUri($redirectUri);

            Log::info('Token exchange setup', [
                'client_id' => $clientId,
                'redirect_uri' => $redirectUri,
                'auth_code_length' => strlen($authCode),
            ]);

            // Attempt token exchange
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

            Log::info('Token exchange response', [
                'has_error' => isset($accessToken['error']),
                'error' => $accessToken['error'] ?? null,
                'error_description' => $accessToken['error_description'] ?? null,
                'has_access_token' => isset($accessToken['access_token']),
                'has_refresh_token' => isset($accessToken['refresh_token']),
                'token_type' => $accessToken['token_type'] ?? null,
                'expires_in' => $accessToken['expires_in'] ?? null,
            ]);

            if (isset($accessToken['error'])) {
                $errorMsg = $accessToken['error'];
                $errorDesc = $accessToken['error_description'] ?? 'No description';

                Log::error('Token exchange failed with OAuth error', [
                    'error' => $errorMsg,
                    'description' => $errorDesc,
                    'client_id_used' => $clientId,
                    'redirect_uri_used' => $redirectUri,
                ]);

                // Provide specific error messages
                if ($errorMsg === 'invalid_client') {
                    $message = 'Invalid Client credentials. Please verify:
                1. Client ID and Secret are correct
                2. Redirect URI in Google Console exactly matches: '.$redirectUri.'
                3. OAuth consent screen is configured';
                } else {
                    $message = "OAuth Error: {$errorMsg} - {$errorDesc}";
                }

                return redirect()->route('admin.settings.index', ['tab' => 'backup'])
                    ->with('error', $message);
            }

            // Store the access token
            Setting::set('gdrive_access_token', json_encode($accessToken), [
                'group' => 'backup',
                'type' => 'json',
                'description' => 'Google Drive access token',
                'is_encrypted' => true,
            ]);

            // Store refresh token if available
            if (isset($accessToken['refresh_token'])) {
                Setting::set('gdrive_refresh_token', $accessToken['refresh_token'], [
                    'group' => 'backup',
                    'type' => 'text',
                    'description' => 'Google Drive refresh token',
                    'is_encrypted' => true,
                ]);

                Log::info('Refresh token stored');
            } else {
                Log::warning('No refresh token received - this might cause issues later');
            }

            // Test the connection immediately
            try {
                $client->setAccessToken($accessToken);
                $service = new GoogleDrive($client);
                $about = $service->about->get(['fields' => 'user']);

                $userEmail = $about->getUser()->getEmailAddress();

                Log::info('Google Drive authorization and test successful', [
                    'user_email' => $userEmail,
                ]);

                return redirect()->route('admin.settings.index', ['tab' => 'backup'])
                    ->with('success', 'Google Drive authorization successful! Connected as: '.$userEmail);

            } catch (\Exception $testError) {
                Log::warning('Authorization succeeded but connection test failed', [
                    'error' => $testError->getMessage(),
                ]);

                return redirect()->route('admin.settings.index', ['tab' => 'backup'])
                    ->with('warning', 'Authorization completed but connection test failed. Please test manually.');
            }

        } catch (\Exception $e) {
            Log::error('OAuth callback processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.settings.index', ['tab' => 'backup'])
                ->with('error', 'Authorization failed: '.$e->getMessage());
        }
    }

    /**
     * Test Google Drive connection
     */
    public function testGoogleDriveConnection()
    {
        try {
            $result = $this->backupService->testGoogleDriveConnection();

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Google Drive connection test failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * List Google Drive backups
     */
    public function listGoogleDriveBackups()
    {
        try {
            $backups = $this->backupService->getGoogleDriveBackups();

            return response()->json([
                'success' => true,
                'backups' => $backups,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to list Google Drive backups: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to list Google Drive backups: '.$e->getMessage(),
                'backups' => [],
            ]);
        }
    }

    /**
     * Create manual backup
     */
    public function createManualBackup(Request $request)
    {
        try {
            $request->validate([
                'type' => 'required|in:database,code,settings,both',
            ]);

            $type = $request->input('type');

            switch ($type) {
                case 'database':
                    $result = $this->backupService->createDatabaseBackup('manual');
                    break;

                case 'settings':
                    $backupPath = $this->createSettingsBackup();
                    $result = [
                        'success' => $backupPath !== false,
                        'message' => $backupPath ? 'Settings backup created successfully' : 'Settings backup failed',
                        'filename' => $backupPath ? basename($backupPath) : null,
                    ];
                    break;

                case 'code':
                    $result = $this->backupService->createCodeBackup();
                    break;

                case 'both':
                    $dbResult = $this->backupService->createDatabaseBackup('manual');
                    $settingsPath = $this->createSettingsBackup();

                    $result = [
                        'success' => $dbResult['success'] && $settingsPath !== false,
                        'message' => 'Database and settings backup '.($dbResult['success'] && $settingsPath ? 'created successfully' : 'partially failed'),
                        'details' => [
                            'database' => $dbResult,
                            'settings' => $settingsPath ? 'Success' : 'Failed',
                        ],
                    ];
                    break;

                default:
                    $result = ['success' => false, 'message' => 'Invalid backup type'];
            }

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Manual backup failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Manual backup failed: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Cleanup old backups
     */
    public function cleanupBackups()
    {
        try {
            $retentionDays = Setting::get('backup_retention_days', 30);
            $deletedCount = $this->backupService->cleanupOldBackups($retentionDays);

            return response()->json([
                'success' => true,
                'message' => "Successfully cleaned up {$deletedCount} old backup files.",
                'deleted_count' => $deletedCount,
            ]);

        } catch (\Exception $e) {
            Log::error('Backup cleanup error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Cleanup failed: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Restore database from backup
     */
    public function restoreDatabase(Request $request)
    {
        try {
            $request->validate([
                'filename' => 'required|string',
            ]);

            $filename = $request->input('filename');
            $result = $this->backupService->restoreDatabaseBackup($filename);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Database restore failed', [
                'error' => $e->getMessage(),
                'filename' => $request->input('filename'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Database restore failed: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Restore settings from backup
     */
    public function restoreSettings(Request $request)
    {
        try {
            $request->validate([
                'filename' => 'required|string',
            ]);

            $filename = $request->input('filename');
            $backupPath = storage_path("app/backups/{$filename}");

            if (! file_exists($backupPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Settings backup file not found',
                ]);
            }

            // Read and restore settings
            $settings = json_decode(file_get_contents($backupPath), true);

            if (! $settings) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid settings backup file format',
                ]);
            }

            foreach ($settings as $key => $value) {
                Setting::set($key, $value);
            }

            Log::info('Settings restored successfully', [
                'filename' => $filename,
                'settings_count' => count($settings),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Settings restored successfully from '.$filename,
            ]);

        } catch (\Exception $e) {
            Log::error('Settings restore failed', [
                'error' => $e->getMessage(),
                'filename' => $request->input('filename'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Settings restore failed: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Create settings backup using your Setting model's export functionality
     */
    private function createSettingsBackup()
    {
        try {
            $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
            $filename = "settings_backup_{$timestamp}.json";
            $backupPath = storage_path("app/backups/{$filename}");

            // Ensure backup directory exists
            if (! file_exists(dirname($backupPath))) {
                mkdir(dirname($backupPath), 0755, true);
            }

            // Use your Setting model's export functionality
            $settings = Setting::export(null, false); // Don't include encrypted settings in backup

            // Save to file
            file_put_contents($backupPath, json_encode($settings, JSON_PRETTY_PRINT));

            Log::info('Settings backup created', [
                'filename' => $filename,
                'path' => $backupPath,
                'settings_count' => count($settings),
            ]);

            return $backupPath;

        } catch (\Exception $e) {
            Log::error('Settings backup failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Upload specific backup to Google Drive
     */
    public function uploadToGoogleDrive(Request $request)
    {
        try {
            $request->validate([
                'filename' => 'required|string',
            ]);

            $filename = $request->input('filename');
            $backupPath = storage_path("app/backups/{$filename}");

            if (! file_exists($backupPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Backup file not found',
                ]);
            }

            $result = $this->backupService->uploadToGoogleDrive($backupPath, $filename);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Google Drive upload failed', [
                'error' => $e->getMessage(),
                'filename' => $request->input('filename'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Upload failed: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Get Google Drive client - delegates to service
     */
    public function getGoogleDriveClient()
    {
        return $this->backupService->getGoogleDriveClient();
    }

    /**
     * Reset Google Drive authorization
     */
    public function resetGoogleDriveAuth()
    {
        try {
            // Remove stored tokens
            Setting::remove('gdrive_access_token');
            Setting::remove('gdrive_refresh_token');

            Log::info('Google Drive authorization reset');

            return response()->json([
                'success' => true,
                'message' => 'Google Drive authorization has been reset. Please re-authorize to continue using Google Drive backups.',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to reset Google Drive authorization', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reset authorization: '.$e->getMessage(),
            ]);
        }
    }
}
