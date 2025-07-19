<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, ensure category_type exists in fee_categories table
        Schema::table('fee_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('fee_categories', 'category_type')) {
                $table->enum('category_type', [
                    'tuition_fee', 
                    'uniform_fee', 
                    'library_fee', 
                    'exam_fee', 
                    'lab_fee', 
                    'transport_fee', 
                    'hostel_fee',
                    'sports_fee',
                    'registration_fee',
                    'caution_deposit',
                    'other'
                ])->default('other')->after('name');
            }
        });

        // Add component tracking fields to payments table (if not exists)
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'component_details')) {
                $table->json('component_details')->nullable()->after('notes')
                    ->comment('JSON array of fee components paid in this transaction');
            }
            
            if (!Schema::hasColumn('payments', 'payment_type')) {
                $table->enum('payment_type', ['full', 'partial', 'component', 'bulk'])
                    ->default('full')->after('payment_method')
                    ->comment('Type of payment: full invoice, partial, component-wise, or bulk');
            }
        });

        // Enhance student_fees table with additional tracking
        Schema::table('student_fees', function (Blueprint $table) {
            if (!Schema::hasColumn('student_fees', 'payment_id')) {
                $table->foreignId('payment_id')->nullable()->after('invoice_id')
                    ->constrained('payments')->onDelete('set null')
                    ->comment('Reference to the payment that settled this fee');
            }
            
            if (!Schema::hasColumn('student_fees', 'partial_payments')) {
                $table->json('partial_payments')->nullable()->after('transaction_id')
                    ->comment('Track multiple partial payments for this fee component');
            }
            
            if (!Schema::hasColumn('student_fees', 'original_amount')) {
                $table->decimal('original_amount', 10, 2)->nullable()->after('amount')
                    ->comment('Original amount before any splits due to partial payments');
            }
        });

        // Create component payment tracking table
        if (!Schema::hasTable('component_payments')) {
            Schema::create('component_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payment_id')->constrained()->onDelete('cascade');
                $table->foreignId('student_id')->constrained()->onDelete('cascade');
                $table->foreignId('fee_category_id')->constrained()->onDelete('cascade');
                $table->foreignId('student_fee_id')->nullable()->constrained()->onDelete('set null');
                $table->decimal('amount', 10, 2);
                $table->text('notes')->nullable();
                $table->timestamps();
                
                $table->index(['payment_id', 'fee_category_id']);
                $table->index(['student_id', 'fee_category_id']);
            });
        }

        // Create component payment summary view (for reporting) - simplified version
        DB::statement("
            CREATE OR REPLACE VIEW component_payment_summary AS
            SELECT 
                s.id as student_id,
                s.name as student_name,
                s.enrollment_number,
                fc.id as fee_category_id,
                fc.name as category_name,
                COALESCE(fc.category_type, 'other') as category_type,
                COUNT(sf.id) as total_fees,
                COALESCE(SUM(sf.amount), 0) as total_amount,
                COALESCE(SUM(CASE WHEN sf.status = 'paid' THEN sf.amount ELSE 0 END), 0) as paid_amount,
                COALESCE(SUM(CASE WHEN sf.status = 'unpaid' THEN sf.amount ELSE 0 END), 0) as unpaid_amount,
                COUNT(CASE WHEN sf.status = 'paid' THEN 1 END) as paid_count,
                COUNT(CASE WHEN sf.status = 'unpaid' THEN 1 END) as unpaid_count,
                ROUND(
                    CASE 
                        WHEN COALESCE(SUM(sf.amount), 0) > 0 
                        THEN (COALESCE(SUM(CASE WHEN sf.status = 'paid' THEN sf.amount ELSE 0 END), 0) / SUM(sf.amount)) * 100 
                        ELSE 0 
                    END, 2
                ) as payment_percentage
            FROM students s
            CROSS JOIN fee_categories fc
            LEFT JOIN student_fees sf ON s.id = sf.student_id AND fc.id = sf.fee_category_id
            GROUP BY s.id, s.name, s.enrollment_number, fc.id, fc.name, fc.category_type
            HAVING total_fees > 0
            ORDER BY s.enrollment_number, fc.name
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the view first
        DB::statement("DROP VIEW IF EXISTS component_payment_summary");
        
        // Drop component_payments table
        Schema::dropIfExists('component_payments');
        
        // Remove added columns from student_fees
        Schema::table('student_fees', function (Blueprint $table) {
            if (Schema::hasColumn('student_fees', 'payment_id')) {
                $table->dropForeign(['payment_id']);
                $table->dropColumn('payment_id');
            }
            if (Schema::hasColumn('student_fees', 'partial_payments')) {
                $table->dropColumn('partial_payments');
            }
            if (Schema::hasColumn('student_fees', 'original_amount')) {
                $table->dropColumn('original_amount');
            }
        });
        
        // Remove added columns from payments
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'component_details')) {
                $table->dropColumn('component_details');
            }
            if (Schema::hasColumn('payments', 'payment_type')) {
                $table->dropColumn('payment_type');
            }
        });
    }
};