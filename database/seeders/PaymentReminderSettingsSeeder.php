<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class PaymentReminderSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // Payment Reminder Settings
            ['key' => 'payment_reminders_enabled', 'value' => '1', 'group' => 'payment_reminders'],
            ['key' => 'reminder_days_before', 'value' => '7', 'group' => 'payment_reminders'],
            ['key' => 'reminder_days_urgent', 'value' => '3', 'group' => 'payment_reminders'],
            ['key' => 'overdue_reminder_frequency', 'value' => '7', 'group' => 'payment_reminders'],
            ['key' => 'escalation_days', 'value' => '30', 'group' => 'payment_reminders'],
            ['key' => 'final_notice_days', 'value' => '45', 'group' => 'payment_reminders'],
            ['key' => 'reminder_time', 'value' => '09:00', 'group' => 'payment_reminders'],

            // Communication Settings
            ['key' => 'email_reminders_enabled', 'value' => '1', 'group' => 'communication'],
            ['key' => 'sms_reminders_enabled', 'value' => '1', 'group' => 'communication'],
            ['key' => 'whatsapp_reminders_enabled', 'value' => '0', 'group' => 'communication'],

            // Defaulter Management
            ['key' => 'defaulter_tracking_enabled', 'value' => '1', 'group' => 'defaulter_management'],
            ['key' => 'mild_defaulter_days', 'value' => '15', 'group' => 'defaulter_management'],
            ['key' => 'moderate_defaulter_days', 'value' => '30', 'group' => 'defaulter_management'],
            ['key' => 'severe_defaulter_days', 'value' => '60', 'group' => 'defaulter_management'],
            ['key' => 'chronic_defaulter_days', 'value' => '90', 'group' => 'defaulter_management'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
