<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * This migration ensures your database structure supports the enhanced timetable requirements
     */
    public function up(): void
    {
        // Update subjects table to support lab requirements
        Schema::table('subjects', function (Blueprint $table) {
            if (!Schema::hasColumn('subjects', 'lab_hours')) {
                $table->integer('lab_hours')->nullable()->after('requires_lab');
            }
            if (!Schema::hasColumn('subjects', 'theory_hours')) {
                $table->integer('theory_hours')->nullable()->after('lab_hours');
            }
            if (!Schema::hasColumn('subjects', 'description')) {
                $table->text('description')->nullable()->after('theory_hours');
            }
        });

        // Update practical_groups table to support academic year
        Schema::table('practical_groups', function (Blueprint $table) {
            if (!Schema::hasColumn('practical_groups', 'academic_year_id')) {
                $table->foreignId('academic_year_id')->after('classroom_id')->constrained()->onDelete('cascade');
            }
            
            // Only drop semester column if it exists and academic_year_id was successfully added
            if (Schema::hasColumn('practical_groups', 'semester') && Schema::hasColumn('practical_groups', 'academic_year_id')) {
                $table->dropColumn('semester');
            }
        });

        // Ensure timetables table has all required fields for enhanced functionality
        Schema::table('timetables', function (Blueprint $table) {
            // Add indexes for better performance with shortened names
            if (!$this->hasIndex('timetables', 'batch_date_time_idx')) {
                $table->index(['batch_id', 'schedule_date', 'time_slot_id'], 'batch_date_time_idx');
            }
            
            if (!$this->hasIndex('timetables', 'lab_session_idx')) {
                $table->index(['is_lab_session', 'academic_year_id'], 'lab_session_idx');
            }
            
            if (!$this->hasIndex('timetables', 'practical_group_idx')) {
                $table->index(['practical_group_id', 'schedule_date'], 'practical_group_idx');
            }
        });

        // Update time_slots table to support session types (if not already present)
        Schema::table('time_slots', function (Blueprint $table) {
            if (!Schema::hasColumn('time_slots', 'type')) {
                $table->enum('type', ['morning', 'afternoon', 'evening'])->default('morning')->after('end_time');
            }
            if (!Schema::hasColumn('time_slots', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('type');
            }
        });

        // Update classrooms table to support detailed lab information
        Schema::table('classrooms', function (Blueprint $table) {
            if (!Schema::hasColumn('classrooms', 'lab_type')) {
                $table->string('lab_type')->nullable()->after('type');
            }
            if (!Schema::hasColumn('classrooms', 'equipment')) {
                $table->text('equipment')->nullable()->after('lab_type');
            }
            if (!Schema::hasColumn('classrooms', 'max_group_size')) {
                $table->integer('max_group_size')->nullable()->after('capacity');
            }
        });

        // Create a new table for timetable generation logs
        if (!Schema::hasTable('timetable_generation_logs')) {
            Schema::create('timetable_generation_logs', function (Blueprint $table) {
                $table->id();
                $table->json('course_ids'); // Courses included in generation
                $table->foreignId('academic_year_id')->constrained()->onDelete('cascade');
                $table->date('week_start_date');
                $table->date('week_end_date');
                $table->json('generation_parameters'); // Parameters used for generation
                $table->json('statistics'); // Generation statistics
                $table->text('report')->nullable(); // Detailed generation report
                $table->json('violations')->nullable(); // Any requirement violations found
                $table->enum('status', ['started', 'completed', 'failed'])->default('started');
                $table->text('error_message')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
                
                // Indexes with shortened names
                $table->index(['academic_year_id', 'week_start_date'], 'academic_week_idx');
                $table->index(['status', 'created_at'], 'status_created_idx');
            });
        }

        // Create a requirements validation table for tracking compliance
        if (!Schema::hasTable('timetable_requirement_validations')) {
            Schema::create('timetable_requirement_validations', function (Blueprint $table) {
                $table->id();
                $table->string('requirement_code'); // e.g., 'FR-2', 'FR-3', etc.
                $table->string('requirement_name');
                $table->text('requirement_description');
                $table->foreignId('batch_id')->nullable()->constrained()->onDelete('cascade');
                $table->foreignId('practical_group_id')->nullable()->constrained()->onDelete('cascade');
                $table->foreignId('academic_year_id')->constrained()->onDelete('cascade');
                $table->date('validation_date');
                $table->boolean('is_compliant')->default(false);
                $table->text('violation_details')->nullable();
                $table->json('affected_timetable_ids')->nullable(); // IDs of timetable entries causing violations
                $table->timestamps();
                
                // Indexes with shortened names
                $table->index(['requirement_code', 'validation_date'], 'req_code_val_date_idx');
                $table->index(['is_compliant', 'academic_year_id'], 'compliant_academic_idx');
                $table->index(['batch_id', 'validation_date'], 'batch_val_date_idx');
            });
        }

        // Seed default lab subjects if they don't exist
        $this->seedDefaultLabSubjects();
        
        // Update existing time slots with types if needed
        $this->updateTimeSlotTypes();
        
        // Update existing classrooms with lab types
        $this->updateClassroomLabTypes();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove added columns (be careful with this in production)
        Schema::table('subjects', function (Blueprint $table) {
            if (Schema::hasColumn('subjects', 'lab_hours')) {
                $table->dropColumn('lab_hours');
            }
            if (Schema::hasColumn('subjects', 'theory_hours')) {
                $table->dropColumn('theory_hours');
            }
            if (Schema::hasColumn('subjects', 'description')) {
                $table->dropColumn('description');
            }
        });

        Schema::table('practical_groups', function (Blueprint $table) {
            if (Schema::hasColumn('practical_groups', 'academic_year_id')) {
                $table->dropForeign(['academic_year_id']);
                $table->dropColumn('academic_year_id');
            }
            if (!Schema::hasColumn('practical_groups', 'semester')) {
                $table->integer('semester')->nullable(); // Restore old semester column
            }
        });

        Schema::table('time_slots', function (Blueprint $table) {
            if (Schema::hasColumn('time_slots', 'type')) {
                $table->dropColumn('type');
            }
            // Don't drop is_active as it might be used elsewhere
        });

        Schema::table('classrooms', function (Blueprint $table) {
            if (Schema::hasColumn('classrooms', 'lab_type')) {
                $table->dropColumn('lab_type');
            }
            if (Schema::hasColumn('classrooms', 'equipment')) {
                $table->dropColumn('equipment');
            }
            if (Schema::hasColumn('classrooms', 'max_group_size')) {
                $table->dropColumn('max_group_size');
            }
        });

        // Drop indexes safely
        Schema::table('timetables', function (Blueprint $table) {
            if ($this->hasIndex('timetables', 'timetables_batch_date_time_index')) {
                $table->dropIndex('timetables_batch_date_time_index');
            }
            if ($this->hasIndex('timetables', 'timetables_lab_session_index')) {
                $table->dropIndex('timetables_lab_session_index');
            }
            if ($this->hasIndex('timetables', 'timetables_practical_group_index')) {
                $table->dropIndex('timetables_practical_group_index');
            }
        });

        // Drop new tables
        Schema::dropIfExists('timetable_requirement_validations');
        Schema::dropIfExists('timetable_generation_logs');
    }

    /**
     * Check if an index exists on a table
     */
    private function hasIndex(string $table, string $indexName): bool
    {
        return collect(\DB::select("SHOW INDEX FROM {$table}"))
            ->pluck('Key_name')
            ->contains($indexName);
    }

    /**
     * Seed default lab subjects required by the system
     */
    private function seedDefaultLabSubjects(): void
    {
        $requiredLabTypes = [
            ['name' => 'Service Lab', 'code' => 'SERVICELAB', 'lab_type' => 'service'],
            ['name' => 'Kitchen Lab', 'code' => 'KITCHENLAB', 'lab_type' => 'kitchen'],
            ['name' => 'Front Office Lab', 'code' => 'FRONTOFFICELAB', 'lab_type' => 'front_office'],
            ['name' => 'Housekeeping Lab', 'code' => 'HOUSEKEEPINGLAB', 'lab_type' => 'housekeeping']
        ];

        foreach ($requiredLabTypes as $labType) {
            // Check if subject already exists
            $exists = \DB::table('subjects')
                ->where('name', 'LIKE', "%{$labType['name']}%")
                ->where('requires_lab', true)
                ->exists();

            if (!$exists) {
                \DB::table('subjects')->insert([
                    'name' => $labType['name'],
                    'code' => $labType['code'],
                    'requires_lab' => true,
                    'lab_hours' => 2,
                    'theory_hours' => 0,
                    'description' => "Practical {$labType['name']} sessions focusing on hands-on skills development",
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
    }

    /**
     * Update existing time slots with appropriate types
     */
    private function updateTimeSlotTypes(): void
    {
        if (Schema::hasColumn('time_slots', 'type')) {
            // Update morning slots (typically before 12:00)
            \DB::table('time_slots')
                ->where('start_time', '<', '12:00:00')
                ->update(['type' => 'morning']);

            // Update afternoon slots (12:00 - 17:00)
            \DB::table('time_slots')
                ->where('start_time', '>=', '12:00:00')
                ->where('start_time', '<', '17:00:00')
                ->update(['type' => 'afternoon']);

            // Update evening slots (after 17:00)
            \DB::table('time_slots')
                ->where('start_time', '>=', '17:00:00')
                ->update(['type' => 'evening']);
        }
    }

    /**
     * Update existing classrooms with lab types based on their names
     */
    private function updateClassroomLabTypes(): void
    {
        if (Schema::hasColumn('classrooms', 'lab_type')) {
            $labMappings = [
                'kitchen' => ['kitchen', 'culinary', 'cooking', 'food'],
                'service' => ['service', 'restaurant', 'dining'],
                'front_office' => ['front office', 'reception', 'front desk', 'fo'],
                'housekeeping' => ['housekeeping', 'maintenance', 'cleaning', 'hk']
            ];

            foreach ($labMappings as $labType => $keywords) {
                foreach ($keywords as $keyword) {
                    \DB::table('classrooms')
                        ->where('type', 'lab')
                        ->where('name', 'LIKE', "%{$keyword}%")
                        ->whereNull('lab_type')
                        ->update([
                            'lab_type' => $labType,
                            'max_group_size' => 15 // Default optimal group size
                        ]);
                }
            }

            // Set equipment details for different lab types
            \DB::table('classrooms')
                ->where('lab_type', 'kitchen')
                ->update(['equipment' => 'Commercial stoves, ovens, prep stations, refrigeration units, cooking utensils']);

            \DB::table('classrooms')
                ->where('lab_type', 'service')
                ->update(['equipment' => 'Dining tables, service stations, POS systems, glassware, cutlery']);

            \DB::table('classrooms')
                ->where('lab_type', 'front_office')
                ->update(['equipment' => 'Reception desk, computers, telephone systems, booking software, printers']);

            \DB::table('classrooms')
                ->where('lab_type', 'housekeeping')
                ->update(['equipment' => 'Cleaning equipment, laundry facilities, maintenance tools, storage units']);
        }
    }
};
