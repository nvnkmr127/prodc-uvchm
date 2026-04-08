<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Payment Reminder Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the payment reminder
    | system including channels, scheduling, defaulter categories, and
    | automation settings.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Enable/Disable Payment Reminders
    |--------------------------------------------------------------------------
    |
    | This option controls whether the payment reminder system is enabled
    | globally. When disabled, no reminders will be sent regardless of
    | other settings.
    |
    */
    'enabled' => env('PAYMENT_REMINDERS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Communication Channels
    |--------------------------------------------------------------------------
    |
    | Configure the available communication channels for sending payment
    | reminders. Each channel can be individually enabled/disabled and
    | has its own configuration options.
    |
    */
    'channels' => [
        'email' => [
            'enabled' => env('EMAIL_REMINDERS_ENABLED', true),
            'queue' => env('EMAIL_REMINDER_QUEUE', 'default'),
            'rate_limit' => env('EMAIL_RATE_LIMIT', 100), // per hour
            'retry_attempts' => 3,
            'retry_delay' => 300, // seconds
            'from_address' => env('REMINDER_EMAIL_FROM'),
            'from_name' => env('REMINDER_EMAIL_FROM_NAME', 'College Administration'),
            'reply_to' => env('REMINDER_EMAIL_REPLY_TO'),
        ],
        'sms' => [
            'enabled' => env('SMS_REMINDERS_ENABLED', true),
            'queue' => env('SMS_REMINDER_QUEUE', 'high'),
            'rate_limit' => env('SMS_RATE_LIMIT', 50), // per hour
            'cost_per_sms' => env('SMS_COST_PER_MESSAGE', 0.10), // in local currency
            'max_length' => 160, // characters
            'retry_attempts' => 2,
            'retry_delay' => 600, // seconds
            'api_url' => env('SMS_API_URL'),
            'api_key' => env('SMS_API_KEY'),
            'sender_id' => env('SMS_SENDER_ID', 'COLLEGE'),
        ],
        'whatsapp' => [
            'enabled' => env('WHATSAPP_REMINDERS_ENABLED', false),
            'queue' => env('WHATSAPP_REMINDER_QUEUE', 'high'),
            'rate_limit' => env('WHATSAPP_RATE_LIMIT', 80), // per hour
            'retry_attempts' => 3,
            'retry_delay' => 900, // seconds
            'api_url' => env('WHATSAPP_API_URL'),
            'api_token' => env('WHATSAPP_API_TOKEN'),
            'phone_number' => env('WHATSAPP_PHONE_NUMBER'),
            'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
        ],
        'push_notification' => [
            'enabled' => env('PUSH_REMINDERS_ENABLED', false),
            'queue' => env('PUSH_REMINDER_QUEUE', 'default'),
            'rate_limit' => env('PUSH_RATE_LIMIT', 200), // per hour
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Reminder Schedule Configuration
    |--------------------------------------------------------------------------
    |
    | Define when reminders should be sent relative to payment due dates.
    | All values are in days.
    |
    */
    'schedule' => [
        // Days before due date to send reminders
        'before_due_days' => [
            'first_reminder' => env('FIRST_REMINDER_DAYS', 7),
            'second_reminder' => env('SECOND_REMINDER_DAYS', 3),
            'final_reminder' => env('FINAL_REMINDER_DAYS', 1),
        ],

        // Days after due date to send overdue reminders
        'after_due_days' => [
            'first_overdue' => env('FIRST_OVERDUE_DAYS', 1),
            'second_overdue' => env('SECOND_OVERDUE_DAYS', 7),
            'third_overdue' => env('THIRD_OVERDUE_DAYS', 15),
            'escalation' => env('ESCALATION_DAYS', 30),
        ],

        // Special notice timings
        'escalation_days' => env('ESCALATION_DAYS', 30),
        'final_notice_days' => env('FINAL_NOTICE_DAYS', 45),
        'suspension_warning_days' => env('SUSPENSION_WARNING_DAYS', 60),

        // Daily reminder time
        'daily_reminder_time' => env('DAILY_REMINDER_TIME', '09:00'),
        'urgent_reminder_time' => env('URGENT_REMINDER_TIME', '16:00'),

        // Weekend handling
        'send_on_weekends' => env('SEND_REMINDERS_WEEKENDS', false),
        'skip_holidays' => env('SKIP_HOLIDAYS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Defaulter Categories
    |--------------------------------------------------------------------------
    |
    | Define how students are categorized based on their payment behavior.
    | Each category has threshold days and associated styling.
    |
    */
    'defaulter_categories' => [
        'mild' => [
            'days' => env('MILD_DEFAULTER_DAYS', 15),
            'amount_threshold' => env('MILD_DEFAULTER_AMOUNT', 5000),
            'color' => 'info',
            'icon' => 'fas fa-info-circle',
            'actions' => ['email_reminder', 'sms_reminder'],
        ],
        'moderate' => [
            'days' => env('MODERATE_DEFAULTER_DAYS', 30),
            'amount_threshold' => env('MODERATE_DEFAULTER_AMOUNT', 10000),
            'color' => 'warning',
            'icon' => 'fas fa-exclamation-triangle',
            'actions' => ['email_reminder', 'sms_reminder', 'phone_call'],
        ],
        'severe' => [
            'days' => env('SEVERE_DEFAULTER_DAYS', 60),
            'amount_threshold' => env('SEVERE_DEFAULTER_AMOUNT', 25000),
            'color' => 'danger',
            'icon' => 'fas fa-times-circle',
            'actions' => ['email_reminder', 'sms_reminder', 'phone_call', 'parent_contact'],
        ],
        'chronic' => [
            'days' => env('CHRONIC_DEFAULTER_DAYS', 90),
            'amount_threshold' => env('CHRONIC_DEFAULTER_AMOUNT', 50000),
            'color' => 'dark',
            'icon' => 'fas fa-ban',
            'actions' => ['email_reminder', 'sms_reminder', 'phone_call', 'parent_contact', 'escalation'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fee Type Priorities
    |--------------------------------------------------------------------------
    |
    | Define priority levels for different fee types. Higher priority fees
    | get more frequent reminders and faster escalation.
    |
    */
    'fee_type_priorities' => [
        'tuition_fee' => [
            'priority' => 'critical',
            'reminder_frequency' => 'daily',
            'escalation_speed' => 'fast',
            'color' => 'danger',
        ],
        'exam_fee' => [
            'priority' => 'high',
            'reminder_frequency' => 'daily',
            'escalation_speed' => 'fast',
            'color' => 'warning',
        ],
        'lab_fee' => [
            'priority' => 'medium',
            'reminder_frequency' => 'weekly',
            'escalation_speed' => 'normal',
            'color' => 'info',
        ],
        'library_fee' => [
            'priority' => 'medium',
            'reminder_frequency' => 'weekly',
            'escalation_speed' => 'normal',
            'color' => 'info',
        ],
        'uniform_fee' => [
            'priority' => 'low',
            'reminder_frequency' => 'bi-weekly',
            'escalation_speed' => 'slow',
            'color' => 'success',
        ],
        'transport_fee' => [
            'priority' => 'low',
            'reminder_frequency' => 'monthly',
            'escalation_speed' => 'slow',
            'color' => 'secondary',
        ],
        'hostel_fee' => [
            'priority' => 'high',
            'reminder_frequency' => 'weekly',
            'escalation_speed' => 'normal',
            'color' => 'primary',
        ],
        'sports_fee' => [
            'priority' => 'low',
            'reminder_frequency' => 'monthly',
            'escalation_speed' => 'slow',
            'color' => 'success',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Automation Settings
    |--------------------------------------------------------------------------
    |
    | Configure automated behavior for the payment reminder system.
    |
    */
    'automation' => [
        // Automatically schedule reminders for new invoices
        'auto_schedule_reminders' => env('AUTO_SCHEDULE_REMINDERS', true),

        // Automatically escalate overdue payments
        'auto_escalate' => env('AUTO_ESCALATE', false),

        // Automatically suspend students for chronic non-payment
        'auto_suspend' => env('AUTO_SUSPEND', false),

        // Batch processing settings
        'batch_size' => env('REMINDER_BATCH_SIZE', 50),
        'max_concurrent_jobs' => env('MAX_CONCURRENT_REMINDER_JOBS', 5),

        // Retry settings for failed reminders
        'retry_failed' => env('RETRY_FAILED_REMINDERS', true),
        'max_retries' => env('MAX_REMINDER_RETRIES', 3),
        'retry_backoff' => env('RETRY_BACKOFF_MINUTES', 60), // minutes

        // Smart scheduling (avoid weekends, holidays)
        'smart_scheduling' => env('SMART_SCHEDULING', true),
        'business_hours_only' => env('BUSINESS_HOURS_ONLY', false),
        'business_start_time' => env('BUSINESS_START_TIME', '09:00'),
        'business_end_time' => env('BUSINESS_END_TIME', '17:00'),

        // Duplicate prevention
        'prevent_duplicate_reminders' => env('PREVENT_DUPLICATE_REMINDERS', true),
        'duplicate_check_window' => env('DUPLICATE_CHECK_WINDOW_HOURS', 24), // hours
    ],

    /*
    |--------------------------------------------------------------------------
    | Message Templates
    |--------------------------------------------------------------------------
    |
    | Configure message templates and personalization options.
    |
    */
    'templates' => [
        // Use custom templates instead of default ones
        'use_custom' => env('USE_CUSTOM_TEMPLATES', false),

        // Path to custom template files
        'custom_path' => resource_path('views/payment/templates'),

        // Template variables available for personalization
        'available_variables' => [
            'student_name', 'enrollment_number', 'course_name', 'batch_name',
            'fee_type', 'amount', 'due_date', 'days_overdue', 'college_name',
            'contact_number', 'email', 'father_name', 'mother_name',
        ],

        // Message length limits
        'max_email_length' => 2000,
        'max_sms_length' => 160,
        'max_whatsapp_length' => 1000,

        // Language support
        'default_language' => env('DEFAULT_REMINDER_LANGUAGE', 'en'),
        'supported_languages' => ['en', 'hi'], // English, Hindi
        'auto_detect_language' => env('AUTO_DETECT_LANGUAGE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Reporting and Analytics
    |--------------------------------------------------------------------------
    |
    | Configure reporting and analytics features.
    |
    */
    'reporting' => [
        // Enable/disable different types of reports
        'daily_summary' => env('DAILY_SUMMARY_ENABLED', true),
        'weekly_report' => env('WEEKLY_REPORT_ENABLED', true),
        'monthly_analytics' => env('MONTHLY_ANALYTICS_ENABLED', true),
        'quarterly_insights' => env('QUARTERLY_INSIGHTS_ENABLED', true),

        // Real-time dashboard updates
        'real_time_dashboard' => env('REAL_TIME_DASHBOARD', true),
        'dashboard_refresh_interval' => env('DASHBOARD_REFRESH_INTERVAL', 300), // seconds

        // Data retention for reports
        'retain_daily_reports' => env('RETAIN_DAILY_REPORTS_DAYS', 90),
        'retain_weekly_reports' => env('RETAIN_WEEKLY_REPORTS_WEEKS', 52),
        'retain_monthly_reports' => env('RETAIN_MONTHLY_REPORTS_MONTHS', 36),

        // Export formats
        'export_formats' => ['pdf', 'excel', 'csv'],
        'default_export_format' => env('DEFAULT_EXPORT_FORMAT', 'excel'),

        // Email reports to administrators
        'email_reports' => env('EMAIL_REPORTS_ENABLED', true),
        'report_recipients' => [
            'principal@college.edu',
            'accounts@college.edu',
            'admin@college.edu',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Settings
    |--------------------------------------------------------------------------
    |
    | Configure integrations with external services.
    |
    */
    'integrations' => [
        // Payment gateway integration
        'payment_gateways' => [
            'razorpay' => [
                'enabled' => env('RAZORPAY_ENABLED', false),
                'webhook_enabled' => env('RAZORPAY_WEBHOOK_ENABLED', false),
            ],
            'payu' => [
                'enabled' => env('PAYU_ENABLED', false),
                'webhook_enabled' => env('PAYU_WEBHOOK_ENABLED', false),
            ],
        ],

        // Student information system integration
        'sis_integration' => env('SIS_INTEGRATION_ENABLED', false),

        // Parent portal integration
        'parent_portal' => env('PARENT_PORTAL_ENABLED', false),

        // Mobile app integration
        'mobile_app_api' => env('MOBILE_APP_API_ENABLED', true),

        // Third-party SMS/Email services
        'external_services' => [
            'twilio' => env('TWILIO_ENABLED', false),
            'sendgrid' => env('SENDGRID_ENABLED', false),
            'mailgun' => env('MAILGUN_ENABLED', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security and Compliance
    |--------------------------------------------------------------------------
    |
    | Security settings and compliance options.
    |
    */
    'security' => [
        // Encrypt sensitive data in database
        'encrypt_data' => env('ENCRYPT_REMINDER_DATA', true),

        // Data retention and privacy
        'data_retention_days' => env('REMINDER_DATA_RETENTION_DAYS', 365),
        'auto_cleanup_old_data' => env('AUTO_CLEANUP_OLD_DATA', true),
        'anonymize_expired_data' => env('ANONYMIZE_EXPIRED_DATA', true),

        // Access control
        'require_two_factor' => env('REQUIRE_2FA_FOR_REMINDERS', false),
        'log_all_activities' => env('LOG_REMINDER_ACTIVITIES', true),
        'audit_trail' => env('REMINDER_AUDIT_TRAIL', true),

        // Rate limiting and abuse prevention
        'rate_limit_per_user' => env('RATE_LIMIT_PER_USER', 100), // per hour
        'block_suspicious_activity' => env('BLOCK_SUSPICIOUS_ACTIVITY', true),
        'max_failed_attempts' => env('MAX_FAILED_ATTEMPTS', 5),
        'lockout_duration' => env('LOCKOUT_DURATION_MINUTES', 30), // minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Optimization
    |--------------------------------------------------------------------------
    |
    | Settings to optimize system performance.
    |
    */
    'performance' => [
        // Caching settings
        'cache_enabled' => env('REMINDER_CACHE_ENABLED', true),
        'cache_duration' => env('REMINDER_CACHE_DURATION', 3600), // seconds
        'cache_driver' => env('REMINDER_CACHE_DRIVER', 'redis'),

        // Database optimization
        'use_database_indexes' => true,
        'optimize_queries' => true,
        'batch_database_operations' => true,

        // Memory management
        'memory_limit' => env('REMINDER_MEMORY_LIMIT', '256M'),
        'garbage_collection' => env('REMINDER_GC_ENABLED', true),

        // Parallel processing
        'parallel_processing' => env('PARALLEL_PROCESSING', true),
        'max_parallel_workers' => env('MAX_PARALLEL_WORKERS', 4),
    ],

    /*
    |--------------------------------------------------------------------------
    | Development and Testing
    |--------------------------------------------------------------------------
    |
    | Settings for development and testing environments.
    |
    */
    'development' => [
        // Debug mode
        'debug_mode' => env('REMINDER_DEBUG_MODE', false),
        'verbose_logging' => env('VERBOSE_REMINDER_LOGGING', false),

        // Testing settings
        'test_mode' => env('REMINDER_TEST_MODE', false),
        'test_recipients' => [
            'email' => env('TEST_EMAIL_RECIPIENT'),
            'sms' => env('TEST_SMS_RECIPIENT'),
            'whatsapp' => env('TEST_WHATSAPP_RECIPIENT'),
        ],

        // Mock external services in testing
        'mock_sms_service' => env('MOCK_SMS_SERVICE', false),
        'mock_email_service' => env('MOCK_EMAIL_SERVICE', false),
        'mock_whatsapp_service' => env('MOCK_WHATSAPP_SERVICE', false),

        // Development helpers
        'show_sql_queries' => env('SHOW_REMINDER_SQL_QUERIES', false),
        'profile_performance' => env('PROFILE_REMINDER_PERFORMANCE', false),
    ],
];
