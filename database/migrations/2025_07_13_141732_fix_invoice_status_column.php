<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, let's check what the current column looks like
        $columns = DB::select("SHOW COLUMNS FROM invoices WHERE Field = 'status'");

        if (! empty($columns)) {
            $currentType = $columns[0]->Type;
            echo "Current status column type: {$currentType}\n";
        }

        // Update any existing 'partial' values to 'partially_paid'
        DB::statement("UPDATE invoices SET status = 'partially_paid' WHERE status = 'partial'");

        // Recreate the status column with correct ENUM values
        Schema::table('invoices', function (Blueprint $table) {
            // Drop and recreate the status column to ensure proper ENUM setup
            $table->dropColumn('status');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->enum('status', ['unpaid', 'partially_paid', 'paid', 'cancelled'])
                ->default('unpaid')
                ->after('due_amount');
        });

        echo "Status column recreated with proper ENUM values.\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original if needed
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->enum('status', ['unpaid', 'partially_paid', 'paid', 'cancelled'])
                ->default('unpaid')
                ->after('due_amount');
        });
    }
};
