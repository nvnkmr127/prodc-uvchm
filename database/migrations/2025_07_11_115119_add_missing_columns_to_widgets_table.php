<?php
// database/migrations/xxxx_add_missing_columns_to_widgets_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('widgets', function (Blueprint $table) {
            // Add view_path column if it doesn't exist
            if (!Schema::hasColumn('widgets', 'view_path')) {
                $table->string('view_path', 200)->nullable()->after('component');
            }
            
            // Add other missing columns if they don't exist
            if (!Schema::hasColumn('widgets', 'category')) {
                $table->string('category', 50)->default('general')->after('component');
            }
            
            if (!Schema::hasColumn('widgets', 'icon')) {
                $table->string('icon', 100)->nullable()->after('category');
            }
            
            if (!Schema::hasColumn('widgets', 'description')) {
                $table->text('description')->nullable()->after('icon');
            }
            
            if (!Schema::hasColumn('widgets', 'default_config')) {
                $table->json('default_config')->nullable()->after('description');
            }
            
            if (!Schema::hasColumn('widgets', 'data_source')) {
                $table->string('data_source', 200)->nullable()->after('default_config');
            }
            
            if (!Schema::hasColumn('widgets', 'default_width')) {
                $table->integer('default_width')->default(2)->after('data_source');
            }
            
            if (!Schema::hasColumn('widgets', 'default_height')) {
                $table->integer('default_height')->default(2)->after('default_width');
            }
            
            if (!Schema::hasColumn('widgets', 'min_width')) {
                $table->integer('min_width')->default(1)->after('default_height');
            }
            
            if (!Schema::hasColumn('widgets', 'min_height')) {
                $table->integer('min_height')->default(1)->after('min_width');
            }
            
            if (!Schema::hasColumn('widgets', 'max_width')) {
                $table->integer('max_width')->default(12)->after('min_height');
            }
            
            if (!Schema::hasColumn('widgets', 'max_height')) {
                $table->integer('max_height')->default(10)->after('max_width');
            }
            
            if (!Schema::hasColumn('widgets', 'is_resizable')) {
                $table->boolean('is_resizable')->default(true)->after('max_height');
            }
            
            if (!Schema::hasColumn('widgets', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('is_resizable');
            }
            
            if (!Schema::hasColumn('widgets', 'last_updated_at')) {
                $table->timestamp('last_updated_at')->nullable()->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('widgets', function (Blueprint $table) {
            $columnsToCheck = [
                'view_path', 'category', 'icon', 'description', 'default_config',
                'data_source', 'default_width', 'default_height', 'min_width', 
                'min_height', 'max_width', 'max_height', 'is_resizable', 
                'is_active', 'last_updated_at'
            ];
            
            foreach ($columnsToCheck as $column) {
                if (Schema::hasColumn('widgets', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};