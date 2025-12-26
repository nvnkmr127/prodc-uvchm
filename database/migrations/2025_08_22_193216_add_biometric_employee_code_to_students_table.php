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
        Schema::table('students', function (Blueprint $table) {
            // Add biometric employee code field after enrollment_number
            $table->string('biometric_employee_code', 50)
                  ->nullable()
                  ->after('enrollment_number')
                  ->comment('Employee code used by biometric devices');
            
            // Add unique index for fast lookups and prevent duplicates
            $table->unique('biometric_employee_code', 'idx_students_biometric_code');
            
            // Add regular index for fast searching
            $table->index('biometric_employee_code', 'idx_students_biometric_search');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Drop indexes first
            $table->dropUnique('idx_students_biometric_code');
            $table->dropIndex('idx_students_biometric_search');
            
            // Drop the column
            $table->dropColumn('biometric_employee_code');
        });
    }
};