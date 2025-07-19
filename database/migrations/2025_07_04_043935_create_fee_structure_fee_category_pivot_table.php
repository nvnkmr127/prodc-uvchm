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
        Schema::create('fee_structure_fee_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_structure_id')->constrained()->onDelete('cascade');
            $table->foreignId('fee_category_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_structure_fee_category');
    }
};
