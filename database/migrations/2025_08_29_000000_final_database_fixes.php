<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Final fixes for any remaining database constraint issues
     */
    public function up(): void
    {
        // Ensure payment_reminder_logs has all required foreign keys
        $this->fixPaymentReminderLogs();

        // Ensure payment_defaulters has all required columns and constraints
        $this->fixPaymentDefaulters();

        // Ensure subject_user table has proper structure
        $this->fixSubjectUserTable();

        // Add any missing indexes for performance
        $this->addMissingIndexes();
    }

    /**
     * Fix payment_reminder_logs table
     */
    private function fixPaymentReminderLogs(): void
    {
        if (! Schema::hasTable('payment_reminder_logs')) {
            return;
        }

        Schema::table('payment_reminder_logs', function (Blueprint $table) {
            $columns = Schema::getColumnListing('payment_reminder_logs');
            $foreignKeys = $this->getExistingForeignKeys('payment_reminder_logs');

            // Ensure foreign key to payment_reminders exists
            if (Schema::hasTable('payment_reminders') &&
                ! in_array('payment_reminder_logs_payment_reminder_id_foreign', $foreignKeys)) {
                try {
                    $table->foreign('payment_reminder_id')
                        ->references('id')
                        ->on('payment_reminders')
                        ->onDelete('cascade');
                } catch (\Exception $e) {
                    // Foreign key might already exist
                }
            }

            // Ensure foreign key to users exists for performed_by
            if (Schema::hasTable('users') &&
                in_array('performed_by', $columns) &&
                ! in_array('payment_reminder_logs_performed_by_foreign', $foreignKeys)) {
                try {
                    $table->foreign('performed_by')
                        ->references('id')
                        ->on('users')
                        ->onDelete('set null');
                } catch (\Exception $e) {
                    // Foreign key might already exist
                }
            }
        });
    }

    /**
     * Fix payment_defaulters table
     */
    private function fixPaymentDefaulters(): void
    {
        if (! Schema::hasTable('payment_defaulters')) {
            return;
        }

        Schema::table('payment_defaulters', function (Blueprint $table) {
            $columns = Schema::getColumnListing('payment_defaulters');
            $foreignKeys = $this->getExistingForeignKeys('payment_defaulters');

            // Ensure fee_category_id foreign key exists
            if (Schema::hasTable('fee_categories') &&
                in_array('fee_category_id', $columns) &&
                ! in_array('payment_defaulters_fee_category_id_foreign', $foreignKeys)) {
                try {
                    $table->foreign('fee_category_id')
                        ->references('id')
                        ->on('fee_categories')
                        ->onDelete('set null');
                } catch (\Exception $e) {
                    // Foreign key might already exist
                }
            }

            // Ensure resolved_by foreign key exists if column exists
            if (Schema::hasTable('users') &&
                in_array('resolved_by', $columns) &&
                ! in_array('payment_defaulters_resolved_by_foreign', $foreignKeys)) {
                try {
                    $table->foreign('resolved_by')
                        ->references('id')
                        ->on('users')
                        ->onDelete('set null');
                } catch (\Exception $e) {
                    // Foreign key might already exist
                }
            }
        });
    }

    /**
     * Fix subject_user table
     */
    private function fixSubjectUserTable(): void
    {
        if (! Schema::hasTable('subject_user')) {
            return;
        }

        $foreignKeys = $this->getExistingForeignKeys('subject_user');

        Schema::table('subject_user', function (Blueprint $table) use ($foreignKeys) {
            // Ensure subject_id foreign key exists
            if (Schema::hasTable('subjects') &&
                ! in_array('subject_user_subject_id_foreign', $foreignKeys)) {
                try {
                    $table->foreign('subject_id')
                        ->references('id')
                        ->on('subjects')
                        ->onDelete('cascade');
                } catch (\Exception $e) {
                    // Foreign key might already exist
                }
            }

            // Ensure user_id foreign key exists
            if (Schema::hasTable('users') &&
                ! in_array('subject_user_user_id_foreign', $foreignKeys)) {
                try {
                    $table->foreign('user_id')
                        ->references('id')
                        ->on('users')
                        ->onDelete('cascade');
                } catch (\Exception $e) {
                    // Foreign key might already exist
                }
            }
        });

        // Ensure unique constraint exists
        if (! $this->constraintExists('subject_user', 'subject_user_unique')) {
            try {
                Schema::table('subject_user', function (Blueprint $table) {
                    $table->unique(['subject_id', 'user_id'], 'subject_user_unique');
                });
            } catch (\Exception $e) {
                // Constraint might already exist
            }
        }
    }

    /**
     * Add missing indexes for performance
     */
    private function addMissingIndexes(): void
    {
        // Add indexes to payment_reminder_logs if they don't exist
        if (Schema::hasTable('payment_reminder_logs')) {
            try {
                Schema::table('payment_reminder_logs', function (Blueprint $table) {
                    if (! $this->indexExists('payment_reminder_logs', 'payment_reminder_logs_action_created_at_index')) {
                        $table->index(['action', 'created_at']);
                    }
                });
            } catch (\Exception $e) {
                // Index might already exist
            }
        }

        // Add indexes to payment_defaulters if they don't exist
        if (Schema::hasTable('payment_defaulters')) {
            try {
                Schema::table('payment_defaulters', function (Blueprint $table) {
                    if (! $this->indexExists('payment_defaulters', 'payment_defaulters_current_status_created_at_index')) {
                        $table->index(['current_status', 'created_at']);
                    }
                });
            } catch (\Exception $e) {
                // Index might already exist
            }
        }
    }

    /**
     * Get existing foreign key names for a table
     */
    private function getExistingForeignKeys(string $tableName): array
    {
        try {
            $foreignKeys = DB::select('
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

    /**
     * Check if a specific constraint exists
     */
    private function constraintExists(string $tableName, string $constraintName): bool
    {
        try {
            $constraints = DB::select('
                SELECT CONSTRAINT_NAME 
                FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = ? 
                AND CONSTRAINT_NAME = ?
            ', [$tableName, $constraintName]);

            return count($constraints) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if an index exists
     */
    private function indexExists(string $tableName, string $indexName): bool
    {
        try {
            $indexes = DB::select("SHOW INDEX FROM {$tableName} WHERE Key_name = ?", [$indexName]);

            return count($indexes) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration only adds constraints and indexes,
        // so we don't need to reverse anything critical
    }
};
