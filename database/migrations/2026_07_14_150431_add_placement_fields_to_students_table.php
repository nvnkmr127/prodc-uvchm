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
            $table->string('placement_status')->nullable()->default('Not Placed')->after('status');
            $table->string('placed_at')->nullable()->after('placement_status');
            $table->string('placement_designation')->nullable()->after('placed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'placement_status',
                'placed_at',
                'placement_designation'
            ]);
        });
    }
};
