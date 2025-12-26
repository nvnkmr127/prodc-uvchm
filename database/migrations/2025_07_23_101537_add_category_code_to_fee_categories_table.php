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
        Schema::table('fee_categories', function (Blueprint $table) {
            // Only add the category_code column if it doesn't already exist
            if (!Schema::hasColumn('fee_categories', 'category_code')) {
                $table->string('category_code')->nullable()->unique()->after('name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fee_categories', function (Blueprint $table) {
            // Only drop the column if it exists
            if (Schema::hasColumn('fee_categories', 'category_code')) {
                $table->dropColumn('category_code');
            }
        });
    }
};