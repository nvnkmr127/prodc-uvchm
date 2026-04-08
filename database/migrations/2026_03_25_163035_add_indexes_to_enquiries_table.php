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
        $existingIndexes = collect(Schema::getIndexes('enquiries'))->pluck('name')->toArray();

        Schema::table('enquiries', function (Blueprint $table) use ($existingIndexes) {
            // Indexing common filter columns for performance on large datasets (500k+)
            if (! in_array('enquiries_status_index', $existingIndexes)) {
                $table->index('status');
            }
            if (! in_array('enquiries_source_index', $existingIndexes)) {
                $table->index('source');
            }
            if (! in_array('enquiries_phone_number_index', $existingIndexes)) {
                $table->index('phone_number');
            }
            if (! in_array('enquiries_created_at_index', $existingIndexes)) {
                $table->index('created_at');
            }
            if (! in_array('enquiries_next_follow_up_date_index', $existingIndexes)) {
                $table->index('next_follow_up_date');
            }

            // Note: student_name LIKE '%%' cannot be efficiently indexed with B-Tree.
            // Consider MySQL Full-Text Search or Laravel Scout if name search is slow.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $existingIndexes = collect(Schema::getIndexes('enquiries'))->pluck('name')->toArray();

        Schema::table('enquiries', function (Blueprint $table) use ($existingIndexes) {
            if (in_array('enquiries_status_index', $existingIndexes)) {
                $table->dropIndex(['status']);
            }
            if (in_array('enquiries_source_index', $existingIndexes)) {
                $table->dropIndex(['source']);
            }
            if (in_array('enquiries_phone_number_index', $existingIndexes)) {
                $table->dropIndex(['phone_number']);
            }
            if (in_array('enquiries_created_at_index', $existingIndexes)) {
                $table->dropIndex(['created_at']);
            }
            if (in_array('enquiries_next_follow_up_date_index', $existingIndexes)) {
                $table->dropIndex(['next_follow_up_date']);
            }
        });
    }
};
