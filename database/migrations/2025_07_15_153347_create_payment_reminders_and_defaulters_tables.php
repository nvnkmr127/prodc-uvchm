<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create payment_reminders table
        if (! Schema::hasTable('payment_reminders')) {
            Schema::create('payment_reminders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained()->onDelete('cascade');
                $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('cascade');
                $table->foreignId('fee_category_id')->constrained()->onDelete('cascade');
                $table->enum('reminder_type', ['upcoming_due', 'overdue', 'escalation', 'final_notice']);
                $table->enum('status', ['pending', 'sent', 'failed', 'cancelled'])->default('pending');
                $table->datetime('scheduled_date');
                $table->datetime('sent_at')->nullable();
                $table->enum('channel', ['email', 'sms', 'whatsapp', 'phone_call', 'physical_notice']);
                $table->json('recipient_details')->nullable(); // Store email, phone numbers, etc.
                $table->text('message_content')->nullable();
                $table->boolean('response_received')->default(false);
                $table->text('error_message')->nullable();
                $table->integer('retry_count')->default(0);
                $table->datetime('last_retry_at')->nullable();
                $table->timestamps();

                // Indexes for better performance
                $table->index(['status', 'scheduled_date'], 'idx_status_scheduled');
                $table->index(['student_id', 'reminder_type', 'status'], 'idx_student_type_status');
                $table->index(['reminder_type', 'channel'], 'idx_type_channel');
                $table->index(['scheduled_date', 'status'], 'idx_scheduled_status');
            });
        }

        // Create payment_defaulters table
        if (! Schema::hasTable('payment_defaulters')) {
            Schema::create('payment_defaulters', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained()->onDelete('cascade');
                $table->enum('defaulter_category', ['mild', 'moderate', 'severe', 'chronic']);
                $table->decimal('total_overdue_amount', 15, 2);
                $table->integer('overdue_days');
                $table->integer('overdue_invoice_count');
                $table->enum('current_status', [
                    'active',
                    'contact_pending',
                    'payment_promised',
                    'escalated',
                    'resolved',
                    'suspended',
                ])->default('active');
                $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
                $table->date('next_action_date')->nullable();
                $table->json('notes')->nullable(); // Array of notes with timestamps
                $table->integer('escalation_level')->default(1);
                $table->date('last_contact_date')->nullable();
                $table->integer('contact_attempts')->default(0);
                $table->date('resolution_date')->nullable();
                $table->json('contact_history')->nullable(); // Track all contact attempts
                $table->timestamps();

                // Indexes for better performance
                $table->index(['defaulter_category', 'current_status'], 'idx_category_status');
                $table->index(['overdue_days', 'total_overdue_amount'], 'idx_overdue_amount');
                $table->index(['assigned_to', 'next_action_date'], 'idx_assigned_action');
                $table->index(['current_status', 'escalation_level'], 'idx_status_escalation');
                $table->unique('student_id'); // One defaulter record per student
            });
        }

        // Create payment_reminder_templates table for customizable messages
        if (! Schema::hasTable('payment_reminder_templates')) {
            Schema::create('payment_reminder_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->enum('reminder_type', ['upcoming_due', 'overdue', 'escalation', 'final_notice']);
                $table->enum('channel', ['email', 'sms', 'whatsapp', 'phone_call', 'physical_notice']);
                $table->string('subject')->nullable(); // For email
                $table->text('message_template');
                $table->json('available_variables')->nullable(); // Store available template variables
                $table->boolean('is_active')->default(true);
                $table->boolean('is_default')->default(false);
                $table->timestamps();

                $table->index(['reminder_type', 'channel', 'is_active'], 'idx_template_lookup');
            });
        }

        // Create payment_reminder_logs table for detailed tracking
        if (! Schema::hasTable('payment_reminder_logs')) {
            Schema::create('payment_reminder_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payment_reminder_id')->constrained()->onDelete('cascade');
                $table->enum('action', ['scheduled', 'sent', 'failed', 'cancelled', 'rescheduled']);
                $table->text('details')->nullable();
                $table->json('metadata')->nullable(); // Store additional context
                $table->foreignId('performed_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();

                $table->index(['payment_reminder_id', 'action'], 'idx_reminder_action');
                $table->index(['created_at'], 'idx_log_date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_reminder_logs');
        Schema::dropIfExists('payment_reminder_templates');
        Schema::dropIfExists('payment_defaulters');
        Schema::dropIfExists('payment_reminders');
    }
};
