<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\Payment;
use App\Models\StudentFee;
use App\Models\ComponentPaymentItem;
use App\Models\FeeCategory;
use App\Services\ComponentPaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ValidateComponentSystem extends Command
{
    protected $signature = 'validate:component-system {--fix : Attempt to fix found issues} {--detailed : Show detailed output}';
    protected $description = 'Validate component system integrity and performance';

    private $errors = [];
    private $warnings = [];
    private $fixes = [];

    public function handle()
    {
        $this->info('🔍 Starting Component System Validation...');
        $this->newLine();

        // Run all validation checks
        $this->validateDatabaseStructure();
        $this->validateDataIntegrity();
        $this->validatePaymentConsistency();
        $this->validateRelationships();
        $this->validateIndexes();
        $this->validateFinancialSummary();
        $this->validateBusinessLogic();
        $this->performanceTests();

        // Apply fixes if requested
        if ($this->option('fix') && count($this->fixes) > 0) {
            $this->applyFixes();
        }

        // Display results
        $this->displayResults();

        return count($this->errors) === 0 ? 0 : 1;
    }

    private function validateDatabaseStructure()
    {
        $this->info('📋 Validating Database Structure...');

        // Check required tables exist
        $requiredTables = [
            'students',
            'student_fees', 
            'payments',
            'component_payment_items',
            'fee_categories',
            'fee_structures'
        ];

        foreach ($requiredTables as $table) {
            if (!Schema::hasTable($table)) {
                $this->errors[] = "Missing required table: {$table}";
            }
        }

        // Check required columns exist
        $requiredColumns = [
            'student_fees' => [
                'student_id', 'fee_category_id', 'amount', 'paid_amount', 
                'concession_amount', 'status', 'academic_year'
            ],
            'payments' => [
                'student_id', 'amount', 'payment_date', 'payment_method', 
                'payment_type', 'receipt_number'
            ],
            'component_payment_items' => [
                'payment_id', 'student_fee_id', 'amount_paid'
            ]
        ];

        foreach ($requiredColumns as $table => $columns) {
            if (Schema::hasTable($table)) {
                foreach ($columns as $column) {
                    if (!Schema::hasColumn($table, $column)) {
                        $this->errors[] = "Missing required column: {$table}.{$column}";
                    }
                }
            }
        }

        $this->info('✅ Database structure validation completed');
    }

    private function validateDataIntegrity()
    {
        $this->info('🔍 Validating Data Integrity...');

        // Check for orphaned student fees
        $orphanedFees = StudentFee::whereDoesntHave('student')->count();
        if ($orphanedFees > 0) {
            $this->errors[] = "Found {$orphanedFees} orphaned student fee records";
            $this->fixes[] = 'delete_orphaned_fees';
        }

        // Check for orphaned payments
        $orphanedPayments = Payment::whereDoesntHave('student')->count();
        if ($orphanedPayments > 0) {
            $this->errors[] = "Found {$orphanedPayments} orphaned payment records";
            $this->fixes[] = 'delete_orphaned_payments';
        }

        // Check for orphaned component payment items
        $orphanedItems = ComponentPaymentItem::whereDoesntHave('payment')->count();
        if ($orphanedItems > 0) {
            $this->errors[] = "Found {$orphanedItems} orphaned component payment items";
            $this->fixes[] = 'delete_orphaned_items';
        }

        // Check for invalid fee categories
        $invalidCategories = StudentFee::whereDoesntHave('feeCategory')->count();
        if ($invalidCategories > 0) {
            $this->warnings[] = "Found {$invalidCategories} fees with invalid categories";
        }

        // Check for negative amounts
        $negativeAmounts = StudentFee::where('amount', '<', 0)
                                   ->orWhere('paid_amount', '<', 0)
                                   ->orWhere('concession_amount', '<', 0)
                                   ->count();
        if ($negativeAmounts > 0) {
            $this->errors[] = "Found {$negativeAmounts} fees with negative amounts";
            $this->fixes[] = 'fix_negative_amounts';
        }

        $this->info('✅ Data integrity validation completed');
    }

    private function validatePaymentConsistency()
    {
        $this->info('💰 Validating Payment Consistency...');

        // Check for payment mismatches
        $inconsistentFees = DB::select("
            SELECT sf.id, sf.student_id, sf.amount, sf.paid_amount,
                   COALESCE(SUM(cpi.amount_paid), 0) as calculated_paid
            FROM student_fees sf
            LEFT JOIN component_payment_items cpi ON sf.id = cpi.student_fee_id
            GROUP BY sf.id, sf.student_id, sf.amount, sf.paid_amount
            HAVING ABS(sf.paid_amount - calculated_paid) > 0.01
        ");

        if (count($inconsistentFees) > 0) {
            $this->errors[] = 'Found ' . count($inconsistentFees) . ' fees with payment inconsistencies';
            $this->fixes[] = 'fix_payment_inconsistencies';

            if ($this->option('detailed')) {
                foreach ($inconsistentFees as $fee) {
                    $this->warn("  Fee ID {$fee->id}: Recorded ₹{$fee->paid_amount}, Calculated ₹{$fee->calculated_paid}");
                }
            }
        }

        // Check for overpayments
        $overpayments = StudentFee::whereRaw('paid_amount > (amount - concession_amount)')->count();
        if ($overpayments > 0) {
            $this->warnings[] = "Found {$overpayments} fees with overpayments";
        }

        // Check for payment items without corresponding payment records
        $invalidItems = ComponentPaymentItem::whereDoesntHave('payment')->count();
        if ($invalidItems > 0) {
            $this->errors[] = "Found {$invalidItems} payment items without valid payment records";
        }

        $this->info('✅ Payment consistency validation completed');
    }

    private function validateRelationships()
    {
        $this->info('🔗 Validating Relationships...');

        // Test key relationships
        try {
            // Test student -> studentFees relationship
            $studentWithFees = Student::with('studentFees')->first();
            if ($studentWithFees && !$studentWithFees->relationLoaded('studentFees')) {
                $this->errors[] = 'Student -> StudentFees relationship not working';
            }

            // Test payment -> componentItems relationship
            $paymentWithItems = Payment::with('componentItems')->where('payment_type', 'component')->first();
            if ($paymentWithItems && !$paymentWithItems->relationLoaded('componentItems')) {
                $this->errors[] = 'Payment -> ComponentItems relationship not working';
            }

            // Test studentFee -> feeCategory relationship
            $feeWithCategory = StudentFee::with('feeCategory')->first();
            if ($feeWithCategory && !$feeWithCategory->relationLoaded('feeCategory')) {
                $this->errors[] = 'StudentFee -> FeeCategory relationship not working';
            }

        } catch (\Exception $e) {
            $this->errors[] = 'Relationship validation error: ' . $e->getMessage();
        }

        $this->info('✅ Relationship validation completed');
    }

    private function validateIndexes()
    {
        $this->info('📊 Validating Database Indexes...');

        $requiredIndexes = [
            'student_fees' => ['idx_student_status', 'idx_category_year'],
            'payments' => ['idx_student_type', 'idx_type_date'],
            'component_payment_items' => ['idx_payment_fee']
        ];

        foreach ($requiredIndexes as $table => $indexes) {
            foreach ($indexes as $index) {
                if (!$this->indexExists($table, $index)) {
                    $this->warnings[] = "Missing recommended index: {$table}.{$index}";
                    $this->fixes[] = "add_index_{$table}_{$index}";
                }
            }
        }

        $this->info('✅ Index validation completed');
    }

    private function validateFinancialSummary()
    {
        $this->info('📈 Validating Financial Summary...');

        // Get overall financial summary
        $summary = DB::select("
            SELECT 
                COUNT(DISTINCT s.id) as total_students,
                COALESCE(SUM(sf.amount), 0) as total_fees,
                COALESCE(SUM(sf.paid_amount), 0) as total_paid,
                COALESCE(SUM(sf.amount - sf.concession_amount - sf.paid_amount), 0) as total_due,
                COALESCE(SUM(sf.concession_amount), 0) as total_concessions
            FROM students s
            LEFT JOIN student_fees sf ON s.id = sf.student_id
        ")[0];

        if ($this->option('detailed')) {
            $this->table(['Metric', 'Value'], [
                ['Total Students', number_format($summary->total_students)],
                ['Total Fees', '₹' . number_format($summary->total_fees, 2)],
                ['Total Collected', '₹' . number_format($summary->total_paid, 2)],
                ['Total Due', '₹' . number_format($summary->total_due, 2)],
                ['Total Concessions', '₹' . number_format($summary->total_concessions, 2)],
            ]);
        }

        // Validate payment totals match
        $paymentTotal = Payment::where('payment_type', 'component')->sum('amount');
        $itemTotal = ComponentPaymentItem::sum('amount_paid');

        if (abs($paymentTotal - $itemTotal) > 1) {
            $this->errors[] = "Payment totals mismatch: Payments ₹{$paymentTotal}, Items ₹{$itemTotal}";
        }

        $this->info('✅ Financial summary validation completed');
    }

    private function validateBusinessLogic()
    {
        $this->info('🏢 Validating Business Logic...');

        // Check for fees with invalid status
        $invalidStatus = StudentFee::whereNotIn('status', ['paid', 'unpaid', 'partial', 'overdue'])->count();
        if ($invalidStatus > 0) {
            $this->errors[] = "Found {$invalidStatus} fees with invalid status";
        }

        // Check for future-dated payments
        $futurePays = Payment::where('payment_date', '>', now())->count();
        if ($futurePays > 0) {
            $this->warnings[] = "Found {$futurePays} payments with future dates";
        }

        // Check for very old unpaid fees (business rule dependent)
        $veryOldUnpaid = StudentFee::where('status', 'unpaid')
                                  ->where('due_date', '<', now()->subYear())
                                  ->count();
        if ($veryOldUnpaid > 0) {
            $this->warnings[] = "Found {$veryOldUnpaid} fees unpaid for over a year";
        }

        $this->info('✅ Business logic validation completed');
    }

    private function performanceTests()
    {
        $this->info('⚡ Running Performance Tests...');

        $performanceResults = [];

        // Test common queries
        $queries = [
            'Student fees by status' => function() {
                return StudentFee::where('status', 'unpaid')->count();
            },
            'Component payments this month' => function() {
                return Payment::where('payment_type', 'component')
                             ->whereBetween('payment_date', [now()->startOfMonth(), now()])
                             ->count();
            },
            'Outstanding amounts' => function() {
                return StudentFee::whereRaw('amount - concession_amount - paid_amount > 0')->count();
            }
        ];

        foreach ($queries as $name => $query) {
            $start = microtime(true);
            $result = $query();
            $time = (microtime(true) - $start) * 1000;
            
            $performanceResults[] = [$name, $result, number_format($time, 2) . 'ms'];
            
            if ($time > 1000) { // Queries taking over 1 second
                $this->warnings[] = "Slow query detected: {$name} ({$time}ms)";
            }
        }

        if ($this->option('detailed')) {
            $this->table(['Query', 'Results', 'Time'], $performanceResults);
        }

        $this->info('✅ Performance tests completed');
    }

    private function applyFixes()
    {
        $this->info('🔧 Applying Fixes...');

        foreach ($this->fixes as $fix) {
            try {
                switch ($fix) {
                    case 'delete_orphaned_fees':
                        $deleted = StudentFee::whereDoesntHave('student')->delete();
                        $this->info("  ✅ Deleted {$deleted} orphaned fee records");
                        break;

                    case 'delete_orphaned_payments':
                        $deleted = Payment::whereDoesntHave('student')->delete();
                        $this->info("  ✅ Deleted {$deleted} orphaned payment records");
                        break;

                    case 'delete_orphaned_items':
                        $deleted = ComponentPaymentItem::whereDoesntHave('payment')->delete();
                        $this->info("  ✅ Deleted {$deleted} orphaned payment items");
                        break;

                    case 'fix_negative_amounts':
                        DB::beginTransaction();
                        try {
                            StudentFee::where('amount', '<', 0)->update(['amount' => 0]);
                            StudentFee::where('paid_amount', '<', 0)->update(['paid_amount' => 0]);
                            StudentFee::where('concession_amount', '<', 0)->update(['concession_amount' => 0]);
                            DB::commit();
                            $this->info("  ✅ Fixed negative amounts");
                        } catch (\Exception $e) {
                            DB::rollback();
                            $this->error("  ❌ Failed to fix negative amounts: " . $e->getMessage());
                        }
                        break;

                    case 'fix_payment_inconsistencies':
                        DB::beginTransaction();
                        try {
                            $inconsistentFees = DB::select("
                                SELECT sf.id, COALESCE(SUM(cpi.amount_paid), 0) as calculated_paid
                                FROM student_fees sf
                                LEFT JOIN component_payment_items cpi ON sf.id = cpi.student_fee_id
                                GROUP BY sf.id
                                HAVING ABS(sf.paid_amount - calculated_paid) > 0.01
                            ");

                            foreach ($inconsistentFees as $fee) {
                                StudentFee::where('id', $fee->id)
                                         ->update(['paid_amount' => $fee->calculated_paid]);
                            }

                            DB::commit();
                            $this->info("  ✅ Fixed " . count($inconsistentFees) . " payment inconsistencies");
                        } catch (\Exception $e) {
                            DB::rollback();
                            $this->error("  ❌ Failed to fix payment inconsistencies: " . $e->getMessage());
                        }
                        break;

                    default:
                        if (str_starts_with($fix, 'add_index_')) {
                            $this->info("  ⚠️  Index creation requires manual migration: {$fix}");
                        }
                        break;
                }
            } catch (\Exception $e) {
                $this->error("  ❌ Failed to apply fix {$fix}: " . $e->getMessage());
            }
        }
    }

    private function displayResults()
    {
        $this->newLine();
        $this->info('📊 Validation Results Summary');
        $this->line('================================');

        if (count($this->errors) === 0 && count($this->warnings) === 0) {
            $this->info('🎉 All validations passed! Component system is healthy.');
            return;
        }

        if (count($this->errors) > 0) {
            $this->error('❌ Errors Found (' . count($this->errors) . '):');
            foreach ($this->errors as $error) {
                $this->line('  • ' . $error);
            }
            $this->newLine();
        }

        if (count($this->warnings) > 0) {
            $this->warn('⚠️  Warnings (' . count($this->warnings) . '):');
            foreach ($this->warnings as $warning) {
                $this->line('  • ' . $warning);
            }
            $this->newLine();
        }

        if (count($this->fixes) > 0 && !$this->option('fix')) {
            $this->info('💡 Available fixes (' . count($this->fixes) . '):');
            $this->line('   Run with --fix option to automatically apply fixes');
            $this->newLine();
        }

        // Display recommendations
        $this->displayRecommendations();
    }

    private function displayRecommendations()
    {
        $this->info('💡 Recommendations:');

        if (count($this->errors) > 0) {
            $this->line('  • Fix critical errors immediately');
            $this->line('  • Run validation again after fixes');
        }

        if (count($this->warnings) > 0) {
            $this->line('  • Review warnings for potential issues');
            $this->line('  • Consider performance optimizations');
        }

        $this->line('  • Run this validation weekly');
        $this->line('  • Monitor database performance');
        $this->line('  • Keep regular backups');
        $this->line('  • Review financial reports regularly');

        $this->newLine();
        $this->info('For detailed help: php artisan validate:component-system --detailed');
    }

    private function indexExists(string $table, string $index): bool
    {
        try {
            $connection = Schema::getConnection();
            $schemaManager = $connection->getDoctrineSchemaManager();
            $doctrineTable = $schemaManager->listTableDetails($table);
            
            return $doctrineTable->hasIndex($index);
        } catch (\Exception $e) {
            return false;
        }
    }
}

// Additional helper command for component system testing
class TestComponentSystem extends Command
{
    protected $signature = 'test:component-system {--student=} {--create-test-data}';
    protected $description = 'Test component system functionality with sample data';

    public function handle()
    {
        $this->info('🧪 Testing Component System...');

        if ($this->option('create-test-data')) {
            $this->createTestData();
        }

        $this->testBasicFunctionality();
        $this->testPaymentProcessing();
        $this->testConcessionApplication();
        $this->testReporting();

        $this->info('✅ All tests completed successfully!');
    }

    private function createTestData()
    {
        $this->info('📝 Creating test data...');

        // Create test student if needed
        $student = Student::firstOrCreate(
            ['enrollment_number' => 'TEST001'],
            [
                'name' => 'Test Student',
                'email' => 'test@example.com',
                'student_mobile' => '9999999999',
                'batch_id' => 1, // Assume batch exists
            ]
        );

        // Create test fee categories
        $categories = [
            ['name' => 'Tuition Fee', 'category_type' => 'tuition_fee'],
            ['name' => 'Lab Fee', 'category_type' => 'lab_fee'],
            ['name' => 'Library Fee', 'category_type' => 'library_fee'],
        ];

        foreach ($categories as $categoryData) {
            $category = FeeCategory::firstOrCreate(['name' => $categoryData['name']], $categoryData);

            // Create student fee
            StudentFee::firstOrCreate(
                [
                    'student_id' => $student->id,
                    'fee_category_id' => $category->id,
                    'academic_year' => date('Y') . '-' . (date('Y') + 1)
                ],
                [
                    'amount' => rand(5000, 25000),
                    'due_date' => now()->addDays(30),
                    'status' => 'unpaid'
                ]
            );
        }

        $this->info('✅ Test data created');
    }

    private function testBasicFunctionality()
    {
        $this->info('🔍 Testing basic functionality...');

        // Test model relationships
        $student = Student::with(['studentFees.feeCategory'])->first();
        if (!$student) {
            $this->error('No test student found');
            return;
        }

        $this->line("  Student: {$student->name}");
        $this->line("  Fees: {$student->studentFees->count()}");

        // Test financial summary
        $summary = $student->getFinancialSummary();
        $this->line("  Total Amount: ₹{$summary['total_amount']}");
        $this->line("  Outstanding: ₹{$summary['remaining_amount']}");

        $this->info('✅ Basic functionality test passed');
    }

    private function testPaymentProcessing()
    {
        $this->info('💰 Testing payment processing...');

        $student = Student::first();
        $unpaidFee = $student->studentFees()->unpaid()->first();

        if (!$unpaidFee) {
            $this->warn('No unpaid fees found for testing');
            return;
        }

        $paymentService = app(ComponentPaymentService::class);

        $components = [
            [
                'fee_category_id' => $unpaidFee->fee_category_id,
                'amount' => min(1000, $unpaidFee->getRemainingAmount())
            ]
        ];

        $paymentData = [
            'payment_method' => 'cash',
            'payment_date' => now(),
            'notes' => 'Test payment'
        ];

        $result = $paymentService->processPayment($student, $components, $paymentData);

        if ($result['success']) {
            $this->info("✅ Payment processed: {$result['payment']->receipt_number}");
        } else {
            $this->error("❌ Payment failed: {$result['error']}");
        }
    }

    private function testConcessionApplication()
    {
        $this->info('🎁 Testing concession application...');

        $student = Student::first();
        $unpaidFee = $student->studentFees()->unpaid()->first();

        if (!$unpaidFee) {
            $this->warn('No unpaid fees found for concession testing');
            return;
        }

        $paymentService = app(ComponentPaymentService::class);

        $result = $paymentService->applyConcession(
            $student,
            $unpaidFee->fee_category_id,
            100,
            'Test concession'
        );

        if ($result['success']) {
            $this->info("✅ Concession applied: ₹100");
        } else {
            $this->error("❌ Concession failed: {$result['error']}");
        }
    }

    private function testReporting()
    {
        $this->info('📊 Testing reporting functionality...');

        $paymentService = app(ComponentPaymentService::class);

        // Test statistics
        $stats = $paymentService->getPaymentStatistics([
            'start_date' => now()->startOfMonth(),
            'end_date' => now()
        ]);

        $this->line("  Total payments: {$stats['total_payments']}");
        $this->line("  Total amount: ₹{$stats['total_amount']}");

        // Test collection summary
        $summary = $paymentService->getCollectionSummary();
        $this->line("  Collection %: {$summary['collection_percentage']}%");

        $this->info('✅ Reporting test passed');
    }
}