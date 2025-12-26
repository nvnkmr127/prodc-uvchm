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
        // Check if table already exists before creating
        if (!Schema::hasTable('notification_logs')) {
            Schema::create('notification_logs', function (Blueprint $table) {
                $table->id();
                
                // Notification Classification
                $table->enum('notification_type', [
                    'attendance_marked',
                    'absence_alert',
                    'late_arrival',
                    'biometric_scan',
                    'low_attendance_warning',
                    'monthly_report',
                    'fee_reminder',
                    'exam_notification',
                    'system_alert',
                    'emergency_alert',
                    'bulk_notification',
                    'custom'
                ])->index();
                
                $table->enum('category', [
                    'attendance',
                    'financial',
                    'academic', 
                    'system',
                    'emergency',
                    'administrative'
                ])->default('attendance')->index();
                
                $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal')->index();
                
                // Delivery Channel
                $table->enum('channel', ['sms', 'email', 'whatsapp', 'push', 'voice', 'in_app'])->index();
                
                // Recipient Information
                $table->enum('recipient_type', ['student', 'parent', 'faculty', 'admin', 'system'])->index();
                $table->string('recipient_id')->nullable(); // Can be user ID, phone number, email, etc.
                
                // Relationships
                $table->foreignId('student_id')->nullable()->constrained()->onDelete('cascade');
                $table->foreignId('parent_contact_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('sender_id')->nullable()->constrained('users')->onDelete('set null');
                
                // Message Content
                $table->string('subject')->nullable();
                $table->text('message');
                $table->string('template_used')->nullable();
                $table->json('personalization_data')->nullable(); // Data used for template personalization
                
                // Delivery Tracking
                $table->enum('delivery_status', [
                    'pending',
                    'sent', 
                    'delivered',
                    'failed',
                    'cancelled'
                ])->default('pending')->index();
                
                $table->integer('delivery_attempts')->default(0);
                $table->timestamp('sent_at')->nullable()->index();
                $table->timestamp('delivered_at')->nullable()->index();
                $table->timestamp('read_at')->nullable();
                $table->timestamp('failed_at')->nullable()->index();
                
                // Error Handling
                $table->text('error_message')->nullable();
                $table->json('provider_response')->nullable(); // Response from SMS/Email provider
                $table->string('provider_message_id')->nullable(); // Provider's message ID for tracking
                
                // Cost Tracking
                $table->decimal('cost', 8, 4)->default(0)->index(); // Cost of sending notification
                
                // Batch Processing
                $table->string('batch_id')->nullable()->index(); // For bulk notifications
                
                // Polymorphic relationship for what triggered this notification
                $table->string('triggered_by_type')->nullable();
                $table->unsignedBigInteger('triggered_by_id')->nullable();
                
                // Additional metadata
                $table->json('metadata')->nullable(); // Flexible field for additional data
                
                // Timestamps
                $table->timestamps();
                
                // Indexes for Performance
                $table->index(['student_id', 'notification_type', 'created_at'], 'student_notifications');
                $table->index(['delivery_status', 'channel', 'created_at'], 'delivery_performance');
                $table->index(['category', 'priority', 'created_at'], 'notification_priority');
                $table->index(['batch_id', 'delivery_status'], 'batch_tracking');
                $table->index(['triggered_by_type', 'triggered_by_id'], 'trigger_tracking');
                $table->index(['sent_at', 'delivered_at'], 'delivery_timing');
                
                // Composite index for dashboard queries
                $table->index([
                    'notification_type', 
                    'delivery_status', 
                    'created_at'
                ], 'dashboard_stats');
                
                // Index for cleanup operations
                $table->index(['created_at', 'delivery_status'], 'cleanup_index');
            });
            
            // Add computed column for delivery time (virtual column) only if table was created
            DB::statement('
                ALTER TABLE notification_logs 
                ADD COLUMN delivery_time INT GENERATED ALWAYS AS (
                    CASE 
                        WHEN sent_at IS NOT NULL AND delivered_at IS NOT NULL 
                        THEN TIMESTAMPDIFF(SECOND, sent_at, delivered_at)
                        ELSE NULL 
                    END
                ) VIRTUAL
            ');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};