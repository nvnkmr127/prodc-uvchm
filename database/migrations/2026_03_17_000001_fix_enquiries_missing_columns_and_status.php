<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enquiries', function (Blueprint $table) {
            // Re-add email column (was dropped in a previous migration but is still used in code)
            if (!Schema::hasColumn('enquiries', 'email')) {
                $table->string('email')->nullable()->after('phone_number');
            }

            // Add date_of_birth column (referenced in model, validation, and forms but never migrated)
            if (!Schema::hasColumn('enquiries', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('gender');
            }
        });

        // Extend status ENUM to include 'Interested Next Year'
        DB::statement("ALTER TABLE enquiries MODIFY COLUMN status ENUM('New', 'Contacted', 'Interested', 'Not Interested', 'Admitted', 'Follow-up', 'Interested Next Year') NOT NULL DEFAULT 'New'");
    }

    public function down(): void
    {
        // Revert 'Interested Next Year' status before dropping the ENUM value
        DB::table('enquiries')->where('status', 'Interested Next Year')->update(['status' => 'Interested']);

        DB::statement("ALTER TABLE enquiries MODIFY COLUMN status ENUM('New', 'Contacted', 'Interested', 'Not Interested', 'Admitted', 'Follow-up') NOT NULL DEFAULT 'New'");

        Schema::table('enquiries', function (Blueprint $table) {
            if (Schema::hasColumn('enquiries', 'date_of_birth')) {
                $table->dropColumn('date_of_birth');
            }
            if (Schema::hasColumn('enquiries', 'email')) {
                $table->dropColumn('email');
            }
        });
    }
};
