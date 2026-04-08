<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add working days configuration to settings
        Setting::updateOrCreate(
            ['key' => 'working_days'],
            [
                'value' => json_encode(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday']),
                'type' => 'multiselect',
                'group' => 'academic',
                'label' => 'Working Days',
                'description' => 'Configure working days for timetable generation',
            ]
        );

        // Add Saturday theory only setting
        Setting::updateOrCreate(
            ['key' => 'saturday_theory_only'],
            [
                'value' => '1',
                'type' => 'toggle',
                'group' => 'academic',
                'label' => 'Saturday Theory Classes Only',
                'description' => 'Restrict Saturday to theory classes only (no lab sessions)',
            ]
        );

        // Add lab-theory alternation rule
        Setting::updateOrCreate(
            ['key' => 'lab_theory_alternation'],
            [
                'value' => '1',
                'type' => 'toggle',
                'group' => 'academic',
                'label' => 'Lab-Theory Alternation Rule',
                'description' => 'Enforce alternation: Lab in morning → Theory in afternoon, Lab in afternoon → Theory in morning',
            ]
        );

        // Add auto-create lab subjects setting
        Setting::updateOrCreate(
            ['key' => 'auto_create_lab_subjects'],
            [
                'value' => '1',
                'type' => 'toggle',
                'group' => 'academic',
                'label' => 'Auto-Create Required Lab Subjects',
                'description' => 'Automatically create Service Lab, Kitchen Lab, Front Office Lab, and Housekeeping Lab subjects if missing',
            ]
        );

        // Add max lab sessions per week
        Setting::updateOrCreate(
            ['key' => 'max_lab_sessions_per_week'],
            [
                'value' => '4',
                'type' => 'number',
                'group' => 'academic',
                'label' => 'Maximum Lab Sessions Per Week',
                'description' => 'Maximum number of lab sessions per practical group per week',
            ]
        );

        // Add timetable conflict resolution strategy
        Setting::updateOrCreate(
            ['key' => 'timetable_conflict_resolution'],
            [
                'value' => 'auto_resolve',
                'type' => 'select',
                'group' => 'academic',
                'label' => 'Automatic Conflict Resolution',
                'description' => 'How to handle timetable generation conflicts',
                'options' => json_encode([
                    'strict' => 'Strict - Fail on any conflict',
                    'auto_resolve' => 'Auto-resolve by finding alternatives',
                    'warn_continue' => 'Warn but continue generation',
                ]),
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $settingsToRemove = [
            'working_days',
            'saturday_theory_only',
            'lab_theory_alternation',
            'auto_create_lab_subjects',
            'max_lab_sessions_per_week',
            'timetable_conflict_resolution',
        ];

        Setting::whereIn('key', $settingsToRemove)->delete();
    }
};
