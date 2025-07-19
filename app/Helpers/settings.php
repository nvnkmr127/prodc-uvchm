<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

if (!function_exists('setting')) {
    /**
     * Get a setting value by key
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function setting($key, $default = null)
    {
        try {
            // Check if settings table exists
            if (!Schema::hasTable('settings')) {
                return $default;
            }

            $cacheKey = "setting_{$key}";
            
            return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
                $setting = Setting::where('key', $key)->first();
                
                if (!$setting) {
                    return $default;
                }

                return $setting->getTypedValue();
            });
            
        } catch (\Exception $e) {
            \Log::warning("Settings helper error for key '{$key}': " . $e->getMessage());
            return $default;
        }
    }
}

if (!function_exists('settings')) {
    /**
     * Get multiple settings at once
     * 
     * @param array $keys
     * @param array $defaults
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

if (!function_exists('update_setting')) {
    /**
     * Update a setting value
     * 
     * @param string $key
     * @param mixed $value
     * @param string $group
     * @param string $type
     * @return bool
     */
    function update_setting($key, $value, $group = 'general', $type = 'text')
    {
        try {
            if (!Schema::hasTable('settings')) {
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
            \Log::error("Failed to update setting '{$key}': " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('clear_settings_cache')) {
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
            if (Schema::hasTable('settings')) {
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
            \Log::warning('Failed to clear settings cache: ' . $e->getMessage());
        }
    }
}

if (!function_exists('public_settings')) {
    /**
     * Get all public settings (for frontend use)
     * 
     * @return array
     */
    function public_settings()
    {
        try {
            if (!Schema::hasTable('settings')) {
                return [];
            }

            return Cache::remember('public_settings', 3600, function () {
                return Setting::where('is_public', true)
                    ->pluck('value', 'key')
                    ->toArray();
            });
            
        } catch (\Exception $e) {
            \Log::warning("Public settings error: " . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('get_settings_by_group')) {
    /**
     * Get all settings for a specific group
     * 
     * @param string $group
     * @return array
     */
    function get_settings_by_group($group)
    {
        try {
            if (!Schema::hasTable('settings')) {
                return [];
            }

            return Cache::remember("settings_group_{$group}", 3600, function () use ($group) {
                return Setting::where('group', $group)
                    ->pluck('value', 'key')
                    ->toArray();
            });
            
        } catch (\Exception $e) {
            \Log::warning("Group settings error for '{$group}': " . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('setting_exists')) {
    /**
     * Check if a setting exists
     * 
     * @param string $key
     * @return bool
     */
    function setting_exists($key)
    {
        try {
            if (!Schema::hasTable('settings')) {
                return false;
            }

            return Setting::where('key', $key)->exists();
            
        } catch (\Exception $e) {
            return false;
        }
    }
}

if (!function_exists('format_setting_value')) {
    /**
     * Format setting value for display
     * 
     * @param string $key
     * @param mixed $value
     * @param string $type
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
                return $symbol . number_format($value, 2);
            
            case 'percentage':
                return $value . '%';
            
            case 'array':
            case 'multiselect':
                $array = is_string($value) ? json_decode($value, true) : $value;
                return is_array($array) ? implode(', ', $array) : $value;
            
            case 'date':
                $format = setting('date_format', 'd-m-Y');
                return \Carbon\Carbon::parse($value)->format($format);
            
            case 'file':
                return $value ? '<a href="' . asset('storage/' . $value) . '" target="_blank">View File</a>' : 'No file';
            
            case 'password':
                return str_repeat('•', min(8, strlen($value)));
            
            case 'email':
                return '<a href="mailto:' . $value . '">' . $value . '</a>';
            
            case 'url':
                return '<a href="' . $value . '" target="_blank">' . \Str::limit($value, 30) . '</a>';
            
            default:
                return (string) $value;
        }
    }
}

if (!function_exists('validate_setting_value')) {
    /**
     * Validate setting value based on type
     * 
     * @param mixed $value
     * @param string $type
     * @param array $options
     * @return array
     */
    function validate_setting_value($value, $type, $options = [])
    {
        switch ($type) {
            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return ['valid' => false, 'message' => 'Invalid email format'];
                }
                break;
            
            case 'url':
                if (!filter_var($value, FILTER_VALIDATE_URL)) {
                    return ['valid' => false, 'message' => 'Invalid URL format'];
                }
                break;
            
            case 'number':
            case 'integer':
                if (!is_numeric($value)) {
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
                if (isset($options['allowed']) && !in_array($value, $options['allowed'])) {
                    return ['valid' => false, 'message' => 'Invalid option selected'];
                }
                break;
            
            case 'boolean':
            case 'toggle':
                if (!in_array($value, ['0', '1', 0, 1, true, false], true)) {
                    return ['valid' => false, 'message' => 'Invalid boolean value'];
                }
                break;
        }

        return ['valid' => true, 'message' => 'Valid'];
    }
}
