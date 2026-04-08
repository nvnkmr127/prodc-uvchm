<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('time_slots', function (Blueprint $table) {
            if (! Schema::hasColumn('time_slots', 'duration')) {
                $table->integer('duration')->nullable()->after('end_time');
            }
            if (! Schema::hasColumn('time_slots', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('duration');
            }
            if (! Schema::hasColumn('time_slots', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('is_active');
            }
            if (! Schema::hasColumn('time_slots', 'description')) {
                $table->text('description')->nullable()->after('sort_order');
            }
        });
    }

    public function down(): void
    {
        Schema::table('time_slots', function (Blueprint $table) {
            if (Schema::hasColumn('time_slots', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('time_slots', 'sort_order')) {
                $table->dropColumn('sort_order');
            }
            if (Schema::hasColumn('time_slots', 'is_active')) {
                $table->dropColumn('is_active');
            }
            if (Schema::hasColumn('time_slots', 'duration')) {
                $table->dropColumn('duration');
            }
        });
    }
};
