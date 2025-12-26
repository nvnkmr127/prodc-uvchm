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
        // Instead of creating the table, modify the existing one
        Schema::table('dashboards', function (Blueprint $table) {
            // Add new columns that don't exist
            if (!Schema::hasColumn('dashboards', 'slug')) {
                $table->string('slug')->after('name');
            }
            
            if (!Schema::hasColumn('dashboards', 'layout')) {
                $table->json('layout')->nullable()->after('role_id');
            }
            
            if (!Schema::hasColumn('dashboards', 'config')) {
                $table->json('config')->nullable()->after('layout');
            }
            
            if (!Schema::hasColumn('dashboards', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('config');
            }
            
            if (!Schema::hasColumn('dashboards', 'is_default')) {
                $table->boolean('is_default')->default(false)->after('is_active');
            }
        });

        // Add unique constraint for slug if it doesn't exist
        try {
            Schema::table('dashboards', function (Blueprint $table) {
                $table->unique('slug');
            });
        } catch (\Exception $e) {
            // Index might already exist, ignore
        }

        // Populate slug column for existing records if any
        DB::table('dashboards')->whereNull('slug')->orWhere('slug', '')->update([
            'slug' => DB::raw("LOWER(REPLACE(name, ' ', '-'))")
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dashboards', function (Blueprint $table) {
            $table->dropColumn(['slug', 'layout', 'config', 'is_active', 'is_default']);
        });
    }
};