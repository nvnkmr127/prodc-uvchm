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

}
