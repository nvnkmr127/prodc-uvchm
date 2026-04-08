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
        Schema::table('student_portal_activity_logs', function (Blueprint $table) {
            $table->boolean('is_suspicious')->default(false)->after('metadata');
            $table->text('flagged_reason')->nullable()->after('is_suspicious');
            $table->index('is_suspicious');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_portal_activity_logs', function (Blueprint $table) {
            $table->dropColumn(['is_suspicious', 'flagged_reason']);
        });
    }
};
