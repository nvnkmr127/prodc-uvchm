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
    Schema::create('timetables', function (Blueprint $table) {
        $table->id();
        $table->foreignId('course_id')->constrained()->onDelete('cascade');
        $table->foreignId('subject_id')->constrained()->onDelete('cascade');
        $table->foreignId('user_id')->constrained()->onDelete('cascade'); // The faculty member
        $table->foreignId('classroom_id')->constrained()->onDelete('cascade');
        $table->foreignId('time_slot_id')->constrained()->onDelete('cascade');
        $table->enum('weekday', ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timetables');
    }
};
