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
        Schema::table('courses', function (Blueprint $table) {
            // Add enrollment_prefix after name
            if (! Schema::hasColumn('courses', 'enrollment_prefix')) {
                $table->string('enrollment_prefix', 10)->nullable()->after('name');
            }

            // Add max_batch_size after description
            if (! Schema::hasColumn('courses', 'max_batch_size')) {
                $table->integer('max_batch_size')->default(30)->after('description');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['enrollment_prefix', 'max_batch_size']);
        });
    }
};
