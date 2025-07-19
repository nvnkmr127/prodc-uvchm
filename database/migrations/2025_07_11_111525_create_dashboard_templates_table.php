<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDashboardTemplatesTable extends Migration
{
    public function up()
    {
        Schema::create('dashboard_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category', 50)->default('general');
            $table->json('layout'); // Widget layout configuration
            $table->json('config')->nullable(); // Dashboard settings
            $table->string('preview_image')->nullable();
            $table->boolean('is_public')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('usage_count')->default(0);
            $table->timestamps();

            $table->index(['is_public', 'category']);
            $table->index(['created_by', 'is_public']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('dashboard_templates');
    }
}
