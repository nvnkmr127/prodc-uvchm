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
            if (Schema::hasColumn('payment_edit_logs', 'changes')) {
                // If it exists, either drop it or make it nullable to prevent errors
                // Since changes_summary is used, changes is likely redundant
                $table->text('changes')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_edit_logs', function (Blueprint $table) {
            if (Schema::hasColumn('payment_edit_logs', 'changes')) {
                $table->text('changes')->nullable(false)->change();
            }
        });
    }
};
