<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PaymentReminderService;
use App\Services\PaymentAnalyticsService;
use App\Models\FeeCategory;
use App\Models\Student;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Batch;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PaymentReportsController extends Controller
{
    protected $reminderService;
    protected $analyticsService;

    public function __construct(
        PaymentReminderService $reminderService,
        PaymentAnalyticsService $analyticsService
    ) {
        $this->reminderService = $reminderService;
        $this->analyticsService = $analyticsService;
    }

    /**
     * Payment collection dashboard
     */
    public function dashboard()
    {
        $overview = [
            'total_students' => Student::count(),
            'active_students' => Student::where('status', 'active')->count(),
            'total_invoiced' => Invoice::sum('total_amount'),
            'total_collected' => Payment::sum('amount'),
            'pending_amount' => Invoice::sum('due_amount'),
            'overdue_amount' => Invoice::where('due_date', '<', now())->where('status', 'unpaid')->sum('due_amount'),
            'defaulters_count' => Student::whereHas('invoices', function($q) {
                $q->where('due_date', '<', now())->where('status', 'unpaid');
            })->count()
        ];

        $overview['collection_rate'] = $overview['total_invoiced'] > 0 ? 
            round(($overview['total_collected'] / $overview['total_invoiced']) * 100, 2) : 0;

        // Monthly collection trends (last 12 months)
        $monthlyTrends = Payment::selectRaw('DATE_FORMAT(payment_date, "%Y-%m") as month, SUM(amount) as total')
            ->where('payment_date', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('total', 'month');

        // Fee-wise collection stats
        $feeWiseCollection = $this->analyticsService->getFeeWiseComparison();

        // Recent payments
        $recentPayments = Payment::with(['student', 'invoice'])
            ->latest()
            ->limit(10)
            ->get();

        // Top performing batches
        $topBatches = $this->analyticsService->getBatchWisePerformance();

        return view('admin.payment-reports.dashboard', compact(
            'overview', 'monthlyTrends', 'feeWiseCollection', 'recentPayments', 'topBatches'
        ));
    }

    /**
     * Detailed collection report
     */
    public function collectionReport(Request $request)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : now()->endOfMonth();
        $feeType = $request->fee_type;
        $batchId = $request->batch_id;

        $query = Payment::with(['student.batch.course', 'invoice.items.feeCategory'])
            ->whereBetween('payment_date', [$startDate, $endDate]);

        if ($feeType) {
            $query->whereHas('invoice.items.feeCategory', function($q) use ($feeType) {
                $q->where('category_type', $feeType);
            });
        }

        if ($batchId) {
            $query->whereHas('student', function($q) use ($batchId) {
                $q->where('batch_id', $batchId);
            });
        }

        $payments = $query->paginate(50);
        $totalCollection = $query->sum('amount');
        
        // Payment method breakdown
        $paymentMethods = Payment::whereBetween('payment_date', [$startDate, $endDate])
            ->selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('payment_method')
            ->get();

        // Daily collection data for chart
        $dailyCollection = Payment::selectRaw('DATE(payment_date) as date, SUM(amount) as total')
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('total', 'date');

        $feeCategories = FeeCategory::all();
        $batches = Batch::with('course')->get();

        return view('admin.payment-reports.collection', compact(
            'payments', 'totalCollection', 'paymentMethods', 'dailyCollection',
            'startDate', 'endDate', 'feeType', 'batchId', 'feeCategories', 'batches'
        ));
    }

    /**
     * Outstanding dues report
     */
    public function outstandingReport()
    {
        // Outstanding by fee category
        $outstandingByCategory = FeeCategory::withSum(['invoices as outstanding_amount' => function($query) {
            $query->where('status', 'unpaid');
        }], 'due_amount')->get();

        // Aging analysis
        $agingAnalysis = [
            '0-30' => Invoice::whereBetween('due_date', [now()->subDays(30), now()])
                          ->where('status', 'unpaid')
                          ->sum('due_amount'),
            '31-60' => Invoice::whereBetween('due_date', [now()->subDays(60), now()->subDays(31)])
                           ->where('status', 'unpaid')
                           ->sum('due_amount'),
            '61-90' => Invoice::whereBetween('due_date', [now()->subDays(90), now()->subDays(61)])
                           ->where('status', 'unpaid')
                           ->sum('due_amount'),
            '90+' => Invoice::where('due_date', '<', now()->subDays(90))
                         ->where('status', 'unpaid')
                         ->sum('due_amount'),
        ];

        // Top defaulters
        $topDefaulters = Student::with(['batch.course'])
            ->select('students.*')
            ->selectRaw('SUM(invoices.due_amount) as total_due')
            ->join('invoices', 'students.id', '=', 'invoices.student_id')
            ->where('invoices.status', 'unpaid')
            ->groupBy('students.id')
            ->orderByDesc('total_due')
            ->limit(20)
            ->get();

        // Outstanding by batch
        $outstandingByBatch = Batch::with('course')
            ->withSum(['students.invoices as outstanding_amount' => function($query) {
                $query->where('status', 'unpaid');
            }], 'due_amount')
            ->orderByDesc('outstanding_amount')
            ->get();

        return view('admin.payment-reports.outstanding', compact(
            'outstandingByCategory', 'agingAnalysis', 'topDefaulters', 'outstandingByBatch'
        ));
    }

    /**
     * Analytics report
     */
    public function analyticsReport()
    {
        $insights = $this->analyticsService->getPaymentBehaviorInsights();
        
        return view('admin.payment-reports.analytics', compact('insights'));
    }

    /**
     * Fee-wise collection report
     */
    public function feeWiseReport()
    {
        $feeWiseData = $this->analyticsService->getFeeWiseComparison();
        
        return view('admin.payment-reports.fee-wise', compact('feeWiseData'));
    }

    /**
     * Batch-wise performance report
     */
    public function batchWiseReport()
    {
        $batchPerformance = $this->analyticsService->getBatchWisePerformance();
        
        return view('admin.payment-reports.batch-wise', compact('batchPerformance'));
    }

    /**
     * Export various reports
     */
    public function exportReport(Request $request, $type)
    {
        try {
            switch ($type) {
                case 'collection':
                    return $this->exportCollectionReport($request);
                case 'outstanding':
                    return $this->exportOutstandingReport($request);
                case 'analytics':
                    return $this->exportAnalyticsReport($request);
                default:
                    throw new \Exception('Invalid report type');
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }

    private function exportCollectionReport(Request $request)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : now()->endOfMonth();

        $payments = Payment::with(['student', 'invoice'])
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->get();

        $csv = "Date,Student,Enrollment,Amount,Payment Method,Invoice Number,Fee Type\n";
        
        foreach ($payments as $payment) {
            $csv .= sprintf(
                '"%s","%s","%s","%.2f","%s","%s","%s"' . "\n",
                $payment->payment_date->format('d-m-Y'),
                $payment->student->name,
                $payment->student->enrollment_number,
                $payment->amount,
                $payment->payment_method,
                $payment->invoice->invoice_number,
                'Mixed' // You can improve this by getting actual fee types
            );
        }

        $filename = 'collection_report_' . $startDate->format('Y_m_d') . '_to_' . $endDate->format('Y_m_d') . '.csv';
        
        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    private function exportOutstandingReport(Request $request)
    {
        $outstandingInvoices = Invoice::with(['student.batch.course'])
            ->where('status', 'unpaid')
            ->orderBy('due_date')
            ->get();

        $csv = "Student,Enrollment,Course,Batch,Invoice Number,Amount,Due Date,Days Overdue\n";
        
        foreach ($outstandingInvoices as $invoice) {
            $daysOverdue = $invoice->due_date->diffInDays(now(), false);
            $csv .= sprintf(
                '"%s","%s","%s","%s","%s","%.2f","%s","%d"' . "\n",
                $invoice->student->name,
                $invoice->student->enrollment_number,
                $invoice->student->course_name,
                $invoice->student->batch_name,
                $invoice->invoice_number,
                $invoice->due_amount,
                $invoice->due_date->format('d-m-Y'),
                $daysOverdue
            );
        }

        $filename = 'outstanding_report_' . date('Y_m_d_H_i_s') . '.csv';
        
        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    private function exportAnalyticsReport(Request $request)
    {
        $insights = $this->analyticsService->getPaymentBehaviorInsights();
        
        // Create a comprehensive analytics CSV
        $csv = "Analytics Report - Payment Behavior Insights\n\n";
        
        $csv .= "Early Payers:\n";
        $csv .= "Student,Average Early Days\n";
        foreach ($insights['early_payers'] as $payer) {
            $csv .= sprintf('"%s","%.1f"' . "\n", $payer['name'], abs($payer['avg_early_days']));
        }
        
        $csv .= "\nLate Payers:\n";
        $csv .= "Student,Average Late Days\n";
        foreach ($insights['late_payers'] as $payer) {
            $csv .= sprintf('"%s","%.1f"' . "\n", $payer['name'], $payer['avg_late_days']);
        }
        
        $filename = 'analytics_report_' . date('Y_m_d_H_i_s') . '.csv';
        
        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}