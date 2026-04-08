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
        // Add missing columns to invoices table
        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'due_amount')) {
                $table->decimal('due_amount', 10, 2)->default(0)->after('paid_amount');
            }

            if (! Schema::hasColumn('invoices', 'term_number')) {
                $table->integer('term_number')->nullable()->after('student_id');
            }

            if (! Schema::hasColumn('invoices', 'concession_amount')) {
                $table->decimal('concession_amount', 10, 2)->default(0)->after('total_amount');
            }

            if (! Schema::hasColumn('invoices', 'concession_notes')) {
                $table->text('concession_notes')->nullable()->after('concession_amount');
            }

            if (! Schema::hasColumn('invoices', 'token')) {
                $table->string('token')->nullable()->after('id');
            }
        });

        // Update existing records to calculate due_amount
        DB::statement('UPDATE invoices SET due_amount = (total_amount - COALESCE(concession_amount, 0) - paid_amount) WHERE due_amount = 0');

        // Add indexes for better performance
        Schema::table('invoices', function (Blueprint $table) {
            if (! $this->indexExists('invoices', 'invoices_student_id_status_index')) {
                $table->index(['student_id', 'status']);
            }
            if (! $this->indexExists('invoices', 'invoices_due_date_index')) {
                $table->index('due_date');
            }
            if (! $this->indexExists('invoices', 'invoices_invoice_number_unique')) {
                $table->unique('invoice_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['due_amount', 'term_number', 'concession_amount', 'concession_notes', 'token']);
        });
    }

    /**
     * Check if an index exists
     */
    private function indexExists($table, $index)
    {
        $indexes = DB::select("SHOW INDEX FROM {$table}");
        foreach ($indexes as $idx) {
            if ($idx->Key_name === $index) {
                return true;
            }
        }

        return false;
    }
};
