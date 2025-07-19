<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\FeeCategory;
use App\Models\Batch;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PaymentAnalyticsService
{
    /**
     * Get payment behavior insights
     */
    public function getPaymentBehaviorInsights(): array
    {
        return [
            'early_payers' => $this->getEarlyPayers(),
            'late_payers' => $this->getLatePayers(),
            'consistent_defaulters' => $this->getConsistentDefaulters(),
            'payment_patterns' => $this->getPaymentPatterns(),
            'seasonal_trends' => $this->getSeasonalTrends(),
            'risk_assessment' => $this->getRiskAssessment()
        ];
    }

    /**
     * Get students who consistently pay early
     */
    private function getEarlyPayers(): array
    {
        return Student::select('students.*')
            ->selectRaw('AVG(DATEDIFF(payments.payment_date, invoices.due_date)) as avg_early_days')
            ->join('invoices', 'students.id', '=', 'invoices.student_id')
            ->join('payments', 'invoices.id', '=', 'payments.invoice_id')
            ->where('payments.payment_date', '<', DB::raw('invoices.due_date'))
            ->groupBy('students.id')
            ->havingRaw('COUNT(payments.id) >= 3') // At least 3 payments
            ->havingRaw('AVG(DATEDIFF(payments.payment_date, invoices.due_date)) <= -3') // Average 3+ days early
            ->orderByDesc('avg_early_days')
            ->limit(20)
            ->get()
            ->toArray();
    }

    /**
     * Get students who consistently pay late
     */
    private function getLatePayers(): array
    {
        return Student::select('students.*')
            ->selectRaw('AVG(DATEDIFF(payments.payment_date, invoices.due_date)) as avg_late_days')
            ->selectRaw('COUNT(payments.id) as total_payments')
            ->join('invoices', 'students.id', '=', 'invoices.student_id')
            ->join('payments', 'invoices.id', '=', 'payments.invoice_id')
            ->where('payments.payment_date', '>', DB::raw('invoices.due_date'))
            ->groupBy('students.id')
            ->havingRaw('COUNT(payments.id) >= 2')
            ->havingRaw('AVG(DATEDIFF(payments.payment_date, invoices.due_date)) >= 7') // Average 7+ days late
            ->orderByDesc('avg_late_days')
            ->limit(50)
            ->get()
            ->toArray();
    }

    /**
     * Get students who are consistent defaulters
     */
    private function getConsistentDefaulters(): array
    {
        return Student::select('students.*')
            ->selectRaw('COUNT(invoices.id) as total_invoices')
            ->selectRaw('COUNT(CASE WHEN invoices.status = "unpaid" AND invoices.due_date < NOW() THEN 1 END) as overdue_invoices')
            ->selectRaw('SUM(CASE WHEN invoices.status = "unpaid" THEN invoices.due_amount ELSE 0 END) as total_overdue')
            ->join('invoices', 'students.id', '=', 'invoices.student_id')
            ->groupBy('students.id')
            ->havingRaw('COUNT(invoices.id) >= 3')
            ->havingRaw('(COUNT(CASE WHEN invoices.status = "unpaid" AND invoices.due_date < NOW() THEN 1 END) / COUNT(invoices.id)) >= 0.5')
            ->orderByDesc('total_overdue')
            ->limit(30)
            ->get()
            ->toArray();
    }

    /**
     * Analyze payment patterns by month/season
     */
    private function getPaymentPatterns(): array
    {
        $monthlyPatterns = Payment::selectRaw('MONTH(payment_date) as month, COUNT(*) as payment_count, SUM(amount) as total_amount')
            ->where('payment_date', '>=', now()->subYear())
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month')
            ->toArray();

        $weeklyPatterns = Payment::selectRaw('DAYOFWEEK(payment_date) as day_of_week, COUNT(*) as payment_count')
            ->where('payment_date', '>=', now()->subMonths(3))
            ->groupBy('day_of_week')
            ->orderBy('day_of_week')
            ->get()
            ->keyBy('day_of_week')
            ->toArray();

        return [
            'monthly' => $monthlyPatterns,
            'weekly' => $weeklyPatterns,
            'peak_payment_months' => $this->getPeakPaymentMonths($monthlyPatterns),
            'preferred_payment_days' => $this->getPreferredPaymentDays($weeklyPatterns)
        ];
    }

    /**
     * Get seasonal payment trends
     */
    private function getSeasonalTrends(): array
    {
        return [
            'admission_season_collection' => $this->getSeasonalCollection([6, 7, 8]), // June-August
            'mid_year_collection' => $this->getSeasonalCollection([10, 11, 12]), // Oct-Dec
            'year_end_collection' => $this->getSeasonalCollection([1, 2, 3]), // Jan-March
            'summer_collection' => $this->getSeasonalCollection([4, 5]) // April-May
        ];
    }

    private function getSeasonalCollection(array $months): array
    {
        return Payment::selectRaw('YEAR(payment_date) as year, SUM(amount) as total_amount, COUNT(*) as payment_count')
            ->whereIn(DB::raw('MONTH(payment_date)'), $months)
            ->where('payment_date', '>=', now()->subYears(3))
            ->groupBy('year')
            ->orderBy('year')
            ->get()
            ->toArray();
    }

    /**
     * Risk assessment for fee collection
     */
    private function getRiskAssessment(): array
    {
        $totalStudents = Student::count();
        $activeStudents = Student::where('status', 'active')->count();
        $totalOverdue = Invoice::where('status', 'unpaid')
            ->where('due_date', '<', now())
            ->sum('due_amount');
        
        $riskFactors = [
            'high_risk_students' => Student::whereHas('invoices', function($q) {
                $q->where('due_date', '<', now()->subDays(60))
                  ->where('status', 'unpaid');
            })->count(),
            
            'medium_risk_students' => Student::whereHas('invoices', function($q) {
                $q->where('due_date', '<', now()->subDays(30))
                  ->where('due_date', '>=', now()->subDays(60))
                  ->where('status', 'unpaid');
            })->count(),
            
            'collection_efficiency' => $this->calculateCollectionEfficiency(),
            'default_rate' => $this->calculateDefaultRate(),
            'recovery_rate' => $this->calculateRecoveryRate()
        ];

        return [
            'overall_risk_score' => $this->calculateOverallRiskScore($riskFactors),
            'risk_factors' => $riskFactors,
            'recommendations' => $this->generateRiskRecommendations($riskFactors)
        ];
    }

    private function calculateOverallRiskScore(array $factors): string
    {
        $score = 0;
        
        // High risk students contribute more to risk
        $score += ($factors['high_risk_students'] / Student::count()) * 40;
        $score += ($factors['medium_risk_students'] / Student::count()) * 20;
        $score += (100 - $factors['collection_efficiency']) * 0.3;
        $score += $factors['default_rate'] * 0.1;
        
        return match(true) {
            $score >= 30 => 'HIGH',
            $score >= 15 => 'MEDIUM',
            default => 'LOW'
        };
    }

    private function generateRiskRecommendations(array $factors): array
    {
        $recommendations = [];

        if ($factors['high_risk_students'] > 0) {
            $recommendations[] = "Immediate action needed for {$factors['high_risk_students']} high-risk students";
        }

        if ($factors['collection_efficiency'] < 80) {
            $recommendations[] = "Collection efficiency is below optimal (80%). Review payment processes.";
        }

        if ($factors['default_rate'] > 10) {
            $recommendations[] = "Default rate is concerning. Implement stricter follow-up procedures.";
        }

        return $recommendations;
    }

    private function calculateCollectionEfficiency(): float
    {
        $totalInvoiced = Invoice::sum('total_amount');
        $totalCollected = Payment::sum('amount');
        
        return $totalInvoiced > 0 ? round(($totalCollected / $totalInvoiced) * 100, 2) : 0;
    }

    private function calculateDefaultRate(): float
    {
        $totalInvoices = Invoice::count();
        $overdueInvoices = Invoice::where('due_date', '<', now())
            ->where('status', 'unpaid')
            ->count();
            
        return $totalInvoices > 0 ? round(($overdueInvoices / $totalInvoices) * 100, 2) : 0;
    }

    private function calculateRecoveryRate(): float
    {
        $overdueInvoicesValue = Invoice::where('due_date', '<', now()->subDays(30))
            ->sum('total_amount');
        $recoveredFromOverdue = Payment::whereHas('invoice', function($q) {
            $q->where('due_date', '<', now()->subDays(30));
        })->sum('amount');
        
        return $overdueInvoicesValue > 0 ? round(($recoveredFromOverdue / $overdueInvoicesValue) * 100, 2) : 0;
    }

    private function getPeakPaymentMonths(array $monthlyData): array
    {
        $sorted = collect($monthlyData)->sortByDesc('total_amount')->take(3);
        return $sorted->map(function($data, $month) {
            return [
                'month' => Carbon::create(null, $month)->format('F'),
                'amount' => $data['total_amount'],
                'count' => $data['payment_count']
            ];
        })->values()->toArray();
    }

    private function getPreferredPaymentDays(array $weeklyData): array
    {
        $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        
        return collect($weeklyData)->map(function($data, $dayNumber) use ($dayNames) {
            return [
                'day' => $dayNames[$dayNumber - 1] ?? 'Unknown',
                'count' => $data['payment_count']
            ];
        })->sortByDesc('count')->values()->toArray();
    }

    /**
     * Generate fee-wise collection comparison
     */
    public function getFeeWiseComparison(): array
    {
        return FeeCategory::select('fee_categories.*')
            ->selectRaw('
                SUM(CASE WHEN student_fees.status = "paid" THEN student_fees.amount ELSE 0 END) as collected,
                SUM(CASE WHEN student_fees.status = "unpaid" THEN student_fees.amount ELSE 0 END) as pending,
                SUM(student_fees.amount) as total,
                COUNT(CASE WHEN student_fees.status = "paid" THEN 1 END) as paid_count,
                COUNT(CASE WHEN student_fees.status = "unpaid" THEN 1 END) as unpaid_count,
                COUNT(student_fees.id) as total_count
            ')
            ->leftJoin('student_fees', 'fee_categories.id', '=', 'student_fees.fee_category_id')
            ->groupBy('fee_categories.id')
            ->get()
            ->map(function($category) {
                $category->collection_rate = $category->total > 0 ? 
                    round(($category->collected / $category->total) * 100, 2) : 0;
                $category->default_rate = $category->total_count > 0 ?
                    round(($category->unpaid_count / $category->total_count) * 100, 2) : 0;
                return $category;
            })
            ->toArray();
    }

    /**
     * Generate batch-wise payment performance
     */
    public function getBatchWisePerformance(): array
    {
        return Batch::select('batches.*')
            ->selectRaw('
                COUNT(DISTINCT students.id) as total_students,
                SUM(CASE WHEN invoices.status = "paid" THEN invoices.total_amount ELSE 0 END) as collected,
                SUM(CASE WHEN invoices.status = "unpaid" THEN invoices.due_amount ELSE 0 END) as pending,
                SUM(invoices.total_amount) as total_invoiced,
                COUNT(CASE WHEN invoices.status = "unpaid" AND invoices.due_date < NOW() THEN 1 END) as overdue_invoices
            ')
            ->leftJoin('students', 'batches.id', '=', 'students.batch_id')
            ->leftJoin('invoices', 'students.id', '=', 'invoices.student_id')
            ->with('course')
            ->groupBy('batches.id')
            ->get()
            ->map(function($batch) {
                $batch->collection_rate = $batch->total_invoiced > 0 ?
                    round(($batch->collected / $batch->total_invoiced) * 100, 2) : 0;
                $batch->per_student_collection = $batch->total_students > 0 ?
                    round($batch->collected / $batch->total_students, 2) : 0;
                return $batch;
            })
            ->sortByDesc('collection_rate')
            ->values()
            ->toArray();
    }
}