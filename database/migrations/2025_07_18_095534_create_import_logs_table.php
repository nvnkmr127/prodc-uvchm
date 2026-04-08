<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Main import logs table
        Schema::create('import_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->nullable()->constrained()->onDelete('set null');
            $table->string('batch_name')->nullable();
            $table->string('course_name')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('user_name')->nullable();
            $table->string('import_type')->default('students_bulk_upload');
            $table->enum('status', ['processing', 'completed', 'failed'])->default('processing');
            $table->boolean('auto_create_invoices')->default(true);
            $table->integer('total_rows')->default(0);
            $table->integer('imported_count')->default(0);
            $table->integer('skipped_count')->default(0);
            $table->integer('rejected_count')->default(0);
            $table->integer('invoices_created')->default(0);
            $table->integer('invoice_errors_count')->default(0);
            $table->json('settings')->nullable();
            $table->json('summary')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['batch_id', 'status']);
            $table->index(['user_id', 'created_at']);
            $table->index('import_type');
        });

        // Detailed row-by-row logs
        Schema::create('import_log_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_log_id')->constrained()->onDelete('cascade');
            $table->json('row_data');
            $table->foreignId('student_id')->nullable()->constrained()->onDelete('set null');
            $table->string('student_name')->nullable();
            $table->enum('status', ['imported', 'skipped', 'rejected', 'error'])->default('imported');
            $table->text('message')->nullable();
            $table->timestamp('processed_at');
            $table->timestamps();

            $table->index(['import_log_id', 'status']);
            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_log_details');
        Schema::dropIfExists('import_logs');
    }
};
