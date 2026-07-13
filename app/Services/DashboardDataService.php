<?php

namespace App\Services;

use App\Models\Student;

class DashboardDataService
{
    protected $componentPaymentService;

    public function __construct(ComponentPaymentService $componentPaymentService)
    {
        $this->componentPaymentService = $componentPaymentService;
    }

    // ===================================
    // UPDATED METHODS (Component-Based)
    // ===================================

    // ===================================
    // EXISTING METHODS (Keep as-is or enhance)
    // ===================================

    // ===================================
    // NEW HELPER METHODS (Component-based)
    // ===================================

    private function calculateDefaulterSeverity(Student $student): string
    {
        $daysOverdue = $student->getDaysOverdue();
        $overdueAmount = $student->getTotalOverdueAmount();

        if ($daysOverdue > 60 || $overdueAmount > 25000) {
            return 'critical';
        }
        if ($daysOverdue > 30 || $overdueAmount > 15000) {
            return 'high';
        }
        if ($daysOverdue > 7 || $overdueAmount > 5000) {
            return 'medium';
        }

        return 'low';
    }

    private function identifySeasonalPatterns(array $trends): array
    {
        // Analyze seasonal patterns in the data
        $monthlyData = [];
        foreach ($trends as $trend) {
            $month = date('n', strtotime($trend['date'].'-01'));
            if (! isset($monthlyData[$month])) {
                $monthlyData[$month] = [];
            }
            $monthlyData[$month][] = $trend['amount'];
        }

        $monthlyAverages = [];
        foreach ($monthlyData as $month => $amounts) {
            $monthlyAverages[$month] = array_sum($amounts) / count($amounts);
        }

        $peakMonths = array_keys($monthlyAverages, max($monthlyAverages));
        $lowMonths = array_keys($monthlyAverages, min($monthlyAverages));

        return [
            'peak_months' => array_map(fn ($m) => date('F', mktime(0, 0, 0, $m, 1)), $peakMonths),
            'low_months' => array_map(fn ($m) => date('F', mktime(0, 0, 0, $m, 1)), $lowMonths),
            'seasonal_variance' => $this->calculateSeasonalVariance($monthlyAverages),
        ];
    }

    private function getAnnualRevenueTarget(): float
    {
        // This would typically come from settings or planning data
        return 10000000; // 1 crore sample target
    }

    private function generateRevenueForecast(array $trends): array
    {
        return $this->generateCollectionForecast($trends);
    }

    // ===================================
    // ADDITIONAL HELPER METHODS
    // ===================================

    private function calculateVolatility(array $values): float
    {
        if (count($values) < 2) {
            return 0;
        }

        $mean = array_sum($values) / count($values);
        $variance = array_sum(array_map(fn ($v) => pow($v - $mean, 2), $values)) / count($values);

        return round(sqrt($variance), 1);
    }

    private function calculateGrowthConsistency(array $growthRates): float
    {
        if (empty($growthRates)) {
            return 0;
        }

        $positiveCount = count(array_filter($growthRates, fn ($rate) => $rate > 0));

        return round(($positiveCount / count($growthRates)) * 100, 1);
    }

    private function calculateSeasonalVariance(array $monthlyAverages): float
    {
        if (count($monthlyAverages) < 2) {
            return 0;
        }

        $max = max($monthlyAverages);
        $min = min($monthlyAverages);
        $avg = array_sum($monthlyAverages) / count($monthlyAverages);

        return $avg > 0 ? round((($max - $min) / $avg) * 100, 1) : 0;
    }

    // ===================================
    // PUBLIC API METHODS
    // ===================================

    /**
     * Get widget-specific data
     */
    public function getWidgetData(string $widgetType): array
    {
        switch ($widgetType) {
            case 'fee_collection_status':
                return $this->getFeeCollectionStatusData();

            case 'monthly_revenue':
                return $this->getMonthlyRevenueData();

            case 'defaulter_students':
                return $this->getDefaulterStudentsData();

            case 'pending_payments':
                return $this->getPendingPaymentsData();

            case 'collection_trends':
                return $this->getCollectionTrendsData();

            case 'revenue_overview':
                return $this->getRevenueOverviewData();

            default:
                return [];
        }
    }

    /**
     * Get basic financial summary for non-financial roles
     */
    private function getBasicFinancialSummary(): array
    {
        $financialData = $this->componentPaymentService->getDashboardFinancialData();

        return [
            'total_revenue' => $financialData['total_revenue'],
            'monthly_collection' => $financialData['monthly_collection'],
            'collection_rate' => $financialData['collection_rate'],
            'pending_amount' => $financialData['pending_amount'],
        ];
    }

    // ===================================
    // PLACEHOLDER METHODS (To be implemented)
    // ===================================

    private function generateCollectionForecast(array $trends): array
    {
        if (count($trends) < 3) {
            return ['next_month' => 0, 'confidence' => 'low'];
        }

        // Simple linear regression forecast
        $recentTrends = array_slice($trends, -6); // Last 6 months
        $amounts = array_column($recentTrends, 'amount');
        $avgGrowth = $this->calculateAverageGrowth($amounts);
        $lastAmount = end($amounts);

        return [
            'next_month' => $lastAmount * (1 + ($avgGrowth / 100)),
            'next_quarter' => $lastAmount * 3 * (1 + ($avgGrowth / 100)),
            'confidence' => count($recentTrends) >= 6 ? 'high' : 'medium',
            'trend_direction' => $avgGrowth > 0 ? 'upward' : ($avgGrowth < 0 ? 'downward' : 'stable'),
        ];
    }

    /**
     * Get fee collection status using component system
     */
    protected function getFeeCollectionStatusData(): array
    {
        $collectionSummary = $this->componentPaymentService->getCollectionSummary();
        $financialData = $this->componentPaymentService->getDashboardFinancialData();

        return [
            'total_expected' => $collectionSummary['total_expected'],
            'total_collected' => $collectionSummary['total_collected'],
            'pending_amount' => $financialData['pending_amount'],
            'overdue_amount' => $financialData['overdue_amount'],
            'collection_rate' => $collectionSummary['collection_percentage'],
            'pending_percentage' => $collectionSummary['total_expected'] > 0 ?
                round(($financialData['pending_amount'] / $collectionSummary['total_expected']) * 100, 1) : 0,
            'overdue_percentage' => $collectionSummary['total_expected'] > 0 ?
                round(($financialData['overdue_amount'] / $collectionSummary['total_expected']) * 100, 1) : 0,
            'status' => $collectionSummary['collection_percentage'] >= 80 ? 'good' :
                       ($collectionSummary['collection_percentage'] >= 60 ? 'warning' : 'danger'),
            'concessions_given' => $financialData['total_concessions'],
        ];
    }

    /**
     * Get monthly revenue data using component system
     */
    protected function getMonthlyRevenueData(): array
    {
        $trends = $this->componentPaymentService->getMonthlyCollectionTrends(2);

        $currentMonth = end($trends)['amount'] ?? 0;
        $lastMonth = count($trends) > 1 ? $trends[count($trends) - 2]['amount'] : 0;

        $change = $lastMonth > 0 ? (($currentMonth - $lastMonth) / $lastMonth) * 100 : 0;

        return [
            'current_month' => $currentMonth,
            'last_month' => $lastMonth,
            'change_percentage' => round($change, 1),
            'trend' => $change >= 0 ? 'up' : 'down',
            'formatted_amount' => '₹'.number_format($currentMonth, 2),
            'target_achievement' => $this->getMonthlyTargetAchievement($currentMonth),
        ];
    }

    /**
     * Get defaulter students data using component system
     */
    protected function getDefaulterStudentsData(): array
    {
        $studentsWithOverdue = $this->componentPaymentService->getStudentsWithOverdueFees();
        $defaulters = $studentsWithOverdue->with(['batch.course', 'studentFees' => function ($q) {
            $q->whereRaw('amount - concession_amount - paid_amount > 0')
                ->with('feeCategory');
        }])->limit(20)->get();

        return [
            'defaulters' => $defaulters->map(function ($student) {
                $overdueAmount = $student->getTotalOverdueAmount();
                $oldestDue = $student->getOverdueFees()->min('due_date');

                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'email' => $student->email,
                    'course' => $student->batch->course->name ?? 'N/A',
                    'batch' => $student->batch->name ?? 'N/A',
                    'overdue_amount' => $overdueAmount,
                    'formatted_amount' => '₹'.number_format($overdueAmount, 2),
                    'days_overdue' => $student->getDaysOverdue(),
                    'oldest_due_date' => $oldestDue ? Carbon::parse($oldestDue)->format('M j, Y') : 'N/A',
                    'overdue_fees_count' => $student->getOverdueFeesCount(),
                    'payment_history' => $student->getRecentComponentPayments(3),
                    'severity' => $this->calculateDefaulterSeverity($student),
                ];
            })->toArray(),
            'total_defaulters' => $studentsWithOverdue->count(),
            'total_overdue_amount' => $defaulters->sum(fn ($student) => $student->getTotalOverdueAmount()),
            'severity_breakdown' => $this->getDefaulterSeverityBreakdown($defaulters),
        ];
    }

    /**
     * Get pending payments data using component system
     */
    protected function getPendingPaymentsData(): array
    {
        $financialData = $this->componentPaymentService->getDashboardFinancialData();
        $outstanding = $this->componentPaymentService->getOutstandingFeesSummary();

        return [
            'total_pending' => $financialData['pending_amount'],
            'overdue_amount' => $financialData['overdue_amount'],
            'current_due' => $financialData['pending_amount'] - $financialData['overdue_amount'],
            'overdue_percentage' => $financialData['pending_amount'] > 0 ?
                round(($financialData['overdue_amount'] / $financialData['pending_amount']) * 100, 1) : 0,
            'formatted_pending' => '₹'.number_format($financialData['pending_amount'], 2),
            'formatted_overdue' => '₹'.number_format($financialData['overdue_amount'], 2),
            'aging_breakdown' => $outstanding['by_aging'],
            'category_breakdown' => $outstanding['by_category'],
        ];
    }

    /**
     * Get collection trends using component system
     */
    protected function getCollectionTrendsData(): array
    {
        $trends = $this->componentPaymentService->getMonthlyCollectionTrends(12);
        $statistics = $this->componentPaymentService->getPaymentStatistics([
            'start_date' => now()->subYear(),
            'end_date' => now(),
        ]);

        return [
            'monthly_trends' => $trends,
            'labels' => array_column($trends, 'month'),
            'data' => array_column($trends, 'amount'),
            'growth_analysis' => $this->calculateGrowthAnalysis($trends),
            'seasonal_patterns' => $this->identifySeasonalPatterns($trends),
            'payment_frequency' => $statistics['by_month'],
            'forecasting' => $this->generateCollectionForecast($trends),
        ];
    }

    /**
     * Get revenue overview using component system
     */
    protected function getRevenueOverviewData(): array
    {
        $financialData = $this->componentPaymentService->getDashboardFinancialData();
        $collectionSummary = $this->componentPaymentService->getCollectionSummary();
        $trends = $this->componentPaymentService->getMonthlyCollectionTrends(12);

        return [
            'total_revenue' => $financialData['total_revenue'],
            'monthly_revenue' => $financialData['monthly_collection'],
            'annual_target' => $this->getAnnualRevenueTarget(),
            'target_achievement' => $this->calculateTargetAchievement($financialData),
            'growth_rate' => $this->calculateRevenueGrowthRate($trends),
            'revenue_sources' => $this->getRevenueSourceBreakdown(),
            'collection_efficiency' => $collectionSummary['collection_percentage'],
            'revenue_forecast' => $this->generateRevenueForecast($trends),
        ];
    }

    private function calculateAverageGrowth(array $amounts): float
    {
        if (count($amounts) < 2) {
            return 0;
        }

        $growthRates = [];
        for ($i = 1; $i < count($amounts); $i++) {
            $current = $amounts[$i];
            $previous = $amounts[$i - 1];
            $growthRates[] = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;
        }

        return count($growthRates) > 0 ? array_sum($growthRates) / count($growthRates) : 0;
    }

    private function getMonthlyTargetAchievement(float $currentMonth): array
    {
        $monthlyTarget = 833333; // Sample target (10L/12)
        $achievement = $monthlyTarget > 0 ? ($currentMonth / $monthlyTarget) * 100 : 0;

        return [
            'target' => $monthlyTarget,
            'achievement_percentage' => round($achievement, 1),
            'status' => $achievement >= 100 ? 'exceeded' :
                       ($achievement >= 80 ? 'on_track' : 'behind'),
        ];
    }

    private function getDefaulterSeverityBreakdown($defaulters): array
    {
        $breakdown = ['critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0];

        foreach ($defaulters as $student) {
            $severity = $this->calculateDefaulterSeverity($student);
            $breakdown[$severity]++;
        }

        return $breakdown;
    }

    private function calculateGrowthAnalysis(array $trends): array
    {
        if (count($trends) < 2) {
            return ['growth_rate' => 0, 'trend' => 'stable'];
        }

        $growthRates = [];
        for ($i = 1; $i < count($trends); $i++) {
            $current = $trends[$i]['amount'];
            $previous = $trends[$i - 1]['amount'];
            $growthRates[] = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;
        }

        $avgGrowth = array_sum($growthRates) / count($growthRates);

        return [
            'average_growth_rate' => round($avgGrowth, 1),
            'trend' => $avgGrowth > 2 ? 'growing' : ($avgGrowth < -2 ? 'declining' : 'stable'),
            'volatility' => $this->calculateVolatility($growthRates),
            'consistency' => $this->calculateGrowthConsistency($growthRates),
        ];
    }

    private function calculateTargetAchievement(array $financialData): array
    {
        $annualTarget = $this->getAnnualRevenueTarget();
        $currentRevenue = $financialData['total_revenue'];

        return [
            'annual_target' => $annualTarget,
            'current_revenue' => $currentRevenue,
            'achievement_percentage' => round(($currentRevenue / $annualTarget) * 100, 1),
            'remaining_target' => $annualTarget - $currentRevenue,
        ];
    }

    private function calculateRevenueGrowthRate(array $trends): float
    {
        if (count($trends) < 2) {
            return 0;
        }

        $latest = end($trends)['amount'];
        $previous = prev($trends)['amount'];

        return $previous > 0 ? round((($latest - $previous) / $previous) * 100, 1) : 0;
    }

    private function getRevenueSourceBreakdown(): array
    {
        $outstanding = $this->componentPaymentService->getOutstandingFeesSummary();

        return $outstanding['by_category'];
    }
}
