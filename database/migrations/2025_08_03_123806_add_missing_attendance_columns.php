<?php
// File: database/migrations/2025_08_03_add_missing_attendance_columns.php

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
            $columns = Schema::getColumnListing('attendances');
            
            // Add missing columns that are referenced in the model
            if (!in_array('marked_at', $columns)) {
                $table->timestamp('marked_at')->nullable()->after('status');
            }
            
            if (!in_array('marked_by', $columns)) {
                $table->foreignId('marked_by')->nullable()->constrained('users')->onDelete('set null')->after('marked_at');
            }
            
            if (!in_array('notes', $columns)) {
                $table->text('notes')->nullable()->after('marked_by');
            }
            
            if (!in_array('late_minutes', $columns)) {
                $table->integer('late_minutes')->nullable()->after('notes');
            }
            
            if (!in_array('location', $columns)) {
                $table->string('location')->nullable()->after('late_minutes');
            }
            
            if (!in_array('device_id', $columns)) {
                $table->string('device_id')->nullable()->after('location');
            }
            
            if (!in_array('biometric_log_id', $columns)) {
                $table->foreignId('biometric_log_id')->nullable()->after('device_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $columns = Schema::getColumnListing('attendances');
            
            $columnsToDropSafely = [];
            
            if (in_array('biometric_log_id', $columns)) {
                $columnsToDropSafely[] = 'biometric_log_id';
            }
            if (in_array('device_id', $columns)) {
                $columnsToDropSafely[] = 'device_id';
            }
            if (in_array('location', $columns)) {
                $columnsToDropSafely[] = 'location';
            }
            if (in_array('late_minutes', $columns)) {
                $columnsToDropSafely[] = 'late_minutes';
            }
            if (in_array('notes', $columns)) {
                $columnsToDropSafely[] = 'notes';
            }
            if (in_array('marked_by', $columns)) {
                $table->dropForeign(['marked_by']);
                $columnsToDropSafely[] = 'marked_by';
            }
            if (in_array('marked_at', $columns)) {
                $columnsToDropSafely[] = 'marked_at';
            }
            
            if (!empty($columnsToDropSafely)) {
                $table->dropColumn($columnsToDropSafely);
            }
        });
    }
};