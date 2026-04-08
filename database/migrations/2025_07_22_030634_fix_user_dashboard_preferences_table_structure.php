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
        // Check if table exists and what columns it has
        if (Schema::hasTable('user_dashboard_preferences')) {
            $columns = Schema::getColumnListing('user_dashboard_preferences');

            // If table only has basic columns (id, timestamps), rebuild it
            if (count($columns) <= 3) {
                Schema::dropIfExists('user_dashboard_preferences');
                $this->createCompleteTable();
            } else {
                // Add missing columns if they don't exist
                Schema::table('user_dashboard_preferences', function (Blueprint $table) use ($columns) {
                    if (! in_array('user_id', $columns)) {
                        $table->foreignId('user_id')->after('id')->constrained('users')->onDelete('cascade');
                    }
                    if (! in_array('dashboard_id', $columns)) {
                        $table->foreignId('dashboard_id')->after('user_id')->constrained('dashboards')->onDelete('cascade');
                    }
                    if (! in_array('layout_preferences', $columns)) {
                        $table->json('layout_preferences')->nullable()->after('dashboard_id');
                    }
                    if (! in_array('widget_preferences', $columns)) {
                        $table->json('widget_preferences')->nullable()->after('layout_preferences');
                    }
                    if (! in_array('filter_preferences', $columns)) {
                        $table->json('filter_preferences')->nullable()->after('widget_preferences');
                    }
                    if (! in_array('is_customized', $columns)) {
                        $table->boolean('is_customized')->default(false)->after('filter_preferences');
                    }
                    if (! in_array('last_accessed_at', $columns)) {
                        $table->timestamp('last_accessed_at')->nullable()->after('is_customized');
                    }
                });

                // Add constraints and indexes
                try {
                    Schema::table('user_dashboard_preferences', function (Blueprint $table) {
                        $table->unique(['user_id', 'dashboard_id']);
                        $table->index(['user_id', 'last_accessed_at']);
                    });
                } catch (\Exception $e) {
                    // Constraints might already exist
                }
            }
        } else {
            $this->createCompleteTable();
        }
    }

    private function createCompleteTable(): void
    {
        Schema::create('user_dashboard_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('dashboard_id')->constrained('dashboards')->onDelete('cascade');
            $table->json('layout_preferences')->nullable();
            $table->json('widget_preferences')->nullable();
            $table->json('filter_preferences')->nullable();
            $table->boolean('is_customized')->default(false);
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'dashboard_id']);
            $table->index(['user_id', 'last_accessed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_dashboard_preferences');
    }
};
