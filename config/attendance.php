<?php
// File: config/attendance.php

return [
    /*
    |--------------------------------------------------------------------------
    | Attendance System Configuration
    |--------------------------------------------------------------------------
    */

    'enabled' => env('ATTENDANCE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Attendance Rules
    |--------------------------------------------------------------------------
    */
    'rules' => [
        'minimum_percentage' => env('ATTENDANCE_MINIMUM_PERCENTAGE', 75),
        'grace_period_minutes' => env('ATTENDANCE_GRACE_PERIOD', 10),
        'late_threshold_minutes' => env('ATTENDANCE_LATE_THRESHOLD', 15),
        'early_arrival_buffer_minutes' => env('ATTENDANCE_EARLY_BUFFER', 30),
        'mark_late_as_present' => env('ATTENDANCE_MARK_LATE_AS_PRESENT', true),
        'auto_mark_absent' => env('ATTENDANCE_AUTO_MARK_ABSENT', false),
        'weekend_working_days' => ['saturday'], // sunday, monday, etc.
        'holiday_auto_absent' => env('ATTENDANCE_HOLIDAY_AUTO_ABSENT', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Status Configuration
    |--------------------------------------------------------------------------
    */
    'statuses' => [
        'present' => [
            'label' => 'Present',
            'color' => 'success',
            'icon' => 'check-circle',
            'count_as_present' => true,
        ],
        'absent' => [
            'label' => 'Absent',
            'color' => 'danger',
            'icon' => 'x-circle',
            'count_as_present' => false,
        ],
        'late' => [
            'label' => 'Late',
            'color' => 'warning',
            'icon' => 'clock',
            'count_as_present' => true,
        ],
        'excused' => [
            'label' => 'Excused',
            'color' => 'info',
            'icon' => 'info-circle',
            'count_as_present' => true,
        ],
    ],
    
    'rules' => [
    'grace_period_minutes' => 10,
    'late_threshold_minutes' => 15,
    'mark_late_as_present' => true,
    'prevent_future_dates' => true,
    'consecutive_absence_limit' => 3,
],

'notifications' => [
    'low_attendance_threshold' => 75.0,
    'parent_notifications' => true,
    'faculty_notifications' => true,
    'admin_alerts' => true,
],
    

    /*
    |--------------------------------------------------------------------------
    | Performance Levels
    |--------------------------------------------------------------------------
    */
    'performance_levels' => [
        'excellent' => ['min' => 90, 'color' => 'success', 'label' => 'Excellent'],
        'good' => ['min' => 80, 'color' => 'primary', 'label' => 'Good'],
        'satisfactory' => ['min' => 75, 'color' => 'warning', 'label' => 'Satisfactory'],
        'needs_improvement' => ['min' => 0, 'color' => 'danger', 'label' => 'Needs Improvement'],
    ],
    

    /*
    |--------------------------------------------------------------------------
    | Risk Levels
    |--------------------------------------------------------------------------
    */
    'risk_levels' => [
        'low' => ['color' => 'success', 'label' => 'Low Risk', 'threshold' => 85],
        'medium' => ['color' => 'warning', 'label' => 'Medium Risk', 'threshold' => 75],
        'high' => ['color' => 'danger', 'label' => 'High Risk', 'threshold' => 60],
        'critical' => ['color' => 'dark', 'label' => 'Critical', 'threshold' => 0],
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics & Caching
    |--------------------------------------------------------------------------
    */
    'analytics' => [
        'cache_enabled' => env('ATTENDANCE_CACHE_ENABLED', true),
        'cache_ttl' => env('ATTENDANCE_CACHE_TTL', 300), // 5 minutes
        'auto_update_cache' => env('ATTENDANCE_AUTO_UPDATE_CACHE', true),
        'batch_processing' => env('ATTENDANCE_BATCH_PROCESSING', true),
        'real_time_updates' => env('ATTENDANCE_REAL_TIME_UPDATES', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Reporting Configuration
    |--------------------------------------------------------------------------
    */
    'reports' => [
        'default_format' => env('ATTENDANCE_REPORT_FORMAT', 'pdf'),
        'available_formats' => ['pdf', 'excel', 'csv'],
        'include_charts' => env('ATTENDANCE_INCLUDE_CHARTS', true),
        'watermark' => env('ATTENDANCE_REPORT_WATERMARK', true),
        'auto_archive' => env('ATTENDANCE_AUTO_ARCHIVE_REPORTS', true),
        'archive_days' => env('ATTENDANCE_ARCHIVE_DAYS', 90),
    ],

    /*
    |--------------------------------------------------------------------------
    | Import/Export Settings
    |--------------------------------------------------------------------------
    */
    'import_export' => [
        'max_import_size' => env('ATTENDANCE_MAX_IMPORT_SIZE', 10000), // records
        'allowed_import_formats' => ['csv', 'xlsx'],
        'validation_strict' => env('ATTENDANCE_VALIDATION_STRICT', true),
        'backup_before_import' => env('ATTENDANCE_BACKUP_BEFORE_IMPORT', true),
        'export_chunk_size' => env('ATTENDANCE_EXPORT_CHUNK_SIZE', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    */
    'security' => [
        'log_changes' => env('ATTENDANCE_LOG_CHANGES', true),
        'require_reason_for_changes' => env('ATTENDANCE_REQUIRE_REASON', true),
        'prevent_future_dates' => env('ATTENDANCE_PREVENT_FUTURE', true),
        'edit_time_limit_hours' => env('ATTENDANCE_EDIT_TIME_LIMIT', 24),
        'admin_override_enabled' => env('ATTENDANCE_ADMIN_OVERRIDE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Settings
    |--------------------------------------------------------------------------
    */
    'integrations' => [
        'timetable_integration' => env('ATTENDANCE_TIMETABLE_INTEGRATION', true),
        'biometric_integration' => env('ATTENDANCE_BIOMETRIC_INTEGRATION', false),
        'mobile_app_integration' => env('ATTENDANCE_MOBILE_INTEGRATION', false),
        'parent_portal_sync' => env('ATTENDANCE_PARENT_PORTAL_SYNC', false),
        'sms_gateway_integration' => env('ATTENDANCE_SMS_INTEGRATION', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | UI/UX Settings
    |--------------------------------------------------------------------------
    */
    'ui' => [
        'theme_color' => env('ATTENDANCE_THEME_COLOR', 'primary'),
        'show_photos' => env('ATTENDANCE_SHOW_PHOTOS', true),
        'bulk_actions_enabled' => env('ATTENDANCE_BULK_ACTIONS', true),
        'quick_filters' => env('ATTENDANCE_QUICK_FILTERS', true),
        'auto_refresh_interval' => env('ATTENDANCE_AUTO_REFRESH', 30), // seconds
        'pagination_size' => env('ATTENDANCE_PAGINATION_SIZE', 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Retention
    |--------------------------------------------------------------------------
    */
    'data_retention' => [
        'keep_records_years' => env('ATTENDANCE_KEEP_RECORDS_YEARS', 7),
        'archive_old_records' => env('ATTENDANCE_ARCHIVE_OLD_RECORDS', true),
        'cleanup_logs_days' => env('ATTENDANCE_CLEANUP_LOGS_DAYS', 90),
        'cleanup_cache_days' => env('ATTENDANCE_CLEANUP_CACHE_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    */
    'validation' => [
        'required_fields' => ['student_id', 'attendance_date', 'status'],
        'optional_fields' => ['notes', 'late_minutes', 'location'],
        'max_note_length' => 500,
        'allowed_date_range_days' => 30, // How far back/forward can attendance be marked
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    */
    'features' => [
        'geolocation_tracking' => env('ATTENDANCE_GEOLOCATION', false),
        'photo_verification' => env('ATTENDANCE_PHOTO_VERIFICATION', false),
        'qr_code_attendance' => env('ATTENDANCE_QR_CODE', false),
        'facial_recognition' => env('ATTENDANCE_FACIAL_RECOGNITION', false),
        'voice_commands' => env('ATTENDANCE_VOICE_COMMANDS', false),
        'offline_mode' => env('ATTENDANCE_OFFLINE_MODE', false),
        'multi_device_sync' => env('ATTENDANCE_MULTI_DEVICE_SYNC', true),
    ],
];

// File: config/notifications.php

return [
    /*
    |--------------------------------------------------------------------------
    | Notification System Configuration
    |--------------------------------------------------------------------------
    */

    'enabled' => env('NOTIFICATIONS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Default Channels
    |--------------------------------------------------------------------------
    */
    'default_channels' => ['database', 'mail'],

    /*
    |--------------------------------------------------------------------------
    | Channel Configuration
    |--------------------------------------------------------------------------
    */
    'channels' => [
        'database' => [
            'enabled' => env('NOTIFICATIONS_DATABASE_ENABLED', true),
            'table' => 'system_notifications',
            'cleanup_days' => env('NOTIFICATIONS_DATABASE_CLEANUP_DAYS', 90),
        ],
        
        'mail' => [
            'enabled' => env('NOTIFICATIONS_MAIL_ENABLED', true),
            'queue' => env('NOTIFICATIONS_MAIL_QUEUE', 'emails'),
            'retry_attempts' => env('NOTIFICATIONS_MAIL_RETRY', 3),
            'template_path' => 'emails.notifications',
        ],
        
        'sms' => [
            'enabled' => env('NOTIFICATIONS_SMS_ENABLED', false),
            'provider' => env('SMS_PROVIDER', 'twilio'), // twilio, nexmo, etc.
            'queue' => env('NOTIFICATIONS_SMS_QUEUE', 'sms'),
            'retry_attempts' => env('NOTIFICATIONS_SMS_RETRY', 3),
            'rate_limit' => env('SMS_RATE_LIMIT', 100), // per hour
        ],
        
        'push' => [
            'enabled' => env('NOTIFICATIONS_PUSH_ENABLED', false),
            'provider' => env('PUSH_PROVIDER', 'fcm'), // fcm, apns
            'queue' => env('NOTIFICATIONS_PUSH_QUEUE', 'push'),
            'retry_attempts' => env('NOTIFICATIONS_PUSH_RETRY', 3),
        ],
        
        'slack' => [
            'enabled' => env('NOTIFICATIONS_SLACK_ENABLED', false),
            'webhook_url' => env('SLACK_WEBHOOK_URL'),
            'channel' => env('SLACK_CHANNEL', '#general'),
            'username' => env('SLACK_USERNAME', 'AttendanceBot'),
        ],
        
        'whatsapp' => [
            'enabled' => env('NOTIFICATIONS_WHATSAPP_ENABLED', false),
            'provider' => env('WHATSAPP_PROVIDER', 'twilio'),
            'queue' => env('NOTIFICATIONS_WHATSAPP_QUEUE', 'whatsapp'),
            'retry_attempts' => env('NOTIFICATIONS_WHATSAPP_RETRY', 3),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Attendance Notifications
    |--------------------------------------------------------------------------
    */
    'attendance' => [
        'enabled' => env('ATTENDANCE_NOTIFICATIONS_ENABLED', true),
        
        'low_attendance' => [
            'enabled' => env('ATTENDANCE_LOW_NOTIFICATION_ENABLED', true),
            'threshold' => env('ATTENDANCE_LOW_THRESHOLD', 75),
            'channels' => ['database', 'mail'],
            'recipients' => ['student', 'parent', 'admin'],
            'frequency' => 'weekly', // daily, weekly, monthly
            'template' => 'attendance.low_attendance',
        ],
        
        'absent_alert' => [
            'enabled' => env('ATTENDANCE_ABSENT_ALERT_ENABLED', true),
            'immediate' => env('ATTENDANCE_IMMEDIATE_ABSENT_ALERT', true),
            'channels' => ['database', 'sms'],
            'recipients' => ['parent'],
            'template' => 'attendance.absent_alert',
        ],
        
        'daily_summary' => [
            'enabled' => env('ATTENDANCE_DAILY_SUMMARY_ENABLED', true),
            'time' => env('ATTENDANCE_DAILY_SUMMARY_TIME', '18:00'),
            'channels' => ['database', 'mail'],
            'recipients' => ['admin', 'faculty'],
            'template' => 'attendance.daily_summary',
        ],
        
        'weekly_report' => [
            'enabled' => env('ATTENDANCE_WEEKLY_REPORT_ENABLED', true),
            'day' => env('ATTENDANCE_WEEKLY_REPORT_DAY', 'friday'),
            'time' => env('ATTENDANCE_WEEKLY_REPORT_TIME', '17:00'),
            'channels' => ['database', 'mail'],
            'recipients' => ['admin', 'parent'],
            'template' => 'attendance.weekly_report',
        ],
        
        'monthly_report' => [
            'enabled' => env('ATTENDANCE_MONTHLY_REPORT_ENABLED', true),
            'day' => env('ATTENDANCE_MONTHLY_REPORT_DAY', 1), // 1st of month
            'time' => env('ATTENDANCE_MONTHLY_REPORT_TIME', '09:00'),
            'channels' => ['database', 'mail'],
            'recipients' => ['admin', 'parent'],
            'template' => 'attendance.monthly_report',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Biometric Notifications
    |--------------------------------------------------------------------------
    */
    'biometric' => [
        'enabled' => env('BIOMETRIC_NOTIFICATIONS_ENABLED', true),
        
        'device_offline' => [
            'enabled' => env('BIOMETRIC_DEVICE_OFFLINE_ENABLED', true),
            'channels' => ['database', 'slack'],
            'recipients' => ['admin', 'technical'],
            'template' => 'biometric.device_offline',
        ],
        
        'sync_failure' => [
            'enabled' => env('BIOMETRIC_SYNC_FAILURE_ENABLED', true),
            'channels' => ['database', 'mail'],
            'recipients' => ['admin'],
            'template' => 'biometric.sync_failure',
        ],
        
        'unauthorized_access' => [
            'enabled' => env('BIOMETRIC_UNAUTHORIZED_ACCESS_ENABLED', true),
            'channels' => ['database', 'slack', 'mail'],
            'recipients' => ['admin', 'security'],
            'template' => 'biometric.unauthorized_access',
            'priority' => 'high',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | System Notifications
    |--------------------------------------------------------------------------
    */
    'system' => [
        'enabled' => env('SYSTEM_NOTIFICATIONS_ENABLED', true),
        
        'maintenance' => [
            'enabled' => env('SYSTEM_MAINTENANCE_NOTIFICATIONS_ENABLED', true),
            'channels' => ['database', 'mail'],
            'recipients' => ['admin'],
            'advance_notice_hours' => 24,
        ],
        
        'backup' => [
            'enabled' => env('SYSTEM_BACKUP_NOTIFICATIONS_ENABLED', true),
            'channels' => ['database'],
            'recipients' => ['admin'],
            'notify_on_success' => false,
            'notify_on_failure' => true,
        ],
        
        'security' => [
            'enabled' => env('SYSTEM_SECURITY_NOTIFICATIONS_ENABLED', true),
            'channels' => ['database', 'mail', 'slack'],
            'recipients' => ['admin', 'security'],
            'priority' => 'high',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Recipient Configuration
    |--------------------------------------------------------------------------
    */
    'recipients' => [
        'student' => [
            'channels' => ['database'],
            'contact_field' => 'email',
        ],
        
        'parent' => [
            'channels' => ['sms', 'mail', 'whatsapp'],
            'contact_sources' => ['parent_contacts', 'student.parent_email', 'student.parent_mobile'],
            'preferred_channel' => 'sms',
        ],
        
        'faculty' => [
            'channels' => ['database', 'mail'],
            'contact_field' => 'email',
            'roles' => ['faculty', 'staff'],
        ],
        
        'admin' => [
            'channels' => ['database', 'mail', 'slack'],
            'contact_field' => 'email',
            'roles' => ['super-admin', 'college-admin'],
        ],
        
        'technical' => [
            'channels' => ['database', 'slack'],
            'contact_field' => 'email',
            'roles' => ['technical-admin'],
        ],
        
        'security' => [
            'channels' => ['database', 'mail', 'sms'],
            'contact_field' => 'email',
            'roles' => ['security-admin'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Priority Levels
    |--------------------------------------------------------------------------
    */
    'priorities' => [
        'low' => [
            'label' => 'Low',
            'color' => 'secondary',
            'delivery_delay' => 0, // minutes
        ],
        'normal' => [
            'label' => 'Normal',
            'color' => 'primary',
            'delivery_delay' => 0,
        ],
        'high' => [
            'label' => 'High',
            'color' => 'warning',
            'delivery_delay' => 0,
        ],
        'urgent' => [
            'label' => 'Urgent',
            'color' => 'danger',
            'delivery_delay' => 0,
            'force_immediate' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Templates
    |--------------------------------------------------------------------------
    */
    'templates' => [
        'path' => 'notifications',
        'default_layout' => 'notifications.layout',
        'cache_compiled' => env('NOTIFICATION_TEMPLATE_CACHE', true),
        'variables' => [
            'app_name' => env('APP_NAME', 'College Management System'),
            'app_url' => env('APP_URL', 'http://localhost'),
            'support_email' => env('SUPPORT_EMAIL', 'support@college.edu'),
            'support_phone' => env('SUPPORT_PHONE', '+1-234-567-8900'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    */
    'queue' => [
        'enabled' => env('NOTIFICATION_QUEUE_ENABLED', true),
        'default_queue' => env('NOTIFICATION_DEFAULT_QUEUE', 'notifications'),
        'high_priority_queue' => env('NOTIFICATION_HIGH_PRIORITY_QUEUE', 'high-priority'),
        'batch_size' => env('NOTIFICATION_BATCH_SIZE', 100),
        'retry_attempts' => env('NOTIFICATION_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('NOTIFICATION_RETRY_DELAY', 60), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'rate_limiting' => [
        'enabled' => env('NOTIFICATION_RATE_LIMITING_ENABLED', true),
        'per_user_per_hour' => env('NOTIFICATION_RATE_LIMIT_USER', 50),
        'per_type_per_hour' => env('NOTIFICATION_RATE_LIMIT_TYPE', 100),
        'global_per_hour' => env('NOTIFICATION_RATE_LIMIT_GLOBAL', 1000),
        'burst_allowance' => env('NOTIFICATION_BURST_ALLOWANCE', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics & Tracking
    |--------------------------------------------------------------------------
    */
    'analytics' => [
        'enabled' => env('NOTIFICATION_ANALYTICS_ENABLED', true),
        'track_opens' => env('NOTIFICATION_TRACK_OPENS', true),
        'track_clicks' => env('NOTIFICATION_TRACK_CLICKS', true),
        'track_delivery' => env('NOTIFICATION_TRACK_DELIVERY', true),
        'retention_days' => env('NOTIFICATION_ANALYTICS_RETENTION', 90),
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    */
    'features' => [
        'digest_mode' => env('NOTIFICATION_DIGEST_MODE', false),
        'smart_scheduling' => env('NOTIFICATION_SMART_SCHEDULING', false),
        'ai_optimization' => env('NOTIFICATION_AI_OPTIMIZATION', false),
        'multilingual' => env('NOTIFICATION_MULTILINGUAL', false),
        'dark_mode_emails' => env('NOTIFICATION_DARK_MODE_EMAILS', false),
        'rich_notifications' => env('NOTIFICATION_RICH_NOTIFICATIONS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    */
    'security' => [
        'encrypt_sensitive_data' => env('NOTIFICATION_ENCRYPT_SENSITIVE', true),
        'sanitize_content' => env('NOTIFICATION_SANITIZE_CONTENT', true),
        'verify_recipients' => env('NOTIFICATION_VERIFY_RECIPIENTS', true),
        'log_all_notifications' => env('NOTIFICATION_LOG_ALL', true),
        'audit_trail' => env('NOTIFICATION_AUDIT_TRAIL', true),
    ],
];