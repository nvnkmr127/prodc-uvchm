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
    Schema::create('admissions', function (Blueprint $table) {
        $table->id();
        $table->string('full_name');
        $table->string('email')->unique();
        $table->string('phone_number');
        $table->date('date_of_birth');
        $table->text('address');
        $table->foreignId('course_id')->constrained(); // Which course they are applying for
        $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
        $table->text('notes')->nullable(); // For admin notes
        $table->timestamps();
    });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admissions');
    }
};
