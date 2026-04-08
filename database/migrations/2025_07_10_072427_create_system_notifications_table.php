<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('message');
            $table->enum('type', ['success', 'error', 'warning', 'info'])->default('info');
            $table->enum('category', ['financial', 'academic', 'system', 'attendance', 'general'])->default('general');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->json('data')->nullable();
            $table->string('action_url')->nullable();
            $table->string('action_text')->nullable();
            $table->boolean('requires_action')->default(false);
            $table->boolean('play_sound')->default(false);
            $table->string('sound_file')->nullable();
            $table->boolean('is_persistent')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->json('sent_to_roles')->nullable();
            $table->json('sent_to_users')->nullable();
            $table->json('read_by')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['type', 'category', 'priority'], 'idx_notifications_lookup');
            $table->index(['created_at', 'expires_at'], 'idx_notifications_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_notifications');
    }
};
