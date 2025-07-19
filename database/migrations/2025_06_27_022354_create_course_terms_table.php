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
    Schema::create('course_terms', function (Blueprint $table) {
        $table->id();
        $table->foreignId('course_id')->constrained()->onDelete('cascade');
        $table->string('name'); // e.g., "Semester 1", "Industrial Training"
        $table->enum('type', ['Academic', 'Training'])->default('Academic');
        $table->integer('sequence')->default(1); // To keep terms in order (1, 2, 3...)
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_terms');
    }
};
