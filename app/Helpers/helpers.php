<?php

// app/helpers.php - Add these functions to your helpers file or create a new one

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

if (! function_exists('settings_table_available')) {
    /**
     * Check if settings table can be queried safely.
     *
     * Uses per-request static caching to avoid repeated DB metadata queries
     * when the database is unavailable or misconfigured.
     */
    function settings_table_available(): bool
    {
        static $isAvailable = null;
        static $hasLoggedError = false;

        if ($isAvailable !== null) {
            return $isAvailable;
        }

        try {
            $isAvailable = Schema::hasTable('settings');
        } catch (\Throwable $e) {
            $isAvailable = false;

            if (! $hasLoggedError) {
                \Log::warning('Settings table availability check failed: '.$e->getMessage());
                $hasLoggedError = true;
            }
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

                // Handle different data types
                $value = $setting->value;

                switch ($setting->type) {
                    case 'boolean':
                    case 'toggle':
                        return filter_var($value, FILTER_VALIDATE_BOOLEAN);

                    case 'integer':
                    case 'number':
                        return is_numeric($value) ? (int) $value : $default;

                    case 'float':
                    case 'decimal':
                        return is_numeric($value) ? (float) $value : $default;

                    case 'array':
                    case 'multiselect':
                        return is_string($value) ? json_decode($value, true) : $value;

                    case 'json':
                        return is_string($value) ? json_decode($value, true) : $value;

                    default:
                        return $value;
                }
            });

        } catch (\Throwable $e) {
            // Log error but don't break the application
            \Log::warning("Settings helper error for key '{$key}': ".$e->getMessage());

            return $default;
        }
    }
}
if (! function_exists('get_payment_status_badge')) {
    function get_payment_status_badge($status)
    {
        $badges = [
            'paid' => '<span class="badge badge-success">Paid</span>',
            'unpaid' => '<span class="badge badge-danger">Unpaid</span>',
            'partial' => '<span class="badge badge-warning">Partial</span>',
            'overdue' => '<span class="badge badge-dark">Overdue</span>',
        ];

        return $badges[$status] ?? '<span class="badge badge-secondary">Unknown</span>';
    }
}

if (! function_exists('get_defaulter_category_badge')) {
    function get_defaulter_category_badge($category)
    {
        $badges = [
            'mild' => '<span class="badge badge-info">Mild</span>',
            'moderate' => '<span class="badge badge-warning">Moderate</span>',
            'severe' => '<span class="badge badge-danger">Severe</span>',
            'chronic' => '<span class="badge badge-dark">Chronic</span>',
        ];

        return $badges[$category] ?? '<span class="badge badge-secondary">Unknown</span>';
    }
}

if (! function_exists('format_overdue_days')) {
    function format_overdue_days($days)
    {
        if ($days < 0) {
            return abs($days).' days left';
        } elseif ($days == 0) {
            return 'Due today';
        } else {
            return $days.' days overdue';
        }
    }
}

if (! function_exists('get_fee_type_color')) {
    function get_fee_type_color($feeType)
    {
        $colors = [
            'tuition_fee' => 'primary',
            'uniform_fee' => 'success',
            'library_fee' => 'info',
            'lab_fee' => 'warning',
            'exam_fee' => 'danger',
            'transport_fee' => 'secondary',
        ];

        return $colors[$feeType] ?? 'dark';
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

if (! function_exists('app_setting')) {
    /**
     * Get application-specific settings with proper defaults
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    function app_setting($key, $default = null)
    {
        $appDefaults = [
            'app_name' => config('app.name', 'College Management System'),
            'app_tagline' => 'Empowering Education Excellence',
            'timezone' => config('app.timezone', 'Asia/Kolkata'),
            'date_format' => 'd-m-Y',
            'currency_symbol' => '₹',
            'currency_code' => 'INR',
            'academic_year_start' => 7, // July
            'minimum_attendance_percentage' => 75,
            'attendance_grace_period' => 10,
        ];

        $defaultValue = $appDefaults[$key] ?? $default;

        return setting($key, $defaultValue);
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

            Setting::updateOrCreate(
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
            if (! settings_table_available()) {
                Cache::forget('all_settings');
                Cache::forget('public_settings');

                return;
            }

            // Clear individual setting caches
            $settings = Setting::pluck('key');
            foreach ($settings as $key) {
                Cache::forget("setting_{$key}");
            }

            // Clear bulk caches
            Cache::forget('all_settings');
            Cache::forget('public_settings');
            Cache::tags(['settings'])->flush();

        } catch (\Exception $e) {
            \Log::warning('Failed to clear settings cache: '.$e->getMessage());
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
        switch ($type) {
            case 'boolean':
            case 'toggle':
                return $value ? 'Yes' : 'No';

            case 'currency':
                $symbol = app_setting('currency_symbol', '₹');

                return $symbol.number_format($value, 2);

            case 'percentage':
                return $value.'%';

            case 'array':
            case 'multiselect':
                $array = is_string($value) ? json_decode($value, true) : $value;

                return is_array($array) ? implode(', ', $array) : $value;

            case 'date':
                $format = app_setting('date_format', 'd-m-Y');

                return \Carbon\Carbon::parse($value)->format($format);

            case 'file':
                return $value ? basename($value) : 'No file';

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
     * @return bool
     */
    function validate_setting_value($value, $type, $options = [])
    {
        switch ($type) {
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;

            case 'url':
                return filter_var($value, FILTER_VALIDATE_URL) !== false;

            case 'number':
            case 'integer':
                if (! is_numeric($value)) {
                    return false;
                }

                if (isset($options['min']) && $value < $options['min']) {
                    return false;
                }
                if (isset($options['max']) && $value > $options['max']) {
                    return false;
                }

                return true;

            case 'select':
                return isset($options['allowed']) ? in_array($value, $options['allowed']) : true;

            case 'boolean':
            case 'toggle':
                return in_array($value, ['0', '1', 0, 1, true, false], true);

            default:
                return true;
        }
    }
}

if (! function_exists('get_setting_groups')) {
    /**
     * Get all setting groups with their configurations
     *
     * @return array
     */
    function get_setting_groups()
    {
        return [
            'general' => [
                'title' => 'General Settings',
                'icon' => 'fas fa-cog',
                'description' => 'Basic application configuration',
            ],
            'college' => [
                'title' => 'College Information',
                'icon' => 'fas fa-university',
                'description' => 'College details and contact information',
            ],
            'academic' => [
                'title' => 'Academic Settings',
                'icon' => 'fas fa-graduation-cap',
                'description' => 'Academic year and session configuration',
            ],
            'email' => [
                'title' => 'Email Configuration',
                'icon' => 'fas fa-envelope',
                'description' => 'SMTP and email settings',
            ],
            'attendance' => [
                'title' => 'Attendance Settings',
                'icon' => 'fas fa-calendar-check',
                'description' => 'Attendance and presence configuration',
            ],
            'financial' => [
                'title' => 'Financial Settings',
                'icon' => 'fas fa-dollar-sign',
                'description' => 'Fee and payment configuration',
            ],
        ];
    }
}

if (! function_exists('seed_default_settings')) {
    /**
     * Seed default settings if they don't exist
     *
     * @return int Number of settings created
     */
    function seed_default_settings()
    {
        if (! settings_table_available()) {
            return 0;
        }

        $defaultSettings = [
            // General Settings
            'app_name' => [
                'value' => config('app.name', 'College Management System'),
                'group' => 'general',
                'type' => 'text',
                'description' => 'Application name displayed throughout the system',
                'is_public' => true,
            ],
            'app_tagline' => [
                'value' => 'Empowering Education Excellence',
                'group' => 'general',
                'type' => 'text',
                'description' => 'Application tagline or motto',
                'is_public' => true,
            ],
            'timezone' => [
                'value' => config('app.timezone', 'Asia/Kolkata'),
                'group' => 'general',
                'type' => 'select',
                'description' => 'Default timezone for the application',
                'is_public' => false,
            ],
            'date_format' => [
                'value' => 'd-m-Y',
                'group' => 'general',
                'type' => 'select',
                'description' => 'Default date format for display',
                'is_public' => false,
            ],
            'maintenance_mode' => [
                'value' => '0',
                'group' => 'general',
                'type' => 'toggle',
                'description' => 'Put application in maintenance mode',
                'is_public' => false,
            ],

            // Academic Settings
            'academic_year_start' => [
                'value' => '7',
                'group' => 'academic',
                'type' => 'select',
                'description' => 'Month when academic year starts (1-12)',
                'is_public' => false,
            ],
            'current_academic_year' => [
                'value' => date('Y').'-'.(date('Y') + 1),
                'group' => 'academic',
                'type' => 'text',
                'description' => 'Current academic year (YYYY-YYYY format)',
                'is_public' => true,
            ],

            // Attendance Settings
            'minimum_attendance_percentage' => [
                'value' => '75',
                'group' => 'attendance',
                'type' => 'number',
                'description' => 'Minimum attendance required for exam eligibility',
                'is_public' => false,
            ],
            'attendance_grace_period' => [
                'value' => '10',
                'group' => 'attendance',
                'type' => 'number',
                'description' => 'Late arrival grace period in minutes',
                'is_public' => false,
            ],

            // Financial Settings
            'currency_symbol' => [
                'value' => '₹',
                'group' => 'financial',
                'type' => 'text',
                'description' => 'Currency symbol to display',
                'is_public' => true,
            ],
            'currency_code' => [
                'value' => 'INR',
                'group' => 'financial',
                'type' => 'text',
                'description' => 'ISO currency code',
                'is_public' => true,
            ],
            'tax_rate' => [
                'value' => '0',
                'group' => 'financial',
                'type' => 'number',
                'description' => 'Default tax rate percentage',
                'is_public' => false,
            ],
        ];

        $created = 0;

        foreach ($defaultSettings as $key => $data) {
            $exists = Setting::where('key', $key)->exists();

            if (! $exists) {
                Setting::create(array_merge(['key' => $key], $data));
                $created++;
            }
        }

        // Clear cache after seeding
        if ($created > 0) {
            clear_settings_cache();
        }

        return $created;
    }
}

if (! function_exists('export_settings')) {
    /**
     * Export all settings to array
     *
     * @param  string|null  $group  Export specific group only
     * @return array
     */
    function export_settings($group = null)
    {
        try {
            if (! settings_table_available()) {
                return [];
            }

            $query = Setting::query();

            if ($group) {
                $query->where('group', $group);
            }

            return $query->get()->mapWithKeys(function ($setting) {
                return [
                    $setting->key => [
                        'value' => $setting->value,
                        'group' => $setting->group,
                        'type' => $setting->type,
                        'description' => $setting->description,
                        'is_public' => $setting->is_public,
                    ],
                ];
            })->toArray();

        } catch (\Exception $e) {
            \Log::error('Failed to export settings: '.$e->getMessage());

            return [];
        }
    }
}

if (! function_exists('import_settings')) {
    /**
     * Import settings from array
     *
     * @param  bool  $overwrite
     * @return int Number of settings imported
     */
    function import_settings(array $settings, $overwrite = false)
    {
        try {
            if (! settings_table_available()) {
                return 0;
            }

            $imported = 0;

            foreach ($settings as $key => $data) {
                $exists = Setting::where('key', $key)->exists();

                if (! $exists || $overwrite) {
                    Setting::updateOrCreate(
                        ['key' => $key],
                        [
                            'value' => $data['value'],
                            'group' => $data['group'] ?? 'general',
                            'type' => $data['type'] ?? 'text',
                            'description' => $data['description'] ?? '',
                            'is_public' => $data['is_public'] ?? false,
                        ]
                    );
                    $imported++;
                }
            }

            // Clear cache after import
            if ($imported > 0) {
                clear_settings_cache();
            }

            return $imported;

        } catch (\Exception $e) {
            \Log::error('Failed to import settings: '.$e->getMessage());

            return 0;
        }
    }
}
