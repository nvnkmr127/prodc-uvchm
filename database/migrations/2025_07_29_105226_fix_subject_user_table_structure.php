<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if table exists and has correct structure
        if (Schema::hasTable('subject_user')) {
            $columns = Schema::getColumnListing('subject_user');
            
            // Check existing indexes to avoid duplicates
            $indexes = $this->getTableIndexes('subject_user');
            
            Schema::table('subject_user', function (Blueprint $table) use ($columns, $indexes) {
                // Add missing columns if they don't exist
                if (!in_array('id', $columns)) {
                    $table->id()->first();
                }
                if (!in_array('subject_id', $columns)) {
                    $table->foreignId('subject_id')->constrained()->onDelete('cascade');
                }
                if (!in_array('user_id', $columns)) {
                    $table->foreignId('user_id')->constrained()->onDelete('cascade');
                }
                if (!in_array('created_at', $columns)) {
                    $table->timestamps();
                }
            });
            
            // Add unique constraint only if it doesn't exist
            if (!$this->constraintExists('subject_user', 'subject_user_unique')) {
                Schema::table('subject_user', function (Blueprint $table) {
                    $table->unique(['subject_id', 'user_id'], 'subject_user_unique');
                });
            }
        } else {
            // Create table if it doesn't exist
            Schema::create('subject_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('subject_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->timestamps();
                $table->unique(['subject_id', 'user_id'], 'subject_user_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't drop the table as it might contain data
        // Schema::dropIfExists('subject_user');
    }
    
    /**
     * Get all indexes for a table
     */
    private function getTableIndexes(string $tableName): array
    {
        try {
            $indexes = DB::select("SHOW INDEX FROM {$tableName}");
            return collect($indexes)->pluck('Key_name')->toArray();
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
            $constraints = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = ? 
                AND CONSTRAINT_NAME = ?
            ", [$tableName, $constraintName]);
            
            return count($constraints) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
};