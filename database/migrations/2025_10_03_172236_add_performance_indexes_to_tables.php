<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        return !empty($indexes);
    }

    /**
     * Run the migrations - Add performance indexes to frequently queried tables
     */
    public function up(): void
    {
        // Payments table indexes for better query performance
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if (Schema::hasColumn('payments', 'payment_date') && !$this->indexExists('payments', 'idx_payments_payment_date')) {
                    $table->index('payment_date', 'idx_payments_payment_date');
                }
                if (Schema::hasColumn('payments', 'payment_method') && !$this->indexExists('payments', 'idx_payments_payment_method')) {
                    $table->index('payment_method', 'idx_payments_payment_method');
                }
                if (Schema::hasColumn('payments', 'created_at') && !$this->indexExists('payments', 'idx_payments_created_at')) {
                    $table->index('created_at', 'idx_payments_created_at');
                }
                if (Schema::hasColumn('payments', 'student_id') && Schema::hasColumn('payments', 'payment_date') && !$this->indexExists('payments', 'idx_payments_student_date')) {
                    $table->index(['student_id', 'payment_date'], 'idx_payments_student_date');
                }
            });
        }

        // Students table indexes
        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                if (Schema::hasColumn('students', 'status') && !$this->indexExists('students', 'idx_students_status')) {
                    $table->index('status', 'idx_students_status');
                }
                if (Schema::hasColumn('students', 'batch_id') && !$this->indexExists('students', 'idx_students_batch_id')) {
                    $table->index('batch_id', 'idx_students_batch_id');
                }
                if (Schema::hasColumn('students', 'enrollment_number') && !$this->indexExists('students', 'idx_students_enrollment')) {
                    $table->index('enrollment_number', 'idx_students_enrollment');
                }
                if (Schema::hasColumn('students', 'batch_id') && Schema::hasColumn('students', 'status') && !$this->indexExists('students', 'idx_students_batch_status')) {
                    $table->index(['batch_id', 'status'], 'idx_students_batch_status');
                }
            });
        }

        // Student Fees table indexes
        if (Schema::hasTable('student_fees')) {
            Schema::table('student_fees', function (Blueprint $table) {
                if (Schema::hasColumn('student_fees', 'student_id') && !$this->indexExists('student_fees', 'idx_student_fees_student_id')) {
                    $table->index('student_id', 'idx_student_fees_student_id');
                }
                if (Schema::hasColumn('student_fees', 'fee_category_id') && !$this->indexExists('student_fees', 'idx_student_fees_category')) {
                    $table->index('fee_category_id', 'idx_student_fees_category');
                }
                if (Schema::hasColumn('student_fees', 'due_date') && !$this->indexExists('student_fees', 'idx_student_fees_due_date')) {
                    $table->index('due_date', 'idx_student_fees_due_date');
                }
            });
        }

        // Attendance table indexes - Skip if table doesn't exist or columns don't match
        // Note: Attendance tracking may use different table name or structure
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes safely - only if they exist
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                try { $table->dropIndex('idx_payments_payment_date'); } catch (\Exception $e) {}
                try { $table->dropIndex('idx_payments_payment_method'); } catch (\Exception $e) {}
                try { $table->dropIndex('idx_payments_created_at'); } catch (\Exception $e) {}
                try { $table->dropIndex('idx_payments_student_date'); } catch (\Exception $e) {}
            });
        }

        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                try { $table->dropIndex('idx_students_status'); } catch (\Exception $e) {}
                try { $table->dropIndex('idx_students_batch_id'); } catch (\Exception $e) {}
                try { $table->dropIndex('idx_students_enrollment'); } catch (\Exception $e) {}
                try { $table->dropIndex('idx_students_batch_status'); } catch (\Exception $e) {}
            });
        }

        if (Schema::hasTable('student_fees')) {
            Schema::table('student_fees', function (Blueprint $table) {
                try { $table->dropIndex('idx_student_fees_student_id'); } catch (\Exception $e) {}
                try { $table->dropIndex('idx_student_fees_category'); } catch (\Exception $e) {}
                try { $table->dropIndex('idx_student_fees_due_date'); } catch (\Exception $e) {}
            });
        }
    }
};
