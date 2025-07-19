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
        // First, add the new column and link it to the batches table
        $table->foreignId('batch_id')->after('schedule_date')->constrained()->onDelete('cascade');

        // Then, remove the old course_id column and its foreign key
        $table->dropForeign(['course_id']);
        $table->dropColumn('course_id');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timetables', function (Blueprint $table) {
            //
        });
    }
};
