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
        Schema::table('payments', function (Blueprint $table) {
            // Add transaction_id column if it doesn't exist
            if (! Schema::hasColumn('payments', 'transaction_id')) {
                $table->string('transaction_id')->nullable()->after('payment_method');
            }

            // Add student_id column for direct student payments (optional but recommended)
            if (! Schema::hasColumn('payments', 'student_id')) {
                $table->foreignId('student_id')->nullable()->constrained()->onDelete('cascade')->after('invoice_id');
            }
        });

        // Add index in a separate schema call to avoid conflicts
        Schema::table('payments', function (Blueprint $table) {
            $foreignKeys = $this->getExistingForeignKeys('payments');

            // Add index for better performance only if it doesn't exist
            try {
                $indexExists = \DB::select(
                    "SHOW INDEX FROM payments WHERE Key_name = 'payments_student_id_payment_date_index'"
                );
                if (empty($indexExists)) {
                    $table->index(['student_id', 'payment_date']);
                }
            } catch (\Exception $e) {
                // Index might already exist, ignore
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Check if foreign key exists before dropping it
            $foreignKeys = $this->getExistingForeignKeys('payments');

            if (in_array('payments_student_id_foreign', $foreignKeys)) {
                $table->dropForeign(['student_id']);
            }

            // Check if index exists before dropping it using raw SQL
            try {
                $indexExists = \DB::select(
                    "SHOW INDEX FROM payments WHERE Key_name = 'payments_student_id_payment_date_index'"
                );
                if (! empty($indexExists)) {
                    \DB::statement('ALTER TABLE payments DROP INDEX payments_student_id_payment_date_index');
                }
            } catch (\Exception $e) {
                // Index might not exist, ignore
            }

            // Drop columns if they exist
            if (Schema::hasColumn('payments', 'transaction_id')) {
                $table->dropColumn('transaction_id');
            }
            if (Schema::hasColumn('payments', 'student_id')) {
                $table->dropColumn('student_id');
            }
        });
    }

    /**
     * Get existing foreign key names for a table
     */
    private function getExistingForeignKeys(string $tableName): array
    {
        try {
            $foreignKeys = \DB::select('
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = ? 
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ', [$tableName]);

            return array_column($foreignKeys, 'CONSTRAINT_NAME');
        } catch (\Exception $e) {
            return [];
        }
    }
};
