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
        // Add indexes for better performance, checking for existence first

        // Student fees indexes
        $this->addIndexIfNotExists('student_fees', 'idx_student_status_safe', ['student_id', 'status']);
        $this->addIndexIfNotExists('student_fees', 'idx_category_year_safe', ['fee_category_id', 'academic_year']);
        $this->addIndexIfNotExists('student_fees', 'idx_student_category_safe', ['student_id', 'fee_category_id']);
        $this->addIndexIfNotExists('student_fees', 'idx_year_status_safe', ['academic_year', 'status']);
        $this->addIndexIfNotExists('student_fees', 'idx_due_status_safe', ['due_date', 'status']);
        $this->addIndexIfNotExists('student_fees', 'idx_status_safe', ['status']);
        $this->addIndexIfNotExists('student_fees', 'idx_due_date_safe', ['due_date']);
        $this->addIndexIfNotExists('student_fees', 'idx_academic_year_safe', ['academic_year']);

        // Payments indexes
        $this->addIndexIfNotExists('payments', 'idx_student_type_safe', ['student_id', 'payment_type']);
        $this->addIndexIfNotExists('payments', 'idx_type_date_safe', ['payment_type', 'payment_date']);
        $this->addIndexIfNotExists('payments', 'idx_method_date_safe', ['payment_method', 'payment_date']);
        $this->addIndexIfNotExists('payments', 'idx_payment_type_safe', ['payment_type']);
        $this->addIndexIfNotExists('payments', 'idx_payment_date_safe', ['payment_date']);
        $this->addIndexIfNotExists('payments', 'idx_payment_method_safe', ['payment_method']);

        // Component payment items indexes
        $this->addIndexIfNotExists('component_payment_items', 'idx_payment_fee_safe', ['payment_id', 'student_fee_id']);
        $this->addIndexIfNotExists('component_payment_items', 'idx_fee_created_safe', ['student_fee_id', 'created_at']);
        $this->addIndexIfNotExists('component_payment_items', 'idx_created_at_safe', ['created_at']);

        // Additional table indexes
        $this->addIndexIfNotExists('students', 'idx_enrollment_safe', ['enrollment_number']);
        $this->addIndexIfNotExists('students', 'idx_batch_safe', ['batch_id']);
        $this->addIndexIfNotExists('students', 'idx_email_safe', ['email']);
        $this->addIndexIfNotExists('students', 'idx_mobile_safe', ['student_mobile']);

        $this->addIndexIfNotExists('fee_categories', 'idx_cat_type_safe', ['category_type']);
        $this->addIndexIfNotExists('fee_categories', 'idx_cat_name_safe', ['name']);

        $this->addIndexIfNotExists('batches', 'idx_course_safe', ['course_id']);
        $this->addIndexIfNotExists('batches', 'idx_fee_struct_safe', ['fee_structure_id']);

        $this->addIndexIfNotExists('fee_structures', 'idx_batch_safe', ['batch_id']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the indexes we created
        $indexes = [
            'student_fees' => [
                'idx_student_status_safe', 'idx_category_year_safe', 'idx_student_category_safe',
                'idx_year_status_safe', 'idx_due_status_safe', 'idx_status_safe',
                'idx_due_date_safe', 'idx_academic_year_safe',
            ],
            'payments' => [
                'idx_student_type_safe', 'idx_type_date_safe', 'idx_method_date_safe',
                'idx_payment_type_safe', 'idx_payment_date_safe', 'idx_payment_method_safe',
            ],
            'component_payment_items' => [
                'idx_payment_fee_safe', 'idx_fee_created_safe', 'idx_created_at_safe',
            ],
            'students' => [
                'idx_enrollment_safe', 'idx_batch_safe', 'idx_email_safe', 'idx_mobile_safe',
            ],
            'fee_categories' => [
                'idx_cat_type_safe', 'idx_cat_name_safe',
            ],
            'batches' => [
                'idx_course_safe', 'idx_fee_struct_safe',
            ],
            'fee_structures' => [
                'idx_batch_safe',
            ],
        ];

        foreach ($indexes as $table => $tableIndexes) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) use ($tableIndexes) {
                    foreach ($tableIndexes as $index) {
                        if ($this->indexExists($table->getTable(), $index)) {
                            $table->dropIndex($index);
                        }
                    }
                });
            }
        }
    }

    /**
     * Add index if it doesn't exist
     */
    private function addIndexIfNotExists(string $table, string $indexName, array $columns): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        if (! $this->indexExists($table, $indexName)) {
            try {
                Schema::table($table, function (Blueprint $table) use ($indexName, $columns) {
                    $table->index($columns, $indexName);
                });
                echo "✅ Created index: {$table}.{$indexName}\n";
            } catch (\Exception $e) {
                echo "⚠️  Skipped index {$table}.{$indexName}: ".$e->getMessage()."\n";
            }
        } else {
            echo "ℹ️  Index already exists: {$table}.{$indexName}\n";
        }
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $index): bool
    {
        try {
            $connection = DB::connection();
            $schemaManager = $connection->getDoctrineSchemaManager();
            $doctrineTable = $schemaManager->listTableDetails($table);

            return $doctrineTable->hasIndex($index);
        } catch (\Exception $e) {
            // If we can't check, assume it doesn't exist
            return false;
        }
    }
};
