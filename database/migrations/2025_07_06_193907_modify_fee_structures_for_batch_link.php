<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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

            // Add course_id column first without constraint
            $table->foreignId('course_id')->nullable()->after('id');
        });

        // Update course_id values based on batch relationships
        DB::statement('
            UPDATE fee_structures fs 
            INNER JOIN batches b ON fs.batch_id = b.id 
            SET fs.course_id = b.course_id
        ');

        // Now add the foreign key constraint
        Schema::table('fee_structures', function (Blueprint $table) {
            $table->foreign('course_id')->references('id')->on('courses');
        });
    }
};