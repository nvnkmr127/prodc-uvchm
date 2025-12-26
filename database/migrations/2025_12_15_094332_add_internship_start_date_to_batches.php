<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->date('internship_start_date')->nullable()->after('is_on_internship');
        });

        // Backfill existing internship batches with their start_date (legacy behavior)
        DB::table('batches')
            ->where('is_on_internship', true)
            ->update(['internship_start_date' => DB::raw('start_date')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->dropColumn('internship_start_date');
        });
    }
};
