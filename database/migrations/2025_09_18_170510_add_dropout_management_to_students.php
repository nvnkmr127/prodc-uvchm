<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Check if columns don't exist before adding
            if (!Schema::hasColumn('students', 'dropout_date')) {
                $table->date('dropout_date')->nullable()->after('status');
            }
            
            if (!Schema::hasColumn('students', 'dropout_reason')) {
                $table->text('dropout_reason')->nullable()->after('dropout_date');
            }
            
            if (!Schema::hasColumn('students', 'final_outstanding_amount')) {
                $table->decimal('final_outstanding_amount', 10, 2)->default(0)->after('dropout_reason');
            }
            
            if (!Schema::hasColumn('students', 'total_paid_amount')) {
                $table->decimal('total_paid_amount', 10, 2)->default(0)->after('final_outstanding_amount');
            }
            
            if (!Schema::hasColumn('students', 'dropout_metadata')) {
                $table->json('dropout_metadata')->nullable()->after('total_paid_amount');
            }
            
            if (!Schema::hasColumn('students', 'processed_by')) {
                $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null')->after('dropout_metadata');
            }
            
            if (!Schema::hasColumn('students', 'dropout_processed_at')) {
                $table->timestamp('dropout_processed_at')->nullable()->after('processed_by');
            }
        });

        // Add index separately - Laravel handles duplicate index detection
        try {
            Schema::table('students', function (Blueprint $table) {
                $table->index(['status', 'dropout_date'], 'students_status_dropout_date_index');
            });
        } catch (\Exception $e) {
            // Index might already exist, ignore the error
        }
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Drop foreign key constraint first if it exists
            try {
                $table->dropForeign(['processed_by']);
            } catch (\Exception $e) {
                // Foreign key might not exist, ignore
            }
            
            // Drop index if it exists
            try {
                $table->dropIndex('students_status_dropout_date_index');
            } catch (\Exception $e) {
                // Index might not exist, ignore
            }
            
            // Get existing columns and only drop the ones that exist
            $existingColumns = Schema::getColumnListing('students');
            $columnsToCheck = [
                'dropout_date', 'dropout_reason', 'final_outstanding_amount',
                'total_paid_amount', 'dropout_metadata', 'processed_by', 'dropout_processed_at'
            ];
            
            $columnsToDrop = array_intersect($columnsToCheck, $existingColumns);
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};