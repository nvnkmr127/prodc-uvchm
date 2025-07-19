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
            
            if (!in_array('view_path', $columns)) {
                $table->string('view_path')->nullable()->after('component');
            }
        });

        // Update existing records to have a default view_path if it was previously required
        \DB::table('widgets')->whereNull('view_path')->update([
            'view_path' => 'dashboard.widgets.default'
        ]);
    }

    public function down(): void
    {
        Schema::table('widgets', function (Blueprint $table) {
            $table->dropColumn('view_path');
        });
    }
};