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
            // Add transaction_id column
            $table->string('transaction_id')->nullable()->after('payment_method');
            
            // Add student_id column for direct student payments (optional but recommended)
            $table->foreignId('student_id')->nullable()->constrained()->onDelete('cascade')->after('invoice_id');
            
            // Add index for better performance
            $table->index(['student_id', 'payment_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->dropIndex(['student_id', 'payment_date']);
            $table->dropColumn(['transaction_id', 'student_id']);
        });
    }
};