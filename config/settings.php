<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Settings Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for the settings system including
    | caching, validation, and default values.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => env('SETTINGS_CACHE_ENABLED', true),
        'ttl' => env('SETTINGS_CACHE_TTL', 3600), // 1 hour
        'key_prefix' => env('SETTINGS_CACHE_PREFIX', 'settings'),
        'tags' => ['settings'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup Configuration
    |--------------------------------------------------------------------------
    */
    'backup' => [
        'enabled' => env('SETTINGS_BACKUP_ENABLED', true),
        'path' => storage_path('app/backups'),
        'frequency' => env('SETTINGS_BACKUP_FREQUENCY', 'daily'), // daily, weekly, monthly
        'retention_days' => env('SETTINGS_BACKUP_RETENTION', 30),
        'auto_cleanup' => env('SETTINGS_AUTO_CLEANUP', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    */
    'security' => [
        'encrypt_sensitive' => env('SETTINGS_ENCRYPT_SENSITIVE', true),
        'allowed_file_types' => ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'],
        'max_file_size' => env('SETTINGS_MAX_FILE_SIZE', 2048), // KB
        'validate_urls' => env('SETTINGS_VALIDATE_URLS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Group Icons
    |--------------------------------------------------------------------------
    */
    'group_icons' => [
        'general' => 'fas fa-cog',
        'college' => 'fas fa-university',
        'academic' => 'fas fa-graduation-cap',
        'financial' => 'fas fa-dollar-sign',
        'attendance' => 'fas fa-user-check',
        'notifications' => 'fas fa-bell',
        'security' => 'fas fa-shield-alt',
        'backup' => 'fas fa-database',
        'mail' => 'fas fa-envelope',
        'sms' => 'fas fa-sms',
        'system' => 'fas fa-server',
        'exam' => 'fas fa-file-alt',
        'library' => 'fas fa-book',
        'hr' => 'fas fa-users',
        'inventory' => 'fas fa-boxes',
    ],

    /*
    |--------------------------------------------------------------------------
    | Field Type Configurations
    |--------------------------------------------------------------------------
    */
    'field_types' => [
        'text' => [
            'validation' => 'string|max:255',
            'cast' => 'string',
        ],
        'textarea' => [
            'validation' => 'string|max:5000',
            'cast' => 'string',
        ],
        'email' => [
            'validation' => 'email|max:255',
            'cast' => 'string',
        ],
        'url' => [
            'validation' => 'url|max:500',
            'cast' => 'string',
        ],
        'number' => [
            'validation' => 'numeric',
            'cast' => 'float',
        ],
        'integer' => [
            'validation' => 'integer',
            'cast' => 'integer',
        ],
        'toggle' => [
            'validation' => 'boolean',
            'cast' => 'boolean',
        ],
        'select' => [
            'validation' => 'string',
            'cast' => 'string',
        ],
        'multiselect' => [
            'validation' => 'array',
            'cast' => 'array',
        ],
        'file' => [
            'validation' => 'file|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'cast' => 'string',
        ],
        'password' => [
            'validation' => 'string|min:6',
            'cast' => 'string',
            'encrypted' => true,
        ],
        'date' => [
            'validation' => 'date',
            'cast' => 'date',
        ],
        'time' => [
            'validation' => 'date_format:H:i',
            'cast' => 'string',
        ],
        'datetime' => [
            'validation' => 'date',
            'cast' => 'datetime',
        ],
        'json' => [
            'validation' => 'json',
            'cast' => 'array',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    */
    'validation' => [
        'required_fields' => [
            'app_name',
            'college_name',
            'timezone',
            'currency_symbol',
            'currency_code',
        ],
        'unique_fields' => [
            // Fields that should be unique across settings
        ],
        'custom_rules' => [
            'email_fields' => ['college_email', 'notification_sender_email'],
            'url_fields' => ['college_website', 'app_url'],
            'numeric_fields' => [
                'minimum_attendance_percentage',
                'late_fee_percentage',
                'womens_discount_percentage',
                'password_min_length',
                'session_timeout',
                'login_attempts',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Values
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'app_name' => 'College Management System',
        'app_tagline' => 'Empowering Education Excellence',
        'timezone' => 'Asia/Kolkata',
        'date_format' => 'd-m-Y',
        'currency_symbol' => '₹',
        'currency_code' => 'INR',
        'decimal_places' => '2',
        'enrollment_prefix' => 'STD',
        'minimum_attendance_percentage' => '75',
        'attendance_grace_period' => '10',
        'fee_payment_terms' => '3',
        'late_fee_percentage' => '5.00',
        'password_min_length' => '8',
        'session_timeout' => '120',
        'login_attempts' => '5',
        'auto_backup' => true,
        'backup_frequency' => 'daily',
        'backup_retention_days' => '30',
        'maintenance_window' => '02:00',
        'email_notifications' => true,
        'semester_system' => true,
        'academic_session_start' => '07',
        'fee_reminder_days' => '7',
        'birthday_notifications' => true,
        'data_retention_period' => '7',
        'passing_marks_percentage' => '40',
        'library_fine_per_day' => '5',
        'max_books_per_student' => '3',
        'book_return_days' => '14',
    ],

    /*
    |--------------------------------------------------------------------------
    | Environment Sync
    |--------------------------------------------------------------------------
    | Settings that should sync with environment variables
    */
    'env_sync' => [
        'app_name' => 'APP_NAME',
        'app_url' => 'APP_URL',
        'timezone' => 'APP_TIMEZONE',
        'debug_mode' => 'APP_DEBUG',
        'mail_host' => 'MAIL_HOST',
        'mail_port' => 'MAIL_PORT',
        'mail_username' => 'MAIL_USERNAME',
        'mail_password' => 'MAIL_PASSWORD',
        'mail_encryption' => 'MAIL_ENCRYPTION',
        'cache_driver' => 'CACHE_DRIVER',
        'session_driver' => 'SESSION_DRIVER',
        'queue_driver' => 'QUEUE_CONNECTION',
    ],

    /*
    |--------------------------------------------------------------------------
    | Public Settings
    |--------------------------------------------------------------------------
    | Settings that are safe to expose to frontend/public
    */
    'public_settings' => [
        'app_name',
        'app_tagline',
        'college_name',
        'college_short_name',
        'college_logo',
        'college_email',
        'college_phone',
        'college_website',
        'college_address',
        'college_established_year',
        'current_academic_year',
        'currency_symbol',
        'currency_code',
        'date_format',
        'timezone',
        'semester_system',
        'academic_session_start',
    ],

    /*
    |--------------------------------------------------------------------------
    | Sensitive Settings
    |--------------------------------------------------------------------------
    | Settings that should be encrypted
    */
    'encrypted_settings' => [
        'mail_password',
        'sms_api_key',
        'biometric_api_key',
        'api_keys',
        'database_passwords',
        'third_party_secrets',
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware Configuration
    |--------------------------------------------------------------------------
    */
    'middleware' => [
        'admin' => ['auth', 'permission:manage settings'],
        'api' => ['auth:sanctum', 'permission:manage settings'],
        'public' => ['throttle:60,1'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Check Configuration
    |--------------------------------------------------------------------------
    */
    'health_check' => [
        'enabled' => env('SETTINGS_HEALTH_CHECK', true),
        'checks' => [
            'database_connection',
            'cache_functionality',
            'settings_functionality',
            'file_permissions',
            'backup_directory',
            'disk_space',
        ],
        'thresholds' => [
            'disk_space_warning' => 80, // Percentage
            'disk_space_critical' => 90, // Percentage
            'memory_usage_warning' => 80, // Percentage
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Import/Export Configuration
    |--------------------------------------------------------------------------
    */
    'import_export' => [
        'formats' => ['json'],
        'max_file_size' => 5120, // KB
        'include_metadata' => true,
        'validate_on_import' => true,
        'backup_before_import' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    */
    'api' => [
        'rate_limiting' => [
            'enabled' => true,
            'requests_per_minute' => 60,
        ],
        'versioning' => [
            'enabled' => true,
            'current_version' => 'v1',
            'supported_versions' => ['v1'],
        ],
        'documentation' => [
            'auto_generate' => env('SETTINGS_API_DOCS_AUTO', false),
            'path' => '/api/settings/documentation',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    */
    'performance' => [
        'lazy_loading' => env('SETTINGS_LAZY_LOADING', true),
        'batch_updates' => env('SETTINGS_BATCH_UPDATES', true),
        'compression' => env('SETTINGS_COMPRESSION', false),
        'cdn_assets' => env('SETTINGS_CDN_ASSETS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Configuration
    |--------------------------------------------------------------------------
    */
    'audit' => [
        'enabled' => env('SETTINGS_AUDIT_ENABLED', true),
        'track_changes' => true,
        'log_access' => false,
        'retention_days' => 90,
    ],
];