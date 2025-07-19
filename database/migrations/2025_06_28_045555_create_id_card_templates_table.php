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
    Schema::create('id_card_templates', function (Blueprint $table) {
        $table->id();
        $table->string('name')->unique();
        $table->text('content'); // Will store the HTML/CSS for the template
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('id_card_templates');
    }
};
