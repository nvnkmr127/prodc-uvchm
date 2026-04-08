<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // MySQL doesn't support direct ENUM modification via Blueprint
        // We need to use raw SQL to alter the ENUM column
        DB::statement("ALTER TABLE student_profile_requests 
            MODIFY COLUMN field_group ENUM('personal', 'address', 'photo', 'dob') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'dob' from ENUM
        // WARNING: This will fail if any records have field_group = 'dob'
        DB::statement("ALTER TABLE student_profile_requests 
            MODIFY COLUMN field_group ENUM('personal', 'address', 'photo') NOT NULL");
    }
};
