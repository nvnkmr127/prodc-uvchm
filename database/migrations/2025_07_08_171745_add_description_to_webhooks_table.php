<?php

// Migration: Add description column to webhooks table
// php artisan make:migration add_description_to_webhooks_table

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('webhooks', function (Blueprint $table) {
            $table->text('description')->nullable()->after('event_name');
            $table->integer('timeout_seconds')->default(30)->after('retry_count');
            $table->json('headers')->nullable()->after('timeout_seconds');
            $table->timestamp('last_success_at')->nullable()->after('last_called_at');
            $table->timestamp('last_failure_at')->nullable()->after('last_success_at');
            $table->integer('consecutive_failures')->default(0)->after('last_failure_at');
            $table->boolean('auto_disable_after_failures')->default(true)->after('consecutive_failures');
            $table->integer('max_failures_before_disable')->default(10)->after('auto_disable_after_failures');
            
            // Add indexes for better performance
            $table->index(['event_name', 'is_active']);
            $table->index('last_called_at');
        });
    }

    public function down()
    {
        Schema::table('webhooks', function (Blueprint $table) {
            $table->dropIndex(['event_name', 'is_active']);
            $table->dropIndex(['last_called_at']);
            
            $table->dropColumn([
                'description',
                'timeout_seconds', 
                'headers',
                'last_success_at',
                'last_failure_at',
                'consecutive_failures',
                'auto_disable_after_failures',
                'max_failures_before_disable'
            ]);
        });
    }
};