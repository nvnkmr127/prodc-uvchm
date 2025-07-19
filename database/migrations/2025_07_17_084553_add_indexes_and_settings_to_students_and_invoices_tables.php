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
        // Add indexes to students table for better import performance
        Schema::table('students', function (Blueprint $table) {
            // Add index on enrollment_number for uniqueness checks (if not already exists)
            if (!$this->indexExists('students', 'students_enrollment_number_unique')) {
                try {
                    $table->unique('enrollment_number');
                } catch (\Exception $e) {
                    // Index might already exist, ignore
                }
            }
            
            // Add index on email for uniqueness checks (only if email column exists)
            if (Schema::hasColumn('students', 'email') && !$this->indexExists('students', 'students_email_unique')) {
                try {
                    $table->unique('email');
                } catch (\Exception $e) {
                    // Index might already exist, ignore
                }
            }
            
            // Add index on batch_id for queries (only if batch_id column exists)
            if (Schema::hasColumn('students', 'batch_id') && !$this->indexExists('students', 'students_batch_id_index')) {
                try {
                    $table->index('batch_id');
                } catch (\Exception $e) {
                    // Index might already exist, ignore
                }
            }
            
            // Add index on course_id for queries (only if course_id column exists)
            if (Schema::hasColumn('students', 'course_id') && !$this->indexExists('students', 'students_course_id_index')) {
                try {
                    $table->index('course_id');
                } catch (\Exception $e) {
                    // Index might already exist, ignore
                }
            }
        });

        // Add indexes to invoices table for better performance
        Schema::table('invoices', function (Blueprint $table) {
            // Add index on invoice_number for uniqueness checks (if not already exists)
            if (!$this->indexExists('invoices', 'invoices_invoice_number_unique')) {
                try {
                    $table->unique('invoice_number');
                } catch (\Exception $e) {
                    // Index might already exist, ignore
                }
            }
            
            // Add index on student_id for queries (if not already exists)
            if (!$this->indexExists('invoices', 'invoices_student_id_index')) {
                try {
                    $table->index('student_id');
                } catch (\Exception $e) {
                    // Index might already exist, ignore
                }
            }
            
            // Add index on status for queries (if not already exists)
            if (!$this->indexExists('invoices', 'invoices_status_index')) {
                try {
                    $table->index('status');
                } catch (\Exception $e) {
                    // Index might already exist, ignore
                }
            }
            
            // Add index on due_date for queries (if not already exists)
            if (!$this->indexExists('invoices', 'invoices_due_date_index')) {
                try {
                    $table->index('due_date');
                } catch (\Exception $e) {
                    // Index might already exist, ignore
                }
            }
        });

        // Add setting to control invoice generation during import
        $this->addSettingIfNotExists('generate_invoices_on_import', 'true', 'boolean', 'Generate invoices automatically when importing students');
        
        // Add setting to control duplicate checking during import
        $this->addSettingIfNotExists('check_duplicates_on_import', 'true', 'boolean', 'Check for duplicate students during import');
        
        // Add setting for enrollment number prefix
        $this->addSettingIfNotExists('enrollment_prefix', 'UV', 'string', 'Prefix for student enrollment numbers');
        
        // Add setting for college short name
        $this->addSettingIfNotExists('college_short_name', 'UVCHM', 'string', 'Short name of the college for enrollment numbers');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the settings
        DB::table('settings')->whereIn('key', [
            'generate_invoices_on_import',
            'check_duplicates_on_import',
            'enrollment_prefix',
            'college_short_name'
        ])->delete();
        
        // Note: We don't remove indexes as they might be needed for other operations
        // and removing them could cause performance issues
    }
    
    /**
     * Check if an index exists on a table
     */
    private function indexExists($table, $index)
    {
        try {
            $indexes = DB::select("SHOW INDEX FROM {$table}");
            foreach ($indexes as $idx) {
                if ($idx->Key_name === $index) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            // If we can't check, assume it doesn't exist
        }
        return false;
    }
    
    /**
     * Add a setting if it doesn't already exist
     */
    private function addSettingIfNotExists($key, $value, $type, $description)
    {
        $exists = DB::table('settings')->where('key', $key)->exists();
        
        if (!$exists) {
            DB::table('settings')->insert([
                'key' => $key,
                'value' => $value,
                'type' => $type,
                'description' => $description,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
};