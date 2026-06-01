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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 20)->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'department')) {
                $table->string('department', 100)->nullable()->after('phone');
            }
            if (!Schema::hasColumn('users', 'employee_id')) {
                $table->string('employee_id', 50)->nullable()->unique()->after('department');
            }
            if (!Schema::hasColumn('users', 'biometric_employee_code')) {
                $table->string('biometric_employee_code', 50)->nullable()->unique()->after('employee_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('users', 'biometric_employee_code')) {
                $columnsToDrop[] = 'biometric_employee_code';
            }
            if (Schema::hasColumn('users', 'employee_id')) {
                $columnsToDrop[] = 'employee_id';
            }
            if (Schema::hasColumn('users', 'department')) {
                $columnsToDrop[] = 'department';
            }
            if (Schema::hasColumn('users', 'phone')) {
                $columnsToDrop[] = 'phone';
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
