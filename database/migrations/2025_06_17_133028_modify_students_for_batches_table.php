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
        Schema::table('students', function (Blueprint $table) {
            // Add the new batch_id column. It can be nullable initially.
            $table->foreignId('batch_id')->nullable()->after('user_id')->constrained()->onDelete('cascade');

            // Drop the old course_id column
            // We first need to drop the foreign key constraint before dropping the column
            $table->dropForeign(['course_id']);
            $table->dropColumn('course_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Re-add the course_id column if we ever roll back
            $table->foreignId('course_id')->nullable()->after('user_id')->constrained()->onDelete('cascade');

            // Drop the batch_id column
            $table->dropForeign(['batch_id']);
            $table->dropColumn('batch_id');
        });
    }
};
