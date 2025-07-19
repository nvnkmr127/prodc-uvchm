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
    Schema::create('leave_balances', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade'); // The faculty member
        $table->foreignId('leave_type_id')->constrained()->onDelete('cascade');
        $table->integer('remaining_days');
        $table->year('year');
        $table->timestamps();

        $table->unique(['user_id', 'leave_type_id', 'year']); // Ensure one balance record per user, per type, per year
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_balances');
    }
};
