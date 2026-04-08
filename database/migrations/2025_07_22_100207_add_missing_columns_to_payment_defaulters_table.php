<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('payment_defaulters', 'contact_attempts') ||
            ! Schema::hasColumn('payment_defaulters', 'resolution_date') ||
            ! Schema::hasColumn('payment_defaulters', 'last_payment_date')) {

            Schema::table('payment_defaulters', function (Blueprint $table) {
                if (! Schema::hasColumn('payment_defaulters', 'contact_attempts')) {
                    $table->integer('contact_attempts')->default(0);
                }

                if (! Schema::hasColumn('payment_defaulters', 'resolution_date')) {
                    $table->date('resolution_date')->nullable();
                }

                if (! Schema::hasColumn('payment_defaulters', 'last_payment_date')) {
                    $table->date('last_payment_date')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('payment_defaulters', function (Blueprint $table) {
            if (Schema::hasColumn('payment_defaulters', 'contact_attempts')) {
                $table->dropColumn('contact_attempts');
            }

            if (Schema::hasColumn('payment_defaulters', 'resolution_date')) {
                $table->dropColumn('resolution_date');
            }

            if (Schema::hasColumn('payment_defaulters', 'last_payment_date')) {
                $table->dropColumn('last_payment_date');
            }
        });
    }
};
