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
        Schema::create('student_portal_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->string('action'); // login_success, login_failed, dashboard_view, profile_update_request, logout
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('mobile_number')->nullable(); // Mobile used for login
            $table->json('location_data')->nullable(); // {country, city, lat, lon}
            $table->json('metadata')->nullable(); // Additional context
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->index(['student_id', 'created_at']);
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_portal_activity_logs');
    }
};
