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
        Schema::table('salary_components', function (Blueprint $table) {
            $table->enum('calculation_type', ['fixed', 'percentage'])->default('fixed');
            $table->foreignId('base_component_id')->nullable()->constrained('salary_components')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salary_components', function (Blueprint $table) {
            $table->dropForeign(['base_component_id']);
            $table->dropColumn(['calculation_type', 'base_component_id']);
        });
    }
};
