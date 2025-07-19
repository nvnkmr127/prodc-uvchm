<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;
use App\Models\PaymentReminderTemplate;

class PaymentReminderSeeder extends Seeder
{
    public function run(): void
    {
        // Seed Settings
        $settings = [
            // Payment Reminder Settings
            ['key' => 'payment_reminders_enabled', 'value' => '1', 'group' => 'payment_reminders'],
            ['key' => 'reminder_days_before', 'value' => '7', 'group' => 'payment_reminders'],
            ['key' => 'reminder_days_urgent', 'value' => '3', 'group' => 'payment_reminders'],
            ['key' => 'overdue_reminder_frequency', 'value' => '7', 'group' => 'payment_reminders'],
            ['key' => 'escalation_days', 'value' => '30', 'group' => 'payment_reminders'],
            ['key' => 'final_notice_days', 'value' => '45', 'group' => 'payment_reminders'],
            ['key' => 'reminder_time', 'value' => '09:00', 'group' => 'payment_reminders'],
            ['key' => 'auto_escalation_enabled', 'value' => '0', 'group' => 'payment_reminders'],
            ['key' => 'max_retry_attempts', 'value' => '3', 'group' => 'payment_reminders'],
            
            // Communication Settings
            ['key' => 'email_reminders_enabled', 'value' => '1', 'group' => 'communication'],
            ['key' => 'sms_reminders_enabled', 'value' => '1', 'group' => 'communication'],
            ['key' => 'whatsapp_reminders_enabled', 'value' => '0', 'group' => 'communication'],
            ['key' => 'phone_call_reminders_enabled', 'value' => '0', 'group' => 'communication'],
            
            // Defaulter Management
            ['key' => 'defaulter_tracking_enabled', 'value' => '1', 'group' => 'defaulter_management'],
            ['key' => 'mild_defaulter_days', 'value' => '15', 'group' => 'defaulter_management'],
            ['key' => 'moderate_defaulter_days', 'value' => '30', 'group' => 'defaulter_management'],
            ['key' => 'severe_defaulter_days', 'value' => '60', 'group' => 'defaulter_management'],
            ['key' => 'chronic_defaulter_days', 'value' => '90', 'group' => 'defaulter_management'],
            ['key' => 'auto_assignment_enabled', 'value' => '0', 'group' => 'defaulter_management'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        // Seed Default Templates
        $templates = [
            // Email Templates
            [
                'name' => 'Default Upcoming Due Email',
                'reminder_type' => 'upcoming_due',
                'channel' => 'email',
                'subject' => 'Payment Reminder - {fee_type} Due Soon',
                'message_template' => "Dear {student_name},\n\nThis is a friendly reminder that your {fee_type} payment of ₹{amount} is due on {due_date}.\n\nPlease make the payment on time to avoid any late fees.\n\nStudent Details:\n- Enrollment Number: {enrollment_number}\n- Course: {course_name}\n- Batch: {batch_name}\n\nFor any queries, please contact the accounts office.\n\nThank you,\n{college_name}",
                'available_variables' => [
                    'student_name', 'enrollment_number', 'fee_type', 'amount', 
                    'due_date', 'course_name', 'batch_name', 'college_name'
                ],
                'is_active' => true,
                'is_default' => true
            ],
            [
                'name' => 'Default Overdue Email',
                'reminder_type' => 'overdue',
                'channel' => 'email',
                'subject' => 'OVERDUE: {fee_type} Payment Required',
                'message_template' => "Dear {student_name},\n\nYour {fee_type} payment of ₹{amount} was due on {due_date} and is now overdue by {days_overdue} days.\n\nPlease make the payment immediately to avoid further action.\n\nOverdue Details:\n- Amount Due: ₹{amount}\n- Due Date: {due_date}\n- Days Overdue: {days_overdue}\n- Late Fee: ₹{late_fee}\n\nStudent Details:\n- Enrollment Number: {enrollment_number}\n- Course: {course_name}\n\nContact accounts office immediately to resolve this matter.\n\nRegards,\n{college_name}",
                'available_variables' => [
                    'student_name', 'enrollment_number', 'fee_type', 'amount', 
                    'due_date', 'days_overdue', 'late_fee', 'course_name', 'college_name'
                ],
                'is_active' => true,
                'is_default' => true
            ],
            [
                'name' => 'Default Escalation Email',
                'reminder_type' => 'escalation',
                'channel' => 'email',
                'subject' => 'URGENT: Payment Escalation Notice',
                'message_template' => "Dear {student_name},\n\nThis is an URGENT notice regarding your overdue payment of ₹{amount} for {fee_type}.\n\nDespite previous reminders, your payment remains outstanding for {days_overdue} days.\n\nImmediate Action Required:\n- Total Amount Due: ₹{total_amount_due}\n- Original Due Date: {due_date}\n- Days Overdue: {days_overdue}\n\nFailure to respond within 7 days may result in:\n- Academic hold on your account\n- Suspension from classes\n- Additional penalties\n\nContact the accounts office immediately to avoid these consequences.\n\nUrgently,\n{college_name}\nAccounts Department",
                'available_variables' => [
                    'student_name', 'enrollment_number', 'fee_type', 'amount',
                    'total_amount_due', 'due_date', 'days_overdue', 'college_name'
                ],
                'is_active' => true,
                'is_default' => true
            ],
            [
                'name' => 'Default Final Notice Email',
                'reminder_type' => 'final_notice',
                'channel' => 'email',
                'subject' => 'FINAL NOTICE: Immediate Payment Required',
                'message_template' => "Dear {student_name},\n\nThis is your FINAL NOTICE for the overdue payment of ₹{amount} for {fee_type}.\n\nDespite multiple reminders, your payment has remained outstanding for {days_overdue} days.\n\nFINAL WARNING:\nIf payment is not received within 3 business days, the following actions will be taken:\n\n1. Academic suspension\n2. Withholding of certificates\n3. Barring from examinations\n4. Legal action for recovery\n\nPayment Details:\n- Amount Due: ₹{total_amount_due}\n- Original Due Date: {due_date}\n- Final Payment Deadline: {final_deadline}\n\nThis is your last opportunity to resolve this matter amicably.\n\nContact: {contact_number}\nEmail: {contact_email}\n\nSincerely,\n{college_name}\nManagement",
                'available_variables' => [
                    'student_name', 'enrollment_number', 'fee_type', 'amount',
                    'total_amount_due', 'due_date', 'days_overdue', 'final_deadline',
                    'contact_number', 'contact_email', 'college_name'
                ],
                'is_active' => true,
                'is_default' => true
            ],

            // SMS Templates
            [
                'name' => 'Default Upcoming Due SMS',
                'reminder_type' => 'upcoming_due',
                'channel' => 'sms',
                'subject' => null,
                'message_template' => "Dear {student_name}, your {fee_type} payment of ₹{amount} is due on {due_date}. Please pay on time to avoid late fees. - {college_name}",
                'available_variables' => [
                    'student_name', 'fee_type', 'amount', 'due_date', 'college_name'
                ],
                'is_active' => true,
                'is_default' => true
            ],
            [
                'name' => 'Default Overdue SMS',
                'reminder_type' => 'overdue',
                'channel' => 'sms',
                'subject' => null,
                'message_template' => "OVERDUE: {student_name}, your {fee_type} payment of ₹{amount} is {days_overdue} days overdue. Pay immediately to avoid penalties. - {college_name}",
                'available_variables' => [
                    'student_name', 'fee_type', 'amount', 'days_overdue', 'college_name'
                ],
                'is_active' => true,
                'is_default' => true
            ],
            [
                'name' => 'Default Escalation SMS',
                'reminder_type' => 'escalation',
                'channel' => 'sms',
                'subject' => null,
                'message_template' => "URGENT: {student_name}, your payment of ₹{amount} is {days_overdue} days overdue. Contact accounts office immediately or face academic suspension. - {college_name}",
                'available_variables' => [
                    'student_name', 'amount', 'days_overdue', 'college_name'
                ],
                'is_active' => true,
                'is_default' => true
            ],
            [
                'name' => 'Default Final Notice SMS',
                'reminder_type' => 'final_notice',
                'channel' => 'sms',
                'subject' => null,
                'message_template' => "FINAL NOTICE: {student_name}, pay ₹{amount} within 3 days or face academic suspension and legal action. This is your last warning. - {college_name}",
                'available_variables' => [
                    'student_name', 'amount', 'college_name'
                ],
                'is_active' => true,
                'is_default' => true
            ],

            // WhatsApp Templates
            [
                'name' => 'Default Upcoming Due WhatsApp',
                'reminder_type' => 'upcoming_due',
                'channel' => 'whatsapp',
                'subject' => null,
                'message_template' => "🔔 Payment Reminder\n\nHi {student_name}!\n\nYour {fee_type} payment of ₹{amount} is due on {due_date}.\n\n📚 Course: {course_name}\n🎓 Enrollment: {enrollment_number}\n\nPay on time to avoid late fees! 💳\n\n- {college_name}",
                'available_variables' => [
                    'student_name', 'fee_type', 'amount', 'due_date', 
                    'course_name', 'enrollment_number', 'college_name'
                ],
                'is_active' => true,
                'is_default' => true
            ],
            [
                'name' => 'Default Overdue WhatsApp',
                'reminder_type' => 'overdue',
                'channel' => 'whatsapp',
                'subject' => null,
                'message_template' => "⚠️ OVERDUE PAYMENT\n\nHi {student_name},\n\nYour {fee_type} payment is now {days_overdue} days overdue!\n\n💰 Amount: ₹{amount}\n📅 Due Date: {due_date}\n\nPlease pay immediately to avoid penalties. 🚨\n\nContact us if you need assistance.\n\n- {college_name}",
                'available_variables' => [
                    'student_name', 'fee_type', 'amount', 'due_date', 
                    'days_overdue', 'college_name'
                ],
                'is_active' => true,
                'is_default' => true
            ]
        ];

        foreach ($templates as $template) {
            PaymentReminderTemplate::updateOrCreate(
                [
                    'reminder_type' => $template['reminder_type'],
                    'channel' => $template['channel'],
                    'is_default' => true
                ],
                $template
            );
        }

        $this->command->info('Payment reminder settings and templates seeded successfully!');
    }
}