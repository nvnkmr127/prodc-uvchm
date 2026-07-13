<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ErrorHandler;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    /**
     * Display the settings page with organized tabs
     */
    public function index(Request $request)
    {
        $activeTab = $request->get('tab', 'general');
        $settingGroups = $this->getSettingGroups();

        // Get current settings
        $settings = Setting::all()->keyBy('key');

        // Validate active tab
        if (! array_key_exists($activeTab, $settingGroups)) {
            $activeTab = 'general';
        }

        return view('admin.settings.index', compact('settingGroups', 'settings', 'activeTab'));
    }

    /**
     * Update settings - FIXED VERSION
     */
    public function update(Request $request)
    {
        try {
            $settingGroups = $this->getSettingGroups();
            $activeTab = $request->get('active_tab', 'general');

            if (! isset($settingGroups[$activeTab])) {
                return back()->with('error', 'Invalid settings group.');
            }

            $currentGroup = $settingGroups[$activeTab];
            $updatedCount = 0;

            foreach ($currentGroup['fields'] as $key => $field) {
                try {
                    $value = $request->input($key);

                    // FIXED: Better password field handling
                    if ($field['type'] === 'password') {
                        // Get current setting to check if it exists
                        $currentSetting = Setting::where('key', $key)->first();

                        // Check if the submitted value is the placeholder or empty
                        $isPlaceholder = ($value === '***ENCRYPTED***');
                        $isEmpty = empty($value);

                        if ($isPlaceholder) {
                            // Skip update if placeholder value - keep current password
                            \Log::info("Skipping password field update for {$key} - placeholder value detected");

                            continue;
                        } elseif ($isEmpty && $currentSetting && ! empty($currentSetting->value)) {
                            // If current setting exists and new value is empty, ask user intention
                            // For now, skip to preserve existing password
                            \Log::info("Skipping password field update for {$key} - empty value, preserving existing");

                            continue;
                        } elseif ($isEmpty) {
                            // If no current setting and empty value, set as empty
                            $value = '';
                        } else {
                            // Encrypt the new password value
                            $value = encrypt($value);
                        }
                    } elseif ($field['type'] === 'toggle' || $field['type'] === 'boolean') {
                        $value = $request->has($key) ? '1' : '0';
                    } elseif ($field['type'] === 'multiselect') {
                        $selectedValues = $request->input($key, []);
                        $value = is_array($selectedValues) ? json_encode($selectedValues) : json_encode([]);
                    } elseif ($field['type'] === 'file') {
                        if ($request->hasFile($key)) {
                            $file = $request->file($key);
                            if (str_starts_with($file->getMimeType(), 'image/')) {
                                $image = imagecreatefromstring(file_get_contents($file->getRealPath()));
                                $width = imagesx($image);
                                $height = imagesy($image);

                                // Resize to max 150px height for optimal navbar loading
                                $newHeight = 150;
                                $newWidth = (int) ($width * ($newHeight / $height));

                                $resized = imagecreatetruecolor($newWidth, $newHeight);
                                imagealphablending($resized, false);
                                imagesavealpha($resized, true);
                                $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
                                imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);

                                imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

                                $filename = time().'_'.pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME).'.png';
                                $path = 'settings/'.$filename;
                                $fullPath = storage_path('app/public/'.$path);

                                if (! file_exists(dirname($fullPath))) {
                                    mkdir(dirname($fullPath), 0755, true);
                                }

                                imagepng($resized, $fullPath, 9); // Max compression
                                imagedestroy($image);
                                imagedestroy($resized);

                                $value = $path;
                            } else {
                                $value = $file->store('settings', 'public');
                            }
                        } else {
                            $currentSetting = Setting::where('key', $key)->first();
                            $value = $currentSetting ? $currentSetting->value : '';
                        }
                    } elseif ($value === null) {
                        $value = '';
                    }

                    // Update or create the setting
                    $setting = Setting::updateOrCreate(
                        ['key' => $key],
                        [
                            'value' => (string) $value,
                            'group' => $activeTab,
                            'type' => $field['type'],
                            'description' => $field['help'] ?? $field['label'] ?? '',
                            'is_public' => in_array($key, [
                                'app_name',
                                'app_tagline',
                                'college_name',
                                'college_logo',
                                'college_short_name',
                                'currency_symbol',
                                'currency_code',
                            ]),
                            'is_encrypted' => $field['type'] === 'password' && ! empty($value),
                        ]
                    );

                    $updatedCount++;
                    \Log::info("Setting updated: {$key}", [
                        'value' => $field['type'] === 'password' ? '[ENCRYPTED]' : $value,
                        'group' => $activeTab,
                        'is_encrypted' => $field['type'] === 'password' && ! empty($value),
                    ]);

                } catch (\Exception $e) {
                    \Log::error("Failed to update setting {$key}", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    continue;
                }
            }

            // Handle toggle fields that might not be in request (unchecked checkboxes)
            foreach ($currentGroup['fields'] as $key => $field) {
                if (($field['type'] === 'toggle' || $field['type'] === 'boolean') && ! $request->has($key)) {
                    Setting::updateOrCreate(
                        ['key' => $key],
                        [
                            'value' => '0',
                            'group' => $activeTab,
                            'type' => $field['type'],
                            'description' => $field['help'] ?? $field['label'] ?? '',
                            'is_public' => false,
                            'is_encrypted' => false,
                        ]
                    );
                    $updatedCount++;
                }
            }

            $this->clearSettingsCache();

            return back()->with('success', "Successfully updated {$updatedCount} setting(s).");

        } catch (\Exception $e) {
            \Log::error('Settings update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Failed to update settings. Please try again.');
        }
    }

    /**
     * Export settings as JSON
     */
    public function export()
    {
        try {
            $settings = Setting::all();
            $export = [
                'exported_at' => now()->toISOString(),
                'app_version' => config('app.version', '1.0.0'),
                'export_version' => '2.0',
                'settings' => $settings->mapWithKeys(function ($setting) {
                    return [
                        $setting->key => [
                            'value' => $setting->is_encrypted ? '[ENCRYPTED]' : $setting->value,
                            'group' => $setting->group,
                            'type' => $setting->type,
                            'description' => $setting->description,
                            'is_public' => $setting->is_public,
                            'is_encrypted' => $setting->is_encrypted,
                        ],
                    ];
                })->toArray(),
            ];

            return response()->json($export)
                ->header('Content-Disposition', 'attachment; filename="settings-export-'.date('Y-m-d-H-i-s').'.json"')
                ->header('Content-Type', 'application/json');
        } catch (\Exception $e) {
            return ErrorHandler::handleWebException(
                $e,
                'Settings export failed',
                'Failed to export settings. Please try again.'
            );
        }
    }

    /**
     * Import settings from JSON
     */
    public function import(Request $request)
    {
        $request->validate([
            'settings_file' => 'required|file|mimes:json|max:2048',
            'overwrite_existing' => 'nullable|boolean',
        ]);

        try {
            $file = $request->file('settings_file');
            $content = file_get_contents($file->getPathname());
            $settings = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()->with('error', 'Invalid JSON file format.');
            }

            $overwriteExisting = $request->boolean('overwrite_existing');
            $importedCount = 0;
            $skippedCount = 0;

            foreach ($settings as $key => $value) {
                // Check if setting already exists
                $existingSetting = Setting::where('key', $key)->first();

                if ($existingSetting && ! $overwriteExisting) {
                    $skippedCount++;

                    continue;
                }

                // Create or update setting
                Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value]
                );

                $importedCount++;
            }

            $message = "Settings imported successfully. {$importedCount} settings imported";
            if ($skippedCount > 0) {
                $message .= ", {$skippedCount} settings skipped (already exist)";
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            return ErrorHandler::handleWebException(
                $e,
                'Settings import failed',
                'Settings import failed. Please check the file format and try again.'
            );
        }
    }

    /**
     * Create backup - FIXED VERSION
     */
    public function createBackup()
    {
        try {
            $backupName = 'settings_backup_'.date('Y-m-d_H-i-s').'.json';
            $backupPath = storage_path('app/backups');

            // Create backups directory if it doesn't exist
            if (! file_exists($backupPath)) {
                mkdir($backupPath, 0755, true);
            }

            // Get all settings
            $settings = Setting::all()->map(function ($setting) {
                return [
                    'key' => $setting->key,
                    'value' => $setting->value,
                    'group' => $setting->group,
                    'type' => $setting->type,
                    'description' => $setting->description,
                    'is_public' => $setting->is_public,
                ];
            });

            $backupData = [
                'created_at' => now()->toISOString(),
                'app_version' => config('app.version', '1.0.0'),
                'laravel_version' => app()->version(),
                'settings_count' => $settings->count(),
                'settings' => $settings,
            ];

            $filePath = $backupPath.'/'.$backupName;
            file_put_contents($filePath, json_encode($backupData, JSON_PRETTY_PRINT));

            return response()->json([
                'success' => true,
                'message' => 'Settings backup created successfully!',
                'filename' => $backupName,
                'path' => $filePath,
                'settings_count' => $settings->count(),
                'file_size' => $this->formatBytes(filesize($filePath)),
            ]);

        } catch (\Exception $e) {
            \Log::error('Backup creation failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create backup: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Optimize database - FIXED VERSION
     */
    public function optimizeDatabase()
    {
        try {
            $results = [];

            // Remove duplicate settings
            $duplicates = DB::select('
            SELECT key, COUNT(*) as count 
            FROM settings 
            GROUP BY key 
            HAVING COUNT(*) > 1
        ');

            $duplicatesRemoved = 0;
            foreach ($duplicates as $duplicate) {
                $idsToDelete = Setting::where('key', $duplicate->key)
                    ->orderBy('updated_at', 'desc')
                    ->skip(1)
                    ->take($duplicate->count - 1)
                    ->pluck('id');

                if ($idsToDelete->count() > 0) {
                    $deletedCount = Setting::whereIn('id', $idsToDelete)->delete();
                    $duplicatesRemoved += $deletedCount;
                }
            }

            // Fix settings without groups
            $ungroupedFixed = Setting::whereNull('group')
                ->orWhere('group', '')
                ->update(['group' => 'general']);

            // Fix settings without types
            $untypedFixed = Setting::whereNull('type')
                ->orWhere('type', '')
                ->update(['type' => 'text']);

            // Optimize database tables (MySQL specific)
            try {
                if (config('database.default') === 'mysql') {
                    DB::statement('OPTIMIZE TABLE settings');
                    $results[] = 'Settings table optimized';
                }
            } catch (\Exception $e) {
                $results[] = 'Table optimization skipped: '.$e->getMessage();
            }

            // Clear settings cache
            if (function_exists('clear_settings_cache')) {
                clear_settings_cache();
            }

            return response()->json([
                'success' => true,
                'message' => 'Database optimization completed!',
                'details' => [
                    'duplicates_removed' => $duplicatesRemoved,
                    'ungrouped_fixed' => $ungroupedFixed,
                    'untyped_fixed' => $untypedFixed,
                    'operations' => $results,
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Database optimization failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Database optimization failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test email configuration
     */
    public function testEmail(Request $request)
    {
        $request->validate([
            'test_email' => 'required|email',
        ]);

        try {
            $testEmail = $request->input('test_email');

            // Get email settings
            $emailSettings = Setting::whereIn('key', [
                'mail_driver',
                'mail_host',
                'mail_port',
                'mail_username',
                'mail_password',
                'mail_encryption',
                'mail_from_address',
                'mail_from_name',
            ])->pluck('value', 'key');

            // Configure mail settings dynamically
            config([
                'mail.default' => $emailSettings['mail_driver'] ?? 'smtp',
                'mail.mailers.smtp.host' => $emailSettings['mail_host'] ?? 'localhost',
                'mail.mailers.smtp.port' => $emailSettings['mail_port'] ?? 587,
                'mail.mailers.smtp.username' => $emailSettings['mail_username'] ?? '',
                'mail.mailers.smtp.password' => $emailSettings['mail_password'] ?? '',
                'mail.mailers.smtp.encryption' => $emailSettings['mail_encryption'] ?? 'tls',
                'mail.from.address' => $emailSettings['mail_from_address'] ?? 'noreply@college.edu',
                'mail.from.name' => $emailSettings['mail_from_name'] ?? 'College Management System',
            ]);

            // Send test email
            Mail::raw('This is a test email from your College Management System. Email configuration is working correctly!', function ($message) use ($testEmail, $emailSettings) {
                $message->to($testEmail)
                    ->subject('Test Email - College Management System')
                    ->from(
                        $emailSettings['mail_from_address'] ?? 'noreply@college.edu',
                        $emailSettings['mail_from_name'] ?? 'College Management System'
                    );
            });

            return response()->json([
                'success' => true,
                'message' => 'Test email sent successfully to '.$testEmail,
            ]);

        } catch (\Exception $e) {
            \Log::error('Test email failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to send test email: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Clear application cache - FIXED VERSION
     */
    public function clearCache()
    {
        try {
            $results = [];

            // Clear various caches with error handling
            try {
                Artisan::call('cache:clear');
                $results[] = 'Application cache cleared';
            } catch (\Exception $e) {
                $results[] = 'Application cache: '.$e->getMessage();
            }

            try {
                Artisan::call('config:clear');
                $results[] = 'Configuration cache cleared';
            } catch (\Exception $e) {
                $results[] = 'Configuration cache: '.$e->getMessage();
            }

            try {
                Artisan::call('route:clear');
                $results[] = 'Route cache cleared';
            } catch (\Exception $e) {
                $results[] = 'Route cache: '.$e->getMessage();
            }

            try {
                Artisan::call('view:clear');
                $results[] = 'View cache cleared';
            } catch (\Exception $e) {
                $results[] = 'View cache: '.$e->getMessage();
            }

            // Clear settings cache using helper function
            try {
                if (function_exists('clear_settings_cache')) {
                    clear_settings_cache();
                    $results[] = 'Settings cache cleared';
                }
            } catch (\Exception $e) {
                $results[] = 'Settings cache: '.$e->getMessage();
            }

            return response()->json([
                'success' => true,
                'message' => 'Cache clearing completed!',
                'details' => $results,
            ]);

        } catch (\Exception $e) {
            \Log::error('Cache clear failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Seed default settings - FIXED VERSION
     */
    /**
     * Seed default settings - FIXED VERSION
     */
    public function seedDefaults()
    {
        try {
            $settingGroups = $this->getSettingGroups();
            $created = 0;
            $updated = 0;

            foreach ($settingGroups as $groupKey => $groupData) {
                foreach ($groupData['fields'] as $key => $field) {
                    if (! isset($field['default'])) {
                        continue;
                    }

                    $value = $field['default'];
                    $setting = Setting::where('key', $key)->first();

                    if (! $setting) {
                        Setting::create([
                            'key' => $key,
                            'value' => (string) $value,
                            'group' => $groupKey,
                            'type' => $field['type'],
                            'description' => $field['help'] ?? $field['label'] ?? '',
                            'is_public' => in_array($key, [
                                'app_name',
                                'app_tagline',
                                'college_name',
                                'college_logo',
                                'college_short_name',
                                'currency_symbol',
                                'currency_code',
                            ]),
                            'is_encrypted' => false,
                        ]);
                        $created++;
                    } elseif (empty($setting->value) && $setting->value !== '0') {
                        $setting->update(['value' => (string) $value]);
                        $updated++;
                    }
                }
            }

            // Clear settings cache
            if (function_exists('clear_settings_cache')) {
                clear_settings_cache();
            }

            return response()->json([
                'success' => true,
                'message' => "Default settings seeded successfully! Created: {$created}, Updated: {$updated}",
                'details' => [
                    'created' => $created,
                    'updated' => $updated,
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Seed defaults failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to seed defaults: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reset settings group to defaults
     */
    public function resetDefaults(Request $request)
    {
        $request->validate([
            'group' => 'required|string',
        ]);

        try {
            $group = $request->input('group');
            $settingGroups = $this->getSettingGroups();

            if (! isset($settingGroups[$group])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid settings group specified',
                ], 422);
            }

            $currentGroup = $settingGroups[$group];
            $resetCount = 0;

            // Reset settings to defaults from getSettingGroups configuration
            foreach ($currentGroup['fields'] as $key => $field) {
                if (isset($field['default'])) {
                    Setting::updateOrCreate(
                        ['key' => $key],
                        [
                            'value' => (string) $field['default'],
                            'group' => $group,
                            'type' => $field['type'],
                            'description' => $field['help'] ?? $field['label'] ?? '',
                            'is_public' => in_array($key, [
                                'app_name',
                                'app_tagline',
                                'college_name',
                                'college_logo',
                                'college_short_name',
                                'currency_symbol',
                                'currency_code',
                            ]),
                            'is_encrypted' => false,
                        ]
                    );
                    $resetCount++;
                }
            }

            $this->clearSettingsCache();

            return response()->json([
                'success' => true,
                'message' => "Settings group '{$group}' reset to defaults. {$resetCount} settings updated.",
            ]);

        } catch (\Exception $e) {
            \Log::error('Group reset failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to reset settings group: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle maintenance mode
     */
    public function toggleMaintenance()
    {
        try {
            if (app()->isDownForMaintenance()) {
                Artisan::call('up');
                $mode = false;
                $message = 'Maintenance mode disabled';
            } else {
                Artisan::call('down', ['--secret' => 'admin-access']);
                $mode = true;
                $message = 'Maintenance mode enabled';
            }

            return response()->json([
                'success' => true,
                'mode' => $mode,
                'message' => $message,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle maintenance mode: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Complete system information method that matches the view
     */
    public function systemInfo(Request $request)
    {
        try {
            // Get settings safely
            $settings = Setting::pluck('value', 'key')->toArray();

            $systemInfo = [
                'application' => [
                    'name' => $settings['app_name'] ?? $settings['college_name'] ?? 'College Management System',
                    'version' => config('app.version', '1.0.0'),
                    'environment' => config('app.env'),
                    'debug_mode' => config('app.debug') ? 'Enabled' : 'Disabled',
                    'timezone' => $settings['timezone'] ?? config('app.timezone'),
                    'url' => config('app.url'),
                    'maintenance_mode' => $settings['maintenance_mode'] ?? '0',
                ],
                'server' => [
                    'php_version' => PHP_VERSION,
                    'laravel_version' => app()->version(),
                    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                    'operating_system' => PHP_OS,
                    'server_ip' => $_SERVER['SERVER_ADDR'] ?? $_SERVER['LOCAL_ADDR'] ?? 'Unknown',
                    'memory_limit' => ini_get('memory_limit'),
                    'memory_usage' => memory_get_usage(true),
                    'max_execution_time' => ini_get('max_execution_time'),
                    'upload_max_filesize' => ini_get('upload_max_filesize'),
                    'post_max_size' => ini_get('post_max_size'),
                    'disk_space' => $this->getDiskUsage(),
                ],
                'database' => [
                    'connection' => config('database.default'),
                    'host' => config('database.connections.'.config('database.default').'.host'),
                    'database' => config('database.connections.'.config('database.default').'.database'),
                    'driver' => config('database.connections.'.config('database.default').'.driver'),
                    'total_tables' => $this->getDatabaseTableCount(),
                    'total_records' => $this->getDatabaseRecordCount(),
                ],
                'cache' => [
                    'default_driver' => config('cache.default'),
                    'prefix' => config('cache.prefix'),
                    'status' => $this->checkCache()['status'] ? 'Working' : 'Failed',
                ],
                'queue' => [
                    'default_connection' => config('queue.default'),
                    'status' => 'Configured',
                ],
                'mail' => [
                    'default_mailer' => config('mail.default'),
                    'from_address' => config('mail.from.address'),
                    'from_name' => config('mail.from.name'),
                    'status' => $this->checkEmailConfiguration()['status'] ? 'Configured' : 'Not Configured',
                ],
                'college' => [
                    'name' => $settings['college_name'] ?? 'Not Set',
                    'short_name' => $settings['college_short_name'] ?? 'Not Set',
                    'email' => $settings['college_email'] ?? 'Not Set',
                    'phone' => $settings['college_phone'] ?? 'Not Set',
                    'address' => $settings['college_address'] ?? 'Not Set',
                ],
                'academic' => [
                    'current_year' => $settings['current_academic_year'] ?? date('Y').'-'.(date('Y') + 1),
                    'enrollment_prefix' => $settings['enrollment_prefix'] ?? 'STD',
                    'semester_system' => $settings['semester_system'] ?? '1',
                ],
                'financial' => [
                    'currency_symbol' => $settings['currency_symbol'] ?? '₹',
                    'currency_code' => $settings['currency_code'] ?? 'INR',
                    'tax_rate' => $settings['tax_rate'] ?? '0',
                    'late_fee_percentage' => $settings['late_fee_percentage'] ?? '5',
                ],
                'extensions' => $this->getRequiredExtensions(),
                'statistics' => [
                    'total_settings' => count($settings),
                    'last_backup' => $this->getLastBackupInfo(),
                    'uptime' => $this->getSystemUptime(),
                ],
            ];

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $systemInfo,
                ]);
            }

            return view('admin.settings.system-info', compact('systemInfo'));

        } catch (\Exception $e) {
            \Log::error('System info error: '.$e->getMessage());

            // Return safe fallback data
            $systemInfo = [
                'application' => ['name' => 'College Management System', 'version' => '1.0.0'],
                'server' => ['php_version' => PHP_VERSION],
                'database' => ['driver' => 'mysql'],
                'cache' => ['status' => 'Unknown'],
                'queue' => ['status' => 'Unknown'],
                'mail' => ['status' => 'Unknown'],
                'college' => ['name' => 'Not Set'],
                'academic' => ['current_year' => date('Y').'-'.(date('Y') + 1)],
                'financial' => ['currency_symbol' => '₹'],
                'extensions' => [],
                'statistics' => ['total_settings' => 0],
                'error' => $e->getMessage(),
            ];

            return view('admin.settings.system-info', compact('systemInfo'));
        }
    }

    /**
     * Get database table count
     */
    private function getDatabaseTableCount()
    {
        try {
            $database = config('database.connections.'.config('database.default').'.database');
            $tables = DB::select('SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = ?', [$database]);

            return $tables[0]->count ?? 0;
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    /**
     * Get last backup info
     */
    private function getLastBackupInfo()
    {
        try {
            $backupPath = base_path('storage/backups');
            if (! is_dir($backupPath)) {
                return 'No backups found';
            }

            $files = glob($backupPath.'/backup_*.sql');
            if (empty($files)) {
                return 'No backups found';
            }

            $lastBackup = max($files);
            $lastBackupTime = filemtime($lastBackup);

            return date('Y-m-d H:i:s', $lastBackupTime);
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    /**
     * Run system health check
     */
    public function healthCheck(Request $request)
    {
        if ($request->isMethod('get') && ! $request->ajax()) {
            return redirect()->route('admin.settings.system-info');
        }

        try {
            $checks = [];
            $passed = 0;
            $total = 0;

            // Database check
            $total++;
            try {
                DB::connection()->getPdo();
                $checks[] = ['name' => 'Database', 'status' => true, 'message' => 'Connected'];
                $passed++;
            } catch (\Exception $e) {
                $checks[] = ['name' => 'Database', 'status' => false, 'message' => $e->getMessage()];
            }

            // Cache check
            $total++;
            try {
                Cache::put('health_check', 'test', 5);
                $test = Cache::get('health_check');
                if ($test === 'test') {
                    $checks[] = ['name' => 'Cache', 'status' => true, 'message' => 'Working'];
                    $passed++;
                } else {
                    $checks[] = ['name' => 'Cache', 'status' => false, 'message' => 'Not working'];
                }
            } catch (\Exception $e) {
                $checks[] = ['name' => 'Cache', 'status' => false, 'message' => $e->getMessage()];
            }

            // Storage check
            $total++;
            try {
                $testFile = storage_path('framework/cache/health_check.txt');
                file_put_contents($testFile, 'test');
                $content = file_get_contents($testFile);
                unlink($testFile);

                if ($content === 'test') {
                    $checks[] = ['name' => 'Storage', 'status' => true, 'message' => 'Writable'];
                    $passed++;
                } else {
                    $checks[] = ['name' => 'Storage', 'status' => false, 'message' => 'Not writable'];
                }
            } catch (\Exception $e) {
                $checks[] = ['name' => 'Storage', 'status' => false, 'message' => $e->getMessage()];
            }

            $status = $passed === $total ? 'healthy' : 'issues';

            return response()->json([
                'status' => $status,
                'summary' => [
                    'passed' => $passed,
                    'total_checks' => $total,
                ],
                'checks' => $checks,
                'message' => "System health check completed. {$passed}/{$total} checks passed.",
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Health check failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reset specific group to defaults
     */
    public function resetGroupToDefaults(Request $request, $group)
    {
        $settingGroups = $this->getSettingGroups();

        if (! isset($settingGroups[$group])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid settings group',
            ], 400);
        }

        try {
            DB::beginTransaction();

            $resetCount = 0;
            foreach ($settingGroups[$group]['fields'] as $key => $field) {
                if (isset($field['default'])) {
                    Setting::updateOrCreate(['key' => $key], ['value' => $field['default']]);
                    $resetCount++;
                }
            }

            DB::commit();
            $this->clearSettingsCache();

            return response()->json([
                'success' => true,
                'message' => "Reset {$resetCount} settings to defaults for ".$settingGroups[$group]['title'],
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Failed to reset settings: '.$e->getMessage(),
            ], 500);
        }
    }

    // API ENDPOINTS FOR SETTINGS MANAGEMENT

    /**
     * Get public settings (safe for frontend)
     */
    public function getPublicSettings(Request $request)
    {
        try {
            $publicSettings = Setting::where('is_public', true)->pluck('value', 'key');

            return response()->json([
                'success' => true,
                'data' => $publicSettings,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get public settings: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get specific setting value
     */
    public function getSetting(Request $request, $key)
    {
        try {
            $setting = Setting::where('key', $key)->first();

            if (! $setting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Setting not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'key' => $key,
                    'value' => $setting->is_encrypted ? '[ENCRYPTED]' : $setting->value,
                    'type' => $setting->type,
                    'group' => $setting->group,
                    'description' => $setting->description,
                    'is_public' => $setting->is_public,
                    'is_encrypted' => $setting->is_encrypted,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting setting: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * IMPROVED: Update specific setting via AJAX with better password handling
     */
    public function updateSetting(Request $request, $key)
    {
        $request->validate([
            'value' => 'nullable',
        ]);

        try {
            // Validate the setting value
            $validation = $this->validateSettingByKey($key, $request->value);

            if (! $validation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $validation['message'],
                ], 422);
            }

            $value = $request->value;

            // Check if this is a password field
            $settingGroups = $this->getSettingGroups();
            $isPassword = false;
            foreach ($settingGroups as $group) {
                if (isset($group['fields'][$key]) && $group['fields'][$key]['type'] === 'password') {
                    $isPassword = true;
                    break;
                }
            }

            // IMPROVED: Better password handling for AJAX updates
            if ($isPassword) {
                $currentSetting = Setting::where('key', $key)->first();

                if ($value === '***ENCRYPTED***') {
                    // Don't update if placeholder
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot save placeholder value. Please enter the actual secret.',
                    ], 422);
                } elseif (empty($value) && $currentSetting && ! empty($currentSetting->value)) {
                    // Confirm clearing existing password
                    return response()->json([
                        'success' => false,
                        'message' => 'To clear existing secret, send explicit confirmation.',
                        'requires_confirmation' => true,
                    ], 422);
                } elseif (! empty($value)) {
                    $value = encrypt($value);
                }
            }

            Setting::updateOrCreate(['key' => $key], [
                'value' => $value,
                'is_encrypted' => $isPassword && ! empty($value),
            ]);

            $this->clearSettingsCache();

            return response()->json([
                'success' => true,
                'message' => 'Setting updated successfully',
                'data' => [
                    'key' => $key,
                    'value' => $isPassword ? '[ENCRYPTED]' : $value,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Setting update failed', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error updating setting: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete specific setting
     */
    public function deleteSetting(Request $request, $key)
    {
        try {
            $deleted = Setting::where('key', $key)->delete();

            if ($deleted) {
                $this->clearSettingsCache();

                return response()->json([
                    'success' => true,
                    'message' => 'Setting deleted successfully',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Setting not found',
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting setting: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get statistics about settings
     */
    public function getStatistics()
    {
        try {
            $stats = [
                'total_settings' => Setting::count(),
                'public_settings' => Setting::where('is_public', true)->count(),
                'encrypted_settings' => Setting::where('is_encrypted', true)->count(),
                'groups_count' => Setting::distinct()->count('group'),
                'by_group' => Setting::selectRaw('`group`, COUNT(*) as count')
                    ->groupBy('group')
                    ->pluck('count', 'group'),
                'by_type' => Setting::selectRaw('type, COUNT(*) as count')
                    ->groupBy('type')
                    ->pluck('count', 'type'),
                'last_updated' => Setting::latest('updated_at')->value('updated_at'),
                'created_today' => Setting::whereDate('created_at', today())->count(),
                'updated_today' => Setting::whereDate('updated_at', today())->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting statistics: '.$e->getMessage(),
            ], 500);
        }
    }

    // PRIVATE HELPER METHODS

    /**
     * Check database connectivity
     */
    private function checkDatabase()
    {
        try {
            DB::connection()->getPdo();
            $databaseName = DB::connection()->getDatabaseName();

            return ['status' => true, 'message' => "Database connection successful to: {$databaseName}"];
        } catch (\Exception $e) {
            return ['status' => false, 'message' => 'Database connection failed: '.$e->getMessage()];
        }
    }

    /**
     * Check cache functionality
     */
    private function checkCache()
    {
        try {
            $testKey = 'health_check_'.time();
            $testValue = 'test_value';

            Cache::put($testKey, $testValue, 60);
            $retrievedValue = Cache::get($testKey);
            Cache::forget($testKey);

            if ($retrievedValue === $testValue) {
                return ['status' => true, 'message' => 'Cache is working properly.'];
            }

            return ['status' => false, 'message' => 'Cache verification failed.'];
        } catch (\Exception $e) {
            return ['status' => false, 'message' => 'Cache check failed: '.$e->getMessage()];
        }
    }

    /**
     * Check file permissions - FIXED
     */
    private function checkFilePermissions()
    {
        try {
            $paths = [
                base_path('storage/framework/'),
                base_path('storage/logs/'),
                base_path('bootstrap/cache/'),
            ];

            foreach ($paths as $path) {
                if (! file_exists($path)) {
                    return ['status' => false, 'message' => "Directory does not exist: {$path}"];
                }

                if (! is_writable($path)) {
                    return ['status' => false, 'message' => "Directory not writable: {$path}"];
                }
            }

            return ['status' => true, 'message' => 'Required directories are writable.'];

        } catch (\Exception $e) {
            return ['status' => false, 'message' => 'Permission check failed: '.$e->getMessage()];
        }
    }

    /**
     * Check storage functionality
     */
    private function checkStorage()
    {
        try {
            $testFile = 'health_check_'.time().'.txt';
            $testContent = 'Health check test file';

            Storage::disk('local')->put($testFile, $testContent);

            if (Storage::disk('local')->exists($testFile)) {
                $retrievedContent = Storage::disk('local')->get($testFile);
                Storage::disk('local')->delete($testFile);

                if ($retrievedContent === $testContent) {
                    return ['status' => true, 'message' => 'Local storage is working properly.'];
                }
            }

            return ['status' => false, 'message' => 'Storage verification failed.'];
        } catch (\Exception $e) {
            return ['status' => false, 'message' => 'Storage check failed: '.$e->getMessage()];
        }
    }

    /**
     * Get disk usage - FIXED
     */
    private function getDiskUsage()
    {
        try {
            $path = base_path('storage');

            if (! function_exists('disk_total_space') || ! function_exists('disk_free_space')) {
                return ['error' => 'Disk functions not available on this system'];
            }

            $totalSpace = disk_total_space($path);
            $freeSpace = disk_free_space($path);

            if ($totalSpace === false || $freeSpace === false) {
                return ['error' => 'Unable to get disk space information'];
            }

            $usedSpace = $totalSpace - $freeSpace;
            $usagePercentage = round(($usedSpace / $totalSpace) * 100, 2);

            return [
                'total' => $this->formatBytes($totalSpace),
                'used' => $this->formatBytes($usedSpace),
                'free' => $this->formatBytes($freeSpace),
                'usage_percentage' => $usagePercentage.'%',
            ];
        } catch (\Exception $e) {
            return ['error' => 'Could not retrieve disk usage: '.$e->getMessage()];
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision).' '.$units[$i];
    }
}
