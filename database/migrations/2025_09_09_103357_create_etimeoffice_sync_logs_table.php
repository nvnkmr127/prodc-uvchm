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
        Schema::create('etimeoffice_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('sync_type', ['manual', 'auto', 'scheduled', 'webhook'])->default('manual');
            $table->string('date_range_type')->nullable(); // today, yesterday, custom, etc.
            $table->datetime('date_range_start');
            $table->datetime('date_range_end');
            $table->json('employee_codes')->nullable(); // JSON array of employee codes
            $table->boolean('test_mode')->default(false);
            
            // Results
            $table->enum('status', ['running', 'success', 'failed', 'partial'])->default('running');
            $table->integer('total_records')->default(0);
            $table->integer('processed_records')->default(0);
            $table->integer('created_records')->default(0);
            $table->integer('updated_records')->default(0);
            $table->integer('skipped_records')->default(0);
            $table->json('errors')->nullable(); // JSON array of error messages
            
            // Timing
            $table->datetime('started_at');
            $table->datetime('completed_at')->nullable();
            $table->integer('duration_seconds')->nullable();
            
            // User tracking
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            
            // Additional metadata
            $table->string('api_endpoint')->nullable();
            $table->json('request_params')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['status', 'created_at']);
            $table->index(['date_range_start', 'date_range_end']);
            $table->index('user_id');
            $table->index('sync_type');
            $table->index(['test_mode', 'status']);
            
            // Foreign key constraint
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('etimeoffice_sync_logs');
    }
};