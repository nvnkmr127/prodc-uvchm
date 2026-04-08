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
        Schema::table('timetables', function (Blueprint $table) {
            // Add practical group support - NULLABLE because regular classes don't use groups
            $table->foreignId('practical_group_id')->nullable()->after('batch_id')
                ->constrained('practical_groups')->onDelete('cascade');

            // Add flag to distinguish lab sessions from regular classes
            $table->boolean('is_lab_session')->default(false)->after('classroom_id');

            // Add notes field for additional information
            $table->text('notes')->nullable()->after('is_lab_session');

            // Add indexes for better performance
            $table->index(['schedule_date', 'time_slot_id', 'classroom_id'], 'timetable_schedule_idx');
            $table->index(['batch_id', 'practical_group_id'], 'timetable_batch_group_idx');
            $table->index('is_lab_session', 'timetable_lab_session_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timetables', function (Blueprint $table) {
            $table->dropForeign(['practical_group_id']);
            $table->dropIndex('timetable_schedule_idx');
            $table->dropIndex('timetable_batch_group_idx');
            $table->dropIndex('timetable_lab_session_idx');
            $table->dropColumn(['practical_group_id', 'is_lab_session', 'notes']);
        });
    }
};
