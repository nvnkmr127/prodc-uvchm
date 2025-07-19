<?php

namespace Database\Seeders;

use App\Models\PaymentReminderTemplate;
use Illuminate\Database\Seeder;

class PaymentReminderTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            // Upcoming Due Templates
            [
                'name' => 'Upcoming Due - Email',
                'description' => 'Email reminder for upcoming payment due date',
                'reminder_type' => 'upcoming_due',
                'channel' => 'email',
                'subject_template' => 'Payment Reminder: {fee_type} Due on {due_date} - {college_name}',
                'message_template' => "Dear {student_name},\n\nThis is a friendly reminder that your {fee_type} payment of ₹{amount} is due on {due_date}.\n\nStudent Details:\n- Enrollment Number: {enrollment_number}\n- Course: {course_name}\n- Batch: {batch_name}\n\nPlease make the payment before the due date to avoid any late fees.\n\nFor any queries, contact us at {contact_email} or {contact_number}.\n\nThank you,\n{college_name}",
                'is_active' => true,
                'is_default' => true,
            ],
            [
                'name' => 'Upcoming Due - SMS',
                'description' => 'SMS reminder for upcoming payment due date',
                'reminder_type' => 'upcoming_due',
                'channel' => 'sms',
                'message_template' => "Dear {student_name}, your {fee_type} payment of ₹{amount} is due on {due_date}. Please pay before due date to avoid late fees. - {college_name}",
                'character_limit' => 160,
                'is_active' => true,
                'is_default' => true,
            ],

            // Overdue Templates
            [
                'name' => 'Overdue Payment - Email',
                'description' => 'Email reminder for overdue payments',
                'reminder_type' => 'overdue',
                'channel' => 'email',
                'subject_template' => 'OVERDUE: {fee_type} Payment Required - {college_name}',
                'message_template' => "Dear {student_name},\n\nYour {fee_type} payment of ₹{amount} was due on {due_date} and is now {days_overdue} days overdue.\n\nStudent Details:\n- Enrollment Number: {enrollment_number}\n- Course: {course_name}\n- Total Outstanding: ₹{total_amount_due}\n\nPlease make the payment immediately to avoid further action.\n\nFor payment assistance, contact us at {contact_email} or {contact_number}.\n\nRegards,\nAccounts Department\n{college_name}",
                'is_active' => true,
                'is_default' => true,
            ],
            [
                'name' => 'Overdue Payment - SMS',
                'description' => 'SMS reminder for overdue payments',
                'reminder_type' => 'overdue',
                'channel' => 'sms',
                'message_template' => "OVERDUE: {student_name}, your {fee_type} payment of ₹{amount} was due on {due_date}. Pay immediately to avoid action. - {college_name}",
                'character_limit' => 160,
                'is_active' => true,
                'is_default' => true,
            ],
            [
                'name' => 'Overdue Payment - WhatsApp',
                'description' => 'WhatsApp reminder for overdue payments',
                'reminder_type' => 'overdue',
                'channel' => 'whatsapp',
                'message_template' => "🚨 *PAYMENT OVERDUE* 🚨\n\nDear {student_name},\n\nYour {fee_type} payment is overdue:\n💰 Amount: ₹{amount}\n📅 Due Date: {due_date}\n⏰ Days Overdue: {days_overdue}\n\n📋 Student ID: {enrollment_number}\n🎓 Course: {course_name}\n\nPlease make payment immediately to avoid further action.\n\n📞 Contact: {contact_number}\n📧 Email: {contact_email}\n\n*{college_name}*",
                'character_limit' => 4096,
                'is_active' => true,
                'is_default' => true,
            ],

            // Escalation Templates
            [
                'name' => 'Escalation Notice - Email',
                'description' => 'Email template for escalated payment reminders',
                'reminder_type' => 'escalation',
                'channel' => 'email',
                'subject_template' => 'URGENT: Escalated Payment Notice - {college_name}',
                'message_template' => "Dear {student_name},\n\nThis is an URGENT notice regarding your overdue payment.\n\nPayment Details:\n- Fee Type: {fee_type}\n- Amount: ₹{amount}\n- Original Due Date: {due_date}\n- Days Overdue: {days_overdue}\n- Total Outstanding: ₹{total_amount_due}\n\nDespite previous reminders, your payment remains outstanding. This matter has been escalated and requires immediate attention.\n\nPlease contact the accounts office immediately or make the payment by {final_deadline} to avoid suspension of services.\n\nContact Details:\n- Phone: {contact_number}\n- Email: {contact_email}\n\nFailure to respond may result in:\n- Suspension of academic services\n- Withholding of certificates\n- Additional late fees\n\nImmediate action is required.\n\nAccounts Department\n{college_name}",
                'is_active' => true,
                'is_default' => true,
            ],
            [
                'name' => 'Escalation Notice - SMS',
                'description' => 'SMS template for escalated payment reminders',
                'reminder_type' => 'escalation',
                'channel' => 'sms',
                'message_template' => "URGENT: {student_name}, your overdue payment of ₹{amount} requires immediate attention. Contact accounts office or pay by {final_deadline}. - {college_name}",
                'character_limit' => 160,
                'is_active' => true,
                'is_default' => true,
            ],

            // Final Notice Templates
            [
                'name' => 'Final Notice - Email',
                'description' => 'Final notice email before suspension',
                'reminder_type' => 'final_notice',
                'channel' => 'email',
                'subject_template' => 'FINAL NOTICE: Payment Required to Avoid Suspension - {college_name}',
                'message_template' => "Dear {student_name},\n\n**THIS IS YOUR FINAL NOTICE**\n\nPayment Details:\n- Fee Type: {fee_type}\n- Amount: ₹{amount}\n- Original Due Date: {due_date}\n- Days Overdue: {days_overdue}\n- Total Outstanding: ₹{total_amount_due}\n\nDespite multiple reminders, your payment remains outstanding. This is your final opportunity to settle the dues before suspension of services.\n\n**IMMEDIATE ACTION REQUIRED**\n\nIf payment is not received by {final_deadline}, the following actions will be taken:\n1. Suspension of all academic services\n2. Denial of access to college facilities\n3. Withholding of all certificates and transcripts\n4. Additional penalty charges\n5. Legal action for debt recovery\n\n**Contact Information:**\n- Phone: {contact_number}\n- Email: {contact_email}\n- Office Hours: Monday to Friday, 9 AM to 5 PM\n\nWe urge you to contact us immediately to resolve this matter.\n\nAccounts Department\n{college_name}",
                'is_active' => true,
                'is_default' => true,
            ],
            [
                'name' => 'Final Notice - Physical',
                'description' => 'Physical notice template for final warning',
                'reminder_type' => 'final_notice',
                'channel' => 'physical_notice',
                'message_template' => "FINAL NOTICE\n\nTo: {student_name}\nEnrollment No: {enrollment_number}\nCourse: {course_name}\n\nThis is your FINAL NOTICE for payment of ₹{amount} for {fee_type}, which was due on {due_date} (now {days_overdue} days overdue).\n\nTotal Outstanding: ₹{total_amount_due}\n\nPayment must be made by {final_deadline} to avoid suspension.\n\nContact: {contact_number}\n\n{college_name}\nAccounts Department\nDate: " . date('d/m/Y'),
                'is_active' => true,
                'is_default' => true,
            ],
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

        $this->command->info('Payment reminder templates seeded successfully!');
    }
}