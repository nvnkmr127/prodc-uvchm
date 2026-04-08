<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('practical_groups', function (Blueprint $table) {
            // Add missing columns if they don't exist
            if (! Schema::hasColumn('practical_groups', 'course_term_id')) {
                $table->foreignId('course_term_id')
                    ->nullable()
                    ->after('batch_id')
                    ->constrained('course_terms')
                    ->onDelete('set null');
            }

            if (! Schema::hasColumn('practical_groups', 'classroom_id')) {
                $table->foreignId('classroom_id')
                    ->nullable()
                    ->after('course_term_id')
                    ->constrained('classrooms')
                    ->onDelete('set null');
            }

            if (! Schema::hasColumn('practical_groups', 'max_students')) {
                $table->integer('max_students')->default(20)->after('classroom_id');
            }

            if (! Schema::hasColumn('practical_groups', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('max_students');
            }

            if (! Schema::hasColumn('practical_groups', 'description')) {
                $table->text('description')->nullable()->after('is_active');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('practical_groups', function (Blueprint $table) {
            $table->dropForeign(['course_term_id']);
            $table->dropForeign(['classroom_id']);
            $table->dropColumn([
                'course_term_id',
                'classroom_id',
                'max_students',
                'is_active',
                'description',
            ]);
        });
    }
};
