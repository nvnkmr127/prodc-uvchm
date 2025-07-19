<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void {
    Schema::create('dashboard_widgets', function (Blueprint $table) {
        $table->id();
        $table->foreignId('dashboard_id')->constrained()->onDelete('cascade');
        $table->foreignId('widget_id')->constrained()->onDelete('cascade');
        $table->integer('row');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dashboard_widgets');
    }
};
