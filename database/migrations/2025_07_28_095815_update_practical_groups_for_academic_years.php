<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('practical_groups', function (Blueprint $table) {
            // Add academic_year_id column
            $table->foreignId('academic_year_id')->nullable()->after('batch_id')->constrained('academic_years')->onDelete('cascade');

            // Drop course_term_id if it exists
            if (Schema::hasColumn('practical_groups', 'course_term_id')) {
                $table->dropForeign(['course_term_id']);
                $table->dropColumn('course_term_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('practical_groups', function (Blueprint $table) {
            if (Schema::hasColumn('practical_groups', 'academic_year_id')) {
                $table->dropForeign(['academic_year_id']);
                $table->dropColumn('academic_year_id');
            }

            // Re-add course_term_id
            $table->foreignId('course_term_id')->nullable()->after('batch_id')->constrained()->onDelete('cascade');
        });
    }
};
