<?php

// config/mobile_validation.php

return [

    /*
    |--------------------------------------------------------------------------
    | Mobile Number Validation Settings
    |--------------------------------------------------------------------------
    |
    | Configure how mobile number validation works across the application.
    |
    */

    // Enable/disable mobile duplicate validation
    'enable_duplicate_validation' => env('MOBILE_DUPLICATE_VALIDATION', true),

    // Mobile number format patterns by country
    'patterns' => [
        'india' => [
            'pattern' => '/^[6-9]\d{9}$/',
            'description' => '10 digits starting with 6, 7, 8, or 9',
            'example' => '9876543210'
        ],
        'international' => [
            'pattern' => '/^\+[1-9]\d{1,14}$/',
            'description' => 'International format with country code',
            'example' => '+919876543210'
        ]
    ],

    // Default country for validation
    'default_country' => env('MOBILE_DEFAULT_COUNTRY', 'india'),

    // Cross-field validation settings
    'cross_validation' => [
        // Prevent student and father mobile from being the same
        'prevent_same_mobiles' => env('PREVENT_SAME_MOBILES', true),
        
        // Prevent cross-field duplicates (student mobile as father mobile in another record)
        'prevent_cross_duplicates' => env('PREVENT_CROSS_DUPLICATES', true),
    ],

    // Import settings
    'import' => [
        // What to do with duplicate mobiles during import
        'duplicate_handling' => env('IMPORT_DUPLICATE_HANDLING', 'skip'), // skip, overwrite, append_suffix
        
        // Log duplicate findings
        'log_duplicates' => env('LOG_IMPORT_DUPLICATES', true),
        
        // Maximum attempts to generate unique numbers
        'max_generation_attempts' => env('MAX_MOBILE_GENERATION_ATTEMPTS', 100),
    ],

    // API settings for real-time validation
    'api' => [
        // Enable real-time duplicate checking via AJAX
        'enable_realtime_check' => env('ENABLE_REALTIME_MOBILE_CHECK', true),
        
        // Debounce delay for API calls (milliseconds)
        'debounce_delay' => env('MOBILE_CHECK_DEBOUNCE', 800),
        
        // Rate limiting for duplicate check API
        'rate_limit' => env('MOBILE_CHECK_RATE_LIMIT', '60,1'), // 60 requests per minute
    ],

    // Cleanup settings
    'cleanup' => [
        // Auto-cleanup duplicate mobiles
        'auto_cleanup' => env('AUTO_CLEANUP_DUPLICATE_MOBILES', false),
        
        // Cleanup strategy: 'clear_duplicates', 'keep_oldest', 'keep_newest'
        'cleanup_strategy' => env('MOBILE_CLEANUP_STRATEGY', 'clear_duplicates'),
        
        // Schedule for automatic cleanup
        'cleanup_schedule' => env('MOBILE_CLEANUP_SCHEDULE', 'weekly'),
    ],

    // Notification settings
    'notifications' => [
        // Notify administrators about duplicate mobiles found
        'notify_admin_duplicates' => env('NOTIFY_ADMIN_MOBILE_DUPLICATES', true),
        
        // Notify users when their mobile is found as duplicate
        'notify_user_duplicates' => env('NOTIFY_USER_MOBILE_DUPLICATES', false),
        
        // Channels for notifications
        'notification_channels' => ['mail', 'database'],
    ],

    // Validation messages
    'messages' => [
        'format_invalid' => 'Mobile number must be a valid :format format (:example)',
        'duplicate_student' => 'This student mobile number is already registered with another student.',
        'duplicate_father' => 'This father mobile number is already registered with another student.',
        'same_mobiles' => 'Father mobile number cannot be the same as student mobile number.',
        'cross_duplicate' => 'This mobile number is already registered as :field for another student.',
        'not_available' => 'This mobile number is not available.',
        'available' => 'Mobile number is available.',
        'checking' => 'Checking availability...',
        'error_checking' => 'Could not verify mobile number availability.',
    ],

    // Database settings
    'database' => [
        // Add indexes for better performance
        'add_indexes' => env('ADD_MOBILE_INDEXES', true),
        
        // Index names
        'indexes' => [
            'student_mobile' => 'idx_students_student_mobile',
            'father_mobile' => 'idx_students_father_mobile',
        ],
    ],

    // Feature flags
    'features' => [
        // Enable enhanced mobile validation in forms
        'enhanced_form_validation' => env('ENHANCED_MOBILE_FORM_VALIDATION', true),
        
        // Enable bulk duplicate checking for imports
        'bulk_duplicate_check' => env('BULK_MOBILE_DUPLICATE_CHECK', true),
        
        // Enable mobile number formatting in UI
        'format_display' => env('FORMAT_MOBILE_DISPLAY', true),
        
        // Enable mobile number masking for privacy
        'enable_masking' => env('ENABLE_MOBILE_MASKING', false),
    ],

];