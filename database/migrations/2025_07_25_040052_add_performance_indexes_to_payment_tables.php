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
        // Add indexes for student_fees table
        Schema::table('student_fees', function (Blueprint $table) {
            // Index for filtering by student and status
            if (! $this->indexExists('student_fees', 'idx_student_status')) {
                $table->index(['student_id', 'status'], 'idx_student_status');
            }

            // Index for filtering by category and academic year
            if (! $this->indexExists('student_fees', 'idx_category_year')) {
                $table->index(['fee_category_id', 'academic_year'], 'idx_category_year');
            }
        });

        // Add indexes for payments table
        Schema::table('payments', function (Blueprint $table) {
            // Index for filtering by student and payment type
            if (! $this->indexExists('payments', 'idx_student_type')) {
                $table->index(['student_id', 'payment_type'], 'idx_student_type');
            }

            // Index for filtering by payment type and date
            if (! $this->indexExists('payments', 'idx_type_date')) {
                $table->index(['payment_type', 'payment_date'], 'idx_type_date');
            }
        });

        // Add indexes for component_payment_items table
        Schema::table('component_payment_items', function (Blueprint $table) {
            // Index for joining payments with fees
            if (! $this->indexExists('component_payment_items', 'idx_payment_fee')) {
                $table->index(['payment_id', 'student_fee_id'], 'idx_payment_fee');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_fees', function (Blueprint $table) {
            if ($this->indexExists('student_fees', 'idx_student_status')) {
                $table->dropIndex('idx_student_status');
            }
            if ($this->indexExists('student_fees', 'idx_category_year')) {
                $table->dropIndex('idx_category_year');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if ($this->indexExists('payments', 'idx_student_type')) {
                $table->dropIndex('idx_student_type');
            }
            if ($this->indexExists('payments', 'idx_type_date')) {
                $table->dropIndex('idx_type_date');
            }
        });

        Schema::table('component_payment_items', function (Blueprint $table) {
            if ($this->indexExists('component_payment_items', 'idx_payment_fee')) {
                $table->dropIndex('idx_payment_fee');
            }
        });
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $index): bool
    {
        try {
            $indexes = DB::select("SHOW INDEX FROM {$table}");
            foreach ($indexes as $idx) {
                if ($idx->Key_name === $index) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            return false;
        }

        return false;
    }
};
