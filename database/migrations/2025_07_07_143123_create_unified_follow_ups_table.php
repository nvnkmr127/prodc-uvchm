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
    // ADD THIS LINE: This will drop the old table if it exists.
    Schema::dropIfExists('follow_ups');

    // This is the code to create the new table.
    Schema::create('follow_ups', function (Blueprint $table) {
        $table->id();
        $table->morphs('followable'); 
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->text('notes');
        $table->timestamps();
    });
}

/**
 * Reverse the migrations.
 */
public function down(): void
{
    Schema::dropIfExists('follow_ups');
}
};
