<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create student_concessions table for component-level concessions
        if (! Schema::hasTable('student_concessions')) {
            Schema::create('student_concessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained()->onDelete('cascade');
                $table->foreignId('fee_category_id')->constrained()->onDelete('cascade');
                $table->enum('concession_type', ['fixed', 'percentage']);
                $table->decimal('concession_value', 10, 2);
                $table->decimal('concession_amount', 10, 2);
                $table->text('notes')->nullable();
                $table->foreignId('applied_by')->constrained('users');
                $table->timestamp('applied_at');
                $table->foreignId('reversed_by')->nullable()->constrained('users');
                $table->timestamp('reversed_at')->nullable();
                $table->timestamps();

                $table->index(['student_id', 'fee_category_id']);
                $table->index(['applied_at']);
            });
        }

        // 2. Enhance student_fees table for pure component system
        Schema::table('student_fees', function (Blueprint $table) {
            // Make invoice_id nullable (we'll remove dependency later)
            if (Schema::hasColumn('student_fees', 'invoice_id')) {
                $table->foreignId('invoice_id')->nullable()->change();
            }

            // Add concession fields
            if (! Schema::hasColumn('student_fees', 'concession_amount')) {
                $table->decimal('concession_amount', 10, 2)->default(0)->after('amount');
            }
            if (! Schema::hasColumn('student_fees', 'concession_notes')) {
                $table->text('concession_notes')->nullable()->after('concession_amount');
            }

            // Add installment support
            if (! Schema::hasColumn('student_fees', 'installment_number')) {
                $table->integer('installment_number')->default(1)->after('fee_category_id');
            }
            if (! Schema::hasColumn('student_fees', 'total_installments')) {
                $table->integer('total_installments')->default(1)->after('installment_number');
            }

            // Add better tracking
            if (! Schema::hasColumn('student_fees', 'original_amount')) {
                $table->decimal('original_amount', 10, 2)->nullable()->after('amount');
            }
            if (! Schema::hasColumn('student_fees', 'paid_amount')) {
                $table->decimal('paid_amount', 10, 2)->default(0)->after('original_amount');
            }

            // Add academic year tracking
            if (! Schema::hasColumn('student_fees', 'academic_year')) {
                $table->string('academic_year', 10)->nullable()->after('fee_structure_id');
            }
        });

        // 3. Update payments table for component payments
        Schema::table('payments', function (Blueprint $table) {
            // Make invoice_id nullable
            if (Schema::hasColumn('payments', 'invoice_id')) {
                $table->foreignId('invoice_id')->nullable()->change();
            }

            // Add student_id if not exists
            if (! Schema::hasColumn('payments', 'student_id')) {
                $table->foreignId('student_id')->constrained()->onDelete('cascade')->after('id');
            }

            // Add component payment fields
            if (! Schema::hasColumn('payments', 'payment_type')) {
                $table->enum('payment_type', ['component', 'bulk', 'partial', 'full'])
                    ->default('component')->after('payment_method');
            }

            if (! Schema::hasColumn('payments', 'component_details')) {
                $table->json('component_details')->nullable()->after('payment_type')
                    ->comment('JSON array of fee components paid');
            }

            if (! Schema::hasColumn('payments', 'receipt_number')) {
                $table->string('receipt_number')->unique()->nullable()->after('transaction_id');
            }
        });

        // 4. Create component_payment_items for detailed tracking
        if (! Schema::hasTable('component_payment_items')) {
            Schema::create('component_payment_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payment_id')->constrained()->onDelete('cascade');
                $table->foreignId('student_fee_id')->constrained()->onDelete('cascade');
                $table->decimal('amount_paid', 10, 2);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['payment_id', 'student_fee_id']);
            });
        }

        // 5. Create fee_installments table for installment management
        if (! Schema::hasTable('fee_installments')) {
            Schema::create('fee_installments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained()->onDelete('cascade');
                $table->foreignId('fee_category_id')->constrained()->onDelete('cascade');
                $table->string('academic_year', 10);
                $table->integer('installment_number');
                $table->integer('total_installments');
                $table->decimal('amount', 10, 2);
                $table->decimal('paid_amount', 10, 2)->default(0);
                $table->decimal('concession_amount', 10, 2)->default(0);
                $table->date('due_date');
                $table->date('paid_date')->nullable();
                $table->enum('status', ['unpaid', 'partial', 'paid', 'overdue'])->default('unpaid');
                $table->timestamps();

                $table->unique(['student_id', 'fee_category_id', 'academic_year', 'installment_number'], 'unique_installment');
                $table->index(['student_id', 'status']);
                $table->index(['due_date', 'status']);
            });
        }

        // 6. Create updated financial summary view
        DB::statement("
            CREATE OR REPLACE VIEW student_financial_summary AS
            SELECT 
                s.id as student_id,
                s.name as student_name,
                s.enrollment_number,
                COUNT(DISTINCT fc.id) as total_fee_categories,
                COALESCE(SUM(sf.amount - sf.concession_amount), 0) as total_fees_after_concession,
                COALESCE(SUM(sf.paid_amount), 0) as total_paid,
                COALESCE(SUM(sf.amount - sf.concession_amount - sf.paid_amount), 0) as total_due,
                COALESCE(SUM(sf.concession_amount), 0) as total_concessions,
                COUNT(CASE WHEN sf.status = 'paid' THEN 1 END) as paid_components,
                COUNT(CASE WHEN sf.status = 'unpaid' THEN 1 END) as unpaid_components,
                COUNT(CASE WHEN sf.status = 'unpaid' AND sf.due_date < CURDATE() THEN 1 END) as overdue_components,
                ROUND(
                    CASE 
                        WHEN SUM(sf.amount - sf.concession_amount) > 0 
                        THEN (SUM(sf.paid_amount) / SUM(sf.amount - sf.concession_amount)) * 100 
                        ELSE 100 
                    END, 2
                ) as payment_percentage
            FROM students s
            LEFT JOIN student_fees sf ON s.id = sf.student_id
            LEFT JOIN fee_categories fc ON sf.fee_category_id = fc.id
            GROUP BY s.id, s.name, s.enrollment_number
        ");
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS student_financial_summary');

        Schema::dropIfExists('fee_installments');
        Schema::dropIfExists('component_payment_items');
        Schema::dropIfExists('student_concessions');

        // Revert student_fees changes - with existence checks
        Schema::table('student_fees', function (Blueprint $table) {
            $existingColumns = Schema::getColumnListing('student_fees');
            $potentialColumns = [
                'concession_amount', 'concession_notes', 'installment_number',
                'total_installments', 'original_amount', 'paid_amount', 'academic_year',
            ];

            $columnsToDrop = array_intersect($potentialColumns, $existingColumns);

            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });

        // Revert payments changes - with existence checks
        Schema::table('payments', function (Blueprint $table) {
            $existingColumns = Schema::getColumnListing('payments');
            $potentialColumns = ['payment_type', 'component_details', 'receipt_number'];

            $columnsToDrop = array_intersect($potentialColumns, $existingColumns);

            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
