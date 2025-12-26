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
        Schema::table('timetables', function (Blueprint $table) {
            // Add academic_year_id column
            $table->foreignId('academic_year_id')->nullable()->after('schedule_date')->constrained('academic_years')->onDelete('cascade');
            
            // Add index for better performance
            $table->index(['academic_year_id', 'schedule_date', 'time_slot_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timetables', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['academic_year_id']);
            
            // Drop the index
            $table->dropIndex(['academic_year_id', 'schedule_date', 'time_slot_id']);
            
            // Drop the column
            $table->dropColumn('academic_year_id');
        });
    }
};