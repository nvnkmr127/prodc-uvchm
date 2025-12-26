<?php

// Only create this migration if you need the old "status" column for compatibility
// Otherwise, use the fixed query above

// Create: database/migrations/2025_08_01_add_status_to_biometric_logs_table.php

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
        Schema::table('biometric_logs', function (Blueprint $table) {
            // Add status column if you need backward compatibility
            $table->enum('status', ['processed', 'failed', 'duplicate', 'pending'])
                  ->after('sync_status')
                  ->nullable()
                  ->comment('Legacy status field for backward compatibility');
            
            // Create index for better performance
            $table->index('status');
        });
        
        // Update existing records to populate the new status field
        DB::statement("
            UPDATE biometric_logs 
            SET status = CASE 
                WHEN processed = 1 AND sync_status = 'success' THEN 'processed'
                WHEN processed = 0 AND sync_status = 'failed' THEN 'failed'
                WHEN processed = 0 AND sync_status = 'pending' THEN 'pending'
                ELSE 'pending'
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('biometric_logs', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });
    }
};