<?php

// FILE: app/Http/Controllers/Admin/PaymentReportsController.php

namespace App\Http\Controllers\Admin;

use App\Exports\PaymentReportsExport;
use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\FeeCategory;
use App\Services\ComponentPaymentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class PaymentReportsController extends Controller
{
    protected $paymentService;

    public function __construct(ComponentPaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function dashboard()
    {
        $data = [
            'overview' => $this->getReportsOverview(),
            'collectionSummary' => $this->paymentService->getCollectionSummary(),
            'outstandingSummary' => $this->paymentService->getOutstandingSummary(),
            'recentReports' => $this->getRecentReports(),
        ];

        return view('admin.payment-reports.dashboard', $data);
    }

    public function collectionReport(Request $request)
    {
        $filters = $this->buildFilters($request);

        $data = [
            'collections' => $this->paymentService->getCollectionReport($filters),
            'summary' => $this->paymentService->getCollectionSummary($filters),
            'trends' => $this->paymentService->getCollectionTrends($filters['days'] ?? 30),
            'filters' => $filters,
        ];

        return view('admin.payment-reports.collection', $data);
    }

    public function outstandingReport(Request $request)
    {
        $filters = $this->buildFilters($request);

        $data = [
            'outstanding' => $this->paymentService->getOutstandingReport($filters),
            'aging' => $this->paymentService->getAgingAnalysis(),
            'defaulters' => $this->paymentService->getDefaultersList($filters),
            'filters' => $filters,
        ];

        return view('admin.payment-reports.outstanding', $data);
    }

    public function analyticsReport(Request $request)
    {
        $filters = $this->buildFilters($request);

        $data = [
            'analytics' => $this->paymentService->getPaymentAnalytics($filters),
            'trends' => $this->paymentService->getPaymentTrends($filters),
            'patterns' => $this->paymentService->getPaymentPatterns($filters),
            'forecasts' => $this->paymentService->getPaymentForecasts($filters),
            'filters' => $filters,
        ];

        return view('admin.payment-reports.analytics', $data);
    }

    public function feeWiseReport(Request $request)
    {
        $feeCategories = FeeCategory::all();
        $filters = $this->buildFilters($request);

        $data = [
            'feeWiseData' => $this->paymentService->getFeeWiseReport($filters),
            'categories' => $feeCategories,
            'comparison' => $this->paymentService->getFeeWiseComparison($filters),
            'filters' => $filters,
        ];

        return view('admin.payment-reports.fee-wise', $data);
    }

    public function batchWiseReport(Request $request)
    {
        $batches = Batch::with('course')->get();
        $filters = $this->buildFilters($request);

        $data = [
            'batchWiseData' => $this->paymentService->getBatchWiseReport($filters),
            'batches' => $batches,
            'comparison' => $this->paymentService->getBatchWiseComparison($filters),
            'filters' => $filters,
        ];

        return view('admin.payment-reports.batch-wise', $data);
    }

    public function exportReport(Request $request, $type)
    {
        $filters = $this->buildFilters($request);

        switch ($type) {
            case 'collection':
                $data = $this->paymentService->getCollectionReport($filters);
                $filename = 'collection-report-'.now()->format('Y-m-d');
                break;

            case 'outstanding':
                $data = $this->paymentService->getOutstandingReport($filters);
                $filename = 'outstanding-report-'.now()->format('Y-m-d');
                break;

            case 'analytics':
                $data = $this->paymentService->getPaymentAnalytics($filters);
                $filename = 'analytics-report-'.now()->format('Y-m-d');
                break;

            default:
                abort(404, 'Report type not found');
        }

        return Excel::download(
            new PaymentReportsExport($data, $type),
            $filename.'.xlsx'
        );
    }

    private function buildFilters(Request $request): array
    {
        return [
            'start_date' => $request->get('start_date', now()->startOfMonth()->format('Y-m-d')),
            'end_date' => $request->get('end_date', now()->format('Y-m-d')),
            'fee_category_id' => $request->get('fee_category_id'),
            'batch_id' => $request->get('batch_id'),
            'payment_method' => $request->get('payment_method'),
            'status' => $request->get('status'),
            'days' => $request->get('days', 30),
        ];
    }

    private function getFinancialOverview(): array
    {
        return [
            'total_revenue' => $this->paymentService->getTotalRevenue(),
            'monthly_revenue' => $this->paymentService->getMonthlyRevenue(),
            'outstanding_amount' => $this->paymentService->getTotalOutstandingAmount(),
            'collection_rate' => $this->paymentService->getCollectionRate(),
            'growth_rate' => $this->paymentService->getGrowthRate(),
        ];
    }

    private function getQuickStats(): array
    {
        return [
            'collections_today' => $this->paymentService->getTodayCollections(),
            'collections_this_month' => $this->paymentService->getMonthlyRevenue(),
            'pending_fees' => $this->paymentService->getPendingFeesCount(),
            'overdue_students' => $this->paymentService->getOverdueStudentsCount(),
        ];
    }

    private function getRecentReports(): array
    {
        return [
            ['name' => 'Collection Summary', 'generated_at' => now()->subHours(3)],
            ['name' => 'Outstanding Report', 'generated_at' => now()->subHours(6)],
            ['name' => 'Defaulter Analysis', 'generated_at' => now()->subDays(1)],
        ];
    }

    private function getAvailableReports(): array
    {
        return [
            'collection-summary' => 'Collection Summary Report',
            'outstanding-fees' => 'Outstanding Fees Report',
            'payment-analytics' => 'Payment Analytics Report',
            'defaulters' => 'Defaulters Report',
            'concession-report' => 'Concession Report',
            'profit-loss' => 'Profit & Loss Report',
        ];
    }

    private function getCollectionComparison($filters): array
    {
        $currentPeriod = $this->paymentService->getCollectionSummary($filters);

        // Get previous period for comparison
        $previousStart = Carbon::parse($filters['start_date'])->subDays(
            Carbon::parse($filters['end_date'])->diffInDays(Carbon::parse($filters['start_date']))
        );
        $previousEnd = Carbon::parse($filters['start_date'])->subDay();

        $previousFilters = array_merge($filters, [
            'start_date' => $previousStart->format('Y-m-d'),
            'end_date' => $previousEnd->format('Y-m-d'),
        ]);

        $previousPeriod = $this->paymentService->getCollectionSummary($previousFilters);

        return [
            'current' => $currentPeriod,
            'previous' => $previousPeriod,
            'growth' => $this->calculateGrowth($currentPeriod, $previousPeriod),
        ];
    }

    private function analyzeDefaulterSeverity($defaulters): array
    {
        return [
            'critical' => $defaulters->where('severity', 'critical')->count(),
            'high' => $defaulters->where('severity', 'high')->count(),
            'medium' => $defaulters->where('severity', 'medium')->count(),
            'low' => $defaulters->where('severity', 'low')->count(),
        ];
    }

    private function getDefaulterRecommendations($defaulters): array
    {
        return [
            'immediate_action' => $defaulters->where('overdue_days', '>', 60)->count(),
            'send_reminders' => $defaulters->where('overdue_days', '>', 30)->count(),
            'follow_up_calls' => $defaulters->where('overdue_days', '>', 90)->count(),
            'legal_action' => $defaulters->where('overdue_days', '>', 180)->count(),
        ];
    }

    private function analyzeConcessionImpact($filters): array
    {
        $concessionData = $this->paymentService->getConcessionSummary($filters);

        return [
            'revenue_impact' => $concessionData['total_concession_amount'],
            'student_count' => $concessionData['students_with_concessions'],
            'average_concession' => $concessionData['average_concession_amount'],
            'impact_percentage' => $concessionData['concession_percentage'],
        ];
    }

    private function getMonthlyProfitTrends($filters): array
    {
        $months = [];
        $profits = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthStart = $date->startOfMonth()->format('Y-m-d');
            $monthEnd = $date->endOfMonth()->format('Y-m-d');

            $monthlyIncome = $this->paymentService->getTotalCollections([
                'start_date' => $monthStart,
                'end_date' => $monthEnd,
            ]);

            $monthlyExpenses = Expense::whereBetween('expense_date', [$monthStart, $monthEnd])
                ->sum('amount');

            $months[] = $date->format('M Y');
            $profits[] = $monthlyIncome - $monthlyExpenses;
        }

        return [
            'months' => $months,
            'profits' => $profits,
        ];
    }

    private function calculateGrowth($current, $previous): array
    {
        $growth = [];

        foreach ($current as $key => $value) {
            if (isset($previous[$key]) && is_numeric($value) && is_numeric($previous[$key])) {
                if ($previous[$key] > 0) {
                    $growthRate = (($value - $previous[$key]) / $previous[$key]) * 100;
                } else {
                    $growthRate = $value > 0 ? 100 : 0;
                }

                $growth[$key] = [
                    'value' => $growthRate,
                    'direction' => $growthRate >= 0 ? 'up' : 'down',
                    'color' => $growthRate >= 0 ? 'success' : 'danger',
                ];
            }
        }

        return $growth;
    }
}
