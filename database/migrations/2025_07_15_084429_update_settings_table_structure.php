<?php
// Create this migration file: database/migrations/xxxx_xx_xx_xxxxxx_update_settings_table_structure.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // Add columns if they don't exist
            if (!Schema::hasColumn('settings', 'group')) {
                $table->string('group')->default('general')->index()->after('value');
            }
            
            if (!Schema::hasColumn('settings', 'type')) {
                $table->string('type')->default('text')->after('group');
            }
            
            if (!Schema::hasColumn('settings', 'description')) {
                $table->text('description')->nullable()->after('type');
            }
            
            if (!Schema::hasColumn('settings', 'is_public')) {
                $table->boolean('is_public')->default(false)->after('description');
            }
            
            if (!Schema::hasColumn('settings', 'is_encrypted')) {
                $table->boolean('is_encrypted')->default(false)->after('is_public');
            }
            
            if (!Schema::hasColumn('settings', 'validation_rules')) {
                $table->json('validation_rules')->nullable()->after('is_encrypted');
            }
        });

        // Update existing settings to have proper groups and types if needed
        $this->updateExistingSettings();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $columnsToCheck = ['group', 'type', 'description', 'is_public', 'is_encrypted', 'validation_rules'];
            
            foreach ($columnsToCheck as $column) {
                if (Schema::hasColumn('settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    /**
     * Update existing settings with proper groups and types
     */
    private function updateExistingSettings(): void
    {
        // Only run if Settings model exists
        if (!class_exists(\App\Models\Setting::class)) {
            return;
        }

        try {
            $settings = \App\Models\Setting::all();
            
            foreach ($settings as $setting) {
                $updates = [];
                
                // Set group if empty
                if (empty($setting->group)) {
                    $updates['group'] = $this->detectGroup($setting->key);
                }
                
                // Set type if empty
                if (empty($setting->type)) {
                    $updates['type'] = $this->detectType($setting->value);
                }
                
                // Set is_public
                if ($setting->is_public === null) {
                    $updates['is_public'] = $this->isPublicSetting($setting->key);
                }
                
                // Set is_encrypted for password fields
                if ($setting->is_encrypted === null) {
                    $updates['is_encrypted'] = str_contains($setting->key, 'password');
                }
                
                // Set description if empty
                if (empty($setting->description)) {
                    $updates['description'] = $this->getDescription($setting->key);
                }
                
                if (!empty($updates)) {
                    $setting->update($updates);
                }
            }
        } catch (\Exception $e) {
            // Log error but don't fail migration
            \Log::warning('Failed to update existing settings: ' . $e->getMessage());
        }
    }

    private function detectGroup($key): string
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

    private function detectType($value): string
    {
        if (is_null($value)) {
            return 'text';
        }
        
        if (in_array($value, ['0', '1', 'true', 'false'], true)) {
            return 'toggle';
        }
        
        if (is_numeric($value)) {
            return 'number';
        }
        
        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return 'email';
        }
        
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return 'url';
        }
        
        if (strlen($value) > 255) {
            return 'textarea';
        }
        
        return 'text';
    }

    private function isPublicSetting($key): bool
    {
        $publicSettings = [
            'app_name', 'app_tagline', 'app_description', 'app_logo', 'app_favicon',
            'timezone', 'date_format', 'time_format', 'currency', 'currency_symbol',
            'default_language', 'maintenance_mode', 'online_admission_enabled'
        ];
        
        return in_array($key, $publicSettings);
    }

    private function getDescription($key): string
    {
        $descriptions = [
            'app_name' => 'Name displayed throughout the application',
            'app_tagline' => 'Short description or motto',
            'app_description' => 'Detailed description of the application',
            'timezone' => 'Default timezone for the application',
            'date_format' => 'Default date format',
            'time_format' => 'Default time format',
            'currency' => 'Default currency code',
            'currency_symbol' => 'Currency symbol to display',
            'mail_host' => 'SMTP server hostname',
            'mail_port' => 'SMTP server port',
            'mail_username' => 'SMTP username',
            'mail_password' => 'SMTP password',
            'mail_encryption' => 'Email encryption method',
            'maintenance_mode' => 'Enable maintenance mode',
            'email_notifications' => 'Enable email notifications',
            'sms_notifications' => 'Enable SMS notifications',
            'minimum_attendance_percentage' => 'Minimum attendance required',
            'fee_reminder_days' => 'Days before fee due date to send reminders',
            'enrollment_prefix' => 'Prefix for enrollment numbers',
            'enrollment_starting_number' => 'Starting number for enrollments',
        ];
        
        return $descriptions[$key] ?? ucwords(str_replace('_', ' ', $key));
    }
};