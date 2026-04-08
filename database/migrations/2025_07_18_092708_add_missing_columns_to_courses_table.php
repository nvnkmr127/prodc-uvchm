<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $columns = Schema::getColumnListing('courses');

            // Add code column if it doesn't exist
            if (! in_array('code', $columns)) {
                $table->string('code', 50)->nullable()->unique()->after('enrollment_prefix');
            }

            // Add duration_months column if it doesn't exist
            if (! in_array('duration_months', $columns)) {
                $table->integer('duration_months')->nullable()->after('duration_in_years');
            }

            // Ensure max_batch_size exists (it should from previous migration)
            if (! in_array('max_batch_size', $columns)) {
                $table->integer('max_batch_size')->default(30)->after('duration_months');
            }
        });

        // Only update existing records if both columns exist
        $columns = Schema::getColumnListing('courses');
        if (in_array('duration_in_years', $columns) && in_array('duration_months', $columns)) {
            DB::statement('UPDATE courses SET duration_months = ROUND(duration_in_years * 12) WHERE duration_months IS NULL AND duration_in_years IS NOT NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $columns = Schema::getColumnListing('courses');

            // Only drop columns that exist
            $columnsToDrop = [];
            if (in_array('code', $columns)) {
                $columnsToDrop[] = 'code';
            }
            if (in_array('duration_months', $columns)) {
                $columnsToDrop[] = 'duration_months';
            }

            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
