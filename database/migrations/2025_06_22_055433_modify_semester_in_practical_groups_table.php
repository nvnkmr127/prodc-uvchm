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
    Schema::table('practical_groups', function (Blueprint $table) {
        // We will add the new column first
        $table->string('academic_period')->after('classroom_id');
        // Then we drop the old column
        $table->dropColumn('semester');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('practical_groups', function (Blueprint $table) {
            //
        });
    }
};
