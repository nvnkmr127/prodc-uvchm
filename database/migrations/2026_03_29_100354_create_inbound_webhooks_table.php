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
        Schema::create('inbound_webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('secret_token')->nullable();
            
            // stores JSON like {"student_name": "lead_name", "phone_number": "mobile"}
            $table->json('mapping_rules')->nullable();
            
            // stores the raw JSON of the last received call for testing/mapping UI
            $table->json('last_payload')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->string('source_name')->default('Web App'); // Default source if not mapped
            $table->integer('auto_followup_days')->default(0); // 0 = today, 1 = tomorrow
            
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamp('last_called_at')->nullable();
            $table->integer('success_count')->default(0);
            $table->integer('failure_count')->default(0);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inbound_webhooks');
    }
};
