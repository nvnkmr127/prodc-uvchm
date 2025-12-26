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
        Schema::create('biometric_devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_id')->unique(); // Device unique identifier
            $table->string('device_name'); // Human readable name
            $table->enum('manufacturer', ['ESSL', 'ZKTeco', 'Hikvision', 'Realtime', 'Matrix', 'Mantra', 'Other'])->default('ESSL');
            $table->string('model')->nullable(); // Device model number
            $table->string('serial_number')->nullable(); // Device serial number
            $table->ipAddress('ip_address'); // Device IP address
            $table->integer('port')->default(80); // Device port
            $table->string('location')->nullable(); // Physical location
            $table->enum('status', ['active', 'inactive', 'maintenance', 'error'])->default('active');
            $table->json('settings')->nullable(); // Device specific settings
            $table->timestamp('last_sync')->nullable(); // Last successful sync
            $table->timestamp('last_heartbeat')->nullable(); // Last device ping
            $table->text('api_endpoint')->nullable(); // Device specific API endpoint
            $table->string('api_key')->nullable(); // Device specific API key
            $table->json('supported_features')->nullable(); // What features device supports
            $table->text('notes')->nullable(); // Admin notes
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['manufacturer', 'status']);
            $table->index('last_heartbeat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('biometric_devices');
    }
};