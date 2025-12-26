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
        // Only proceed if student_fees table exists and doesn't have academic_year_id
        if (Schema::hasTable('student_fees') && !Schema::hasColumn('student_fees', 'academic_year_id')) {
            // Add academic_year_id column
            Schema::table('student_fees', function (Blueprint $table) {
                $table->foreignId('academic_year_id')
                    ->nullable()
                    ->constrained('academic_years')
                    ->onDelete('cascade');
            });

            // Backfill with current academic year
            $currentYear = \DB::table('academic_years')->where('is_current', true)->first();
            if ($currentYear) {
                \DB::table('student_fees')
                    ->whereNull('academic_year_id')
                    ->update(['academic_year_id' => $currentYear->id]);
            }

            // Add index if student_id column also exists
            if (Schema::hasColumn('student_fees', 'student_id')) {
                Schema::table('student_fees', function (Blueprint $table) {
                    $table->index(['academic_year_id', 'student_id'], 'idx_student_fees_year_student');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('student_fees') && Schema::hasColumn('student_fees', 'academic_year_id')) {
            // Drop index first
            Schema::table('student_fees', function (Blueprint $table) {
                $indexName = 'idx_student_fees_year_student';
                $indexes = \Schema::getConnection()->getDoctrineSchemaManager()
                    ->listTableIndexes('student_fees');

                if (array_key_exists($indexName, $indexes)) {
                    $table->dropIndex($indexName);
                }
            });

            // Drop foreign key and column
            Schema::table('student_fees', function (Blueprint $table) {
                $table->dropForeign(['academic_year_id']);
                $table->dropColumn('academic_year_id');
            });
        }
    }
};
