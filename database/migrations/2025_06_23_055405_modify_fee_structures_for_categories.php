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
        Schema::table('fee_structures', function (Blueprint $table) {
            // We add the new column first, making it nullable and linking it.
            $table->foreignId('fee_category_id')->nullable()->after('academic_period')->constrained()->onDelete('set null');

            // Then we drop the old column
            $table->dropColumn('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fee_structures', function (Blueprint $table) {
            // This logic correctly reverses the changes from the up() method
            $table->string('description')->after('academic_period');
            $table->dropForeign(['fee_category_id']);
            $table->dropColumn('fee_category_id');
        });
    }
};