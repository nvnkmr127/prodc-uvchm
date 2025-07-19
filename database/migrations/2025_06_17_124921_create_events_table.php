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
    Schema::create('events', function (Blueprint $table) {
        $table->id();
        $table->string('name'); // e.g., "Mid-Term Exam", "Special Baking Workshop"
        $table->foreignId('course_id')->nullable()->constrained(); // Optional: link to a course
        $table->foreignId('subject_id')->nullable()->constrained(); // Optional: link to a subject
        $table->foreignId('user_id')->nullable()->constrained(); // Optional: link to a faculty
        $table->foreignId('classroom_id')->nullable()->constrained(); // Optional: link to a room/lab
        $table->date('event_date');
        $table->time('start_time');
        $table->time('end_time');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
