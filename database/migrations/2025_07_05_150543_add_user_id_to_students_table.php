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
        Schema::table('students', function (Blueprint $table) {
            // Add the user_id column after the id column
            $table->foreignId('user_id')
                ->nullable() // Make it nullable initially if you have old records without users
                ->after('id')
                ->constrained('users') // This creates the foreign key constraint to the users table
                ->onDelete('cascade'); // Optional: if a user is deleted, their student profile is also deleted
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // This is to make the migration reversible
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
