<?php

// Migration: Add description column to webhooks table
// php artisan make:migration add_description_to_webhooks_table

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('webhooks', function (Blueprint $table) {
            // Add columns only if they don't exist
            if (! Schema::hasColumn('webhooks', 'description')) {
                $table->text('description')->nullable()->after('event_name');
            }
            if (! Schema::hasColumn('webhooks', 'timeout_seconds')) {
                $table->integer('timeout_seconds')->default(30)->after('retry_count');
            }
            if (! Schema::hasColumn('webhooks', 'headers')) {
                $table->json('headers')->nullable()->after('timeout_seconds');
            }
            if (! Schema::hasColumn('webhooks', 'last_success_at')) {
                $table->timestamp('last_success_at')->nullable()->after('last_called_at');
            }
            if (! Schema::hasColumn('webhooks', 'last_failure_at')) {
                $table->timestamp('last_failure_at')->nullable()->after('last_success_at');
            }
            if (! Schema::hasColumn('webhooks', 'consecutive_failures')) {
                $table->integer('consecutive_failures')->default(0)->after('last_failure_at');
            }
            if (! Schema::hasColumn('webhooks', 'auto_disable_after_failures')) {
                $table->boolean('auto_disable_after_failures')->default(true)->after('consecutive_failures');
            }
            if (! Schema::hasColumn('webhooks', 'max_failures_before_disable')) {
                $table->integer('max_failures_before_disable')->default(10)->after('auto_disable_after_failures');
            }
        });

        // Add indexes in a separate schema call to avoid conflicts
        Schema::table('webhooks', function (Blueprint $table) {
            // Check if indexes exist before creating them
            try {
                $table->index(['event_name', 'is_active']);
            } catch (Exception $e) {
                // Index may already exist
            }

            try {
                $table->index('last_called_at');
            } catch (Exception $e) {
                // Index may already exist
            }
        });
    }

    public function down()
    {
        Schema::table('webhooks', function (Blueprint $table) {
            // Drop indexes safely using raw SQL
            $indexExists = DB::select("SHOW INDEX FROM webhooks WHERE Key_name = 'webhooks_event_name_is_active_index'");
            if (! empty($indexExists)) {
                DB::statement('ALTER TABLE webhooks DROP INDEX webhooks_event_name_is_active_index');
            }

            $indexExists = DB::select("SHOW INDEX FROM webhooks WHERE Key_name = 'webhooks_last_called_at_index'");
            if (! empty($indexExists)) {
                DB::statement('ALTER TABLE webhooks DROP INDEX webhooks_last_called_at_index');
            }

            // Drop columns only if they exist
            $columnsToCheck = [
                'description',
                'timeout_seconds',
                'headers',
                'last_success_at',
                'last_failure_at',
                'consecutive_failures',
                'auto_disable_after_failures',
                'max_failures_before_disable',
            ];

            foreach ($columnsToCheck as $column) {
                if (Schema::hasColumn('webhooks', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
