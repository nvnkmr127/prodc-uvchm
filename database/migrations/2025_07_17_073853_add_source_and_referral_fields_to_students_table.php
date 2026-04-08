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
            // Add new fields
            $table->string('source')->default('direct')->after('village');
            $table->string('referral_name')->nullable()->after('source');

            // Remove old fields (if you want to remove them from database too)
            // Uncomment these lines if you want to remove current_employer and job_title columns
            // $table->dropColumn(['current_employer', 'job_title']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Remove new fields
            $table->dropColumn(['source', 'referral_name']);

            // Add back old fields (if you removed them in up() method)
            // Uncomment these lines if you removed current_employer and job_title above
            // $table->string('current_employer')->nullable();
            // $table->string('job_title')->nullable();
        });
    }
};
