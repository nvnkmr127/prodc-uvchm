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
        DB::statement("ALTER TABLE enquiries MODIFY COLUMN status ENUM('New', 'Contacted', 'Interested', 'Not Interested', 'Admitted', 'Follow-up', 'Interested Next Year', 'Next Entrance Exam') NOT NULL DEFAULT 'New'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('enquiries')->where('status', 'Next Entrance Exam')->update(['status' => 'Interested']);
        DB::statement("ALTER TABLE enquiries MODIFY COLUMN status ENUM('New', 'Contacted', 'Interested', 'Not Interested', 'Admitted', 'Follow-up', 'Interested Next Year') NOT NULL DEFAULT 'New'");
    }
};
