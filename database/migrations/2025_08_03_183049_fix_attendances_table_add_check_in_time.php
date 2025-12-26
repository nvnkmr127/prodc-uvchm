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
        Schema::table('attendances', function (Blueprint $table) {
            // Add missing check_in_time column if it doesn't exist
            if (!Schema::hasColumn('attendances', 'check_in_time')) {
                $table->time('check_in_time')->nullable()->after('attendance_date');
            }
            
            // Add check_out_time if needed
            if (!Schema::hasColumn('attendances', 'check_out_time')) {
                $table->time('check_out_time')->nullable()->after('check_in_time');
            }
        });
        
        // Add indexes using raw SQL to avoid conflicts
        $connection = Schema::getConnection();
        
        // Check and add attendance_date, check_in_time index
        $indexExists = $connection->select(
            "SHOW INDEX FROM attendances WHERE Key_name = 'attendances_attendance_date_check_in_time_index'"
        );
        if (empty($indexExists)) {
            $connection->statement('CREATE INDEX attendances_attendance_date_check_in_time_index ON attendances (attendance_date, check_in_time)');
        }
        
        // Check and add student_id, attendance_date index (only if it doesn't conflict with foreign key)
        $studentIndexExists = $connection->select(
            "SHOW INDEX FROM attendances WHERE Key_name = 'attendances_student_id_attendance_date_index'"
        );
        if (empty($studentIndexExists)) {
            try {
                $connection->statement('CREATE INDEX attendances_student_id_attendance_date_index ON attendances (student_id, attendance_date)');
            } catch (\Exception $e) {
                // Skip if it conflicts with foreign key constraint
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes using raw SQL to avoid conflicts
        $connection = Schema::getConnection();
        
        // Drop attendance_date, check_in_time index if it exists
        $indexExists = $connection->select(
            "SHOW INDEX FROM attendances WHERE Key_name = 'attendances_attendance_date_check_in_time_index'"
        );
        if (!empty($indexExists)) {
            $connection->statement('DROP INDEX attendances_attendance_date_check_in_time_index ON attendances');
        }
        
        // Drop student_id, attendance_date index if it exists and is not used by foreign key
        $studentIndexExists = $connection->select(
            "SHOW INDEX FROM attendances WHERE Key_name = 'attendances_student_id_attendance_date_index'"
        );
        if (!empty($studentIndexExists)) {
            try {
                $connection->statement('DROP INDEX attendances_student_id_attendance_date_index ON attendances');
            } catch (\Exception $e) {
                // If it fails due to foreign key constraint, skip it
                // The foreign key constraint will handle the indexing
            }
        }
        
        Schema::table('attendances', function (Blueprint $table) {
            // Drop columns if they exist
            if (Schema::hasColumn('attendances', 'check_in_time')) {
                $table->dropColumn('check_in_time');
            }
            if (Schema::hasColumn('attendances', 'check_out_time')) {
                $table->dropColumn('check_out_time');
            }
        });
    }
};