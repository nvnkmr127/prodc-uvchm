<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Add transaction_id column if it doesn't exist
            if (! Schema::hasColumn('payments', 'transaction_id')) {
                $table->string('transaction_id')->nullable()->after('payment_method');
            }

            // Add student_id column if it doesn't exist (optional but useful)
            if (! Schema::hasColumn('payments', 'student_id')) {
                $table->foreignId('student_id')->nullable()->constrained()->onDelete('cascade')->after('invoice_id');
                $table->index(['student_id', 'payment_date']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'student_id')) {
                $table->dropForeign(['student_id']);
                $table->dropIndex(['student_id', 'payment_date']);
                $table->dropColumn('student_id');
            }

            if (Schema::hasColumn('payments', 'transaction_id')) {
                $table->dropColumn('transaction_id');
            }
        });
    }
};
