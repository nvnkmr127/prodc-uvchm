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
        // First, add missing columns
        Schema::table('payment_reminder_logs', function (Blueprint $table) {
            // Only add columns that don't exist
            if (!Schema::hasColumn('payment_reminder_logs', 'response_data')) {
                $table->json('response_data')->nullable()->after('metadata');
            }
            if (!Schema::hasColumn('payment_reminder_logs', 'ip_address')) {
                $table->string('ip_address', 45)->nullable()->after('performed_by');
            }
            if (!Schema::hasColumn('payment_reminder_logs', 'user_agent')) {
                $table->string('user_agent')->nullable()->after('ip_address');
            }
            if (!Schema::hasColumn('payment_reminder_logs', 'action_timestamp')) {
                $table->timestamp('action_timestamp')->useCurrent()->after('user_agent');
            }
        });

        // Check existing foreign keys before adding new ones
        $foreignKeys = $this->getExistingForeignKeys('payment_reminder_logs');
        
        Schema::table('payment_reminder_logs', function (Blueprint $table) use ($foreignKeys) {
            // Add indexes only if they don't exist
            try {
                if (!$this->indexExists('payment_reminder_logs', 'payment_reminder_logs_payment_reminder_id_action_index')) {
                    $table->index(['payment_reminder_id', 'action']);
                }
            } catch (\Exception $e) {}
            
            try {
                if (!$this->indexExists('payment_reminder_logs', 'payment_reminder_logs_action_action_timestamp_index')) {
                    $table->index(['action', 'action_timestamp']);
                }
            } catch (\Exception $e) {}
            
            try {
                if (!$this->indexExists('payment_reminder_logs', 'payment_reminder_logs_performed_by_index')) {
                    $table->index('performed_by');
                }
            } catch (\Exception $e) {}
            
            try {
                if (!$this->indexExists('payment_reminder_logs', 'payment_reminder_logs_action_timestamp_index')) {
                    $table->index('action_timestamp');
                }
            } catch (\Exception $e) {}

            // Add foreign keys only if they don't exist
            if (Schema::hasTable('payment_reminders') && !in_array('payment_reminder_logs_payment_reminder_id_foreign', $foreignKeys)) {
                try {
                    $table->foreign('payment_reminder_id')
                          ->references('id')
                          ->on('payment_reminders')
                          ->onDelete('cascade');
                } catch (\Exception $e) {
                    \Log::info('Foreign key already exists: payment_reminder_id');
                }
            }
            
            if (Schema::hasTable('users') && !in_array('payment_reminder_logs_performed_by_foreign', $foreignKeys)) {
                try {
                    $table->foreign('performed_by')
                          ->references('id')
                          ->on('users')
                          ->onDelete('set null');
                } catch (\Exception $e) {
                    \Log::info('Foreign key already exists: performed_by');
                }
            }
        });
    }

    /**
     * Get existing foreign key names for a table
     */
    private function getExistingForeignKeys(string $tableName): array
    {
        try {
            $foreignKeys = \DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = ? 
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ", [$tableName]);
            
            return array_column($foreignKeys, 'CONSTRAINT_NAME');
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Check if index exists
     */
    private function indexExists(string $tableName, string $indexName): bool
    {
        try {
            $indexes = \DB::select("
                SHOW INDEX FROM {$tableName} WHERE Key_name = ?
            ", [$indexName]);
            
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
        Schema::table('payment_reminder_logs', function (Blueprint $table) {
            // Drop foreign keys first
            try {
                $table->dropForeign(['payment_reminder_id']);
            } catch (\Exception $e) {
                // Ignore if doesn't exist
            }
            
            try {
                $table->dropForeign(['performed_by']);
            } catch (\Exception $e) {
                // Ignore if doesn't exist
            }
            
            // Drop indexes
            try {
                $table->dropIndex(['payment_reminder_id', 'action']);
                $table->dropIndex(['action', 'action_timestamp']);
                $table->dropIndex(['performed_by']);
                $table->dropIndex(['action_timestamp']);
            } catch (\Exception $e) {
                // Ignore if doesn't exist
            }
            
            // Drop added columns
            $table->dropColumn([
                'response_data',
                'ip_address', 
                'user_agent',
                'action_timestamp'
            ]);
        });
    }
};