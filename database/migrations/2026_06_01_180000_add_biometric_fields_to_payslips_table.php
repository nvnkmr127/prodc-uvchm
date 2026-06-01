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
        Schema::table('payslips', function (Blueprint $table) {
            if (!Schema::hasColumn('payslips', 'working_days')) {
                $table->integer('working_days')->nullable()->after('net_salary');
            }
            if (!Schema::hasColumn('payslips', 'days_present')) {
                $table->integer('days_present')->nullable()->after('working_days');
            }
            if (!Schema::hasColumn('payslips', 'leave_days')) {
                $table->integer('leave_days')->nullable()->after('days_present');
            }
            if (!Schema::hasColumn('payslips', 'payment_multiplier')) {
                $table->decimal('payment_multiplier', 5, 4)->default(1.0000)->after('leave_days');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('payslips', 'working_days')) {
                $columnsToDrop[] = 'working_days';
            }
            if (Schema::hasColumn('payslips', 'days_present')) {
                $columnsToDrop[] = 'days_present';
            }
            if (Schema::hasColumn('payslips', 'leave_days')) {
                $columnsToDrop[] = 'leave_days';
            }
            if (Schema::hasColumn('payslips', 'payment_multiplier')) {
                $columnsToDrop[] = 'payment_multiplier';
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
