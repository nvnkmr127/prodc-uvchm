
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_reminders', function (Blueprint $table) {
            // Composite indexes for common queries
            $table->index(['status', 'scheduled_date'], 'idx_status_scheduled');
            $table->index(['student_id', 'reminder_type', 'status'], 'idx_student_type_status');
            $table->index(['reminder_type', 'channel'], 'idx_type_channel');
        });

        Schema::table('payment_defaulters', function (Blueprint $table) {
            $table->index(['defaulter_category', 'current_status'], 'idx_category_status');
            $table->index(['overdue_days', 'total_overdue_amount'], 'idx_overdue_amount');
            $table->index(['assigned_to', 'next_action_date'], 'idx_assigned_action');
        });

        Schema::table('invoices', function (Blueprint $table) {
            // Add index for payment reminder queries if not exists
            if (!Schema::hasColumn('invoices', 'reminder_sent_count')) {
                $table->integer('reminder_sent_count')->default(0)->after('status');
                $table->timestamp('last_reminder_sent_at')->nullable()->after('reminder_sent_count');
                $table->index(['status', 'due_date', 'reminder_sent_count'], 'idx_reminder_queries');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payment_reminders', function (Blueprint $table) {
            $table->dropIndex('idx_status_scheduled');
            $table->dropIndex('idx_student_type_status');
            $table->dropIndex('idx_type_channel');
        });

        Schema::table('payment_defaulters', function (Blueprint $table) {
            $table->dropIndex('idx_category_status');
            $table->dropIndex('idx_overdue_amount');
            $table->dropIndex('idx_assigned_action');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('idx_reminder_queries');
            $table->dropColumn(['reminder_sent_count', 'last_reminder_sent_at']);
        });
    }
};
