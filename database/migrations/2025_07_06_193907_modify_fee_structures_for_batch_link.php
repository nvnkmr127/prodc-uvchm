<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_structures', function (Blueprint $table) {
            // Drop the old foreign key for course_id
            $table->dropForeign(['course_id']);
            $table->dropColumn('course_id');

            // Add the new batch_id foreign key
            $table->foreignId('batch_id')
                  ->after('id')
                  ->unique() // Each batch can only have one fee structure
                  ->constrained('batches')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('fee_structures', function (Blueprint $table) {
            // Revert the changes if we roll back the migration
            $table->dropForeign(['batch_id']);
            $table->dropColumn('batch_id');

            $table->foreignId('course_id')->constrained('courses');
        });
    }
};