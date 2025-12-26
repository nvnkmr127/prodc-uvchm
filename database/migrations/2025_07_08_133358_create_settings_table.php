<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Setting;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Enhance the settings table if it doesn't exist or needs updates
        if (!Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique()->index();
                $table->longText('value')->nullable();
                $table->string('group')->default('general')->index();
                $table->string('type')->default('text');
                $table->text('description')->nullable();
                $table->boolean('is_public')->default(false);
                $table->boolean('is_encrypted')->default(false);
                $table->json('validation_rules')->nullable();
                $table->timestamps();
            });
        } else {
            // Add new columns to existing table
            Schema::table('settings', function (Blueprint $table) {
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
        }

        // Insert default settings
        $this->insertDefaultSettings();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }

    /**
     * Insert default settings
     */
    private function insertDefaultSettings()
    {
        $defaultSettings = [
            // General Settings
            [
                'key' => 'app_name',
                'value' => config('app.name', 'College Management System'),
                'group' => 'general',
                'type' => 'text',
                'description' => 'Application name displayed throughout the system',
                'is_public' => true
            ],
            [
                'key' => 'app_tagline',
                'value' => 'Empowering Education Excellence',
                'group' => 'general',
                'type' => 'text',
                'description' => 'Application tagline or motto',
                'is_public' => true
            ],
            [
                'key' => 'timezone',
                'value' => 'Asia/Kolkata',
                'group' => 'general',
                'type' => 'select',
                'description' => 'Default timezone for the application',
                'is_public' => false
            ],
            [
                'key' => 'date_format',
                'value' => 'd-m-Y',
                'group' => 'general',
                'type' => 'select',
                'description' => 'Default date format for display',
                'is_public' => false
            ],
            [
                'key' => 'maintenance_mode',
                'value' => '0',
                'group' => 'general',
                'type' => 'toggle',
                'description' => 'Put application in maintenance mode',
                'is_public' => false
            ],

            // College Information
            [
                'key' => 'college_name',
                'value' => '',
                'group' => 'college',
                'type' => 'text',
                'description' => 'Official college name',
                'is_public' => true
            ],
            [
                'key' => 'college_short_name',
                'value' => '',
                'group' => 'college',
                'type' => 'text',
                'description' => 'College abbreviation or short name',
                'is_public' => true
            ],
            [
                'key' => 'college_logo',
                'value' => '',
                'group' => 'college',
                'type' => 'file',
                'description' => 'College logo image',
                'is_public' => true
            ],
            [
                'key' => 'college_email',
                'value' => '',
                'group' => 'college',
                'type' => 'email',
                'description' => 'Official college email address',
                'is_public' => true,
                'validation_rules' => json_encode(['email'])
            ],
            [
                'key' => 'college_phone',
                'value' => '',
                'group' => 'college',
                'type' => 'tel',
                'description' => 'College contact phone number',
                'is_public' => true
            ],
            [
                'key' => 'college_website',
                'value' => '',
                'group' => 'college',
                'type' => 'url',
                'description' => 'College website URL',
                'is_public' => true,
                'validation_rules' => json_encode(['url'])
            ],
            [
                'key' => 'college_address',
                'value' => '',
                'group' => 'college',
                'type' => 'textarea',
                'description' => 'Complete college address',
                'is_public' => true
            ],
            [
                'key' => 'college_established_year',
                'value' => '',
                'group' => 'college',
                'type' => 'number',
                'description' => 'Year when college was established',
                'is_public' => true,
                'validation_rules' => json_encode(['integer', 'between:1800,' . date('Y')])
            ],

            // Academic Settings
            [
                'key' => 'current_academic_year',
                'value' => date('Y') . '-' . (date('Y') + 1),
                'group' => 'academic',
                'type' => 'text',
                'description' => 'Current academic year',
                'is_public' => true
            ],
            [
                'key' => 'enrollment_prefix',
                'value' => 'STD',
                'group' => 'academic',
                'type' => 'text',
                'description' => 'Prefix for student enrollment numbers',
                'is_public' => false
            ],
            [
                'key' => 'semester_system',
                'value' => '1',
                'group' => 'academic',
                'type' => 'toggle',
                'description' => 'Enable semester-based academic structure',
                'is_public' => false
            ],
            [
                'key' => 'auto_promotion',
                'value' => '0',
                'group' => 'academic',
                'type' => 'toggle',
                'description' => 'Automatically promote students to next semester',
                'is_public' => false
            ],
            [
                'key' => 'academic_session_start',
                'value' => '07',
                'group' => 'academic',
                'type' => 'select',
                'description' => 'Month when academic session starts',
                'is_public' => false
            ],

            // Financial Settings
            [
                'key' => 'currency_symbol',
                'value' => '₹',
                'group' => 'financial',
                'type' => 'text',
                'description' => 'Currency symbol for amounts',
                'is_public' => true
            ],
            [
                'key' => 'currency_code',
                'value' => 'INR',
                'group' => 'financial',
                'type' => 'select',
                'description' => 'Currency code',
                'is_public' => true
            ],
            [
                'key' => 'decimal_places',
                'value' => '2',
                'group' => 'financial',
                'type' => 'select',
                'description' => 'Number of decimal places for amounts',
                'is_public' => false
            ],
            [
                'key' => 'fee_payment_terms',
                'value' => '3',
                'group' => 'financial',
                'type' => 'select',
                'description' => 'Default number of payment installments',
                'is_public' => false,
                'validation_rules' => json_encode(['integer', 'between:1,12'])
            ],
            [
                'key' => 'late_fee_percentage',
                'value' => '5.00',
                'group' => 'financial',
                'type' => 'number',
                'description' => 'Percentage charged for late payments',
                'is_public' => false,
                'validation_rules' => json_encode(['numeric', 'between:0,100'])
            ],
            [
                'key' => 'womens_discount_percentage',
                'value' => '0',
                'group' => 'financial',
                'type' => 'number',
                'description' => 'Automatic discount percentage for female students',
                'is_public' => false,
                'validation_rules' => json_encode(['numeric', 'between:0,100'])
            ],
            [
                'key' => 'invoice_footer_text',
                'value' => 'Thank you for your payment!',
                'group' => 'financial',
                'type' => 'textarea',
                'description' => 'Text displayed at bottom of invoices',
                'is_public' => false
            ],

            // Attendance Settings
            [
                'key' => 'minimum_attendance_percentage',
                'value' => '75',
                'group' => 'attendance',
                'type' => 'number',
                'description' => 'Minimum attendance required for exam eligibility',
                'is_public' => false,
                'validation_rules' => json_encode(['numeric', 'between:0,100'])
            ],
            [
                'key' => 'attendance_grace_period',
                'value' => '10',
                'group' => 'attendance',
                'type' => 'number',
                'description' => 'Late arrival grace period in minutes',
                'is_public' => false,
                'validation_rules' => json_encode(['integer', 'between:0,60'])
            ],
            [
                'key' => 'weekend_working',
                'value' => '["saturday"]',
                'group' => 'attendance',
                'type' => 'multiselect',
                'description' => 'Working weekend days',
                'is_public' => false
            ],
            [
                'key' => 'biometric_attendance',
                'value' => '0',
                'group' => 'attendance',
                'type' => 'toggle',
                'description' => 'Enable biometric attendance system',
                'is_public' => false
            ],
            [
                'key' => 'biometric_api_key',
                'value' => '',
                'group' => 'attendance',
                'type' => 'password',
                'description' => 'API key for biometric devices',
                'is_public' => false,
                'is_encrypted' => true
            ],
            [
                'key' => 'attendance_sms_alerts',
                'value' => '0',
                'group' => 'attendance',
                'type' => 'toggle',
                'description' => 'Send SMS alerts for attendance updates',
                'is_public' => false
            ],

            // Notification Settings
            [
                'key' => 'email_notifications',
                'value' => '1',
                'group' => 'notifications',
                'type' => 'toggle',
                'description' => 'Enable email notifications',
                'is_public' => false
            ],
            [
                'key' => 'sms_notifications',
                'value' => '0',
                'group' => 'notifications',
                'type' => 'toggle',
                'description' => 'Enable SMS notifications',
                'is_public' => false
            ],
            [
                'key' => 'notification_sender_name',
                'value' => '',
                'group' => 'notifications',
                'type' => 'text',
                'description' => 'Name shown in email notifications',
                'is_public' => false
            ],
            [
                'key' => 'notification_sender_email',
                'value' => '',
                'group' => 'notifications',
                'type' => 'email',
                'description' => 'Email address used for notifications',
                'is_public' => false,
                'validation_rules' => json_encode(['email'])
            ],
            [
                'key' => 'fee_reminder_days',
                'value' => '7',
                'group' => 'notifications',
                'type' => 'number',
                'description' => 'Days before due date to send fee reminders',
                'is_public' => false,
                'validation_rules' => json_encode(['integer', 'between:1,30'])
            ],
            [
                'key' => 'birthday_notifications',
                'value' => '1',
                'group' => 'notifications',
                'type' => 'toggle',
                'description' => 'Send birthday wishes to students',
                'is_public' => false
            ],

            // Security Settings
            [
                'key' => 'password_min_length',
                'value' => '8',
                'group' => 'security',
                'type' => 'number',
                'description' => 'Minimum password length requirement',
                'is_public' => false,
                'validation_rules' => json_encode(['integer', 'between:6,20'])
            ],
            [
                'key' => 'session_timeout',
                'value' => '120',
                'group' => 'security',
                'type' => 'number',
                'description' => 'Session timeout in minutes',
                'is_public' => false,
                'validation_rules' => json_encode(['integer', 'between:15,480'])
            ],
            [
                'key' => 'login_attempts',
                'value' => '5',
                'group' => 'security',
                'type' => 'number',
                'description' => 'Maximum login attempts before lockout',
                'is_public' => false,
                'validation_rules' => json_encode(['integer', 'between:3,10'])
            ],
            [
                'key' => 'two_factor_auth',
                'value' => '0',
                'group' => 'security',
                'type' => 'toggle',
                'description' => 'Enable two-factor authentication',
                'is_public' => false
            ],
            [
                'key' => 'data_retention_period',
                'value' => '7',
                'group' => 'security',
                'type' => 'number',
                'description' => 'Data retention period in years',
                'is_public' => false,
                'validation_rules' => json_encode(['integer', 'between:1,10'])
            ],

            // Backup Settings
            [
                'key' => 'auto_backup',
                'value' => '1',
                'group' => 'backup',
                'type' => 'toggle',
                'description' => 'Enable automatic backups',
                'is_public' => false
            ],
            [
                'key' => 'backup_frequency',
                'value' => 'daily',
                'group' => 'backup',
                'type' => 'select',
                'description' => 'Backup frequency',
                'is_public' => false
            ],
            [
                'key' => 'backup_retention_days',
                'value' => '30',
                'group' => 'backup',
                'type' => 'number',
                'description' => 'Backup retention period in days',
                'is_public' => false,
                'validation_rules' => json_encode(['integer', 'between:7,365'])
            ],
            [
                'key' => 'maintenance_window',
                'value' => '02:00',
                'group' => 'backup',
                'type' => 'time',
                'description' => 'Preferred time for system maintenance',
                'is_public' => false
            ],
            [
                'key' => 'auto_cleanup',
                'value' => '1',
                'group' => 'backup',
                'type' => 'toggle',
                'description' => 'Automatically delete old backups based on retention period',
                'is_public' => false
            ],
            [
                'key' => 'backup_notifications',
                'value' => '0',
                'group' => 'backup',
                'type' => 'toggle',
                'description' => 'Send email notifications for backup status',
                'is_public' => false
            ],
            [
                'key' => 'notification_email',
                'value' => '',
                'group' => 'backup',
                'type' => 'email',
                'description' => 'Email address to receive backup notifications',
                'is_public' => false
            ],

            // Mail Settings
            [
                'key' => 'mail_driver',
                'value' => 'smtp',
                'group' => 'mail',
                'type' => 'select',
                'description' => 'Mail driver configuration',
                'is_public' => false
            ],
            [
                'key' => 'mail_host',
                'value' => '',
                'group' => 'mail',
                'type' => 'text',
                'description' => 'SMTP host server',
                'is_public' => false
            ],
            [
                'key' => 'mail_port',
                'value' => '587',
                'group' => 'mail',
                'type' => 'number',
                'description' => 'SMTP port number',
                'is_public' => false,
                'validation_rules' => json_encode(['integer', 'between:1,65535'])
            ],
            [
                'key' => 'mail_username',
                'value' => '',
                'group' => 'mail',
                'type' => 'text',
                'description' => 'SMTP username',
                'is_public' => false
            ],
            [
                'key' => 'mail_password',
                'value' => '',
                'group' => 'mail',
                'type' => 'password',
                'description' => 'SMTP password',
                'is_public' => false,
                'is_encrypted' => true
            ],
            [
                'key' => 'mail_encryption',
                'value' => 'tls',
                'group' => 'mail',
                'type' => 'select',
                'description' => 'Mail encryption method',
                'is_public' => false
            ],

            // SMS Settings
            [
                'key' => 'sms_provider',
                'value' => '',
                'group' => 'sms',
                'type' => 'select',
                'description' => 'SMS service provider',
                'is_public' => false
            ],
            [
                'key' => 'sms_api_key',
                'value' => '',
                'group' => 'sms',
                'type' => 'password',
                'description' => 'SMS API key',
                'is_public' => false,
                'is_encrypted' => true
            ],
            [
                'key' => 'sms_sender_id',
                'value' => '',
                'group' => 'sms',
                'type' => 'text',
                'description' => 'SMS sender ID',
                'is_public' => false
            ],

            // System Settings
            [
                'key' => 'debug_mode',
                'value' => '0',
                'group' => 'system',
                'type' => 'toggle',
                'description' => 'Enable debug mode (development only)',
                'is_public' => false
            ],
            [
                'key' => 'app_url',
                'value' => config('app.url', ''),
                'group' => 'system',
                'type' => 'url',
                'description' => 'Application URL',
                'is_public' => false,
                'validation_rules' => json_encode(['url'])
            ],
            [
                'key' => 'session_driver',
                'value' => 'file',
                'group' => 'system',
                'type' => 'select',
                'description' => 'Session storage driver',
                'is_public' => false
            ],
            [
                'key' => 'cache_driver',
                'value' => 'file',
                'group' => 'system',
                'type' => 'select',
                'description' => 'Cache storage driver',
                'is_public' => false
            ],
            [
                'key' => 'queue_driver',
                'value' => 'sync',
                'group' => 'system',
                'type' => 'select',
                'description' => 'Queue processing driver',
                'is_public' => false
            ],

            // Exam Settings
            [
                'key' => 'exam_result_publish_date',
                'value' => '',
                'group' => 'exam',
                'type' => 'date',
                'description' => 'Default exam result publish date',
                'is_public' => false
            ],
            [
                'key' => 'passing_marks_percentage',
                'value' => '40',
                'group' => 'exam',
                'type' => 'number',
                'description' => 'Minimum passing marks percentage',
                'is_public' => false,
                'validation_rules' => json_encode(['numeric', 'between:0,100'])
            ],
            [
                'key' => 'enable_online_exams',
                'value' => '0',
                'group' => 'exam',
                'type' => 'toggle',
                'description' => 'Enable online examination system',
                'is_public' => false
            ],

            // Library Settings
            [
                'key' => 'library_fine_per_day',
                'value' => '5',
                'group' => 'library',
                'type' => 'number',
                'description' => 'Fine amount per day for overdue books',
                'is_public' => false,
                'validation_rules' => json_encode(['numeric', 'min:0'])
            ],
            [
                'key' => 'max_books_per_student',
                'value' => '3',
                'group' => 'library',
                'type' => 'number',
                'description' => 'Maximum books a student can borrow',
                'is_public' => false,
                'validation_rules' => json_encode(['integer', 'min:1'])
            ],
            [
                'key' => 'book_return_days',
                'value' => '14',
                'group' => 'library',
                'type' => 'number',
                'description' => 'Default book return period in days',
                'is_public' => false,
                'validation_rules' => json_encode(['integer', 'min:1'])
            ]
        ];

        foreach ($defaultSettings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
};