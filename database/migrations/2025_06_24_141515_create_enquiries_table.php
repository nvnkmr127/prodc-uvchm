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
        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('student_name');
            $table->string('phone_number');
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->foreignId('course_id')->nullable()->constrained()->onDelete('set null'); // The course they are interested in
            $table->string('source')->nullable(); // e.g., 'Website', 'Agent'
            $table->text('notes')->nullable();
            $table->date('next_follow_up_date')->nullable();
            $table->enum('status', ['New', 'Contacted', 'Interested', 'Not Interested', 'Admitted'])->default('New');
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->onDelete('set null'); // Staff member responsible
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enquiries');
    }
};
