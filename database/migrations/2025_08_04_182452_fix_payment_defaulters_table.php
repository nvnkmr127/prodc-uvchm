<?php
// File: database/migrations/2025_08_04_180000_fix_payment_defaulters_table.php

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
        Schema::table('payment_defaulters', function (Blueprint $table) {
            $columns = Schema::getColumnListing('payment_defaulters');
            
            // Add missing columns that are being referenced in the code
            if (!in_array('fee_category_id', $columns)) {
                $table->foreignId('fee_category_id')->nullable()->constrained()->onDelete('set null')->after('student_id');
            }
            
            // Fix column name inconsistency
            if (in_array('total_overdue_invoices', $columns) && !in_array('overdue_fee_count', $columns)) {
                $table->renameColumn('total_overdue_invoices', 'overdue_fee_count');
            }
            
            // Add missing columns from the fillable array
            if (!in_array('component_breakdown', $columns)) {
                $table->json('component_breakdown')->nullable()->after('notes');
            }
            
            if (!in_array('affected_categories_count', $columns)) {
                $table->integer('affected_categories_count')->default(0)->after('component_breakdown');
            }
            
            if (!in_array('priority_score', $columns)) {
                $table->decimal('priority_score', 5, 2)->default(0)->after('affected_categories_count');
            }
            
            if (!in_array('contact_attempts', $columns)) {
                $table->integer('contact_attempts')->default(0)->after('last_contact_date');
            }
            
            if (!in_array('resolution_date', $columns)) {
                $table->date('resolution_date')->nullable()->after('contact_attempts');
            }
            
            if (!in_array('contact_history', $columns)) {
                $table->json('contact_history')->nullable()->after('resolution_date');
            }
            
            if (!in_array('escalation_level', $columns)) {
                $table->integer('escalation_level')->default(0)->after('contact_history');
            }
            
            // Add resolution tracking fields
            if (!in_array('resolution_method', $columns)) {
                $table->string('resolution_method')->nullable()->after('resolution_date');
            }
            
            if (!in_array('resolution_note', $columns)) {
                $table->text('resolution_note')->nullable()->after('resolution_method');
            }
            
            if (!in_array('resolved_by', $columns)) {
                $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null')->after('resolution_note');
            }
            
            // Fix the first_overdue_date column name if it exists as oldest_due_date
            if (in_array('first_overdue_date', $columns) && !in_array('oldest_due_date', $columns)) {
                $table->renameColumn('first_overdue_date', 'oldest_due_date');
            } elseif (!in_array('first_overdue_date', $columns) && !in_array('oldest_due_date', $columns)) {
                $table->date('oldest_due_date')->nullable()->after('overdue_days');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_defaulters', function (Blueprint $table) {
            $columns = Schema::getColumnListing('payment_defaulters');
            
            if (in_array('fee_category_id', $columns)) {
                $table->dropForeign(['fee_category_id']);
                $table->dropColumn('fee_category_id');
            }
            
            $columnsToCheck = [
                'component_breakdown', 'affected_categories_count', 'priority_score',
                'contact_attempts', 'resolution_date', 'contact_history', 'escalation_level',
                'resolution_method', 'resolution_note', 'resolved_by'
            ];
            
            foreach ($columnsToCheck as $column) {
                if (in_array($column, $columns)) {
                    if (str_contains($column, '_by')) {
                        $table->dropForeign([$column]);
                    }
                    $table->dropColumn($column);
                }
            }
        });
    }
};