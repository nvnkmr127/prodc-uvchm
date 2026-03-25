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
        Schema::table('enquiries', function (Blueprint $table) {
            // Indexing common filter columns for performance on large datasets (500k+)
            $table->index('status');
            $table->index('source');
            $table->index('phone_number');
            $table->index('created_at');
            $table->index('next_follow_up_date');
            
            // Note: student_name LIKE '%%' cannot be efficiently indexed with B-Tree.
            // Consider MySQL Full-Text Search or Laravel Scout if name search is slow.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enquiries', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['source']);
            $table->dropIndex(['phone_number']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['next_follow_up_date']);
        });
    }
};
