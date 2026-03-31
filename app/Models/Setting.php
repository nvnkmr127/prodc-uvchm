<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Setting extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The table associated with the model.
     */
    protected $table = 'settings';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'key',
        'value',
        'group',
        'type',
        'description',
        'is_public',
        'is_encrypted',
        'validation_rules',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_public' => 'boolean',
        'is_encrypted' => 'boolean',
        'validation_rules' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

/**
     * The attributes that should be hidden for arrays.
     */
    protected $hidden = [
        // Hide raw encrypted values from serialization
    ];

    /**
     * The attributes that should be appended to arrays.
     */
    protected $appends = [
        'display_value',
        'is_default',
    ];
 /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Clear cache when settings are modified
        static::saved(function ($setting) {
            $setting->clearCache();
        });

        static::deleted(function ($setting) {
            $setting->clearCache();
        });

        // Automatically set type based on value if not explicitly set
        static::creating(function ($setting) {
            if (empty($setting->type)) {
                $setting->type = $setting->detectType($setting->getRawValue());
            }
        });

        static::updating(function ($setting) {
            if ($setting->isDirty('value') && $setting->type === 'auto') {
                $setting->type = $setting->detectType($setting->getRawValue());
            }
        });

        // Handle encryption during save operations
        static::saving(function ($setting) {
            $setting->handleEncryption();
        });
    }


  
    /**
     * Activity log configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['key', 'value', 'group', 'type'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Setting {$eventName}");
    }

   /**
     * Get the display value attribute.
     * This handles decryption and type casting.
     */
    public function getDisplayValueAttribute()
    {
        return $this->getTypedValue();
    }

    /**
     * Get the is_default attribute.
     * Checks if this setting matches its default value.
     */
    public function getIsDefaultAttribute()
    {
        $defaultValue = $this->getDefaultValue($this->key);
        return $this->getTypedValue() === $defaultValue;
    }

    /**
     * FIXED: Handle encryption during save operations instead of attribute setting
     */
    protected function handleEncryption()
    {
        if (!$this->is_encrypted || empty($this->attributes['value'])) {
            return;
        }

        // Don't double-encrypt
        if ($this->isEncryptedValue($this->attributes['value'])) {
            return;
        }

        try {
            $this->attributes['value'] = Crypt::encrypt($this->attributes['value']);
            Log::debug("Encrypted setting value for key: {$this->key}");
        } catch (\Exception $e) {
            Log::error("Failed to encrypt setting {$this->key}: " . $e->getMessage());
            // Continue without encryption rather than failing
        }
    }

    /**
     * Check if a value is already encrypted
     */
    protected function isEncryptedValue($value)
    {
        if (!is_string($value)) {
            return false;
        }

        try {
            // Try to decode as Laravel encrypted payload
            $payload = json_decode(base64_decode($value), true);
            return is_array($payload) && 
                   isset($payload['iv']) && 
                   isset($payload['value']) && 
                   isset($payload['mac']);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Set value attribute - FIXED to not encrypt during attribute setting
     */
    public function setValueAttribute($value)
    {
        // Convert arrays and objects to JSON
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        }

        // Convert boolean to string
        if (is_bool($value)) {
            $value = $value ? '1' : '0';
        }

        // Store raw value - encryption happens in handleEncryption() during save
        $this->attributes['value'] = $value;
    }

    /**
     * Get value attribute with proper decryption
     */
    public function getValueAttribute($value)
    {
        // Check if this setting should be encrypted and value exists
        if ($this->is_encrypted && !empty($value)) {
            try {
                return Crypt::decrypt($value);
            } catch (\Exception $e) {
                Log::warning("Failed to decrypt setting {$this->key}", [
                    'error' => $e->getMessage(),
                    'value_length' => strlen($value),
                    'value_preview' => substr($value, 0, 50) . '...'
                ]);
                // Return null if decryption fails
                return null;
            }
        }

        return $value;
    }

    /**
     * Get raw value without decryption (for internal use)
     */
    public function getRawValue()
    {
        return $this->attributes['value'] ?? null;
    }

 /**
     * Get the typed value based on the setting type.
     */
    public function getTypedValue()
    {
        $value = $this->value; // This triggers decryption if needed

        if (is_null($value)) {
            return null;
        }

        return $this->castValue($value, $this->type);
    }

    /**
     * Cast value to specific type.
     */
    protected function castValue($value, $type)
    {
        switch ($type) {
            case 'boolean':
            case 'bool':
                return in_array($value, ['1', 'true', 'yes', 'on', true], true);

            case 'integer':
            case 'int':
                return (int) $value;

            case 'float':
            case 'double':
                return (float) $value;

            case 'array':
            case 'json':
                if (is_string($value)) {
                    $decoded = json_decode($value, true);
                    return $decoded !== null ? $decoded : $value;
                }
                return $value;

            case 'string':
            case 'text':
            case 'textarea':
            case 'email':
            case 'url':
            case 'tel':
            case 'password':
            default:
                return (string) $value;
        }
    }

  /**
     * Detect type based on value.
     */
    protected function detectType($value)
    {
        if (is_bool($value)) {
            return 'boolean';
        }

        if (is_int($value)) {
            return 'integer';
        }

        if (is_float($value)) {
            return 'float';
        }

        if (is_array($value) || (is_string($value) && json_decode($value, true) !== null)) {
            return 'json';
        }

        if (is_string($value)) {
            if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return 'email';
            }

            if (filter_var($value, FILTER_VALIDATE_URL)) {
                return 'url';
            }
        }

        return 'text';
    }

    /**
     * Clear cache for this setting.
     */
    public function clearCache()
    {
        $cacheKeys = [
            'all_settings',
            'public_settings',
            "settings_group_{$this->group}",
            "setting_{$this->key}",
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }

        // Clear tagged cache if supported
        try {
            if (method_exists(Cache::getStore(), 'tags')) {
                Cache::tags(['settings', "group:{$this->group}"])->flush();
            }
        } catch (\Exception $e) {
            Log::debug('Cache tags not supported: ' . $e->getMessage());
        }
    }

    /**
     * Scope to get settings by group.
     */
    public function scopeByGroup(Builder $query, string $group)
    {
        return $query->where('group', $group);
    }

    /**
     * Scope to get public settings only.
     */
    public function scopePublic(Builder $query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope to get encrypted settings only.
     */
    public function scopeEncrypted(Builder $query)
    {
        return $query->where('is_encrypted', true);
    }
     /**
     * Scope to search settings by key or description.
     */
    public function scopeSearch(Builder $query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('key', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    /**
     * Get all settings as key-value pairs.
     */
    public static function getAllSettings($useCache = true)
    {
        if (!$useCache) {
            return static::all()->mapWithKeys(function ($setting) {
                return [$setting->key => $setting->getTypedValue()];
            })->toArray();
        }

        return Cache::remember('all_settings', 3600, function () {
            return static::all()->mapWithKeys(function ($setting) {
                return [$setting->key => $setting->getTypedValue()];
            })->toArray();
        });
    }

    /**
     * Get settings by group.
     */
    public static function getByGroup(string $group, $useCache = true)
    {
        if (!$useCache) {
            return static::byGroup($group)->get()->mapWithKeys(function ($setting) {
                return [$setting->key => $setting->getTypedValue()];
            })->toArray();
        }

        return Cache::remember("settings_group_{$group}", 3600, function () use ($group) {
            return static::byGroup($group)->get()->mapWithKeys(function ($setting) {
                return [$setting->key => $setting->getTypedValue()];
            })->toArray();
        });
    }

    /**
     * Get public settings only.
     */
    public static function getPublicSettings($useCache = true)
    {
        if (!$useCache) {
            return static::public()->get()->mapWithKeys(function ($setting) {
                return [$setting->key => $setting->getTypedValue()];
            })->toArray();
        }

        return Cache::remember('public_settings', 3600, function () {
            return static::public()->get()->mapWithKeys(function ($setting) {
                return [$setting->key => $setting->getTypedValue()];
            })->toArray();
        });
    }

    /**
     * ENHANCED: Set a setting value with better encryption handling
     */
    public static function set(string $key, $value, array $attributes = [])
    {
        $setting = static::firstOrNew(['key' => $key]);

        // Merge default attributes
        $defaultAttributes = [
            'value' => $value,
            'group' => 'general',
            'type' => 'text',
            'is_public' => false,
            'is_encrypted' => false,
        ];

        $setting->fill(array_merge($defaultAttributes, $attributes));

        try {
            $result = $setting->save();
            
            if (!$result) {
                Log::error("Failed to save setting: {$key}");
                return false;
            }

            // Verify the setting was saved correctly
            $verification = static::where('key', $key)->first();
            if (!$verification) {
                Log::error("Setting verification failed after save: {$key}");
                return false;
            }

            Log::debug("Setting saved successfully", [
                'key' => $key,
                'encrypted' => $setting->is_encrypted,
                'value_length' => strlen($verification->getRawValue())
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("Exception while saving setting {$key}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
 /**
     * Get a setting value with default.
     */
    public static function get(string $key, $default = null, $useCache = true)
    {
        try {
            if (!$useCache) {
                $setting = static::where('key', $key)->first();
                return $setting ? $setting->getTypedValue() : $default;
            }

            $settings = static::getAllSettings();
            return $settings[$key] ?? $default;

        } catch (\Exception $e) {
            Log::error("Error retrieving setting {$key}: " . $e->getMessage());
            return $default;
        }
    }

    /**
     * Check if a setting exists.
     */
    public static function has(string $key)
    {
        return static::where('key', $key)->exists();
    }

    /**
     * Remove a setting.
     */
    public static function remove(string $key)
    {
        return static::where('key', $key)->delete();
    }

    /**
     * ENHANCED: Import settings with better error handling
     */
    public static function import($data, $overwrite = false)
    {
        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        if (!is_array($data)) {
            throw new \InvalidArgumentException('Data must be an array or valid JSON');
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($data as $item) {
            $key = $item['key'] ?? null;

            if (!$key) {
                $errors[] = 'Missing key in import item';
                continue;
            }

            try {
                $exists = static::where('key', $key)->exists();

                if ($exists && !$overwrite) {
                    $skipped++;
                    continue;
                }

                static::updateOrCreate(
                    ['key' => $key],
                    [
                        'value' => $item['value'] ?? null,
                        'group' => $item['group'] ?? 'general',
                        'type' => $item['type'] ?? 'text',
                        'description' => $item['description'] ?? null,
                        'is_public' => $item['is_public'] ?? false,
                        'is_encrypted' => $item['is_encrypted'] ?? false,
                        'validation_rules' => $item['validation_rules'] ?? null,
                    ]
                );

                $imported++;

            } catch (\Exception $e) {
                $errors[] = "Failed to import {$key}: " . $e->getMessage();
                Log::error("Setting import error for {$key}", ['error' => $e->getMessage()]);
            }
        }

        return compact('imported', 'skipped', 'errors');
    }

    /**
     * Export settings to array.
     */
    public static function export($groups = null, $includeEncrypted = false)
    {
        $query = static::query();

        if ($groups) {
            $query->whereIn('group', (array) $groups);
        }

        if (!$includeEncrypted) {
            $query->where('is_encrypted', false);
        }

        return $query->get()->map(function ($setting) use ($includeEncrypted) {
            $data = [
                'key' => $setting->key,
                'value' => $includeEncrypted || !$setting->is_encrypted ? $setting->getTypedValue() : '[ENCRYPTED]',
                'group' => $setting->group,
                'type' => $setting->type,
                'description' => $setting->description,
                'is_public' => $setting->is_public,
                'is_encrypted' => $setting->is_encrypted,
                'validation_rules' => $setting->validation_rules,
                'created_at' => $setting->created_at->toISOString(),
                'updated_at' => $setting->updated_at->toISOString(),
            ];

            return $data;
        })->toArray();
    }


    /**
     * Get default value for a setting key.
     */
    public static function getDefaultValue($key)
    {
        $defaults = static::getDefaultSettings();
        return $defaults[$key] ?? null;
    }

    /**
     * Get all default settings.
     */
    public static function getDefaultSettings()
    {
        return [
            // General Settings
            'app_name' => 'College Management System',
            'app_tagline' => 'Empowering Education Excellence',
            'app_description' => 'A comprehensive college management system',
            'app_logo' => null,
            'app_favicon' => null,
            'timezone' => 'Asia/Kolkata',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
            'currency' => 'INR',
            'currency_symbol' => '₹',
            'academic_year_start' => 'July',
            'default_language' => 'en',
            'maintenance_mode' => false,

            // Email Settings
            'mail_driver' => 'smtp',
            'mail_host' => 'smtp.gmail.com',
            'mail_port' => 587,
            'mail_username' => '',
            'mail_password' => '',
            'mail_encryption' => 'tls',
            'mail_from_address' => '',
            'mail_from_name' => 'College Management System',

            // Notification Settings
            'email_notifications' => true,
            'sms_notifications' => false,
            'push_notifications' => true,
            'sound_notifications' => true,

            // Academic Settings
            'minimum_attendance_percentage' => 75,
            'fee_reminder_days' => 7,
            'result_declaration_enabled' => true,
            'online_admission_enabled' => true,

            // System Settings
            'backup_frequency' => 'daily',
            'log_level' => 'info',
            'cache_driver' => 'file',
            'session_lifetime' => 120,
            'max_upload_size' => 10240, // 10MB in KB

            // Security Settings
            'two_factor_auth' => false,
            'password_expiry_days' => 90,
            'max_login_attempts' => 5,
            'lockout_duration' => 15, // minutes

            // Enrollment Settings
            'enrollment_last_number' => 1000,
            
            // Staff Activity Targets
            'staff_target_calls' => 50,
            'staff_target_fee' => 20000,
            'staff_target_admissions' => 1,
        ];
    }

    /**
     * Seed default settings if they don't exist.
     */
    public static function seedDefaults()
    {
        $defaults = static::getDefaultSettings();
        $seeded = 0;

        foreach ($defaults as $key => $value) {
            if (!static::where('key', $key)->exists()) {
                static::create([
                    'key' => $key,
                    'value' => $value,
                    'group' => static::detectGroup($key),
                    'type' => static::detectType($value),
                    'is_public' => static::isPublicSetting($key),
                ]);
                $seeded++;
            }
        }

        return $seeded;
    }
   /**
     * Detect group based on setting key.
     */
    protected static function detectGroup($key)
    {
        if (str_contains($key, 'mail_') || str_contains($key, 'email_')) {
            return 'email';
        }

        if (str_contains($key, 'notification')) {
            return 'notifications';
        }

        if (str_contains($key, 'academic_') || str_contains($key, 'fee_') || str_contains($key, 'attendance_')) {
            return 'academic';
        }

        if (str_contains($key, 'security_') || str_contains($key, 'password_') || str_contains($key, 'auth')) {
            return 'security';
        }

        if (str_contains($key, 'enrollment_')) {
            return 'enrollment';
        }

        if (str_contains($key, 'backup_') || str_contains($key, 'log_') || str_contains($key, 'cache_')) {
            return 'system';
        }

        return 'general';
    }

    /**
     * Check if a setting should be public.
     */
    protected static function isPublicSetting($key)
    {
        $publicSettings = [
            'app_name',
            'app_tagline',
            'app_description',
            'app_logo',
            'app_favicon',
            'timezone',
            'date_format',
            'time_format',
            'currency',
            'currency_symbol',
            'default_language',
            'maintenance_mode',
            'online_admission_enabled',
        ];

        return in_array($key, $publicSettings);
    }

    /**
     * ENHANCED: Test encryption functionality
     */
    public static function testEncryption()
    {
        $testKey = 'encryption_test_' . time();
        $testValue = 'test encryption data ' . time();

        try {
            // Test storage with encryption
            $result = static::set($testKey, $testValue, [
                'type' => 'text',
                'is_encrypted' => true
            ]);

            if (!$result) {
                return ['success' => false, 'error' => 'Failed to store encrypted setting'];
            }

            // Test retrieval
            $retrieved = static::get($testKey);
            
            // Cleanup
            static::remove($testKey);

            if ($retrieved === $testValue) {
                return ['success' => true, 'message' => 'Encryption working correctly'];
            } else {
                return [
                    'success' => false, 
                    'error' => 'Encryption mismatch',
                    'expected' => $testValue,
                    'received' => $retrieved
                ];
            }

        } catch (\Exception $e) {
            // Cleanup on error
            static::remove($testKey);
            
            return [
                'success' => false,
                'error' => 'Encryption test exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Optimize settings by removing duplicates and fixing inconsistencies.
     */
    public static function optimize()
    {
        $duplicatesRemoved = 0;
        $groupsFixed = 0;

        // Remove duplicates (keep the latest one)
        $duplicateKeys = static::select('key')
            ->groupBy('key')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('key');

        foreach ($duplicateKeys as $key) {
            $settings = static::where('key', $key)->orderBy('updated_at', 'desc')->get();
            $keep = $settings->first();
            $remove = $settings->skip(1);

            foreach ($remove as $setting) {
                $setting->delete();
                $duplicatesRemoved++;
            }
        }

        // Fix missing groups
        $settingsWithoutGroup = static::whereNull('group')->orWhere('group', '')->get();
        foreach ($settingsWithoutGroup as $setting) {
            $setting->group = static::detectGroup($setting->key);
            $setting->save();
            $groupsFixed++;
        }

        return compact('duplicatesRemoved', 'groupsFixed');
    }

    /**
     * Get system health check for settings.
     */
    public static function healthCheck()
    {
        $checks = [];

        // Check if required settings exist
        $requiredSettings = ['app_name', 'timezone', 'date_format'];
        foreach ($requiredSettings as $key) {
            $checks[] = [
                'name' => "Required setting: {$key}",
                'status' => static::where('key', $key)->exists() ? 'pass' : 'fail',
            ];
        }

        // Check for duplicate keys
        $duplicates = static::select('key')
            ->groupBy('key')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        $checks[] = [
            'name' => 'No duplicate setting keys',
            'status' => $duplicates === 0 ? 'pass' : 'fail',
            'details' => $duplicates > 0 ? "{$duplicates} duplicate keys found" : null,
        ];

        // Check cache functionality
        try {
            static::getAllSettings();
            $checks[] = [
                'name' => 'Settings cache working',
                'status' => 'pass',
            ];
        } catch (\Exception $e) {
            $checks[] = [
                'name' => 'Settings cache working',
                'status' => 'fail',
                'details' => $e->getMessage(),
            ];
        }

        $passed = collect($checks)->where('status', 'pass')->count();
        $total = count($checks);

        return [
            'status' => $passed === $total ? 'healthy' : 'issues',
            'summary' => compact('passed', 'total'),
            'checks' => $checks,
        ];
    }
}