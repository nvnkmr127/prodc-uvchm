<?php

return [
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

    'development' => [
        'show_sql_queries' => env('SHOW_REMINDER_SQL_QUERIES', false),
    ],
];
