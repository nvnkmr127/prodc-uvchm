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
        // Update existing payment_edit_logs table
        Schema::table('payment_edit_logs', function (Blueprint $table) {
            // Add missing columns only if they don't exist
            $this->addColumnIfNotExists($table, 'changes_summary', function ($table) {
                $table->string('changes_summary')->nullable();
            });

            $this->addColumnIfNotExists($table, 'edit_reason', function ($table) {
                $table->text('edit_reason')->nullable();
            });

            $this->addColumnIfNotExists($table, 'ip_address', function ($table) {
                $table->ipAddress('ip_address')->nullable();
            });

            $this->addColumnIfNotExists($table, 'user_agent', function ($table) {
                $table->text('user_agent')->nullable();
            });

            $this->addColumnIfNotExists($table, 'metadata', function ($table) {
                $table->json('metadata')->nullable();
            });
        });

        // Add foreign keys and indexes separately to avoid conflicts
        $this->addForeignKeyIfNotExists('payment_edit_logs', 'payment_id', 'payments', 'id');
        $this->addForeignKeyIfNotExists('payment_edit_logs', 'user_id', 'users', 'id');

        // Add indexes
        $this->addIndexIfNotExists('payment_edit_logs', ['payment_id']);
        $this->addIndexIfNotExists('payment_edit_logs', ['user_id']);
        $this->addIndexIfNotExists('payment_edit_logs', ['action']);
        $this->addIndexIfNotExists('payment_edit_logs', ['created_at']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_edit_logs', function (Blueprint $table) {
            // Remove only the columns we added
            $columnsToRemove = ['changes_summary', 'edit_reason', 'ip_address', 'user_agent', 'metadata'];

            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('payment_edit_logs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    /**
     * Add column only if it doesn't exist
     */
    private function addColumnIfNotExists($table, $columnName, $callback)
    {
        if (! Schema::hasColumn('payment_edit_logs', $columnName)) {
            $callback($table);
        }
    }

    /**
     * Add foreign key only if it doesn't exist
     */
    private function addForeignKeyIfNotExists($table, $column, $referencedTable, $referencedColumn)
    {
        $constraintName = "{$table}_{$column}_foreign";

        $exists = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE CONSTRAINT_TYPE = 'FOREIGN KEY' 
            AND TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ? 
            AND CONSTRAINT_NAME = ?
        ", [$table, $constraintName]);

        if (empty($exists)) {
            DB::statement("
                ALTER TABLE {$table} 
                ADD CONSTRAINT {$constraintName} 
                FOREIGN KEY ({$column}) REFERENCES {$referencedTable}({$referencedColumn}) 
                ON DELETE CASCADE
            ");
        }
    }

    /**
     * Add index only if it doesn't exist
     */
    private function addIndexIfNotExists($table, $columns)
    {
        $columnList = is_array($columns) ? implode('_', $columns) : $columns;
        $indexName = "{$table}_{$columnList}_index";

        $exists = DB::select('
            SELECT INDEX_NAME 
            FROM information_schema.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ? 
            AND INDEX_NAME = ?
        ', [$table, $indexName]);

        if (empty($exists)) {
            $columnsSql = is_array($columns) ? implode(', ', $columns) : $columns;
            DB::statement("CREATE INDEX {$indexName} ON {$table} ({$columnsSql})");
        }
    }
};
