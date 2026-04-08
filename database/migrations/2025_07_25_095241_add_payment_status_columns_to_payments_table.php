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
        Schema::table('payments', function (Blueprint $table) {
            // Add status column if it doesn't exist
            if (! Schema::hasColumn('payments', 'status')) {
                $table->enum('status', ['pending', 'completed', 'failed', 'cancelled', 'refunded'])
                    ->default('completed')
                    ->after('notes');
            }

            // Add payment_type column if it doesn't exist
            if (! Schema::hasColumn('payments', 'payment_type')) {
                $table->enum('payment_type', ['tuition', 'component', 'miscellaneous', 'fine'])
                    ->default('tuition')
                    ->after('payment_method');
            }

            // Add academic_year column if it doesn't exist
            if (! Schema::hasColumn('payments', 'academic_year')) {
                $table->string('academic_year')
                    ->nullable()
                    ->after('payment_type');
            }

            // Add receipt_number column if it doesn't exist
            if (! Schema::hasColumn('payments', 'receipt_number')) {
                $table->string('receipt_number')
                    ->unique()
                    ->nullable()
                    ->after('academic_year');
            }

            // Add indexes for better performance
            $table->index(['status', 'payment_date']);
            $table->index(['payment_type', 'academic_year']);
            $table->index('receipt_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['status', 'payment_date']);
            $table->dropIndex(['payment_type', 'academic_year']);
            $table->dropIndex(['receipt_number']);

            // Drop columns
            if (Schema::hasColumn('payments', 'status')) {
                $table->dropColumn('status');
            }

            if (Schema::hasColumn('payments', 'payment_type')) {
                $table->dropColumn('payment_type');
            }

            if (Schema::hasColumn('payments', 'academic_year')) {
                $table->dropColumn('academic_year');
            }

            if (Schema::hasColumn('payments', 'receipt_number')) {
                $table->dropColumn('receipt_number');
            }
        });
    }
};
