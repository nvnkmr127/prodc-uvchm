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
        Schema::table('enquiries', function (Blueprint $table) {
            $indexes = DB::select("SHOW INDEX FROM enquiries");
            $indexNames = array_column($indexes, 'Key_name');

            if (!in_array('enquiries_status_index', $indexNames)) {
                $table->index('status');
            }
            if (!in_array('enquiries_phone_number_index', $indexNames)) {
                $table->index('phone_number');
            }
            if (!in_array('enquiries_source_index', $indexNames)) {
                $table->index('source');
            }
            if (!in_array('enquiries_created_at_index', $indexNames)) {
                $table->index('created_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enquiries', function (Blueprint $table) {
            //
        });
    }
};
