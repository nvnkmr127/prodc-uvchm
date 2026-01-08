<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;


class SettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:manage settings');
    }
    /**
     * Setting groups configuration with their metadata
     * COMPLETE IMPLEMENTATION - ALL GROUPS INCLUDED
     */
    private function getSettingGroups()
    {
        return [
            'general' => [
                'title' => 'General Settings',
                'icon' => 'fas fa-cog',
                'description' => 'Basic application configuration',
                'fields' => [
                    'app_name' => [
                        'label' => 'Application Name',
                        'type' => 'text',
                        'required' => true,
                        'placeholder' => 'College Management System',
                        'help' => 'Name displayed throughout the application',
                        'default' => 'College Management System'
                    ],
                    'app_tagline' => [
                        'label' => 'Application Tagline',
                        'type' => 'text',
                        'placeholder' => 'Empowering Education Excellence',
                        'help' => 'Short description or motto',
                        'default' => 'Empowering Education Excellence'
                    ],
                    'timezone' => [
                        'label' => 'Default Timezone',
                        'type' => 'select',
                        'options' => [
                            'Asia/Kolkata' => 'Asia/Kolkata (IST)',
                            'UTC' => 'UTC',
                            'America/New_York' => 'America/New_York (EST)',
                            'Europe/London' => 'Europe/London (GMT)',
                            'America/Los_Angeles' => 'America/Los_Angeles (PST)',
                            'Australia/Sydney' => 'Australia/Sydney (AEST)',
                            'Asia/Dubai' => 'Asia/Dubai (GST)',
                            'Asia/Singapore' => 'Asia/Singapore (SGT)',
                        ],
                        'default' => 'Asia/Kolkata'
                    ],
                    'date_format' => [
                        'label' => 'Date Format',
                        'type' => 'select',
                        'options' => [
                            'd-m-Y' => 'DD-MM-YYYY (31-12-2024)',
                            'Y-m-d' => 'YYYY-MM-DD (2024-12-31)',
                            'm/d/Y' => 'MM/DD/YYYY (12/31/2024)',
                            'd/m/Y' => 'DD/MM/YYYY (31/12/2024)',
                            'j F Y' => 'Day Month Year (31 December 2024)',
                            'F j, Y' => 'Month Day, Year (December 31, 2024)',
                        ],
                        'default' => 'd-m-Y'
                    ],
                    'time_format' => [
                        'label' => 'Time Format',
                        'type' => 'select',
                        'options' => [
                            'H:i' => '24 Hour (14:30)',
                            'h:i A' => '12 Hour (02:30 PM)',
                        ],
                        'default' => 'H:i'
                    ],
                    'maintenance_mode' => [
                        'label' => 'Maintenance Mode',
                        'type' => 'toggle',
                        'help' => 'Put application in maintenance mode',
                        'default' => '0'
                    ],
                    'debug_mode' => [
                        'label' => 'Debug Mode',
                        'type' => 'toggle',
                        'help' => 'Enable debug mode for development',
                        'default' => '0'
                    ],
                ]
            ],
            'college' => [
                'title' => 'College Information',
                'icon' => 'fas fa-university',
                'description' => 'College details and contact information',
                'fields' => [
                    'college_name' => [
                        'label' => 'College Name',
                        'type' => 'text',
                        'required' => true,
                        'placeholder' => 'ABC College of Technology',
                        'help' => 'Official college name'
                    ],
                    'college_short_name' => [
                        'label' => 'Short Name/Acronym',
                        'type' => 'text',
                        'placeholder' => 'ACT',
                        'help' => 'College abbreviation or acronym'
                    ],
                    'college_logo' => [
                        'label' => 'College Logo',
                        'type' => 'file',
                        'accept' => 'image/*',
                        'help' => 'Upload college logo (recommended: 200x200px, PNG/JPG)'
                    ],
                    'college_email' => [
                        'label' => 'Primary Email',
                        'type' => 'email',
                        'placeholder' => 'info@college.edu',
                        'help' => 'Main contact email address'
                    ],
                    'college_phone' => [
                        'label' => 'Phone Number',
                        'type' => 'tel',
                        'placeholder' => '+91 98765 43210',
                        'help' => 'Primary contact phone number'
                    ],
                    'college_fax' => [
                        'label' => 'Fax Number',
                        'type' => 'tel',
                        'placeholder' => '+91 11 12345678',
                        'help' => 'Fax number (optional)'
                    ],
                    'college_website' => [
                        'label' => 'Website URL',
                        'type' => 'url',
                        'placeholder' => 'https://www.yourcollege.edu',
                        'help' => 'Official college website'
                    ],
                    'college_address' => [
                        'label' => 'Complete Address',
                        'type' => 'textarea',
                        'rows' => 3,
                        'placeholder' => 'Street Address, City, State, PIN Code, Country',
                        'help' => 'Full postal address of the college'
                    ],
                    'college_established_year' => [
                        'label' => 'Established Year',
                        'type' => 'number',
                        'min' => 1800,
                        'max' => date('Y'),
                        'placeholder' => '1990',
                        'help' => 'Year the college was established'
                    ],
                    'college_affiliation' => [
                        'label' => 'Affiliation/University',
                        'type' => 'text',
                        'placeholder' => 'University of Technology',
                        'help' => 'Affiliated university or board'
                    ],
                    'college_accreditation' => [
                        'label' => 'Accreditation',
                        'type' => 'text',
                        'placeholder' => 'NAAC A+ Grade',
                        'help' => 'Accreditation details'
                    ],
                ]
            ],
            'academic' => [
                'title' => 'Academic Settings',
                'icon' => 'fas fa-graduation-cap',
                'description' => 'Academic year and enrollment configuration',
                'fields' => [
                    'current_academic_year' => [
                        'label' => 'Current Academic Year',
                        'type' => 'text',
                        'placeholder' => '2024-2025',
                        'help' => 'Format: YYYY-YYYY',
                        'default' => date('Y') . '-' . (date('Y') + 1)
                    ],
                    'enrollment_prefix' => [
                        'label' => 'Enrollment Number Prefix',
                        'type' => 'text',
                        'placeholder' => 'STD',
                        'help' => 'Prefix for student enrollment numbers',
                        'default' => 'STD'
                    ],
                    'semester_system' => [
                        'label' => 'Use Semester System',
                        'type' => 'toggle',
                        'help' => 'Enable semester-based academic structure',
                        'default' => '1'
                    ],
                    'auto_promotion' => [
                        'label' => 'Auto Student Promotion',
                        'type' => 'toggle',
                        'help' => 'Automatically promote students to next semester/year',
                        'default' => '0'
                    ],
                    'academic_session_start' => [
                        'label' => 'Academic Session Start Month',
                        'type' => 'select',
                        'options' => [
                            '01' => 'January',
                            '02' => 'February',
                            '03' => 'March',
                            '04' => 'April',
                            '05' => 'May',
                            '06' => 'June',
                            '07' => 'July',
                            '08' => 'August',
                            '09' => 'September',
                            '10' => 'October',
                            '11' => 'November',
                            '12' => 'December'
                        ],
                        'default' => '07'
                    ],
                    'academic_session_end' => [
                        'label' => 'Academic Session End Month',
                        'type' => 'select',
                        'options' => [
                            '01' => 'January',
                            '02' => 'February',
                            '03' => 'March',
                            '04' => 'April',
                            '05' => 'May',
                            '06' => 'June',
                            '07' => 'July',
                            '08' => 'August',
                            '09' => 'September',
                            '10' => 'October',
                            '11' => 'November',
                            '12' => 'December'
                        ],
                        'default' => '06'
                    ],
                    'default_session_duration' => [
                        'label' => 'Default Class Duration (minutes)',
                        'type' => 'number',
                        'min' => 30,
                        'max' => 480,
                        'default' => '60',
                        'help' => 'Default duration for class sessions'
                    ],
                    'exam_result_publish' => [
                        'label' => 'Auto-Publish Exam Results',
                        'type' => 'toggle',
                        'help' => 'Automatically publish results when all marks are entered',
                        'default' => '0'
                    ],
                    'data_retention_years' => [
                        'label' => 'Data Retention Period (Years)',
                        'type' => 'number',
                        'min' => 1,
                        'max' => 50,
                        'default' => '7',
                        'help' => 'How long to keep student data after graduation'
                    ]
                ]
            ],
            'financial' => [
                'title' => 'Financial Settings',
                'icon' => 'fas fa-dollar-sign',
                'description' => 'Fee structure and payment configuration',
                'fields' => [
                    'currency_symbol' => [
                        'label' => 'Currency Symbol',
                        'type' => 'text',
                        'placeholder' => '₹',
                        'default' => '₹',
                        'maxlength' => 5,
                        'help' => 'Symbol displayed with amounts'
                    ],
                    'currency_code' => [
                        'label' => 'Currency Code',
                        'type' => 'select',
                        'options' => [
                            'INR' => 'Indian Rupee (INR)',
                            'USD' => 'US Dollar (USD)',
                            'EUR' => 'Euro (EUR)',
                            'GBP' => 'British Pound (GBP)',
                            'AUD' => 'Australian Dollar (AUD)',
                            'CAD' => 'Canadian Dollar (CAD)',
                            'SGD' => 'Singapore Dollar (SGD)',
                            'AED' => 'UAE Dirham (AED)',
                        ],
                        'default' => 'INR'
                    ],
                    'decimal_places' => [
                        'label' => 'Decimal Places',
                        'type' => 'select',
                        'options' => [
                            '0' => 'No decimals (₹100)',
                            '1' => '1 decimal (₹100.0)',
                            '2' => '2 decimals (₹100.00)',
                        ],
                        'default' => '2'
                    ],
                    'tax_rate' => [
                        'label' => 'Default Tax Rate (%)',
                        'type' => 'number',
                        'min' => 0,
                        'max' => 100,
                        'step' => 0.01,
                        'default' => '0',
                        'help' => 'Default tax percentage applied to fees'
                    ],
                    'late_fee_percentage' => [
                        'label' => 'Late Payment Fee (%)',
                        'type' => 'number',
                        'min' => 0,
                        'max' => 50,
                        'step' => 0.1,
                        'default' => '5',
                        'help' => 'Late fee percentage on overdue payments'
                    ],
                    'late_fee_grace_days' => [
                        'label' => 'Late Fee Grace Period (Days)',
                        'type' => 'number',
                        'min' => 0,
                        'max' => 90,
                        'default' => '7',
                        'help' => 'Grace period before late fees apply'
                    ],
                    'partial_payment_allowed' => [
                        'label' => 'Allow Partial Payments',
                        'type' => 'toggle',
                        'help' => 'Allow students to pay fees in installments',
                        'default' => '1'
                    ],
                    'minimum_partial_amount' => [
                        'label' => 'Minimum Partial Payment Amount',
                        'type' => 'number',
                        'min' => 1,
                        'default' => '1000',
                        'help' => 'Minimum amount for partial payments'
                    ],
                    // ✅ CHANGED: 'invoice_prefix' updated to 'component_prefix'
                    'component_prefix' => [
                        'label' => 'Component Number Prefix',
                        'type' => 'text',
                        'placeholder' => 'INV',
                        'default' => 'INV',
                        'help' => 'Prefix for component/receipt numbers'
                    ],
                    // ✅ CHANGED: 'invoice_footer' updated to 'component_footer'
                    'component_footer' => [
                        'label' => 'Component/Receipt Footer Text',
                        'type' => 'textarea',
                        'rows' => 3,
                        'placeholder' => 'Thank you for your payment. For any queries, contact the accounts department.',
                        'help' => 'Text displayed at bottom of components/receipts'
                    ],
                    'receipt_footer' => [
                        'label' => 'Receipt Footer Text',
                        'type' => 'textarea',
                        'rows' => 2,
                        'placeholder' => 'This is a computer generated receipt.',
                        'help' => 'Text displayed at bottom of payment receipts'
                    ],
                ]
            ],
            'email' => [
                'title' => 'Email Configuration',
                'icon' => 'fas fa-envelope',
                'description' => 'SMTP and email notification settings',
                'fields' => [
                    'mail_driver' => [
                        'label' => 'Mail Driver',
                        'type' => 'select',
                        'options' => [
                            'smtp' => 'SMTP',
                            'sendmail' => 'Sendmail',
                            'mailgun' => 'Mailgun',
                            'ses' => 'Amazon SES',
                            'postmark' => 'Postmark',
                        ],
                        'default' => 'smtp'
                    ],
                    'mail_host' => [
                        'label' => 'SMTP Host',
                        'type' => 'text',
                        'placeholder' => 'smtp.gmail.com',
                        'help' => 'SMTP server hostname'
                    ],
                    'mail_port' => [
                        'label' => 'SMTP Port',
                        'type' => 'number',
                        'min' => 1,
                        'max' => 65535,
                        'default' => '587',
                        'help' => 'SMTP server port (587 for TLS, 465 for SSL)'
                    ],
                    'mail_username' => [
                        'label' => 'SMTP Username',
                        'type' => 'email',
                        'placeholder' => 'your-email@gmail.com',
                        'help' => 'SMTP authentication username'
                    ],
                    'mail_password' => [
                        'label' => 'SMTP Password',
                        'type' => 'password',
                        'help' => 'SMTP authentication password (use app-specific password for Gmail)'
                    ],
                    'mail_encryption' => [
                        'label' => 'Encryption Method',
                        'type' => 'select',
                        'options' => [
                            'tls' => 'TLS (Recommended)',
                            'ssl' => 'SSL',
                            'none' => 'None (Not recommended)'
                        ],
                        'default' => 'tls'
                    ],
                    'mail_from_address' => [
                        'label' => 'From Email Address',
                        'type' => 'email',
                        'placeholder' => 'noreply@college.edu',
                        'help' => 'Default sender email address'
                    ],
                    'mail_from_name' => [
                        'label' => 'From Name',
                        'type' => 'text',
                        'placeholder' => 'College Management System',
                        'help' => 'Default sender name'
                    ],
                    'mail_reply_to' => [
                        'label' => 'Reply-To Email',
                        'type' => 'email',
                        'placeholder' => 'support@college.edu',
                        'help' => 'Email address for replies'
                    ],
                    'email_notifications' => [
                        'label' => 'Enable Email Notifications',
                        'type' => 'toggle',
                        'help' => 'Send automated email notifications',
                        'default' => '1'
                    ],
                    'email_queue_enabled' => [
                        'label' => 'Queue Email Sending',
                        'type' => 'toggle',
                        'help' => 'Queue emails for background processing',
                        'default' => '1'
                    ],
                ]
            ],
            'attendance' => [
                'title' => 'Attendance Settings',
                'icon' => 'fas fa-calendar-check',
                'description' => 'Attendance tracking and requirements',
                'fields' => [
                    'minimum_attendance_percentage' => [
                        'label' => 'Minimum Attendance Percentage',
                        'type' => 'number',
                        'help' => 'Minimum attendance required for exam eligibility (%)',
                        'default' => '75',
                        'min' => '0',
                        'max' => '100'
                    ],
                    'attendance_grace_period' => [
                        'label' => 'Late Arrival Grace Period',
                        'type' => 'number',
                        'help' => 'Grace period for late arrivals (minutes)',
                        'default' => '10',
                        'min' => '0',
                        'max' => '60'
                    ],
                    'attendance_auto_mark' => [
                        'label' => 'Auto-Mark Attendance',
                        'type' => 'toggle',
                        'help' => 'Automatically mark attendance based on schedules',
                        'default' => '0'
                    ],
                    'weekend_working' => [
                        'label' => 'Working Weekend Days',
                        'type' => 'multiselect',
                        'options' => [
                            'saturday' => 'Saturday',
                            'sunday' => 'Sunday'
                        ],
                        'default' => '["saturday"]',
                        'help' => 'Select working weekend days'
                    ],
                    'holiday_auto_absent' => [
                        'label' => 'Auto-Mark Absent on Holidays',
                        'type' => 'toggle',
                        'help' => 'Automatically mark students absent on declared holidays',
                        'default' => '0'
                    ],
                    'attendance_sms_alerts' => [
                        'label' => 'SMS Attendance Alerts',
                        'type' => 'toggle',
                        'help' => 'Send SMS alerts for attendance updates',
                        'default' => '0'
                    ],
                    'attendance_email_alerts' => [
                        'label' => 'Email Attendance Alerts',
                        'type' => 'toggle',
                        'help' => 'Send email alerts for low attendance',
                        'default' => '1'
                    ],
                    'attendance_report_frequency' => [
                        'label' => 'Report Generation Frequency',
                        'type' => 'select',
                        'options' => [
                            'daily' => 'Daily',
                            'weekly' => 'Weekly',
                            'monthly' => 'Monthly'
                        ],
                        'default' => 'weekly',
                        'help' => 'How often to generate attendance reports'
                    ],
                    'attendance_low_threshold' => [
                        'label' => 'Low Attendance Threshold',
                        'type' => 'number',
                        'help' => 'Send alerts when attendance falls below this percentage',
                        'default' => '80',
                        'min' => '0',
                        'max' => '100'
                    ]
                ]
            ],
            'sms' => [
                'title' => 'SMS Configuration',
                'icon' => 'fas fa-sms',
                'description' => 'SMS gateway and notification settings',
                'fields' => [
                    'sms_enabled' => [
                        'label' => 'Enable SMS Notifications',
                        'type' => 'toggle',
                        'help' => 'Enable SMS notifications throughout the system',
                        'default' => '0'
                    ],
                    'sms_provider' => [
                        'label' => 'SMS Provider',
                        'type' => 'select',
                        'options' => [
                            'twilio' => 'Twilio',
                            'textlocal' => 'TextLocal',
                            'msg91' => 'MSG91',
                            'fast2sms' => 'Fast2SMS',
                            'custom' => 'Custom API',
                        ],
                        'default' => 'textlocal'
                    ],
                    'sms_api_key' => [
                        'label' => 'SMS API Key',
                        'type' => 'password',
                        'help' => 'API key from your SMS provider'
                    ],
                    'sms_api_secret' => [
                        'label' => 'SMS API Secret',
                        'type' => 'password',
                        'help' => 'API secret from your SMS provider (if required)'
                    ],
                    'sms_sender_id' => [
                        'label' => 'SMS Sender ID',
                        'type' => 'text',
                        'placeholder' => 'COLLEGE',
                        'help' => 'Sender ID that appears in SMS messages'
                    ],
                    'sms_api_url' => [
                        'label' => 'Custom SMS API URL',
                        'type' => 'url',
                        'placeholder' => 'https://api.sms-provider.com/send',
                        'help' => 'API endpoint for custom SMS provider'
                    ],
                ]
            ],
            'security' => [
                'title' => 'Security Settings',
                'icon' => 'fas fa-shield-alt',
                'description' => 'Authentication and security configuration',
                'fields' => [
                    'password_min_length' => [
                        'label' => 'Minimum Password Length',
                        'type' => 'number',
                        'min' => 6,
                        'max' => 50,
                        'default' => '8',
                        'help' => 'Minimum number of characters required for passwords'
                    ],
                    'password_require_uppercase' => [
                        'label' => 'Require Uppercase Letters',
                        'type' => 'toggle',
                        'help' => 'Passwords must contain at least one uppercase letter',
                        'default' => '1'
                    ],
                    'password_require_numbers' => [
                        'label' => 'Require Numbers',
                        'type' => 'toggle',
                        'help' => 'Passwords must contain at least one number',
                        'default' => '1'
                    ],
                    'password_require_symbols' => [
                        'label' => 'Require Special Characters',
                        'type' => 'toggle',
                        'help' => 'Passwords must contain at least one special character',
                        'default' => '0'
                    ],
                    'session_timeout' => [
                        'label' => 'Session Timeout (minutes)',
                        'type' => 'number',
                        'min' => 15,
                        'max' => 1440,
                        'default' => '120',
                        'help' => 'Automatic logout after inactivity'
                    ],
                    'max_login_attempts' => [
                        'label' => 'Maximum Login Attempts',
                        'type' => 'number',
                        'min' => 3,
                        'max' => 20,
                        'default' => '5',
                        'help' => 'Maximum failed login attempts before account lockout'
                    ],
                    'lockout_duration' => [
                        'label' => 'Account Lockout Duration (minutes)',
                        'type' => 'number',
                        'min' => 5,
                        'max' => 1440,
                        'default' => '15',
                        'help' => 'How long accounts remain locked after failed attempts'
                    ],
                    'two_factor_auth' => [
                        'label' => 'Enable Two-Factor Authentication',
                        'type' => 'toggle',
                        'help' => 'Require 2FA for admin accounts',
                        'default' => '0'
                    ],
                    'force_password_change' => [
                        'label' => 'Force Password Change (days)',
                        'type' => 'number',
                        'min' => 0,
                        'max' => 365,
                        'default' => '90',
                        'help' => 'Force password change every X days (0 to disable)'
                    ],
                ]
            ],


            'api' => [
                'title' => 'API & Integration',
                'icon' => 'fas fa-plug',
                'description' => 'External API and integration settings',
                'fields' => [
                    'api_enabled' => [
                        'label' => 'Enable API Access',
                        'type' => 'toggle',
                        'help' => 'Allow external API access to the system',
                        'default' => '0'
                    ],
                    'api_rate_limit' => [
                        'label' => 'API Rate Limit (requests/minute)',
                        'type' => 'number',
                        'min' => 10,
                        'max' => 1000,
                        'default' => '60',
                        'help' => 'Maximum API requests per minute per user'
                    ],
                    'api_version' => [
                        'label' => 'Default API Version',
                        'type' => 'select',
                        'options' => [
                            'v1' => 'Version 1.0',
                            'v2' => 'Version 2.0',
                        ],
                        'default' => 'v1'
                    ],
                    'webhook_enabled' => [
                        'label' => 'Enable Webhooks',
                        'type' => 'toggle',
                        'help' => 'Allow sending webhooks for events',
                        'default' => '0'
                    ],
                    'webhook_secret' => [
                        'label' => 'Webhook Secret Key',
                        'type' => 'password',
                        'help' => 'Secret key for webhook security'
                    ],
                    'google_calendar_integration' => [
                        'label' => 'Google Calendar Integration',
                        'type' => 'toggle',
                        'help' => 'Sync events with Google Calendar',
                        'default' => '0'
                    ],
                    'google_api_key' => [
                        'label' => 'Google API Key',
                        'type' => 'password',
                        'help' => 'API key for Google services integration'
                    ],
                ]
            ],
            'backup' => [
                'title' => 'Backup Settings',
                'icon' => 'fas fa-database',
                'description' => 'Configure backup and restore options',
                'fields' => [
                    'auto_backup' => [
                        'label' => 'Enable Automatic Backups',
                        'type' => 'toggle',
                        'help' => 'Enable automatic database backups',
                        'default' => '1'
                    ],
                    'backup_frequency' => [
                        'label' => 'Backup Frequency',
                        'type' => 'select',
                        'options' => [
                            'daily' => 'Daily',
                            'weekly' => 'Weekly',
                            'monthly' => 'Monthly',
                        ],
                        'default' => 'daily',
                        'help' => 'How often to create automatic backups'
                    ],
                    'backup_retention_days' => [
                        'label' => 'Backup Retention (days)',
                        'type' => 'number',
                        'min' => 1,
                        'max' => 365,
                        'default' => '30',
                        'help' => 'Number of days to keep backup files'
                    ],
                    'maintenance_window' => [
                        'label' => 'Backup Time',
                        'type' => 'time',
                        'default' => '02:00',
                        'help' => 'Preferred time for system maintenance and backups (24-hour format)'
                    ],
                    'backup_local_enabled' => [
                        'label' => 'Enable Local Backups',
                        'type' => 'toggle',
                        'help' => 'Store backups on local server storage',
                        'default' => '1'
                    ],
                    'backup_gdrive_enabled' => [
                        'label' => 'Enable Google Drive Backups',
                        'type' => 'toggle',
                        'help' => 'Store backups on Google Drive',
                        'default' => '0'
                    ],
                    'gdrive_client_id' => [
                        'label' => 'Google Drive Client ID',
                        'type' => 'text',
                        'help' => 'OAuth 2.0 Client ID from Google Cloud Console',
                        'placeholder' => 'Enter Google Drive Client ID'
                    ],
                    'gdrive_client_secret' => [
                        'label' => 'Google Drive Client Secret',
                        'type' => 'password',
                        'help' => 'OAuth 2.0 Client Secret from Google Cloud Console',
                        'placeholder' => 'Enter Google Drive Client Secret'
                    ],
                    'gdrive_refresh_token' => [
                        'label' => 'Google Drive Refresh Token',
                        'type' => 'password',
                        'help' => 'OAuth 2.0 Refresh Token (auto-generated after authorization)',
                        'placeholder' => 'Auto-generated after authorization'
                    ],
                    'gdrive_folder_name' => [
                        'label' => 'Google Drive Folder Name',
                        'type' => 'text',
                        'default' => 'College-Backups',
                        'help' => 'Name of the folder to store backups in Google Drive',
                        'placeholder' => 'College-Backups'
                    ],
                    'code_backup_enabled' => [
                        'label' => 'Enable Code Backups',
                        'type' => 'toggle',
                        'help' => 'Enable automatic code backups every 15 days',
                        'default' => '1'
                    ],
                    'code_backup_interval' => [
                        'label' => 'Code Backup Interval (days)',
                        'type' => 'number',
                        'min' => 1,
                        'max' => 90,
                        'default' => '15',
                        'help' => 'Interval in days for automatic code backups'
                    ],
                    'backup_notification_email' => [
                        'label' => 'Backup Notification Email',
                        'type' => 'email',
                        'help' => 'Email address to receive backup status notifications',
                        'placeholder' => 'admin@example.com'
                    ],
                    'backup_compress' => [
                        'label' => 'Compress Backups',
                        'type' => 'toggle',
                        'help' => 'Compress backup files to save storage space',
                        'default' => '1'
                    ],
                    'auto_cleanup' => [
                        'label' => 'Auto Cleanup Old Backups',
                        'type' => 'toggle',
                        'help' => 'Automatically delete old backups based on retention period',
                        'default' => '1'
                    ],
                    'backup_notifications' => [
                        'label' => 'Enable Backup Notifications',
                        'type' => 'toggle',
                        'help' => 'Send email notifications for backup status',
                        'default' => '0'
                    ],
                    'notification_email' => [
                        'label' => 'Notification Email',
                        'type' => 'email',
                        'help' => 'Email address to receive backup notifications',
                        'placeholder' => 'admin@example.com'
                    ]
                ]
            ],
        ];
    }

    /**
     * Display the settings page with organized tabs
     */
    public function index(Request $request)
    {
        $activeTab = $request->get('tab', 'general');
        $settingGroups = $this->getSettingGroups();

        // Get current settings
        $settings = Setting::all()->keyBy('key');

        // Validate active tab
        if (!array_key_exists($activeTab, $settingGroups)) {
            $activeTab = 'general';
        }

        return view('admin.settings.index', compact('settingGroups', 'settings', 'activeTab'));
    }

    /**
     * Update settings - FIXED VERSION
     */
    public function update(Request $request)
    {
        try {
            $settingGroups = $this->getSettingGroups();
            $activeTab = $request->get('active_tab', 'general');

            if (!isset($settingGroups[$activeTab])) {
                return back()->with('error', 'Invalid settings group.');
            }

            $currentGroup = $settingGroups[$activeTab];
            $updatedCount = 0;

            foreach ($currentGroup['fields'] as $key => $field) {
                try {
                    $value = $request->input($key);

                    // FIXED: Better password field handling
                    if ($field['type'] === 'password') {
                        // Get current setting to check if it exists
                        $currentSetting = Setting::where('key', $key)->first();

                        // Check if the submitted value is the placeholder or empty
                        $isPlaceholder = ($value === '***ENCRYPTED***');
                        $isEmpty = empty($value);

                        if ($isPlaceholder) {
                            // Skip update if placeholder value - keep current password
                            \Log::info("Skipping password field update for {$key} - placeholder value detected");
                            continue;
                        } elseif ($isEmpty && $currentSetting && !empty($currentSetting->value)) {
                            // If current setting exists and new value is empty, ask user intention
                            // For now, skip to preserve existing password
                            \Log::info("Skipping password field update for {$key} - empty value, preserving existing");
                            continue;
                        } elseif ($isEmpty) {
                            // If no current setting and empty value, set as empty
                            $value = '';
                        } else {
                            // Encrypt the new password value
                            $value = encrypt($value);
                        }
                    } elseif ($field['type'] === 'toggle' || $field['type'] === 'boolean') {
                        $value = $request->has($key) ? '1' : '0';
                    } elseif ($field['type'] === 'multiselect') {
                        $selectedValues = $request->input($key, []);
                        $value = is_array($selectedValues) ? json_encode($selectedValues) : json_encode([]);
                    } elseif ($value === null) {
                        $value = '';
                    }

                    // Update or create the setting
                    $setting = Setting::updateOrCreate(
                        ['key' => $key],
                        [
                            'value' => (string) $value,
                            'group' => $activeTab,
                            'type' => $field['type'],
                            'description' => $field['help'] ?? $field['label'] ?? '',
                            'is_public' => in_array($key, [
                                'app_name',
                                'app_tagline',
                                'college_name',
                                'college_logo',
                                'college_short_name',
                                'currency_symbol',
                                'currency_code'
                            ]),
                            'is_encrypted' => $field['type'] === 'password' && !empty($value),
                        ]
                    );

                    $updatedCount++;
                    \Log::info("Setting updated: {$key}", [
                        'value' => $field['type'] === 'password' ? '[ENCRYPTED]' : $value,
                        'group' => $activeTab,
                        'is_encrypted' => $field['type'] === 'password' && !empty($value)
                    ]);

                } catch (\Exception $e) {
                    \Log::error("Failed to update setting {$key}", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    continue;
                }
            }

            // Handle toggle fields that might not be in request (unchecked checkboxes)
            foreach ($currentGroup['fields'] as $key => $field) {
                if (($field['type'] === 'toggle' || $field['type'] === 'boolean') && !$request->has($key)) {
                    Setting::updateOrCreate(
                        ['key' => $key],
                        [
                            'value' => '0',
                            'group' => $activeTab,
                            'type' => $field['type'],
                            'description' => $field['help'] ?? $field['label'] ?? '',
                            'is_public' => false,
                            'is_encrypted' => false,
                        ]
                    );
                    $updatedCount++;
                }
            }

            $this->clearSettingsCache();

            return back()->with('success', "Successfully updated {$updatedCount} setting(s).");

        } catch (\Exception $e) {
            \Log::error('Settings update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Failed to update settings. Please try again.');
        }
    }
    /**
     * Export settings as JSON
     */
    public function export()
    {
        try {
            $settings = Setting::all();
            $export = [
                'exported_at' => now()->toISOString(),
                'app_version' => config('app.version', '1.0.0'),
                'export_version' => '2.0',
                'settings' => $settings->mapWithKeys(function ($setting) {
                    return [
                        $setting->key => [
                            'value' => $setting->is_encrypted ? '[ENCRYPTED]' : $setting->value,
                            'group' => $setting->group,
                            'type' => $setting->type,
                            'description' => $setting->description,
                            'is_public' => $setting->is_public,
                            'is_encrypted' => $setting->is_encrypted,
                        ]
                    ];
                })->toArray()
            ];

            return response()->json($export)
                ->header('Content-Disposition', 'attachment; filename="settings-export-' . date('Y-m-d-H-i-s') . '.json"')
                ->header('Content-Type', 'application/json');
        } catch (\Exception $e) {
            return \App\Helpers\ErrorHandler::handleWebException(
                $e,
                'Settings export failed',
                'Failed to export settings. Please try again.'
            );
        }
    }

    /**
     * Import settings from JSON
     */
    public function import(Request $request)
    {
        $request->validate([
            'settings_file' => 'required|file|mimes:json|max:2048',
            'overwrite_existing' => 'nullable|boolean'
        ]);

        try {
            $file = $request->file('settings_file');
            $content = file_get_contents($file->getPathname());
            $settings = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()->with('error', 'Invalid JSON file format.');
            }

            $overwriteExisting = $request->boolean('overwrite_existing');
            $importedCount = 0;
            $skippedCount = 0;

            foreach ($settings as $key => $value) {
                // Check if setting already exists
                $existingSetting = \App\Models\Setting::where('key', $key)->first();

                if ($existingSetting && !$overwriteExisting) {
                    $skippedCount++;
                    continue;
                }

                // Create or update setting
                \App\Models\Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value]
                );

                $importedCount++;
            }

            $message = "Settings imported successfully. {$importedCount} settings imported";
            if ($skippedCount > 0) {
                $message .= ", {$skippedCount} settings skipped (already exist)";
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            return \App\Helpers\ErrorHandler::handleWebException(
                $e,
                'Settings import failed',
                'Settings import failed. Please check the file format and try again.'
            );
        }
    }

    /**
     * Create backup - FIXED VERSION
     */
    public function createBackup()
    {
        try {
            $backupName = 'settings_backup_' . date('Y-m-d_H-i-s') . '.json';
            $backupPath = storage_path('app/backups');

            // Create backups directory if it doesn't exist
            if (!file_exists($backupPath)) {
                mkdir($backupPath, 0755, true);
            }

            // Get all settings
            $settings = Setting::all()->map(function ($setting) {
                return [
                    'key' => $setting->key,
                    'value' => $setting->value,
                    'group' => $setting->group,
                    'type' => $setting->type,
                    'description' => $setting->description,
                    'is_public' => $setting->is_public,
                ];
            });

            $backupData = [
                'created_at' => now()->toISOString(),
                'app_version' => config('app.version', '1.0.0'),
                'laravel_version' => app()->version(),
                'settings_count' => $settings->count(),
                'settings' => $settings
            ];

            $filePath = $backupPath . '/' . $backupName;
            file_put_contents($filePath, json_encode($backupData, JSON_PRETTY_PRINT));

            return response()->json([
                'success' => true,
                'message' => 'Settings backup created successfully!',
                'filename' => $backupName,
                'path' => $filePath,
                'settings_count' => $settings->count(),
                'file_size' => $this->formatBytes(filesize($filePath))
            ]);

        } catch (\Exception $e) {
            \Log::error('Backup creation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create backup: ' . $e->getMessage()
            ], 500);
        }
    }



    /**
     * Optimize database - FIXED VERSION
     */
    public function optimizeDatabase()
    {
        try {
            $results = [];

            // Remove duplicate settings
            $duplicates = DB::select("
            SELECT key, COUNT(*) as count 
            FROM settings 
            GROUP BY key 
            HAVING COUNT(*) > 1
        ");

            $duplicatesRemoved = 0;
            foreach ($duplicates as $duplicate) {
                // Keep the most recent one, delete the rest
                $settingsToDelete = Setting::where('key', $duplicate->key)
                    ->orderBy('updated_at', 'desc')
                    ->skip(1)
                    ->take($duplicate->count - 1)
                    ->get();

                foreach ($settingsToDelete as $setting) {
                    $setting->delete();
                    $duplicatesRemoved++;
                }
            }

            // Fix settings without groups
            $ungroupedFixed = Setting::whereNull('group')
                ->orWhere('group', '')
                ->update(['group' => 'general']);

            // Fix settings without types
            $untypedFixed = Setting::whereNull('type')
                ->orWhere('type', '')
                ->update(['type' => 'text']);

            // Optimize database tables (MySQL specific)
            try {
                if (config('database.default') === 'mysql') {
                    DB::statement('OPTIMIZE TABLE settings');
                    $results[] = 'Settings table optimized';
                }
            } catch (\Exception $e) {
                $results[] = 'Table optimization skipped: ' . $e->getMessage();
            }

            // Clear settings cache
            if (function_exists('clear_settings_cache')) {
                clear_settings_cache();
            }

            return response()->json([
                'success' => true,
                'message' => 'Database optimization completed!',
                'details' => [
                    'duplicates_removed' => $duplicatesRemoved,
                    'ungrouped_fixed' => $ungroupedFixed,
                    'untyped_fixed' => $untypedFixed,
                    'operations' => $results
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Database optimization failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Database optimization failed: ' . $e->getMessage()
            ], 500);
        }
    }



    /**
     * Test SMS configuration
     */
    public function testSMS(Request $request)
    {
        $request->validate([
            'test_phone' => 'required|string'
        ]);

        try {
            // This is a placeholder - implement according to your SMS provider
            $phone = $request->test_phone;
            $message = 'Test SMS from ' . setting('college_name', 'College Management System');

            // Add your SMS sending logic here
            // Example: SMSService::send($phone, $message);

            return response()->json([
                'success' => true,
                'message' => 'Test SMS would be sent to ' . $phone . ' (SMS service not configured)'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'SMS test failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Reset all settings to defaults
     */
    public function resetAllSettings(Request $request)
    {
        if (!$request->has('confirm') || $request->confirm !== 'yes') {
            return response()->json([
                'success' => false,
                'message' => 'Please confirm by sending confirm=yes parameter'
            ]);
        }

        try {
            DB::beginTransaction();

            // Clear all existing settings
            Setting::truncate();

            // Recreate default settings
            $settingGroups = $this->getSettingGroups();
            $createdCount = 0;

            foreach ($settingGroups as $groupKey => $group) {
                foreach ($group['fields'] as $key => $field) {
                    if (isset($field['default'])) {
                        Setting::create([
                            'key' => $key,
                            'value' => $field['default'],
                            'group' => $groupKey,
                            'type' => $field['type'],
                            'description' => $field['help'] ?? $field['label'] ?? '',
                            'is_public' => in_array($key, ['app_name', 'college_name']),
                            'is_encrypted' => false
                        ]);
                        $createdCount++;
                    }
                }
            }

            DB::commit();
            $this->clearSettingsCache();

            return response()->json([
                'success' => true,
                'message' => "All settings reset to defaults. {$createdCount} settings recreated."
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset settings: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Test email configuration
     */
    public function testEmail(Request $request)
    {
        $request->validate([
            'test_email' => 'required|email'
        ]);

        try {
            $testEmail = $request->input('test_email');

            // Get email settings
            $emailSettings = \App\Models\Setting::whereIn('key', [
                'mail_driver',
                'mail_host',
                'mail_port',
                'mail_username',
                'mail_password',
                'mail_encryption',
                'mail_from_address',
                'mail_from_name'
            ])->pluck('value', 'key');

            // Configure mail settings dynamically
            config([
                'mail.default' => $emailSettings['mail_driver'] ?? 'smtp',
                'mail.mailers.smtp.host' => $emailSettings['mail_host'] ?? 'localhost',
                'mail.mailers.smtp.port' => $emailSettings['mail_port'] ?? 587,
                'mail.mailers.smtp.username' => $emailSettings['mail_username'] ?? '',
                'mail.mailers.smtp.password' => $emailSettings['mail_password'] ?? '',
                'mail.mailers.smtp.encryption' => $emailSettings['mail_encryption'] ?? 'tls',
                'mail.from.address' => $emailSettings['mail_from_address'] ?? 'noreply@college.edu',
                'mail.from.name' => $emailSettings['mail_from_name'] ?? 'College Management System',
            ]);

            // Send test email
            Mail::raw('This is a test email from your College Management System. Email configuration is working correctly!', function ($message) use ($testEmail, $emailSettings) {
                $message->to($testEmail)
                    ->subject('Test Email - College Management System')
                    ->from(
                        $emailSettings['mail_from_address'] ?? 'noreply@college.edu',
                        $emailSettings['mail_from_name'] ?? 'College Management System'
                    );
            });

            return response()->json([
                'success' => true,
                'message' => 'Test email sent successfully to ' . $testEmail
            ]);

        } catch (\Exception $e) {
            \Log::error('Test email failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to send test email: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Clear application cache - FIXED VERSION
     */
    public function clearCache()
    {
        try {
            $results = [];

            // Clear various caches with error handling
            try {
                Artisan::call('cache:clear');
                $results[] = 'Application cache cleared';
            } catch (\Exception $e) {
                $results[] = 'Application cache: ' . $e->getMessage();
            }

            try {
                Artisan::call('config:clear');
                $results[] = 'Configuration cache cleared';
            } catch (\Exception $e) {
                $results[] = 'Configuration cache: ' . $e->getMessage();
            }

            try {
                Artisan::call('route:clear');
                $results[] = 'Route cache cleared';
            } catch (\Exception $e) {
                $results[] = 'Route cache: ' . $e->getMessage();
            }

            try {
                Artisan::call('view:clear');
                $results[] = 'View cache cleared';
            } catch (\Exception $e) {
                $results[] = 'View cache: ' . $e->getMessage();
            }

            // Clear settings cache using helper function
            try {
                if (function_exists('clear_settings_cache')) {
                    clear_settings_cache();
                    $results[] = 'Settings cache cleared';
                }
            } catch (\Exception $e) {
                $results[] = 'Settings cache: ' . $e->getMessage();
            }

            return response()->json([
                'success' => true,
                'message' => 'Cache clearing completed!',
                'details' => $results
            ]);

        } catch (\Exception $e) {
            \Log::error('Cache clear failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache: ' . $e->getMessage()
            ], 500);
        }
    }



    /**
     * Seed default settings - FIXED VERSION
     */
    /**
     * Seed default settings - FIXED VERSION
     */
    public function seedDefaults()
    {
        try {
            $settingGroups = $this->getSettingGroups();
            $created = 0;
            $updated = 0;

            foreach ($settingGroups as $groupKey => $groupData) {
                foreach ($groupData['fields'] as $key => $field) {
                    if (!isset($field['default'])) {
                        continue;
                    }

                    $value = $field['default'];
                    $setting = Setting::where('key', $key)->first();

                    if (!$setting) {
                        Setting::create([
                            'key' => $key,
                            'value' => (string) $value,
                            'group' => $groupKey,
                            'type' => $field['type'],
                            'description' => $field['help'] ?? $field['label'] ?? '',
                            'is_public' => in_array($key, [
                                'app_name',
                                'app_tagline',
                                'college_name',
                                'college_logo',
                                'college_short_name',
                                'currency_symbol',
                                'currency_code'
                            ]),
                            'is_encrypted' => false,
                        ]);
                        $created++;
                    } else if (empty($setting->value) && $setting->value !== '0') {
                        $setting->update(['value' => (string) $value]);
                        $updated++;
                    }
                }
            }

            // Clear settings cache
            if (function_exists('clear_settings_cache')) {
                clear_settings_cache();
            }

            return response()->json([
                'success' => true,
                'message' => "Default settings seeded successfully! Created: {$created}, Updated: {$updated}",
                'details' => [
                    'created' => $created,
                    'updated' => $updated
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Seed defaults failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to seed defaults: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Reset settings group to defaults
     */
    public function resetDefaults(Request $request)
    {
        $request->validate([
            'group' => 'required|string'
        ]);

        try {
            $group = $request->input('group');
            $settingGroups = $this->getSettingGroups();

            if (!isset($settingGroups[$group])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid settings group specified'
                ], 422);
            }

            $currentGroup = $settingGroups[$group];
            $resetCount = 0;

            // Reset settings to defaults from getSettingGroups configuration
            foreach ($currentGroup['fields'] as $key => $field) {
                if (isset($field['default'])) {
                    Setting::updateOrCreate(
                        ['key' => $key],
                        [
                            'value' => (string) $field['default'],
                            'group' => $group,
                            'type' => $field['type'],
                            'description' => $field['help'] ?? $field['label'] ?? '',
                            'is_public' => in_array($key, [
                                'app_name',
                                'app_tagline',
                                'college_name',
                                'college_logo',
                                'college_short_name',
                                'currency_symbol',
                                'currency_code'
                            ]),
                            'is_encrypted' => false
                        ]
                    );
                    $resetCount++;
                }
            }

            $this->clearSettingsCache();

            return response()->json([
                'success' => true,
                'message' => "Settings group '{$group}' reset to defaults. {$resetCount} settings updated."
            ]);

        } catch (\Exception $e) {
            \Log::error('Group reset failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset settings group: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle maintenance mode
     */
    public function toggleMaintenance()
    {
        try {
            if (app()->isDownForMaintenance()) {
                Artisan::call('up');
                $mode = false;
                $message = 'Maintenance mode disabled';
            } else {
                Artisan::call('down', ['--secret' => 'admin-access']);
                $mode = true;
                $message = 'Maintenance mode enabled';
            }

            return response()->json([
                'success' => true,
                'mode' => $mode,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle maintenance mode: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Complete system information method that matches the view
     */
    public function systemInfo(Request $request)
    {
        try {
            // Get settings safely
            $settings = Setting::pluck('value', 'key')->toArray();

            $systemInfo = [
                'application' => [
                    'name' => $settings['app_name'] ?? $settings['college_name'] ?? 'College Management System',
                    'version' => config('app.version', '1.0.0'),
                    'environment' => config('app.env'),
                    'debug_mode' => config('app.debug') ? 'Enabled' : 'Disabled',
                    'timezone' => $settings['timezone'] ?? config('app.timezone'),
                    'url' => config('app.url'),
                    'maintenance_mode' => $settings['maintenance_mode'] ?? '0',
                ],
                'server' => [
                    'php_version' => PHP_VERSION,
                    'laravel_version' => app()->version(),
                    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                    'operating_system' => PHP_OS,
                    'server_ip' => $_SERVER['SERVER_ADDR'] ?? $_SERVER['LOCAL_ADDR'] ?? 'Unknown',
                    'memory_limit' => ini_get('memory_limit'),
                    'memory_usage' => memory_get_usage(true),
                    'max_execution_time' => ini_get('max_execution_time'),
                    'upload_max_filesize' => ini_get('upload_max_filesize'),
                    'post_max_size' => ini_get('post_max_size'),
                    'disk_space' => $this->getDiskUsage(),
                ],
                'database' => [
                    'connection' => config('database.default'),
                    'host' => config('database.connections.' . config('database.default') . '.host'),
                    'database' => config('database.connections.' . config('database.default') . '.database'),
                    'driver' => config('database.connections.' . config('database.default') . '.driver'),
                    'total_tables' => $this->getDatabaseTableCount(),
                    'total_records' => $this->getDatabaseRecordCount(),
                ],
                'cache' => [
                    'default_driver' => config('cache.default'),
                    'prefix' => config('cache.prefix'),
                    'status' => $this->checkCache()['status'] ? 'Working' : 'Failed',
                ],
                'queue' => [
                    'default_connection' => config('queue.default'),
                    'status' => 'Configured',
                ],
                'mail' => [
                    'default_mailer' => config('mail.default'),
                    'from_address' => config('mail.from.address'),
                    'from_name' => config('mail.from.name'),
                    'status' => $this->checkEmailConfiguration()['status'] ? 'Configured' : 'Not Configured',
                ],
                'college' => [
                    'name' => $settings['college_name'] ?? 'Not Set',
                    'short_name' => $settings['college_short_name'] ?? 'Not Set',
                    'email' => $settings['college_email'] ?? 'Not Set',
                    'phone' => $settings['college_phone'] ?? 'Not Set',
                    'address' => $settings['college_address'] ?? 'Not Set',
                ],
                'academic' => [
                    'current_year' => $settings['current_academic_year'] ?? date('Y') . '-' . (date('Y') + 1),
                    'enrollment_prefix' => $settings['enrollment_prefix'] ?? 'STD',
                    'semester_system' => $settings['semester_system'] ?? '1',
                ],
                'financial' => [
                    'currency_symbol' => $settings['currency_symbol'] ?? '₹',
                    'currency_code' => $settings['currency_code'] ?? 'INR',
                    'tax_rate' => $settings['tax_rate'] ?? '0',
                    'late_fee_percentage' => $settings['late_fee_percentage'] ?? '5',
                ],
                'extensions' => $this->getRequiredExtensions(),
                'statistics' => [
                    'total_settings' => count($settings),
                    'last_backup' => $this->getLastBackupInfo(),
                    'uptime' => $this->getSystemUptime(),
                ]
            ];

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $systemInfo
                ]);
            }

            return view('admin.settings.system-info', compact('systemInfo'));

        } catch (\Exception $e) {
            \Log::error('System info error: ' . $e->getMessage());

            // Return safe fallback data
            $systemInfo = [
                'application' => ['name' => 'College Management System', 'version' => '1.0.0'],
                'server' => ['php_version' => PHP_VERSION],
                'database' => ['driver' => 'mysql'],
                'cache' => ['status' => 'Unknown'],
                'queue' => ['status' => 'Unknown'],
                'mail' => ['status' => 'Unknown'],
                'college' => ['name' => 'Not Set'],
                'academic' => ['current_year' => date('Y') . '-' . (date('Y') + 1)],
                'financial' => ['currency_symbol' => '₹'],
                'extensions' => [],
                'statistics' => ['total_settings' => 0],
                'error' => $e->getMessage()
            ];

            return view('admin.settings.system-info', compact('systemInfo'));
        }
    }
    /**
     * Get database table count
     */
    private function getDatabaseTableCount()
    {
        try {
            $database = config('database.connections.' . config('database.default') . '.database');
            $tables = DB::select("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = ?", [$database]);
            return $tables[0]->count ?? 0;
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }
    /**
     * Get total database records
     */
    private function getDatabaseRecordCount()
    {
        try {
            $database = config('database.connections.' . config('database.default') . '.database');
            $result = DB::select("
            SELECT SUM(table_rows) as total_records 
            FROM information_schema.tables 
            WHERE table_schema = ? AND table_type = 'BASE TABLE'
        ", [$database]);
            return number_format($result[0]->total_records ?? 0);
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    /**
     * Get last backup info
     */
    private function getLastBackupInfo()
    {
        try {
            $backupPath = base_path('storage/backups');
            if (!is_dir($backupPath)) {
                return 'No backups found';
            }

            $files = glob($backupPath . '/backup_*.sql');
            if (empty($files)) {
                return 'No backups found';
            }

            $lastBackup = max($files);
            $lastBackupTime = filemtime($lastBackup);

            return date('Y-m-d H:i:s', $lastBackupTime);
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    /**
     * Get system uptime approximation
     */
    private function getSystemUptime()
    {
        try {
            if (function_exists('sys_getloadavg')) {
                return 'Load average available';
            }

            if (PHP_OS_FAMILY === 'Linux') {
                return 'Linux system detected';
            }

            return 'System information available';
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    /**
     * Run system health check
     */
    public function healthCheck()
    {
        try {
            $checks = [];
            $passed = 0;
            $total = 0;

            // Database check
            $total++;
            try {
                DB::connection()->getPdo();
                $checks[] = ['name' => 'Database', 'status' => true, 'message' => 'Connected'];
                $passed++;
            } catch (\Exception $e) {
                $checks[] = ['name' => 'Database', 'status' => false, 'message' => $e->getMessage()];
            }

            // Cache check
            $total++;
            try {
                Cache::put('health_check', 'test', 5);
                $test = Cache::get('health_check');
                if ($test === 'test') {
                    $checks[] = ['name' => 'Cache', 'status' => true, 'message' => 'Working'];
                    $passed++;
                } else {
                    $checks[] = ['name' => 'Cache', 'status' => false, 'message' => 'Not working'];
                }
            } catch (\Exception $e) {
                $checks[] = ['name' => 'Cache', 'status' => false, 'message' => $e->getMessage()];
            }

            // Storage check
            $total++;
            try {
                $testFile = storage_path('framework/cache/health_check.txt');
                file_put_contents($testFile, 'test');
                $content = file_get_contents($testFile);
                unlink($testFile);

                if ($content === 'test') {
                    $checks[] = ['name' => 'Storage', 'status' => true, 'message' => 'Writable'];
                    $passed++;
                } else {
                    $checks[] = ['name' => 'Storage', 'status' => false, 'message' => 'Not writable'];
                }
            } catch (\Exception $e) {
                $checks[] = ['name' => 'Storage', 'status' => false, 'message' => $e->getMessage()];
            }

            $status = $passed === $total ? 'healthy' : 'issues';

            return response()->json([
                'status' => $status,
                'summary' => [
                    'passed' => $passed,
                    'total_checks' => $total
                ],
                'checks' => $checks,
                'message' => "System health check completed. {$passed}/{$total} checks passed."
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Health check failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate a setting value
     */
    public function validateSetting(Request $request)
    {
        $request->validate([
            'key' => 'required|string',
            'value' => 'nullable'
        ]);

        $settingGroups = $this->getSettingGroups();
        $key = $request->key;
        $value = $request->value;

        // Find the field configuration
        $field = null;
        foreach ($settingGroups as $group) {
            if (isset($group['fields'][$key])) {
                $field = $group['fields'][$key];
                break;
            }
        }

        if (!$field) {
            return response()->json([
                'valid' => false,
                'message' => 'Unknown setting key: ' . $key
            ]);
        }

        // Validate based on field type
        $validation = $this->validateFieldValue($value, $field);

        return response()->json($validation);
    }

    /**
     * Show specific group settings
     */
    public function showGroup(Request $request, $group)
    {
        $settingGroups = $this->getSettingGroups();

        if (!array_key_exists($group, $settingGroups)) {
            abort(404, 'Settings group not found');
        }

        $settings = Setting::where('group', $group)->pluck('value', 'key');
        $groupConfig = $settingGroups[$group];

        if ($request->wantsJson()) {
            return response()->json([
                'group' => $group,
                'config' => $groupConfig,
                'settings' => $settings
            ]);
        }

        return redirect()->route('admin.settings.index', ['tab' => $group]);
    }

    /**
     * Reset specific group to defaults
     */
    public function resetGroupToDefaults(Request $request, $group)
    {
        $settingGroups = $this->getSettingGroups();

        if (!isset($settingGroups[$group])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid settings group'
            ], 400);
        }

        try {
            DB::beginTransaction();

            $resetCount = 0;
            foreach ($settingGroups[$group]['fields'] as $key => $field) {
                if (isset($field['default'])) {
                    Setting::updateOrCreate(['key' => $key], ['value' => $field['default']]);
                    $resetCount++;
                }
            }

            DB::commit();
            $this->clearSettingsCache();

            return response()->json([
                'success' => true,
                'message' => "Reset {$resetCount} settings to defaults for " . $settingGroups[$group]['title']
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset settings: ' . $e->getMessage()
            ], 500);
        }
    }

    // API ENDPOINTS FOR SETTINGS MANAGEMENT

    /**
     * Get all groups configuration
     */
    public function getGroups(Request $request)
    {
        $groups = $this->getSettingGroups();
        return response()->json([
            'success' => true,
            'data' => $groups
        ]);
    }

    /**
     * Get public settings (safe for frontend)
     */
    public function getPublicSettings(Request $request)
    {
        try {
            $publicSettings = Setting::where('is_public', true)->pluck('value', 'key');
            return response()->json([
                'success' => true,
                'data' => $publicSettings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get public settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific setting value
     */
    public function getSetting(Request $request, $key)
    {
        try {
            $setting = Setting::where('key', $key)->first();

            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Setting not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'key' => $key,
                    'value' => $setting->is_encrypted ? '[ENCRYPTED]' : $setting->value,
                    'type' => $setting->type,
                    'group' => $setting->group,
                    'description' => $setting->description,
                    'is_public' => $setting->is_public,
                    'is_encrypted' => $setting->is_encrypted,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting setting: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * IMPROVED: Update specific setting via AJAX with better password handling
     */
    public function updateSetting(Request $request, $key)
    {
        $request->validate([
            'value' => 'nullable'
        ]);

        try {
            // Validate the setting value
            $validation = $this->validateSettingByKey($key, $request->value);

            if (!$validation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $validation['message']
                ], 422);
            }

            $value = $request->value;

            // Check if this is a password field
            $settingGroups = $this->getSettingGroups();
            $isPassword = false;
            foreach ($settingGroups as $group) {
                if (isset($group['fields'][$key]) && $group['fields'][$key]['type'] === 'password') {
                    $isPassword = true;
                    break;
                }
            }

            // IMPROVED: Better password handling for AJAX updates
            if ($isPassword) {
                $currentSetting = Setting::where('key', $key)->first();

                if ($value === '***ENCRYPTED***') {
                    // Don't update if placeholder
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot save placeholder value. Please enter the actual secret.'
                    ], 422);
                } elseif (empty($value) && $currentSetting && !empty($currentSetting->value)) {
                    // Confirm clearing existing password
                    return response()->json([
                        'success' => false,
                        'message' => 'To clear existing secret, send explicit confirmation.',
                        'requires_confirmation' => true
                    ], 422);
                } elseif (!empty($value)) {
                    $value = encrypt($value);
                }
            }

            Setting::updateOrCreate(['key' => $key], [
                'value' => $value,
                'is_encrypted' => $isPassword && !empty($value)
            ]);

            $this->clearSettingsCache();

            return response()->json([
                'success' => true,
                'message' => 'Setting updated successfully',
                'data' => [
                    'key' => $key,
                    'value' => $isPassword ? '[ENCRYPTED]' : $value
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Setting update failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error updating setting: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete specific setting
     */
    public function deleteSetting(Request $request, $key)
    {
        try {
            $deleted = Setting::where('key', $key)->delete();

            if ($deleted) {
                $this->clearSettingsCache();

                return response()->json([
                    'success' => true,
                    'message' => 'Setting deleted successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Setting not found'
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting setting: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get statistics about settings
     */
    public function getStatistics()
    {
        try {
            $stats = [
                'total_settings' => Setting::count(),
                'public_settings' => Setting::where('is_public', true)->count(),
                'encrypted_settings' => Setting::where('is_encrypted', true)->count(),
                'groups_count' => Setting::distinct()->count('group'),
                'by_group' => Setting::selectRaw('`group`, COUNT(*) as count')
                    ->groupBy('group')
                    ->pluck('count', 'group'),
                'by_type' => Setting::selectRaw('type, COUNT(*) as count')
                    ->groupBy('type')
                    ->pluck('count', 'type'),
                'last_updated' => Setting::latest('updated_at')->value('updated_at'),
                'created_today' => Setting::whereDate('created_at', today())->count(),
                'updated_today' => Setting::whereDate('updated_at', today())->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    // PRIVATE HELPER METHODS

    /**
     * Clear settings specific cache
     */
    private function clearSettingsCache()
    {
        try {
            Cache::forget('all_settings');
            Cache::tags(['settings'])->flush();

            // Clear any other settings-related cache
            Cache::forget('public_settings');
            Cache::forget('system_settings');

            // Clear individual setting caches
            $settings = Setting::pluck('key');
            foreach ($settings as $key) {
                Cache::forget("setting_{$key}");
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to clear settings cache: ' . $e->getMessage());
        }
    }

    /**
     * Validate field value based on field configuration
     */
    private function validateFieldValue($value, $field)
    {
        // Check required
        if (isset($field['required']) && $field['required']) {
            if (empty($value)) {
                return ['valid' => false, 'message' => $field['label'] . ' is required'];
            }
        }

        // Skip validation if value is empty and not required
        if (empty($value)) {
            return ['valid' => true, 'message' => 'Valid'];
        }

        switch ($field['type']) {
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
                if (!is_numeric($value)) {
                    return ['valid' => false, 'message' => 'Value must be a number'];
                }
                if (isset($field['min']) && $value < $field['min']) {
                    return ['valid' => false, 'message' => 'Value must be at least ' . $field['min']];
                }
                if (isset($field['max']) && $value > $field['max']) {
                    return ['valid' => false, 'message' => 'Value must not exceed ' . $field['max']];
                }
                break;
            case 'text':
                if (isset($field['maxlength']) && strlen($value) > $field['maxlength']) {
                    return ['valid' => false, 'message' => 'Maximum length of ' . $field['maxlength'] . ' characters exceeded'];
                }
                break;
            case 'textarea':
                if (strlen($value) > 2000) {
                    return ['valid' => false, 'message' => 'Maximum length of 2000 characters exceeded'];
                }
                break;
        }

        return ['valid' => true, 'message' => 'Valid'];
    }

    private function validateSettingByKey($key, $value)
    {
        $settingGroups = $this->getSettingGroups();
        $field = null;
        foreach ($settingGroups as $group) {
            if (isset($group['fields'][$key])) {
                $field = $group['fields'][$key];
                break;
            }
        }

        if (!$field) {
            return ['valid' => false, 'message' => 'Unknown setting key.'];
        }

        return $this->validateFieldValue($value, $field);
    }

    /**
     * Check database connectivity
     */
    private function checkDatabase()
    {
        try {
            DB::connection()->getPdo();
            $databaseName = DB::connection()->getDatabaseName();
            return ['status' => true, 'message' => "Database connection successful to: {$databaseName}"];
        } catch (\Exception $e) {
            return ['status' => false, 'message' => 'Database connection failed: ' . $e->getMessage()];
        }
    }
    /**
     * Check cache functionality
     */
    private function checkCache()
    {
        try {
            $testKey = 'health_check_' . time();
            $testValue = 'test_value';

            Cache::put($testKey, $testValue, 60);
            $retrievedValue = Cache::get($testKey);
            Cache::forget($testKey);

            if ($retrievedValue === $testValue) {
                return ['status' => true, 'message' => 'Cache is working properly.'];
            }

            return ['status' => false, 'message' => 'Cache verification failed.'];
        } catch (\Exception $e) {
            return ['status' => false, 'message' => 'Cache check failed: ' . $e->getMessage()];
        }
    }



    /**
     * Check file permissions - FIXED
     */
    private function checkFilePermissions()
    {
        try {
            $paths = [
                base_path('storage/framework/'),
                base_path('storage/logs/'),
                base_path('bootstrap/cache/'),
            ];

            foreach ($paths as $path) {
                if (!file_exists($path)) {
                    return ['status' => false, 'message' => "Directory does not exist: {$path}"];
                }

                if (!is_writable($path)) {
                    return ['status' => false, 'message' => "Directory not writable: {$path}"];
                }
            }

            return ['status' => true, 'message' => 'Required directories are writable.'];

        } catch (\Exception $e) {
            return ['status' => false, 'message' => 'Permission check failed: ' . $e->getMessage()];
        }
    }


    /**
     * Check storage functionality
     */
    private function checkStorage()
    {
        try {
            $testFile = 'health_check_' . time() . '.txt';
            $testContent = 'Health check test file';

            Storage::disk('local')->put($testFile, $testContent);

            if (Storage::disk('local')->exists($testFile)) {
                $retrievedContent = Storage::disk('local')->get($testFile);
                Storage::disk('local')->delete($testFile);

                if ($retrievedContent === $testContent) {
                    return ['status' => true, 'message' => 'Local storage is working properly.'];
                }
            }

            return ['status' => false, 'message' => 'Storage verification failed.'];
        } catch (\Exception $e) {
            return ['status' => false, 'message' => 'Storage check failed: ' . $e->getMessage()];
        }
    }

    /**
     * Check settings table
     */
    private function checkSettings()
    {
        try {
            $settingsCount = Setting::count();
            $requiredSettings = ['app_name', 'college_name'];

            foreach ($requiredSettings as $key) {
                if (!Setting::where('key', $key)->exists()) {
                    return ['status' => false, 'message' => "Required setting '{$key}' is missing."];
                }
            }

            return ['status' => true, 'message' => "Settings table accessible with {$settingsCount} settings."];
        } catch (\Exception $e) {
            return ['status' => false, 'message' => 'Settings check failed: ' . $e->getMessage()];
        }
    }


    /**
     * Check email configuration
     */
    private function checkEmailConfiguration()
    {
        try {
            $mailDriver = config('mail.default');

            if ($mailDriver === 'log') {
                return ['status' => true, 'message' => 'Email configured to use log driver.'];
            }

            if ($mailDriver === 'smtp') {
                $host = config('mail.mailers.smtp.host');
                $username = config('mail.mailers.smtp.username');

                if (empty($host)) {
                    return ['status' => false, 'message' => 'SMTP host is not configured.'];
                }

                if (empty($username)) {
                    return ['status' => false, 'message' => 'SMTP username is not configured.'];
                }

                return ['status' => true, 'message' => 'SMTP configuration appears to be complete.'];
            }

            return ['status' => true, 'message' => "Email driver '{$mailDriver}' is configured."];
        } catch (\Exception $e) {
            return ['status' => false, 'message' => 'Email configuration check failed: ' . $e->getMessage()];
        }
    }

    /**
     * Check queue configuration
     */
    private function checkQueue()
    {
        try {
            $queueDriver = config('queue.default');

            if ($queueDriver === 'sync') {
                return ['status' => true, 'message' => 'Queue is using sync driver (immediate processing).'];
            }

            return ['status' => true, 'message' => "Queue driver '{$queueDriver}' is configured."];
        } catch (\Exception $e) {
            return ['status' => false, 'message' => 'Queue check failed: ' . $e->getMessage()];
        }
    }
    /**
     * Get disk usage - FIXED
     */
    private function getDiskUsage()
    {
        try {
            $path = base_path('storage');

            if (!function_exists('disk_total_space') || !function_exists('disk_free_space')) {
                return ['error' => 'Disk functions not available on this system'];
            }

            $totalSpace = disk_total_space($path);
            $freeSpace = disk_free_space($path);

            if ($totalSpace === false || $freeSpace === false) {
                return ['error' => 'Unable to get disk space information'];
            }

            $usedSpace = $totalSpace - $freeSpace;
            $usagePercentage = round(($usedSpace / $totalSpace) * 100, 2);

            return [
                'total' => $this->formatBytes($totalSpace),
                'used' => $this->formatBytes($usedSpace),
                'free' => $this->formatBytes($freeSpace),
                'usage_percentage' => $usagePercentage . '%',
            ];
        } catch (\Exception $e) {
            return ['error' => 'Could not retrieve disk usage: ' . $e->getMessage()];
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }


    /**
     * Get required PHP extensions
     */
    private function getRequiredExtensions()
    {
        $required = [
            'BCMath',
            'Ctype',
            'Fileinfo',
            'JSON',
            'Mbstring',
            'OpenSSL',
            'PDO',
            'Tokenizer',
            'XML',
            'cURL',
            'GD'
        ];

        $extensions = [];
        foreach ($required as $ext) {
            $extensions[$ext] = extension_loaded(strtolower($ext));
        }

        return $extensions;
    }

}