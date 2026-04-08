<?php

namespace App\Console\Commands;

use App\Models\FeeCategory;
use App\Models\Student;
use App\Models\StudentFee;
use Illuminate\Console\Command;

class AnalyzeFeeCategories extends Command
{
    protected $signature = 'fees:analyze-categories {--category-id= : Analyze specific category} {--export : Export results to file}';

    protected $description = 'Analyze fee categories and show payment statistics';

    public function handle()
    {
        $this->info('🔍 Analyzing Fee Categories...');

        $categoryId = $this->option('category-id');
        $export = $this->option('export');

        if ($categoryId) {
            $this->analyzeSingleCategory($categoryId);
        } else {
            $this->analyzeAllCategories();
        }

        if ($export) {
            $this->exportAnalysis();
        }

        return 0;
    }

    private function analyzeSingleCategory($categoryId)
    {
        $category = FeeCategory::find($categoryId);

        if (! $category) {
            $this->error("❌ Fee category with ID {$categoryId} not found!");

            return;
        }

        $this->info("📊 Analyzing Category: {$category->name}");
        $this->newLine();

        $stats = $this->getCategoryStats($category);

        // Display category info
        $this->line('📋 Category Information:');
        $this->table(
            ['Property', 'Value'],
            [
                ['Name', $category->name],
                ['Code', $category->category_code ?? 'N/A'],
                ['Type', $category->category_type ?? 'General'],
                ['Mandatory', $category->is_mandatory ? 'Yes' : 'No'],
                ['Recurring', $category->is_recurring ? 'Yes' : 'No'],
            ]
        );

        $this->newLine();

        // Display statistics
        $this->line('📈 Payment Statistics:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Students', number_format($stats['total_students'])],
                ['Students Paid', number_format($stats['paid_students']).' ('.round($stats['paid_students_percentage'], 2).'%)'],
                ['Students Pending', number_format($stats['pending_students']).' ('.round($stats['pending_students_percentage'], 2).'%)'],
                ['Total Billed', '₹'.number_format($stats['total_billed'])],
                ['Total Collected', '₹'.number_format($stats['total_collected'])],
                ['Total Pending', '₹'.number_format($stats['total_pending'])],
                ['Total Overdue', '₹'.number_format($stats['total_overdue'])],
                ['Collection Rate', round($stats['collection_rate'], 2).'%'],
                ['Average Fee Amount', '₹'.number_format($stats['avg_fee_amount'])],
            ]
        );

        // Show top defaulters for this category
        $this->newLine();
        $this->showTopDefaulters($category);
    }

    private function analyzeAllCategories()
    {
        $this->info('📊 Analyzing All Fee Categories');
        $this->newLine();

        $categories = FeeCategory::select('fee_categories.*')
            ->selectRaw('
                COUNT(DISTINCT student_fees.student_id) as total_students,
                COUNT(DISTINCT CASE WHEN student_fees.status = "paid" THEN student_fees.student_id END) as paid_students,
                COUNT(DISTINCT CASE WHEN student_fees.status IN ("unpaid", "partial") THEN student_fees.student_id END) as pending_students,
                SUM(student_fees.amount) as total_billed,
                SUM(student_fees.paid_amount) as total_collected,
                SUM(CASE WHEN student_fees.status IN ("unpaid", "partial") THEN (student_fees.amount - student_fees.concession_amount - student_fees.paid_amount) ELSE 0 END) as total_pending,
                SUM(CASE WHEN student_fees.due_date < NOW() AND student_fees.status IN ("unpaid", "partial") THEN (student_fees.amount - student_fees.concession_amount - student_fees.paid_amount) ELSE 0 END) as total_overdue
            ')
            ->leftJoin('student_fees', 'fee_categories.id', '=', 'student_fees.fee_category_id')
            ->groupBy('fee_categories.id', 'fee_categories.name', 'fee_categories.category_code', 'fee_categories.category_type', 'fee_categories.is_mandatory', 'fee_categories.created_at', 'fee_categories.updated_at')
            ->orderBy('total_pending', 'desc')
            ->get();

        if ($categories->isEmpty()) {
            $this->warn('⚠️  No fee categories found!');

            return;
        }

        // Summary table
        $this->line('📋 Category Overview:');
        $this->table(
            ['Category', 'Type', 'Students', 'Billed', 'Collected', 'Pending', 'Collection %', 'Status'],
            $categories->map(function ($category) {
                $collectionRate = $category->total_billed > 0 ?
                    ($category->total_collected / $category->total_billed) * 100 : 0;

                $status = $collectionRate >= 80 ? '✅ Good' :
                         ($collectionRate >= 60 ? '⚠️  Warning' : '❌ Critical');

                return [
                    $category->name,
                    ucfirst($category->category_type ?? 'General'),
                    number_format($category->total_students ?? 0),
                    '₹'.number_format($category->total_billed ?? 0),
                    '₹'.number_format($category->total_collected ?? 0),
                    '₹'.number_format($category->total_pending ?? 0),
                    round($collectionRate, 1).'%',
                    $status,
                ];
            })->toArray()
        );

        // Overall statistics
        $this->newLine();
        $this->showOverallStats($categories);

        // Show problematic categories
        $this->newLine();
        $this->showProblematicCategories($categories);
    }

    private function getCategoryStats($category)
    {
        $stats = StudentFee::where('fee_category_id', $category->id)
            ->selectRaw('
                COUNT(DISTINCT student_id) as total_students,
                COUNT(DISTINCT CASE WHEN status = "paid" THEN student_id END) as paid_students,
                COUNT(DISTINCT CASE WHEN status IN ("unpaid", "partial") THEN student_id END) as pending_students,
                SUM(amount) as total_billed,
                SUM(paid_amount) as total_collected,
                SUM(CASE WHEN status IN ("unpaid", "partial") THEN (amount - concession_amount - paid_amount) ELSE 0 END) as total_pending,
                SUM(CASE WHEN due_date < NOW() AND status IN ("unpaid", "partial") THEN (amount - concession_amount - paid_amount) ELSE 0 END) as total_overdue,
                AVG(amount) as avg_fee_amount
            ')
            ->first();

        $totalStudents = $stats->total_students ?? 0;
        $paidStudents = $stats->paid_students ?? 0;
        $pendingStudents = $stats->pending_students ?? 0;
        $totalBilled = $stats->total_billed ?? 0;
        $totalCollected = $stats->total_collected ?? 0;

        return [
            'total_students' => $totalStudents,
            'paid_students' => $paidStudents,
            'pending_students' => $pendingStudents,
            'paid_students_percentage' => $totalStudents > 0 ? ($paidStudents / $totalStudents) * 100 : 0,
            'pending_students_percentage' => $totalStudents > 0 ? ($pendingStudents / $totalStudents) * 100 : 0,
            'total_billed' => $totalBilled,
            'total_collected' => $totalCollected,
            'total_pending' => $stats->total_pending ?? 0,
            'total_overdue' => $stats->total_overdue ?? 0,
            'collection_rate' => $totalBilled > 0 ? ($totalCollected / $totalBilled) * 100 : 0,
            'avg_fee_amount' => $stats->avg_fee_amount ?? 0,
        ];
    }

    private function showTopDefaulters($category)
    {
        $defaulters = Student::select('students.*')
            ->selectRaw('
                SUM(CASE WHEN student_fees.status IN ("unpaid", "partial") THEN (student_fees.amount - student_fees.concession_amount - student_fees.paid_amount) ELSE 0 END) as pending_amount,
                COUNT(CASE WHEN student_fees.status IN ("unpaid", "partial") THEN 1 END) as pending_fees,
                MIN(student_fees.due_date) as earliest_due_date
            ')
            ->join('student_fees', 'students.id', '=', 'student_fees.student_id')
            ->where('student_fees.fee_category_id', $category->id)
            ->whereIn('student_fees.status', ['unpaid', 'partial'])
            ->whereRaw('student_fees.amount - student_fees.concession_amount - student_fees.paid_amount > 0')
            ->with('batch.course')
            ->groupBy('students.id')
            ->orderBy('pending_amount', 'desc')
            ->limit(10)
            ->get();

        if ($defaulters->isEmpty()) {
            $this->info('✅ No students with pending payments in this category!');

            return;
        }

        $this->line('🔥 Top 10 Students with Pending Payments:');
        $this->table(
            ['Student', 'Course', 'Pending Amount', 'Pending Fees', 'Earliest Due'],
            $defaulters->map(function ($student) {
                return [
                    $student->name,
                    $student->batch?->course?->name ?? 'N/A',
                    '₹'.number_format($student->pending_amount),
                    $student->pending_fees,
                    $student->earliest_due_date ? \Carbon\Carbon::parse($student->earliest_due_date)->format('M d, Y') : 'N/A',
                ];
            })->toArray()
        );
    }

    private function showOverallStats($categories)
    {
        $totalBilled = $categories->sum('total_billed');
        $totalCollected = $categories->sum('total_collected');
        $totalPending = $categories->sum('total_pending');
        $totalOverdue = $categories->sum('total_overdue');
        $totalStudents = $categories->sum('total_students');

        $overallCollectionRate = $totalBilled > 0 ? ($totalCollected / $totalBilled) * 100 : 0;

        $this->line('🌟 Overall Statistics:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Categories', number_format($categories->count())],
                ['Total Students', number_format($totalStudents)],
                ['Total Billed', '₹'.number_format($totalBilled)],
                ['Total Collected', '₹'.number_format($totalCollected)],
                ['Total Pending', '₹'.number_format($totalPending)],
                ['Total Overdue', '₹'.number_format($totalOverdue)],
                ['Overall Collection Rate', round($overallCollectionRate, 2).'%'],
                ['Average per Student', $totalStudents > 0 ? '₹'.number_format($totalBilled / $totalStudents) : '₹0'],
            ]
        );
    }

    private function showProblematicCategories($categories)
    {
        $problematic = $categories->filter(function ($category) {
            $collectionRate = $category->total_billed > 0 ?
                ($category->total_collected / $category->total_billed) * 100 : 0;

            return $collectionRate < 70 && $category->total_pending > 50000; // Categories with <70% collection and >50k pending
        });

        if ($problematic->isEmpty()) {
            $this->info('✅ No problematic categories found!');

            return;
        }

        $this->line('⚠️  Categories Requiring Attention:');
        $this->table(
            ['Category', 'Collection Rate', 'Pending Amount', 'Students Affected', 'Recommendation'],
            $problematic->map(function ($category) {
                $collectionRate = $category->total_billed > 0 ?
                    ($category->total_collected / $category->total_billed) * 100 : 0;

                $recommendation = $collectionRate < 50 ? 'Urgent Action Required' : 'Increase Follow-ups';

                return [
                    $category->name,
                    round($collectionRate, 1).'%',
                    '₹'.number_format($category->total_pending ?? 0),
                    number_format($category->pending_students ?? 0),
                    $recommendation,
                ];
            })->toArray()
        );
    }

    private function exportAnalysis()
    {
        $this->info('📁 Exporting analysis to file...');

        // This would generate a detailed export file
        $filename = storage_path('app/fee-category-analysis-'.now()->format('Y-m-d-H-i-s').'.json');

        $categories = FeeCategory::with(['studentFees.student.batch.course'])->get();
        $analysisData = $categories->map(function ($category) {
            $stats = $this->getCategoryStats($category);

            return [
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'code' => $category->category_code,
                    'type' => $category->category_type,
                    'is_mandatory' => $category->is_mandatory,
                    'is_recurring' => $category->is_recurring,
                ],
                'statistics' => $stats,
                'generated_at' => now()->toDateTimeString(),
            ];
        });

        file_put_contents($filename, json_encode($analysisData, JSON_PRETTY_PRINT));

        $this->info("✅ Analysis exported to: {$filename}");
    }
}
