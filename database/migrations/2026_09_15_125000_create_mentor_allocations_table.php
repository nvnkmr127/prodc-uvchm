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
        // Add mentor_id to students table if not exists
        if (! Schema::hasColumn('students', 'mentor_id')) {
            Schema::table('students', function (Blueprint $table) {
                $table->foreignId('mentor_id')->nullable()->after('status')
                    ->constrained('users')->nullOnDelete();
            });
        }

        // Create mentor_allocations table
        if (! Schema::hasTable('mentor_allocations')) {
            Schema::create('mentor_allocations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('academic_year_id')->nullable()
                    ->constrained('academic_years')->nullOnDelete();
                $table->foreignId('student_id')
                    ->constrained('students')->cascadeOnDelete();
                $table->foreignId('mentor_id')
                    ->constrained('users')->cascadeOnDelete();
                $table->foreignId('assigned_by')->nullable()
                    ->constrained('users')->nullOnDelete();
                $table->boolean('is_active')->default(true);
                $table->string('notes')->nullable();
                $table->timestamps();

                $table->index(['academic_year_id', 'student_id']);
                $table->index(['mentor_id', 'academic_year_id']);
                $table->index(['is_active']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mentor_allocations');

        if (Schema::hasColumn('students', 'mentor_id')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropForeign(['mentor_id']);
                $table->dropColumn('mentor_id');
            });
        }
    }
};
