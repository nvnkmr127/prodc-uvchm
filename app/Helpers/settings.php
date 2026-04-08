<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

if (! function_exists('settings_table_available')) {
    /**
     * Check if settings table can be queried safely.
     */
    function settings_table_available(): bool
    {
        static $isAvailable = null;

        if ($isAvailable !== null) {
            return $isAvailable;
        }

        try {
            $isAvailable = Schema::hasTable('settings');
        } catch (\Throwable $e) {
            $isAvailable = false;
        }

        return $isAvailable;
    }
}

if (! function_exists('setting')) {
    /**
     * Get a setting value by key
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    function setting($key, $default = null)
    {
        try {
            // Check if settings table exists
            if (! settings_table_available()) {
                return $default;
            }

            $cacheKey = "setting_{$key}";

            return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
                $setting = Setting::where('key', $key)->first();

                if (! $setting) {
                    return $default;
                }

                return $setting->getTypedValue();
            });

        } catch (\Throwable $e) {
            \Log::warning("Settings helper error for key '{$key}': ".$e->getMessage());

            return $default;
        }
    }
}

if (! function_exists('settings')) {
    /**
     * Get multiple settings at once
     *
     * @return array
     */
    function settings(array $keys, array $defaults = [])
    {
        $result = [];

        foreach ($keys as $key) {
            $default = $defaults[$key] ?? null;
            $result[$key] = setting($key, $default);
        }

        return $result;
    }
}

if (! function_exists('update_setting')) {
    /**
     * Update a setting value
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  string  $group
     * @param  string  $type
     * @return bool
     */
    function update_setting($key, $value, $group = 'general', $type = 'text')
    {
        try {
            if (! settings_table_available()) {
                return false;
            }

            // Handle different value types
            if (is_array($value)) {
                $value = json_encode($value);
                $type = 'json';
            } elseif (is_bool($value)) {
                $value = $value ? '1' : '0';
                $type = 'boolean';
            }

            $setting = Setting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'group' => $group,
                    'type' => $type,
                ]
            );

            // Clear cache
            Cache::forget("setting_{$key}");
            clear_settings_cache();

            return true;

        } catch (\Exception $e) {
            \Log::error("Failed to update setting '{$key}': ".$e->getMessage());

            return false;
        }
    }
}

if (! function_exists('clear_settings_cache')) {
    /**
     * Clear all settings cache
     *
     * @return void
     */
    function clear_settings_cache()
    {
        try {
            // Clear main cache keys
            Cache::forget('all_settings');
            Cache::forget('public_settings');

            // Clear individual setting caches if we can get them
            if (settings_table_available()) {
                $settings = Setting::pluck('key');
                foreach ($settings as $key) {
                    Cache::forget("setting_{$key}");
                }
            }

            // Clear group caches
            $groups = ['general', 'college', 'academic', 'financial', 'email', 'attendance'];
            foreach ($groups as $group) {
                Cache::forget("settings_group_{$group}");
            }

            // Clear tagged cache if supported
            try {
                Cache::tags(['settings'])->flush();
            } catch (\Exception $e) {
                // Cache driver might not support tags
            }

        } catch (\Exception $e) {
            \Log::warning('Failed to clear settings cache: '.$e->getMessage());
        }
    }
}

if (! function_exists('public_settings')) {
    /**
     * Get all public settings (for frontend use)
     *
     * @return array
     */
    function public_settings()
    {
        try {
            if (! settings_table_available()) {
                return [];
            }

            return Cache::remember('public_settings', 3600, function () {
                return Setting::where('is_public', true)
                    ->pluck('value', 'key')
                    ->toArray();
            });

        } catch (\Exception $e) {
            \Log::warning('Public settings error: '.$e->getMessage());

            return [];
        }
    }
}

if (! function_exists('get_settings_by_group')) {
    /**
     * Get all settings for a specific group
     *
     * @param  string  $group
     * @return array
     */
    function get_settings_by_group($group)
    {
        try {
            if (! settings_table_available()) {
                return [];
            }

            return Cache::remember("settings_group_{$group}", 3600, function () use ($group) {
                return Setting::where('group', $group)
                    ->pluck('value', 'key')
                    ->toArray();
            });

        } catch (\Exception $e) {
            \Log::warning("Group settings error for '{$group}': ".$e->getMessage());

            return [];
        }
    }
}

if (! function_exists('setting_exists')) {
    /**
     * Check if a setting exists
     *
     * @param  string  $key
     * @return bool
     */
    function setting_exists($key)
    {
        try {
            if (! settings_table_available()) {
                return false;
            }

            return Setting::where('key', $key)->exists();

        } catch (\Exception $e) {
            return false;
        }
    }
}

if (! function_exists('format_setting_value')) {
    /**
     * Format setting value for display
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  string  $type
     * @return string
     */
    function format_setting_value($key, $value, $type = 'text')
    {
        if ($value === null || $value === '') {
            return '<em class="text-muted">Not set</em>';
        }

        switch ($type) {
            case 'boolean':
            case 'toggle':
                return $value ? '<span class="badge badge-success">Yes</span>' : '<span class="badge badge-secondary">No</span>';

            case 'currency':
                $symbol = setting('currency_symbol', '₹');

                return $symbol.number_format($value, 2);

            case 'percentage':
                return $value.'%';

            case 'array':
            case 'multiselect':
                $array = is_string($value) ? json_decode($value, true) : $value;

                return is_array($array) ? implode(', ', $array) : $value;

            case 'date':
                $format = setting('date_format', 'd-m-Y');

                return \Carbon\Carbon::parse($value)->format($format);

            case 'file':
                return $value ? '<a href="'.asset('storage/'.$value).'" target="_blank">View File</a>' : 'No file';

            case 'password':
                return str_repeat('•', min(8, strlen($value)));

            case 'email':
                return '<a href="mailto:'.$value.'">'.$value.'</a>';

            case 'url':
                return '<a href="'.$value.'" target="_blank">'.\Str::limit($value, 30).'</a>';

            default:
                return (string) $value;
        }
    }
}

if (! function_exists('validate_setting_value')) {
    /**
     * Validate setting value based on type
     *
     * @param  mixed  $value
     * @param  string  $type
     * @param  array  $options
     * @return array
     */
    function validate_setting_value($value, $type, $options = [])
    {
        switch ($type) {
            case 'email':
                if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return ['valid' => false, 'message' => 'Invalid email format'];
                }
                break;

            case 'url':
                if (! filter_var($value, FILTER_VALIDATE_URL)) {
                    return ['valid' => false, 'message' => 'Invalid URL format'];
                }
                break;

            case 'number':
            case 'integer':
                if (! is_numeric($value)) {
                    return ['valid' => false, 'message' => 'Value must be numeric'];
                }

                if (isset($options['min']) && $value < $options['min']) {
                    return ['valid' => false, 'message' => "Value must be at least {$options['min']}"];
                }
                if (isset($options['max']) && $value > $options['max']) {
                    return ['valid' => false, 'message' => "Value must be at most {$options['max']}"];
                }
                break;

            case 'select':
                if (isset($options['allowed']) && ! in_array($value, $options['allowed'])) {
                    return ['valid' => false, 'message' => 'Invalid option selected'];
                }
                break;

            case 'boolean':
            case 'toggle':
                if (! in_array($value, ['0', '1', 0, 1, true, false], true)) {
                    return ['valid' => false, 'message' => 'Invalid boolean value'];
                }
                break;
        }

        return ['valid' => true, 'message' => 'Valid'];
    }
}

if (! function_exists('backup_settings')) {
    /**
     * Create a backup of all application settings
     *
     * @return string|false The backup file path on success, false on failure
     */
    function backup_settings()
    {
        try {
            // Get all settings
            $settings = Setting::all();

            $backupData = [
                'version' => '1.0',
                'created_at' => now()->toISOString(),
                'app_name' => config('app.name'),
                'backup_type' => 'settings',
                'settings_count' => $settings->count(),
                'settings' => $settings->map(function ($setting) {
                    return [
                        'key' => $setting->key,
                        'value' => $setting->value,
                        'type' => $setting->type ?? 'text',
                        'group' => $setting->group ?? 'general',
                        'description' => $setting->description,
                        'is_public' => $setting->is_public ?? false,
                        'is_encrypted' => $setting->is_encrypted ?? false,
                        'created_at' => $setting->created_at?->toISOString(),
                        'updated_at' => $setting->updated_at?->toISOString(),
                    ];
                })->toArray(),
            ];

            // Create backup directory if it doesn't exist
            $backupDir = storage_path('app/backups');
            if (! is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            // Generate filename
            $filename = 'settings-backup-'.date('Y-m-d-H-i-s').'.json';
            $filepath = $backupDir.'/'.$filename;

            // Write backup file
            $result = file_put_contents($filepath, json_encode($backupData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            if ($result === false) {
                \Log::error('Failed to write settings backup file: '.$filepath);

                return false;
            }

            \Log::info('Settings backup created successfully', [
                'filename' => $filename,
                'filepath' => $filepath,
                'settings_count' => $settings->count(),
                'file_size' => filesize($filepath),
            ]);

            return $filepath;

        } catch (\Exception $e) {
            \Log::error('Settings backup failed: '.$e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }
}

if (! function_exists('restore_settings')) {
    /**
     * Restore settings from a backup file
     *
     * @param  string  $filePath  Path to the backup file
     * @return bool Success status
     */
    function restore_settings($filePath)
    {
        try {
            if (! file_exists($filePath)) {
                \Log::error('Settings backup file not found: '.$filePath);

                return false;
            }

            $content = file_get_contents($filePath);
            if ($content === false) {
                \Log::error('Failed to read settings backup file: '.$filePath);

                return false;
            }

            $backupData = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                \Log::error('Invalid JSON in settings backup file: '.json_last_error_msg());

                return false;
            }

            if (! isset($backupData['settings']) || ! is_array($backupData['settings'])) {
                \Log::error('Invalid backup format: settings array not found');

                return false;
            }

            $restored = 0;
            $skipped = 0;

            \DB::beginTransaction();

            foreach ($backupData['settings'] as $settingData) {
                if (! isset($settingData['key']) || empty($settingData['key'])) {
                    $skipped++;

                    continue;
                }

                try {
                    Setting::updateOrCreate(
                        ['key' => $settingData['key']],
                        [
                            'value' => $settingData['value'] ?? '',
                            'type' => $settingData['type'] ?? 'text',
                            'group' => $settingData['group'] ?? 'general',
                            'description' => $settingData['description'] ?? null,
                            'is_public' => $settingData['is_public'] ?? false,
                            'is_encrypted' => $settingData['is_encrypted'] ?? false,
                        ]
                    );
                    $restored++;
                } catch (\Exception $e) {
                    \Log::warning("Failed to restore setting '{$settingData['key']}': ".$e->getMessage());
                    $skipped++;
                }
            }

            \DB::commit();

            // Clear settings cache
            clear_settings_cache();

            \Log::info('Settings restored successfully', [
                'file' => basename($filePath),
                'restored' => $restored,
                'skipped' => $skipped,
                'total' => count($backupData['settings']),
            ]);

            return true;

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Settings restore failed: '.$e->getMessage(), [
                'file' => $filePath,
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }
}
