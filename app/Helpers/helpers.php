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
        } catch (Throwable $e) {
            $isAvailable = false;

            if (! $hasLoggedError) {
                Log::warning('Settings table availability check failed: '.$e->getMessage());
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

        } catch (Throwable $e) {
            // Log error but don't break the application
            Log::warning("Settings helper error for key '{$key}': ".$e->getMessage());

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

        } catch (Exception $e) {
            Log::error("Failed to update setting '{$key}': ".$e->getMessage());

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
                Cache::forget('app_dynamic_settings');

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
            Cache::forget('app_dynamic_settings');
            Cache::tags(['settings'])->flush();

        } catch (Exception $e) {
            Log::warning('Failed to clear settings cache: '.$e->getMessage());
        }
    }
}
