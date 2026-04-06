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
        Schema::table('enquiries', function (Blueprint $table) {
            $table->boolean('include_uniform')->default(false)->after('discount_offered');
            $table->decimal('uniform_price', 10, 2)->nullable()->after('include_uniform');
            $table->boolean('include_books')->default(false)->after('uniform_price');
            $table->decimal('books_price', 10, 2)->nullable()->after('include_books');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enquiries', function (Blueprint $table) {
            $table->dropColumn(['include_uniform', 'uniform_price', 'include_books', 'books_price']);
        });
    }
};
