<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Enhance student_fees table
        Schema::table('student_fees', function (Blueprint $table) {
            if (! Schema::hasColumn('student_fees', 'installment_number')) {
                $table->integer('installment_number')->default(1)->after('academic_year');
            }
            if (! Schema::hasColumn('student_fees', 'total_installments')) {
                $table->integer('total_installments')->default(1)->after('installment_number');
            }
            if (! Schema::hasColumn('student_fees', 'concession_amount')) {
                $table->decimal('concession_amount', 10, 2)->default(0)->after('amount');
            }
            if (! Schema::hasColumn('student_fees', 'concession_reason')) {
                $table->text('concession_reason')->nullable()->after('concession_amount');
            }
            if (! Schema::hasColumn('student_fees', 'concession_approved_by')) {
                $table->foreignId('concession_approved_by')->nullable()->constrained('users')->after('concession_reason');
            }
            if (! Schema::hasColumn('student_fees', 'concession_approved_at')) {
                $table->timestamp('concession_approved_at')->nullable()->after('concession_approved_by');
            }
            if (! Schema::hasColumn('student_fees', 'notes')) {
                $table->text('notes')->nullable()->after('concession_approved_at');
            }
        });

        // 2. Create component_payment_items table
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

        // 3. Create student_concessions table
        if (! Schema::hasTable('student_concessions')) {
            Schema::create('student_concessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained()->onDelete('cascade');
                $table->foreignId('student_fee_id')->constrained()->onDelete('cascade');
                $table->string('concession_type'); // scholarship, financial_aid, discount
                $table->decimal('amount', 10, 2);
                $table->decimal('percentage', 5, 2)->nullable();
                $table->text('reason');
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->foreignId('requested_by')->constrained('users');
                $table->foreignId('approved_by')->nullable()->constrained('users');
                $table->timestamp('approved_at')->nullable();
                $table->text('approval_notes')->nullable();
                $table->timestamps();

                $table->index(['student_id', 'status']);
            });
        }

        // 4. Enhance payments table for component support
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'student_id')) {
                $table->foreignId('student_id')->constrained()->onDelete('cascade')->after('id');
            }
            if (! Schema::hasColumn('payments', 'payment_type')) {
                $table->enum('payment_type', ['component', 'bulk', 'installment'])->default('component')->after('payment_method');
            }
            if (! Schema::hasColumn('payments', 'component_details')) {
                $table->json('component_details')->nullable()->after('payment_type');
            }
            if (! Schema::hasColumn('payments', 'receipt_number')) {
                $table->string('receipt_number')->unique()->nullable()->after('transaction_id');
            }
            if (! Schema::hasColumn('payments', 'academic_year')) {
                $table->string('academic_year', 10)->nullable()->after('receipt_number');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_concessions');
        Schema::dropIfExists('component_payment_items');

        // Remove added columns from student_fees - with existence checks
        Schema::table('student_fees', function (Blueprint $table) {
            $columnsToCheck = [
                'installment_number', 'total_installments', 'concession_amount',
                'concession_reason', 'concession_approved_by', 'concession_approved_at', 'notes',
            ];

            $existingColumns = Schema::getColumnListing('student_fees');
            $columnsToDrop = array_intersect($columnsToCheck, $existingColumns);

            if (! empty($columnsToDrop)) {
                // Drop foreign key constraints first if they exist
                if (in_array('concession_approved_by', $columnsToDrop)) {
                    try {
                        $table->dropForeign(['concession_approved_by']);
                    } catch (\Exception $e) {
                        // Ignore if foreign key doesn't exist
                    }
                }

                $table->dropColumn($columnsToDrop);
            }
        });

        // Remove added columns from payments - with existence checks
        Schema::table('payments', function (Blueprint $table) {
            $columnsToCheck = ['payment_type', 'component_details', 'receipt_number', 'academic_year'];
            $existingColumns = Schema::getColumnListing('payments');
            $columnsToDrop = array_intersect($columnsToCheck, $existingColumns);

            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
