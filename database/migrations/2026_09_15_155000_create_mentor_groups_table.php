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
        if (! Schema::hasTable('mentor_groups')) {
            Schema::create('mentor_groups', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->foreignId('academic_year_id')->nullable()
                    ->constrained('academic_years')->nullOnDelete();
                $table->foreignId('faculty_id')->nullable()
                    ->constrained('users')->nullOnDelete();
                $table->foreignId('counselor_id')->nullable()
                    ->constrained('users')->nullOnDelete();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['academic_year_id', 'is_active']);
                $table->index('faculty_id');
                $table->index('counselor_id');
            });
        }

        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'mentor_group_id')) {
                $table->foreignId('mentor_group_id')->nullable()->after('mentor_id')
                    ->constrained('mentor_groups')->nullOnDelete();
            }
            if (! Schema::hasColumn('students', 'counselor_id')) {
                $table->foreignId('counselor_id')->nullable()->after('mentor_group_id')
                    ->constrained('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'counselor_id')) {
                $table->dropForeign(['counselor_id']);
                $table->dropColumn('counselor_id');
            }
            if (Schema::hasColumn('students', 'mentor_group_id')) {
                $table->dropForeign(['mentor_group_id']);
                $table->dropColumn('mentor_group_id');
            }
        });

        Schema::dropIfExists('mentor_groups');
    }
};
