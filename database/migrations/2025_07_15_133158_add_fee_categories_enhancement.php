<?php

// 1. Enhanced Fee Categories Migration
// database/migrations/add_fee_categories_enhancement.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add specific fee types for better tracking
        Schema::table('fee_categories', function (Blueprint $table) {
            $table->enum('category_type', [
                'tuition_fee',
                'uniform_fee',
                'library_fee',
                'exam_fee',
                'lab_fee',
                'transport_fee',
                'hostel_fee',
                'sports_fee',
                'registration_fee',
                'caution_deposit',
                'other',
            ])->default('tuition_fee')->after('name');

            $table->boolean('is_mandatory')->default(true)->after('category_type');
            $table->boolean('is_recurring')->default(false)->after('is_mandatory');
            $table->string('recurrence_type')->nullable()->after('is_recurring'); // monthly, semester, annual
            $table->decimal('late_fee_percentage', 5, 2)->default(0)->after('recurrence_type');
            $table->integer('reminder_days_before')->default(7)->after('late_fee_percentage');
            $table->integer('escalation_days_after')->default(15)->after('reminder_days_before');
        });

        // Enhanced Payment Reminders Table
        Schema::create('payment_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('fee_category_id')->constrained()->onDelete('cascade');
            $table->enum('reminder_type', ['upcoming_due', 'overdue', 'escalation', 'final_notice']);
            $table->enum('status', ['pending', 'sent', 'delivered', 'failed', 'acknowledged']);
            $table->enum('channel', ['email', 'sms', 'whatsapp', 'phone_call', 'physical_notice']);
            $table->date('scheduled_date');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->text('message_content')->nullable();
            $table->json('recipient_details')->nullable(); // phone, email, address
            $table->text('response_notes')->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users')->onDelete('set null');
            $table->integer('attempt_count')->default(0);
            $table->timestamp('next_attempt_at')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'status']);
            $table->index(['scheduled_date', 'status']);
            $table->index(['reminder_type', 'status']);
        });

        // Payment Defaulters Tracking
        Schema::create('payment_defaulters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->enum('defaulter_category', ['mild', 'moderate', 'severe', 'chronic']);
            $table->decimal('total_overdue_amount', 10, 2);
            $table->integer('overdue_days');
            $table->integer('total_overdue_invoices');
            $table->date('first_overdue_date');
            $table->date('last_payment_date')->nullable();
            $table->json('overdue_fee_types'); // ["tuition_fee", "uniform_fee"]
            $table->enum('current_status', ['active', 'partial_payment', 'payment_plan', 'resolved', 'dropped']);
            $table->text('action_taken')->nullable();
            $table->date('next_action_date')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['defaulter_category', 'current_status']);
            $table->index(['overdue_days']);
            $table->index(['total_overdue_amount']);
        });

        // Fee-specific tracking
        Schema::create('fee_collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_category_id')->constrained()->onDelete('cascade');
            $table->foreignId('batch_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('collection_period'); // "2024-25", "January 2025"
            $table->integer('total_students');
            $table->integer('paid_students')->default(0);
            $table->integer('unpaid_students')->default(0);
            $table->integer('partial_paid_students')->default(0);
            $table->decimal('total_expected_amount', 12, 2);
            $table->decimal('total_collected_amount', 12, 2)->default(0);
            $table->decimal('total_pending_amount', 12, 2)->default(0);
            $table->decimal('collection_percentage', 5, 2)->default(0);
            $table->date('due_date');
            $table->date('last_updated_at');
            $table->timestamps();

            $table->index(['fee_category_id', 'collection_period']);
            $table->index(['due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_collections');
        Schema::dropIfExists('payment_defaulters');
        Schema::dropIfExists('payment_reminders');

        Schema::table('fee_categories', function (Blueprint $table) {
            $table->dropColumn([
                'category_type', 'is_mandatory', 'is_recurring', 'recurrence_type',
                'late_fee_percentage', 'reminder_days_before', 'escalation_days_after',
            ]);
        });
    }
};
