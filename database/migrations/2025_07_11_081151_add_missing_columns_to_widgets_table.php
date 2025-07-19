<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('widgets', function (Blueprint $table) {
            $columns = Schema::getColumnListing('widgets');
            
            // Add missing columns one by one
            if (!in_array('type', $columns)) {
                $table->string('type')->nullable()->after('name');
            }
            if (!in_array('component', $columns)) {
                $table->string('component', 100)->default('ChartWidget')->after('type');
            }
            if (!in_array('category', $columns)) {
                $table->string('category', 50)->default('analytics')->after('component');
            }
            if (!in_array('icon', $columns)) {
                $table->string('icon', 100)->nullable()->after('category');
            }
            if (!in_array('description', $columns)) {
                $table->text('description')->nullable()->after('icon');
            }
            if (!in_array('default_config', $columns)) {
                $table->json('default_config')->nullable()->after('description');
            }
            if (!in_array('data_source', $columns)) {
                $table->string('data_source', 200)->nullable()->after('default_config');
            }
            if (!in_array('default_width', $columns)) {
                $table->integer('default_width')->default(2)->after('data_source');
            }
            if (!in_array('default_height', $columns)) {
                $table->integer('default_height')->default(2)->after('default_width');
            }
            if (!in_array('is_resizable', $columns)) {
                $table->boolean('is_resizable')->default(true)->after('default_height');
            }
            if (!in_array('is_active', $columns)) {
                $table->boolean('is_active')->default(true)->after('is_resizable');
            }
        });

        // Add indexes after columns are created
        try {
            Schema::table('widgets', function (Blueprint $table) {
                $table->index(['category', 'is_active']);
                $table->index('type');
            });
        } catch (\Exception $e) {
            // Indexes might already exist
        }

        // Update existing widgets with default values
        \DB::table('widgets')->whereNull('type')->update([
            'type' => \DB::raw('CONCAT("widget_", id)'),
            'component' => 'ChartWidget',
            'category' => 'analytics',
            'default_width' => 2,
            'default_height' => 2,
            'is_resizable' => true,
            'is_active' => true
        ]);
    }

    public function down(): void
    {
        Schema::table('widgets', function (Blueprint $table) {
            $table->dropIndex(['category', 'is_active']);
            $table->dropIndex(['type']);
            
            $table->dropColumn([
                'type', 'component', 'category', 'icon', 'description',
                'default_config', 'data_source', 'default_width', 
                'default_height', 'is_resizable', 'is_active'
            ]);
        });
    }
};