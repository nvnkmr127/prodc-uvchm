<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('notification_type'); // email, push, sound, desktop
            $table->string('category'); // financial, academic, system, attendance
            $table->boolean('enabled')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'notification_type', 'category'], 'idx_preferences_unique');
            $table->index(['user_id', 'enabled'], 'idx_preferences_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
