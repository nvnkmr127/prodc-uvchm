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
        Schema::table('payment_edit_logs', function (Blueprint $table) {
            // Check and rename previous_state to old_values
            if (Schema::hasColumn('payment_edit_logs', 'previous_state') && !Schema::hasColumn('payment_edit_logs', 'old_values')) {
                $table->renameColumn('previous_state', 'old_values');
            }

            // Check and rename new_state to new_values
            if (Schema::hasColumn('payment_edit_logs', 'new_state') && !Schema::hasColumn('payment_edit_logs', 'new_values')) {
                $table->renameColumn('new_state', 'new_values');
            }

            // Check and rename changes to changes_summary
            if (Schema::hasColumn('payment_edit_logs', 'changes') && !Schema::hasColumn('payment_edit_logs', 'changes_summary')) {
                $table->renameColumn('changes', 'changes_summary');
            }
            
            // Ensure metadata exists
            if (!Schema::hasColumn('payment_edit_logs', 'metadata')) {
                $table->json('metadata')->nullable()->after('user_agent');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_edit_logs', function (Blueprint $table) {
            if (Schema::hasColumn('payment_edit_logs', 'old_values')) {
                $table->renameColumn('old_values', 'previous_state');
            }
            if (Schema::hasColumn('payment_edit_logs', 'new_values')) {
                $table->renameColumn('new_values', 'new_state');
            }
            if (Schema::hasColumn('payment_edit_logs', 'changes_summary')) {
                $table->renameColumn('changes_summary', 'changes');
            }
        });
    }
};
