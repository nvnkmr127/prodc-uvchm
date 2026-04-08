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
        Schema::create('parent_contacts', function (Blueprint $table) {
            $table->id();

            // Foreign Key
            $table->foreignId('student_id')->constrained()->onDelete('cascade');

            // Contact Type & Basic Info
            $table->enum('contact_type', ['primary', 'secondary', 'emergency'])->default('primary');
            $table->string('contact_name');
            $table->enum('relationship', [
                'father',
                'mother',
                'guardian',
                'sibling',
                'grandparent',
                'uncle',
                'aunt',
                'other',
            ])->default('father');

            // Contact Information (encrypted for security)
            $table->text('primary_phone')->nullable(); // Encrypted
            $table->text('secondary_phone')->nullable(); // Encrypted
            $table->string('email')->nullable();
            $table->string('whatsapp_number')->nullable();

            // Preferences
            $table->enum('preferred_language', ['en', 'hi', 'regional'])->default('en');
            $table->json('notification_preferences')->nullable();

            // Contact Management
            $table->boolean('emergency_contact')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('last_contacted_at')->nullable();

            // Delivery Tracking
            $table->integer('contact_attempts')->default(0);
            $table->integer('delivery_failures')->default(0);
            $table->timestamp('blocked_until')->nullable()->index();

            // Additional Notes
            $table->text('notes')->nullable();

            // Timestamps
            $table->timestamps();

            // Indexes for Performance
            $table->index(['student_id', 'contact_type']);
            $table->index(['student_id', 'emergency_contact']);
            $table->index(['is_active', 'delivery_failures']);
            $table->index(['verified_at', 'is_active']);
            $table->index(['last_contacted_at']);

            // Composite index for notification queries
            $table->index(['student_id', 'is_active', 'contact_type'], 'student_active_contacts');
        });

        // Add some default notification preferences after table creation
        // DB::statement("ALTER TABLE parent_contacts ...");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parent_contacts');
    }
};
