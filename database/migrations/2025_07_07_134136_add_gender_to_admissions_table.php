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
        Schema::table('admissions', function (Blueprint $table) {
            // Add the new 'gender' column after the 'full_name' column
            $table->string('gender')->after('full_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            // This allows you to undo the migration if needed
            $table->dropColumn('gender');
        });
    }
};
