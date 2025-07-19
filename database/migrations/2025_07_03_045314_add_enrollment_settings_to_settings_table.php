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
        Schema::table('settings', function (Blueprint $table) {
            // Add a new column to group settings in the UI
            $table->string('group')->default('general')->after('key');
            
            // Columns for Enrollment Number configuration
            $table->string('enrollment_prefix')->nullable()->after('value');
            $table->string('enrollment_year_format')->nullable()->after('enrollment_prefix');
            $table->integer('enrollment_starting_number')->default(1001)->after('enrollment_year_format');
            $table->integer('enrollment_last_number')->default(1000)->after('enrollment_starting_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'group',
                'enrollment_prefix',
                'enrollment_year_format',
                'enrollment_starting_number',
                'enrollment_last_number',
            ]);
        });
    }
};
