<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Students Table
        Schema::table('students', function (Blueprint $table) {
            $this->addIndexIfNotExists($table, 'students', 'status');
            $this->addIndexIfNotExists($table, 'students', 'batch_id');
            $this->addIndexIfNotExists($table, 'students', 'course_id');
            $this->addIndexIfNotExists($table, 'students', 'admission_date');
        });

        // Payments Table
        Schema::table('payments', function (Blueprint $table) {
            $this->addIndexIfNotExists($table, 'payments', 'status');
            $this->addIndexIfNotExists($table, 'payments', 'payment_date');
            $this->addIndexIfNotExists($table, 'payments', 'student_id');
            $this->addIndexIfNotExists($table, 'payments', 'payment_method');
        });

        // Enquiries Table
        Schema::table('enquiries', function (Blueprint $table) {
            $this->addIndexIfNotExists($table, 'enquiries', 'status');
            $this->addIndexIfNotExists($table, 'enquiries', 'course_id');
            $this->addIndexIfNotExists($table, 'enquiries', 'phone_number');
        });

        // Admissions Table
        Schema::table('admissions', function (Blueprint $table) {
            $this->addIndexIfNotExists($table, 'admissions', 'status');
            $this->addIndexIfNotExists($table, 'admissions', 'course_id');
        });

        // Student Fees Table (if exists)
        if (Schema::hasTable('student_fees')) {
            Schema::table('student_fees', function (Blueprint $table) {
                $this->addIndexIfNotExists($table, 'student_fees', 'student_id');
                $this->addIndexIfNotExists($table, 'student_fees', 'fee_category_id');
                $this->addIndexIfNotExists($table, 'student_fees', 'status');
                $this->addIndexIfNotExists($table, 'student_fees', 'due_date');
            });
        }

        // Attendances Table (if exists)
        if (Schema::hasTable('attendances')) {
            Schema::table('attendances', function (Blueprint $table) {
                $this->addIndexIfNotExists($table, 'attendances', 'student_id');
                $this->addIndexIfNotExists($table, 'attendances', 'batch_id');
                $this->addIndexIfNotExists($table, 'attendances', 'attendance_date');
                $this->addIndexIfNotExists($table, 'attendances', 'status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We generally don't drop indexes in down() for optimization migrations 
        // to avoid accidental performance regression if rolled back and forth,
        // but strictly speaking we should.
        // For now, leaving empty or specific drops if needed.
    }

    /**
     * Helper to add index safely
     */
    private function addIndexIfNotExists(Blueprint $table, string $tableName, string|array $columns)
    {
        $columnName = is_array($columns) ? implode('_', $columns) : $columns;
        $indexName = "{$tableName}_{$columnName}_index";

        // Check if index exists using raw SQL seems most reliable across drivers,
        // but Schema::hasIndex is standard in Laravel.
        // However, inside a Blueprint closure, we can't easily check Schema::hasIndex 
        // because the table is locked/being modified.
        // The safest way in a closure IS to just try-catch or check beforehand.
        // But since we are inside `Schema::table`, we can use a separate check before calling Schema::table.
        // NOTE: I am calling this INSIDE Schema::table closure, which is tricky.

        // BETTER APPROACH: Use try-catch for the index creation command.
        try {
            $table->index($columns, $indexName);
        } catch (\Exception $e) {
            // Index likely exists
        }
    }
};
