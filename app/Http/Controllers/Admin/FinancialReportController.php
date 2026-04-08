<?php

namespace App\Http\Controllers\Admin;

use App\Exports\FinancialReportExport;
use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Payment;
use App\Services\ComponentFinancialReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use PDF;

class FinancialReportController extends Controller
{
    protected $reportService;

    public function __construct(ComponentFinancialReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function show(Request $request)
    {
        $reportData = null;
        $reportType = $request->input('report_type', 'summary');
        $filterParams = [];

        // Set default dates for the form fields
        $displayStartDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $displayEndDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        // Only generate a report if the form has been submitted
        if ($request->has('report_type')) {
            [$startDate, $endDate] = $this->parseDateRange($request);
            $filterParams = $request->all();

            // Correctly set the display dates to the ones used in the query
            $displayStartDate = $startDate->toDateString();
            $displayEndDate = $endDate->toDateString();

            if ($reportType == 'summary') {
                $reportData = $this->generateSummaryReport($startDate, $endDate);
            } elseif ($reportType == 'defaulters') {
                $reportData = $this->generateComponentDefaulterReport($request);
            } elseif ($reportType == 'collections') {
                $reportData = $this->generateComponentCollectionReport($startDate, $endDate, $request);
            }

            if ($request->has('export')) {
                if ($reportType == 'summary') {
                    $pdf = PDF::loadView('admin.reports.financial.summary_pdf', compact('reportData', 'startDate', 'endDate'));

                    return $pdf->download('financial-summary-'.time().'.pdf');
                }

                return Excel::download(new FinancialReportExport($reportData, $reportType), "{$reportType}_report.xlsx");
            }
        }

        return view('admin.reports.financial.show', compact('reportData', 'reportType', 'filterParams', 'displayStartDate', 'displayEndDate'));
    }

    private function parseDateRange(Request $request): array
    {
        $range = $request->input('date_range', 'this_month');
        if ($range === 'custom') {
            $request->validate(['start_date' => 'required|date', 'end_date' => 'required|date|after_or_equal:start_date']);

            return [Carbon::parse($request->start_date)->startOfDay(), Carbon::parse($request->end_date)->endOfDay()];
        }

        return match ($range) {
            'today' => [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()],
            'yesterday' => [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()],
            'this_week' => [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()],
            'last_week' => [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()],
            'last_7_days' => [Carbon::now()->subDays(6)->startOfDay(), Carbon::now()->endOfDay()],
            'last_30_days' => [Carbon::now()->subDays(29)->startOfDay(), Carbon::now()->endOfDay()],
            'last_month' => [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()],
            'this_year' => [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()],
            'last_year' => [Carbon::now()->subYear()->startOfYear(), Carbon::now()->subYear()->endOfYear()],
            default => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
        };
    }

    private function generateSummaryReport(Carbon $startDate, Carbon $endDate)
    {
        $totalIncome = Payment::whereBetween('payment_date', [$startDate, $endDate])->sum('amount');
        $totalExpenses = Expense::whereBetween('expense_date', [$startDate, $endDate])->sum('amount');
        $expensesByCategory = Expense::whereBetween('expense_date', [$startDate, $endDate])
            ->join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->select('expense_categories.name', DB::raw('sum(expenses.amount) as total'))
            ->groupBy('expense_categories.name')->get();

        return [
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'net_profit' => $totalIncome - $totalExpenses,
            'expense_chart_labels' => $expensesByCategory->pluck('name'),
            'expense_chart_data' => $expensesByCategory->pluck('total'),
        ];
    }

    /**
     * Generate component-based defaulter report
     * Migrated from invoice-based system to component-based system
     */
    private function generateComponentDefaulterReport(Request $request)
    {
        return $this->reportService->generateDefaulterReport($request->all());
    }

    /**
     * Generate component-based collection report
     * Migrated from invoice-based system to component-based system
     */
    private function generateComponentCollectionReport(Carbon $startDate, Carbon $endDate, Request $request)
    {
        return $this->reportService->generateCollectionReport($startDate, $endDate, $request->all());
    }
}
