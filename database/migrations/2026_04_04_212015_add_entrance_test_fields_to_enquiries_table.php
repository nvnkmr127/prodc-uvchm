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
            $table->boolean('test_attended')->default(false)->after('status');
            $table->integer('test_marks')->nullable()->after('test_attended');
            $table->decimal('discount_offered', 10, 2)->nullable()->after('test_marks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enquiries', function (Blueprint $table) {
            $table->dropColumn(['test_attended', 'test_marks', 'discount_offered']);
        });
    }
};
