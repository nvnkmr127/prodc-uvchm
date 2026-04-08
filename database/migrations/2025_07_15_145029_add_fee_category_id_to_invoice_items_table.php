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
        Schema::table('invoice_items', function (Blueprint $table) {
            // Add fee_category_id column with foreign key constraint
            $table->foreignId('fee_category_id')->nullable()->after('invoice_id')->constrained()->onDelete('cascade');

            // Add quantity column if it doesn't exist
            if (! Schema::hasColumn('invoice_items', 'quantity')) {
                $table->integer('quantity')->default(1)->after('amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropForeign(['fee_category_id']);
            $table->dropColumn('fee_category_id');

            if (Schema::hasColumn('invoice_items', 'quantity')) {
                $table->dropColumn('quantity');
            }
        });
    }
};
