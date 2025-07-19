<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Drop the user_id foreign key first
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');

            // Add new columns for direct student info
            $table->string('name')->after('id');
            $table->string('email')->nullable()->unique()->after('name');
            $table->string('father_name')->nullable()->after('email');
            $table->string('student_mobile')->nullable()->after('father_name');
            $table->string('father_mobile')->nullable()->after('student_mobile');
            $table->string('village')->nullable()->after('father_mobile');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Defines how to reverse the changes if needed
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->dropColumn(['name', 'email', 'father_name', 'student_mobile', 'father_mobile', 'village']);
        });
    }
};