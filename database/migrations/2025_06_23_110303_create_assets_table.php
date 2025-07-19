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
    Schema::create('assets', function (Blueprint $table) {
        $table->id();
        $table->string('name'); // e.g., "Dell Latitude Laptop", "6-Burner Gas Range"
        $table->string('asset_code')->unique()->nullable(); // e.g., "IT-001", "KITCH-005"
        $table->foreignId('asset_category_id')->constrained()->onDelete('cascade');
        $table->text('location'); // e.g., "Admin Office", "Main Kitchen", "Room 102"
        $table->integer('quantity')->default(1);
        $table->enum('condition', ['Good', 'Fair', 'Needs Repair', 'Damaged', 'Missing'])->default('Good');
        $table->date('purchase_date')->nullable();
        $table->decimal('purchase_price', 10, 2)->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
