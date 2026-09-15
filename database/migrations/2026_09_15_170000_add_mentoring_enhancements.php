<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mentoring system enhancements (PRD R2, R7, R8).
 *  - users.is_available        : mentor availability for new allocations (R2)
 *  - follow_ups.interaction_type / follow_up_date : structured interaction log (R7)
 *  - students.needs_escalation + escalated_at/by  : at-risk escalation flag (R8)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'is_available')) {
                $table->boolean('is_available')->default(true)->after('status');
            }
        });

        Schema::table('follow_ups', function (Blueprint $table) {
            if (! Schema::hasColumn('follow_ups', 'interaction_type')) {
                $table->string('interaction_type')->nullable()->after('outcome');
            }
            if (! Schema::hasColumn('follow_ups', 'follow_up_date')) {
                $table->date('follow_up_date')->nullable()->after('interaction_type');
            }
        });

        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'needs_escalation')) {
                $table->boolean('needs_escalation')->default(false)->after('status');
            }
            if (! Schema::hasColumn('students', 'escalated_at')) {
                $table->timestamp('escalated_at')->nullable()->after('needs_escalation');
            }
            if (! Schema::hasColumn('students', 'escalated_by')) {
                $table->foreignId('escalated_by')->nullable()->after('escalated_at')
                    ->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'escalated_by')) {
                $table->dropForeign(['escalated_by']);
                $table->dropColumn('escalated_by');
            }
            $table->dropColumn(array_values(array_filter(
                ['needs_escalation', 'escalated_at'],
                fn ($c) => Schema::hasColumn('students', $c)
            )));
        });

        Schema::table('follow_ups', function (Blueprint $table) {
            $table->dropColumn(array_values(array_filter(
                ['interaction_type', 'follow_up_date'],
                fn ($c) => Schema::hasColumn('follow_ups', $c)
            )));
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'is_available')) {
                $table->dropColumn('is_available');
            }
        });
    }
};
