<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_edit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('user_name')->nullable();
            $table->enum('action', ['edit', 'revert', 'create'])->default('edit');
            $table->json('previous_state'); // Complete previous state
            $table->json('new_state'); // Complete new state
            $table->json('changes'); // Detailed changes summary
            $table->text('notes'); // Edit reason/notes
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            
            $table->index(['invoice_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_edit_logs');
    }
};