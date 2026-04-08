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
        Schema::create('biometric_logs', function (Blueprint $table) {
            $table->id();

            // Device Information
            $table->string('device_id')->nullable()->index();
            $table->string('device_manufacturer')->nullable();
            $table->string('device_location')->nullable();

            // Scan Data
            $table->string('employee_code')->index();
            $table->timestamp('scan_datetime')->index();
            $table->enum('scan_type', ['in', 'out', 'break_in', 'break_out', 'unknown'])->default('in');

            // Raw data from device (JSON format for flexibility)
            $table->json('raw_data')->nullable();

            // Processing Information
            $table->boolean('processed')->default(false)->index();
            $table->enum('sync_status', ['pending', 'success', 'failed', 'ignored'])->default('pending');
            $table->text('processing_notes')->nullable();
            $table->text('failure_reason')->nullable();

            // Relationships
            $table->foreignId('student_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('attendance_id')->nullable()->constrained()->onDelete('set null');

            // Timestamps
            $table->timestamps();

            // Indexes for performance
            $table->index(['device_id', 'scan_datetime']);
            $table->index(['employee_code', 'scan_datetime']);
            $table->index(['processed', 'scan_datetime']);
            $table->index(['sync_status', 'created_at']);

            // Composite index for duplicate detection
            $table->index(['employee_code', 'device_id', 'scan_datetime'], 'duplicate_scan_check');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('biometric_logs');
    }
};
