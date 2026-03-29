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
        Schema::create('inbound_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inbound_webhook_id')->constrained('inbound_webhooks')->onDelete('cascade');
            $table->json('payload')->nullable();
            $table->integer('status_code')->nullable();
            $table->text('error_message')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('method')->nullable();
            $table->foreignId('enquiry_id')->nullable()->constrained('enquiries')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inbound_webhook_logs');
    }
};
