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
        Schema::create('id_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 50)->comment('e.g., receipt, enrollment, biometric_student');
            $table->string('prefix', 50)->comment('e.g., RCP-2026-, 226');
            $table->unsignedBigInteger('last_number')->default(0);
            $table->timestamps();

            // Ensure uniqueness for the combination of entity_type and prefix
            $table->unique(['entity_type', 'prefix']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('id_sequences');
    }
};
