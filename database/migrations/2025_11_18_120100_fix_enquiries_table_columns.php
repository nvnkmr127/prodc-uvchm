<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('enquiries', function (Blueprint $table) {
            // Add gender column to enquiries table if it doesn't exist
            if (!Schema::hasColumn('enquiries', 'gender')) {
                $table->enum('gender', ['Male', 'Female', 'Other'])->nullable()->after('student_name');
            }
            
            // Add education_qualification if missing (often captured during enquiry)
            if (!Schema::hasColumn('enquiries', 'education_qualification')) {
                $table->string('education_qualification')->nullable()->after('address');
            }
        });

        // Update status column to allow 'Follow-up'
        DB::statement("ALTER TABLE enquiries MODIFY COLUMN status ENUM('New', 'Contacted', 'Interested', 'Not Interested', 'Admitted', 'Follow-up') NOT NULL DEFAULT 'New'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enquiries', function (Blueprint $table) {
            $table->dropColumn(['gender', 'education_qualification']);
        });

        // Revert status column
        DB::table('enquiries')->where('status', 'Follow-up')->update(['status' => 'Contacted']);
        DB::statement("ALTER TABLE enquiries MODIFY COLUMN status ENUM('New', 'Contacted', 'Interested', 'Not Interested', 'Admitted') NOT NULL DEFAULT 'New'");
    }
};