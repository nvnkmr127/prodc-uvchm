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
    Schema::create('practical_groups', function (Blueprint $table) {
        $table->id();
        $table->string('name'); // e.g., "Batch A - Kitchen Group"
        $table->foreignId('batch_id')->constrained()->onDelete('cascade');
        $table->foreignId('classroom_id')->constrained()->onDelete('cascade'); // This links to the lab
        $table->integer('semester'); // The semester this group is for
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('practical_groups');
    }
};
