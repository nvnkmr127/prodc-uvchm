<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->decimal('referral_commission_amount', 10, 2)->nullable()->after('referral_commission_paid_at');
            $table->string('referral_payment_mode')->nullable()->after('referral_commission_amount');
            $table->text('referral_payment_remarks')->nullable()->after('referral_payment_mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['referral_commission_amount', 'referral_payment_mode', 'referral_payment_remarks']);
        });
    }
};
