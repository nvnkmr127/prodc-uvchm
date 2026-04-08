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
        Schema::table('payment_reminder_templates', function (Blueprint $table) {
            // Add missing columns
            $table->text('description')->nullable()->after('name');
            $table->integer('character_limit')->nullable()->after('is_default');
            $table->json('template_settings')->nullable()->after('character_limit');
            $table->unsignedBigInteger('created_by')->nullable()->after('template_settings');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');

            // Rename subject to subject_template if it exists
            if (Schema::hasColumn('payment_reminder_templates', 'subject')) {
                $table->renameColumn('subject', 'subject_template');
            }
        });

        // Add foreign keys and indexes in a separate schema call
        Schema::table('payment_reminder_templates', function (Blueprint $table) {
            // Add indexes
            $table->index(['reminder_type', 'channel']);
            $table->index(['is_active', 'is_default']);

            // Add foreign keys (only if users table exists)
            if (Schema::hasTable('users')) {
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
                $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            }
        });

        // Add unique constraint for default templates
        try {
            Schema::table('payment_reminder_templates', function (Blueprint $table) {
                $table->unique(['reminder_type', 'channel', 'is_default'], 'unique_default_template');
            });
        } catch (\Exception $e) {
            // Index might already exist, ignore the error
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_reminder_templates', function (Blueprint $table) {
            // Drop foreign keys first
            if (Schema::hasColumn('payment_reminder_templates', 'created_by')) {
                $table->dropForeign(['created_by']);
            }
            if (Schema::hasColumn('payment_reminder_templates', 'updated_by')) {
                $table->dropForeign(['updated_by']);
            }

            // Drop indexes
            $table->dropIndex(['reminder_type', 'channel']);
            $table->dropIndex(['is_active', 'is_default']);

            // Drop unique constraint
            try {
                $table->dropUnique('unique_default_template');
            } catch (\Exception $e) {
                // Ignore if doesn't exist
            }

            // Drop added columns
            $table->dropColumn([
                'description',
                'character_limit',
                'template_settings',
                'created_by',
                'updated_by',
            ]);

            // Rename back to subject
            if (Schema::hasColumn('payment_reminder_templates', 'subject_template')) {
                $table->renameColumn('subject_template', 'subject');
            }
        });
    }
};
