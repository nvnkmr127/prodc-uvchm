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
        Schema::create('attendance_caches', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('batch_id')->nullable()->constrained()->onDelete('cascade');

            // Cache Configuration
            $table->enum('cache_type', [
                'overall',           // Overall attendance across all subjects
                'subject_wise',      // Per subject attendance
                'monthly',           // Monthly aggregation
                'weekly',            // Weekly aggregation
                'semester',          // Semester wise
                'academic_year',      // Full academic year
            ])->default('overall');

            $table->enum('period_type', [
                'daily',
                'weekly',
                'monthly',
                'semester',
                'academic_year',
                'custom_range',
            ])->default('academic_year');

            $table->string('period_value')->nullable(); // e.g., '2024', '2024-01', 'week-1'

            // Calculation Data
            $table->date('calculation_date')->index();

            // Attendance Statistics
            $table->integer('total_classes')->default(0);
            $table->integer('present_classes')->default(0);
            $table->integer('absent_classes')->default(0);
            $table->integer('late_classes')->default(0);
            $table->integer('excused_classes')->default(0);

            // Calculated Percentages
            $table->decimal('attendance_percentage', 5, 2)->default(100.00)->index();
            $table->decimal('punctuality_percentage', 5, 2)->default(100.00);

            // Trend Analysis
            $table->enum('trend_direction', ['improving', 'declining', 'stable'])->default('stable');
            $table->date('last_attendance_date')->nullable();
            $table->integer('consecutive_absents')->default(0);

            // Extended Analytics (JSON for flexibility)
            $table->json('analytics_data')->nullable();

            // Cache Management
            $table->boolean('is_current')->default(true)->index();
            $table->timestamp('expires_at')->nullable()->index();

            // Timestamps
            $table->timestamps();

            // Indexes for Performance
            $table->index(['student_id', 'cache_type', 'period_type', 'period_value'], 'student_cache_lookup');
            $table->index(['batch_id', 'cache_type', 'is_current'], 'batch_cache_lookup');
            $table->index(['attendance_percentage', 'is_current'], 'attendance_performance');
            $table->index(['calculation_date', 'is_current'], 'cache_freshness');
            $table->index(['expires_at', 'is_current'], 'cache_expiry');

            // Unique constraint to prevent duplicate cache entries
            $table->unique([
                'student_id',
                'cache_type',
                'period_type',
                'period_value',
                'is_current',
            ], 'unique_active_cache');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_caches');
    }
};
