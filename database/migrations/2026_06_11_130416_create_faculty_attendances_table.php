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
        Schema::create('faculty_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_id')->constrained('users')->onDelete('cascade');
            $table->date('attendance_date');
            $table->time('check_in_time')->nullable();
            $table->time('check_out_time')->nullable();
            $table->decimal('working_hours', 4, 2)->nullable();
            $table->string('status', 20)->default('absent'); // present, absent, late, half_day, excused
            $table->integer('late_minutes')->nullable();
            $table->text('notes')->nullable();
            $table->string('device_id')->nullable();
            $table->unsignedBigInteger('biometric_log_id')->nullable();
            $table->dateTime('marked_at')->nullable();
            $table->foreignId('marked_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->unique(['faculty_id', 'attendance_date']);
            $table->index('attendance_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faculty_attendances');
    }
};
