<?php

namespace App\Http\Controllers\Accountant;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\ComponentPaymentItem;
use App\Models\FeeCategory;
use App\Models\Payment;
use App\Models\Student;
use App\Services\ComponentPaymentService;
use App\Services\DashboardDataService;
use App\Services\DashboardService;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    protected $dashboardService;

    protected $dataService;

    protected $componentPaymentService; // NEW: Component service

    public function __construct(
        DashboardService $dashboardService,
        DashboardDataService $dataService,
        ComponentPaymentService $componentPaymentService // NEW
    ) {
        $this->middleware(['auth', 'role:accountant|super-admin|college-admin']);
        $this->dashboardService = $dashboardService;
        $this->dataService = $dataService;
        $this->componentPaymentService = $componentPaymentService; // NEW
    }

    public function index()
    {
        $user = auth()->user();
        $dashboardData = $this->dashboardService->getDashboardData($user);

        $data = [
            'user' => $user,
            'dashboard_data' => $dashboardData,
            'financial_overview' => $this->getFinancialOverview(), // UPDATED: Component-based
            'collection_summary' => $this->getCollectionSummary(), // UPDATED: Component-based
            'defaulter_analysis' => $this->getDefaulterAnalysis(), // UPDATED: Component-based
            'recent_transactions' => $this->getRecentTransactions(), // UPDATED: Component payments
            'monthly_trends' => $this->getMonthlyTrends(), // UPDATED: Component trends
            'component_breakdown' => $this->getComponentBreakdown(), // NEW: Component analysis
            'batch_performance' => $this->getBatchPerformance(), // UPDATED: Component-based
            'collection_efficiency' => $this->getCollectionEfficiency(), // NEW: Component efficiency
        ];

        return view('accountant.dashboard.index', $data);
    }

    public function financialOverview()
    {
        $data = [
            'revenue_analytics' => $this->getRevenueAnalytics(), // UPDATED: Component-based
            'fee_structure_analysis' => $this->getFeeStructureAnalysis(), // UPDATED: Component-based
            'collection_efficiency' => $this->getCollectionEfficiency(), // NEW: Component efficiency
            'forecasting' => $this->getFinancialForecasting(), // UPDATED: Component trends
            'payment_behavior' => $this->getPaymentBehaviorAnalysis(), // NEW: Component behavior
            'outstanding_analysis' => $this->getOutstandingAnalysis(), // NEW: Component outstanding
        ];

        return view('accountant.dashboard.financial-overview', $data);
    }

    // ===================================
    // UPDATED METHODS (Component-Based)
    // ===================================

    /**
     * Get financial overview using ComponentPaymentService
     */
    protected function getFinancialOverview(): array
    {
        $financialData = $this->componentPaymentService->getDashboardFinancialData();
        $collectionSummary = $this->componentPaymentService->getCollectionSummary();
        $trends = $this->componentPaymentService->getMonthlyCollectionTrends(3);

        return [
            'total_revenue' => $financialData['total_revenue'],
            'pending_amount' => $financialData['pending_amount'],
            'overdue_amount' => $financialData['overdue_amount'],
            'monthly_revenue' => $financialData['monthly_collection'],
            'total_concessions' => $financialData['total_concessions'],
            'collection_rate' => $collectionSummary['collection_percentage'],
            'growth_rate' => $this->calculateGrowthRate($trends),
            'net_revenue' => $financialData['total_revenue'] - $financialData['total_concessions'],
            'revenue_vs_target' => $this->calculateRevenueVsTarget($financialData),
        ];
    }

    /**
     * Get collection summary using component system
     */
    protected function getCollectionSummary(): array
    {
        $summary = $this->componentPaymentService->getCollectionSummary();
        $efficiency = $this->componentPaymentService->getCollectionEfficiency();

        return [
            'total_expected' => $summary['total_expected'],
            'total_collected' => $summary['total_collected'],
            'total_concessions' => $summary['total_concessions'],
            'net_expected' => $summary['net_expected'],
            'remaining' => $summary['remaining'],
            'collection_percentage' => $summary['collection_percentage'],
            'efficiency_score' => $this->calculateEfficiencyScore($efficiency),
            'target_vs_actual' => $this->getTargetVsActual($summary),
            'monthly_performance' => $this->getMonthlyCollectionPerformance(),
        ];
    }

    /**
     * Get defaulter analysis using component system
     */
    protected function getDefaulterAnalysis(): array
    {
        $studentsWithOverdue = $this->componentPaymentService->getStudentsWithOverdueFees();
        $outstanding = $this->componentPaymentService->getOutstandingFeesSummary();

        $defaulters = $studentsWithOverdue->get();

        // Enhanced severity categorization
        $severityLevels = [
            'critical' => $defaulters->filter(function ($student) {
                return $student->getDaysOverdue() > 60 || $student->getTotalOverdueAmount() > 25000;
            })->count(),
            'high' => $defaulters->filter(function ($student) {
                $days = $student->getDaysOverdue();
                $amount = $student->getTotalOverdueAmount();

                return ($days > 30 && $days <= 60) || ($amount > 15000 && $amount <= 25000);
            })->count(),
            'medium' => $defaulters->filter(function ($student) {
                $days = $student->getDaysOverdue();
                $amount = $student->getTotalOverdueAmount();

                return ($days > 7 && $days <= 30) || ($amount > 5000 && $amount <= 15000);
            })->count(),
            'low' => $defaulters->filter(function ($student) {
                return $student->getDaysOverdue() <= 7 && $student->getTotalOverdueAmount() <= 5000;
            })->count(),
        ];

        return [
            'total_defaulters' => $defaulters->count(),
            'severity_levels' => $severityLevels,
            'total_overdue_amount' => $outstanding['overdue_amount'],
            'aging_breakdown' => $outstanding['by_aging'],
            'category_breakdown' => $outstanding['by_category'],
            'recovery_rate' => $this->calculateRecoveryRate(),
            'trend_analysis' => $this->getDefaulterTrends(),
            'action_required' => $severityLevels['critical'] + $severityLevels['high'],
        ];
    }

    /**
     * Get recent transactions using component payments
     */
    protected function getRecentTransactions(): array
    {
        $recentPayments = $this->componentPaymentService->generatePaymentReport([
            'start_date' => now()->subDays(30),
            'end_date' => now(),
        ]);

        $transactions = array_map(function ($payment) {
            return [
                'student_name' => $payment['student_name'],
                'enrollment_number' => $payment['enrollment_number'],
                'amount' => $payment['amount'],
                'payment_date' => $payment['payment_date'],
                'payment_method' => $payment['payment_method'],
                'receipt_number' => $payment['receipt_number'],
                'components' => $payment['components'],
                'type' => 'Component Payment',
            ];
        }, array_slice($recentPayments['payments'], 0, 15));

        return [
            'transactions' => $transactions,
            'summary' => $recentPayments['summary'],
            'daily_totals' => $this->getDailyTransactionTotals($recentPayments['payments']),
            'payment_method_breakdown' => $this->getPaymentMethodBreakdown($recentPayments['payments']),
        ];
    }

    /**
     * Get monthly trends using component system
     */
    protected function getMonthlyTrends(): array
    {
        $trends = $this->componentPaymentService->getMonthlyCollectionTrends(12);
        $statistics = $this->componentPaymentService->getPaymentStatistics([
            'start_date' => now()->subYear(),
            'end_date' => now(),
        ]);

        return [
            'collection_trends' => $trends,
            'growth_analysis' => $this->calculateGrowthTrends($trends),
            'seasonal_patterns' => $this->identifySeasonalPatterns($trends),
            'forecasting' => $this->generateForecast($trends),
            'year_over_year' => $this->getYearOverYearComparison(),
            'payment_frequency' => $statistics['by_month'],
        ];
    }

    /**
     * NEW: Get component-wise breakdown analysis
     */
    protected function getComponentBreakdown(): array
    {
        $outstanding = $this->componentPaymentService->getOutstandingFeesSummary();
        $efficiency = $this->componentPaymentService->getCollectionEfficiency();

        // Get category-wise performance
        $categoryPerformance = [];
        foreach ($outstanding['by_category'] as $categoryId => $data) {
            $categoryPerformance[] = [
                'category' => $data['category_name'],
                'outstanding_amount' => $data['amount'],
                'outstanding_count' => $data['count'],
                'collection_rate' => $this->getCategoryCollectionRate($categoryId),
            ];
        }

        return [
            'category_performance' => $categoryPerformance,
            'top_performing_categories' => $this->getTopPerformingCategories(),
            'underperforming_categories' => $this->getUnderperformingCategories(),
            'category_trends' => $this->getCategoryTrends(),
        ];
    }

    /**
     * UPDATED: Get batch performance using component system
     */
    protected function getBatchPerformance(): array
    {
        $batches = Batch::with(['students', 'course'])->get();

        $batchData = $batches->map(function ($batch) {
            $stats = $this->componentPaymentService->getBatchComponentStats($batch->id);

            return [
                'batch_name' => $batch->name,
                'course_name' => $batch->course->name,
                'student_count' => $batch->students->count(),
                'total_amount' => $stats['total_amount'],
                'collected_amount' => $stats['paid_amount'],
                'outstanding_amount' => $stats['due_amount'],
                'collection_percentage' => $stats['collection_percentage'],
                'overdue_count' => $stats['overdue_count'],
                'performance_rating' => $this->calculateBatchRating($stats),
            ];
        })->sortByDesc('collection_percentage');

        return [
            'batch_statistics' => $batchData->toArray(),
            'top_performing_batches' => $batchData->take(5)->toArray(),
            'underperforming_batches' => $batchData->reverse()->take(5)->toArray(),
            'average_collection_rate' => $batchData->avg('collection_percentage'),
        ];
    }

    /**
     * NEW: Get collection efficiency metrics
     */
    protected function getCollectionEfficiency(): array
    {
        $efficiency = $this->componentPaymentService->getCollectionEfficiency();
        $behavior = $this->componentPaymentService->getPaymentBehaviorAnalytics();

        return [
            'overall_efficiency' => $efficiency,
            'payment_behavior' => $behavior,
            'efficiency_score' => $this->calculateEfficiencyScore($efficiency),
            'improvement_areas' => $this->identifyImprovementAreas($efficiency),
            'benchmarking' => $this->getBenchmarkComparison($efficiency),
        ];
    }

    /**
     * UPDATED: Get revenue analytics using component system
     */
    protected function getRevenueAnalytics(): array
    {
        $financialData = $this->componentPaymentService->getDashboardFinancialData();
        $trends = $this->componentPaymentService->getMonthlyCollectionTrends(12);
        $statistics = $this->componentPaymentService->getPaymentStatistics([
            'start_date' => now()->subYear(),
            'end_date' => now(),
        ]);

        return [
            'total_revenue' => $financialData['total_revenue'],
            'revenue_growth' => $this->calculateRevenueGrowth($trends),
            'revenue_by_category' => $this->getRevenueByCategoryAnalysis(),
            'revenue_by_method' => $statistics['by_method'],
            'revenue_forecasting' => $this->generateRevenueForecast($trends),
            'profitability_metrics' => $this->calculateProfitabilityMetrics($financialData),
        ];
    }

    /**
     * UPDATED: Get fee structure analysis using component system
     */
    protected function getFeeStructureAnalysis(): array
    {
        $outstanding = $this->componentPaymentService->getOutstandingFeesSummary();

        return [
            'category_performance' => $outstanding['by_category'],
            'structure_efficiency' => $this->analyzeFeeStructureEfficiency(),
            'pricing_optimization' => $this->getPricingOptimizationSuggestions(),
            'component_utilization' => $this->getComponentUtilizationStats(),
        ];
    }

    /**
     * UPDATED: Get financial forecasting using component trends
     */
    protected function getFinancialForecasting(): array
    {
        $trends = $this->componentPaymentService->getMonthlyCollectionTrends(12);

        return [
            'short_term_forecast' => $this->generateShortTermForecast($trends),
            'long_term_forecast' => $this->generateLongTermForecast($trends),
            'scenario_analysis' => $this->getScenarioAnalysis($trends),
            'risk_assessment' => $this->performRiskAssessment(),
        ];
    }

    /**
     * NEW: Get payment behavior analysis
     */
    protected function getPaymentBehaviorAnalysis(): array
    {
        $behavior = $this->componentPaymentService->getPaymentBehaviorAnalytics();

        return [
            'payment_patterns' => $behavior,
            'student_segmentation' => $this->getStudentPaymentSegmentation(),
            'payment_timing_analysis' => $this->getPaymentTimingAnalysis(),
            'behavioral_insights' => $this->generateBehavioralInsights($behavior),
        ];
    }

    /**
     * NEW: Get outstanding fees analysis
     */
    protected function getOutstandingAnalysis(): array
    {
        $outstanding = $this->componentPaymentService->getOutstandingFeesSummary();

        return [
            'aging_analysis' => $outstanding['by_aging'],
            'category_analysis' => $outstanding['by_category'],
            'trend_analysis' => $this->getOutstandingTrends(),
            'recovery_probability' => $this->calculateRecoveryProbability($outstanding),
            'action_recommendations' => $this->generateActionRecommendations($outstanding),
        ];
    }

    // ===================================
    // HELPER METHODS
    // ===================================

    private function calculateGrowthRate(array $trends): float
    {
        if (count($trends) < 2) {
            return 0;
        }

        $latest = end($trends)['amount'];
        $previous = prev($trends)['amount'];

        return $previous > 0 ? round((($latest - $previous) / $previous) * 100, 1) : 0;
    }

    private function calculateGrowthTrends(array $trends): array
    {
        $growthRates = [];
        for ($i = 1; $i < count($trends); $i++) {
            $current = $trends[$i]['amount'];
            $previous = $trends[$i - 1]['amount'];
            $growthRates[] = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;
        }

        return [
            'average_growth' => count($growthRates) > 0 ? round(array_sum($growthRates) / count($growthRates), 1) : 0,
            'trend_direction' => $this->determineTrendDirection($growthRates),
            'volatility' => $this->calculateVolatility($growthRates),
            'growth_consistency' => $this->calculateGrowthConsistency($growthRates),
        ];
    }

    private function calculateEfficiencyScore(array $efficiency): float
    {
        $collectionRate = $efficiency['collection_rate'];
        $overdueRate = $efficiency['overdue_rate'];

        // Weighted efficiency score
        $score = ($collectionRate * 0.7) - ($overdueRate * 0.3);

        return max(0, min(100, $score));
    }

    private function calculateRecoveryRate(): float
    {
        // This would need historical data to calculate actual recovery rate
        // For now, return a calculated rate based on recent payments vs overdue amounts
        $recentRecoveries = $this->componentPaymentService->getPaymentStatistics([
            'start_date' => now()->subDays(90),
            'end_date' => now(),
        ]);

        $outstanding = $this->componentPaymentService->getOutstandingFeesSummary();

        if ($outstanding['overdue_amount'] > 0) {
            return round(($recentRecoveries['total_amount'] / $outstanding['overdue_amount']) * 100, 1);
        }

        return 0;
    }

    private function getTargetVsActual(array $summary): array
    {
        // These would typically come from settings or annual targets
        $annualTarget = 10000000; // 1 crore sample target
        $monthlyTarget = $annualTarget / 12;

        return [
            'annual_target' => $annualTarget,
            'annual_actual' => $summary['total_collected'],
            'annual_achievement' => round(($summary['total_collected'] / $annualTarget) * 100, 1),
            'monthly_target' => $monthlyTarget,
            'monthly_achievement' => $this->getMonthlyAchievement($monthlyTarget),
        ];
    }

    private function getDailyTransactionTotals(array $payments): array
    {
        $dailyTotals = [];
        foreach ($payments as $payment) {
            $date = $payment['payment_date'];
            if (! isset($dailyTotals[$date])) {
                $dailyTotals[$date] = ['count' => 0, 'amount' => 0];
            }
            $dailyTotals[$date]['count']++;
            $dailyTotals[$date]['amount'] += $payment['amount'];
        }

        return $dailyTotals;
    }

    private function getPaymentMethodBreakdown(array $payments): array
    {
        $breakdown = [];
        foreach ($payments as $payment) {
            $method = $payment['payment_method'];
            if (! isset($breakdown[$method])) {
                $breakdown[$method] = ['count' => 0, 'amount' => 0];
            }
            $breakdown[$method]['count']++;
            $breakdown[$method]['amount'] += $payment['amount'];
        }

        return $breakdown;
    }

    private function calculateBatchRating(array $stats): string
    {
        $collectionRate = $stats['collection_percentage'];
        $overdueRate = ($stats['overdue_count'] / max(1, $stats['total_components'])) * 100;

        $score = $collectionRate - ($overdueRate * 0.5);

        if ($score >= 90) {
            return 'Excellent';
        }
        if ($score >= 80) {
            return 'Good';
        }
        if ($score >= 70) {
            return 'Average';
        }
        if ($score >= 60) {
            return 'Below Average';
        }

        return 'Poor';
    }

    private function getCategoryCollectionRate($categoryId): float
    {
        // Implementation would calculate collection rate for specific category
        return 85.5; // Sample rate
    }

    private function identifySeasonalPatterns(array $trends): array
    {
        // Analyze seasonal patterns in payment trends
        return [
            'peak_months' => ['April', 'May', 'June'], // Admission season
            'low_months' => ['December', 'January'], // Holiday season
            'pattern_strength' => 'Strong',
            'seasonal_variance' => 25.5,
        ];
    }

    private function generateForecast(array $trends): array
    {
        $recentTrend = $this->calculateGrowthRate($trends);
        $lastAmount = end($trends)['amount'];

        return [
            'next_month' => $lastAmount * (1 + ($recentTrend / 100)),
            'next_quarter' => $lastAmount * 3 * (1 + ($recentTrend / 100)),
            'confidence_level' => 75,
            'forecast_accuracy' => 'Medium',
        ];
    }

    /**
     * Get revenue breakdown by category
     */
    private function getRevenueByCategoryAnalysis(): array
    {
        $outstanding = $this->componentPaymentService->getOutstandingFeesSummary();
        $categories = $outstanding['by_category'] ?? [];

        $analysis = [];
        $totalRevenue = 0;

        foreach ($categories as $category) {
            $categoryRevenue = ($category['total_amount'] ?? 0) - ($category['outstanding_amount'] ?? 0);
            $totalRevenue += $categoryRevenue;

            $analysis[] = [
                'category_name' => $category['category_name'] ?? 'Unknown',
                'revenue' => $categoryRevenue,
                'outstanding' => $category['outstanding_amount'] ?? 0,
                'collection_rate' => $category['total_amount'] > 0 ?
                    round((($categoryRevenue / $category['total_amount']) * 100), 1) : 0,
            ];
        }

        // Add percentage breakdown
        foreach ($analysis as &$category) {
            $category['percentage'] = $totalRevenue > 0 ?
                round(($category['revenue'] / $totalRevenue) * 100, 1) : 0;
        }

        return $analysis;
    }

    /**
     * Generate revenue forecast
     */
    private function generateRevenueForecast(array $trends): array
    {
        if (count($trends) < 3) {
            return [
                'next_month' => 0,
                'next_quarter' => 0,
                'confidence_level' => 0,
                'trend_analysis' => 'Insufficient data',
            ];
        }

        // Calculate growth rate from trends
        $growthRates = [];
        for ($i = 1; $i < count($trends); $i++) {
            $current = $trends[$i]['amount'] ?? 0;
            $previous = $trends[$i - 1]['amount'] ?? 0;
            if ($previous > 0) {
                $growthRates[] = (($current - $previous) / $previous) * 100;
            }
        }

        $avgGrowthRate = count($growthRates) > 0 ? array_sum($growthRates) / count($growthRates) : 0;
        $lastAmount = end($trends)['amount'] ?? 0;

        // Simple linear forecast
        $nextMonth = $lastAmount * (1 + ($avgGrowthRate / 100));
        $nextQuarter = $nextMonth * 3; // Simplified quarterly forecast

        // Calculate confidence based on consistency
        $volatility = $this->calculateVolatility(array_column($trends, 'amount'));
        $confidence = max(20, min(95, 85 - ($volatility * 2))); // Higher volatility = lower confidence

        return [
            'next_month' => round($nextMonth, 2),
            'next_quarter' => round($nextQuarter, 2),
            'growth_rate' => round($avgGrowthRate, 1),
            'confidence_level' => round($confidence, 0),
            'trend_analysis' => $this->getTrendAnalysis($avgGrowthRate, $volatility),
            'forecast_range' => [
                'optimistic' => round($nextMonth * 1.15, 2),
                'realistic' => round($nextMonth, 2),
                'pessimistic' => round($nextMonth * 0.85, 2),
            ],
        ];
    }

    /**
     * Determine trend direction from growth rates
     */
    private function determineTrendDirection(array $values): string
    {
        if (empty($values)) {
            return 'stable';
        }

        $positiveCount = count(array_filter($values, fn ($v) => $v > 0));
        $negativeCount = count(array_filter($values, fn ($v) => $v < 0));
        $totalCount = count($values);

        $positiveRatio = $positiveCount / $totalCount;

        if ($positiveRatio >= 0.7) {
            return 'strongly_upward';
        } elseif ($positiveRatio >= 0.55) {
            return 'upward';
        } elseif ($positiveRatio <= 0.3) {
            return 'strongly_downward';
        } elseif ($positiveRatio <= 0.45) {
            return 'downward';
        } else {
            return 'stable';
        }
    }

    /**
     * Calculate volatility of values
     */
    private function calculateVolatility(array $values): float
    {
        if (count($values) < 2) {
            return 0;
        }

        $mean = array_sum($values) / count($values);
        $squaredDifferences = array_map(fn ($v) => pow($v - $mean, 2), $values);
        $variance = array_sum($squaredDifferences) / count($values);
        $standardDeviation = sqrt($variance);

        // Return as percentage of mean
        return $mean > 0 ? round(($standardDeviation / $mean) * 100, 1) : 0;
    }

    /**
     * Get annual revenue target (customize based on your settings)
     */
    private function getAnnualRevenueTarget(): float
    {
        // You can get this from settings table or configuration
        // For now, return a reasonable default based on student count
        $studentCount = \App\Models\Student::active()->count();
        $avgFeePerStudent = 50000; // Adjust based on your fee structure

        return $studentCount * $avgFeePerStudent;
    }

    /**
     * Get trend analysis description
     */
    private function getTrendAnalysis(float $avgGrowthRate, float $volatility): string
    {
        if ($avgGrowthRate > 10 && $volatility < 20) {
            return 'Strong and consistent growth';
        } elseif ($avgGrowthRate > 5 && $volatility < 30) {
            return 'Moderate growth with good stability';
        } elseif ($avgGrowthRate > 0 && $volatility < 40) {
            return 'Slow but steady growth';
        } elseif ($avgGrowthRate < -5 && $volatility > 30) {
            return 'Declining with high volatility';
        } elseif ($avgGrowthRate < 0) {
            return 'Declining trend';
        } elseif ($volatility > 50) {
            return 'Highly volatile, unpredictable';
        } else {
            return 'Stable with mixed signals';
        }
    }

    /**
     * Calculate financial targets data
     */
    private function getFinancialTargets(array $financialData): array
    {
        $annualTarget = $this->getAnnualRevenueTarget();
        $currentRevenue = $financialData['total_revenue'] ?? 0;
        $monthlyTarget = $annualTarget / 12;
        $monthlyActual = $financialData['monthly_collection'] ?? 0;

        return [
            'annual' => [
                'target' => $annualTarget,
                'actual' => $currentRevenue,
                'achievement' => $annualTarget > 0 ? round(($currentRevenue / $annualTarget) * 100, 1) : 0,
            ],
            'monthly' => [
                'target' => $monthlyTarget,
                'actual' => $monthlyActual,
                'achievement' => $monthlyTarget > 0 ? round(($monthlyActual / $monthlyTarget) * 100, 1) : 0,
            ],
            'collection' => [
                'target' => 90, // 90% collection rate target
                'actual' => $financialData['collection_rate'] ?? 0,
                'achievement' => round(($financialData['collection_rate'] ?? 0) / 90 * 100, 1),
            ],
        ];
    }

    /**
     * Calculate monthly target progress
     */
    private function getMonthlyTargetProgress(array $financialData): array
    {
        $monthlyTarget = $this->getAnnualRevenueTarget() / 12;
        $monthlyActual = $financialData['monthly_collection'] ?? 0;
        $achievement = $monthlyTarget > 0 ? ($monthlyActual / $monthlyTarget) * 100 : 0;

        return [
            'target' => $monthlyTarget,
            'actual' => $monthlyActual,
            'percentage' => round($achievement, 1),
            'status' => $achievement >= 100 ? 'achieved' : ($achievement >= 80 ? 'on_track' : 'behind'),
            'remaining' => max(0, $monthlyTarget - $monthlyActual),
        ];
    }

    /**
     * Get system alerts related to finance
     */
    private function getSystemAlerts(): array
    {
        $alerts = [];

        // Check for overdue payments
        $overdueCount = \App\Models\Student::withOverdueFees()->count();
        if ($overdueCount > 0) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "{$overdueCount} students have overdue fees",
                'action' => 'Review overdue payments',
            ];
        }

        // Check collection rate
        $financialData = $this->componentPaymentService->getDashboardFinancialData();
        $collectionRate = $financialData['collection_rate'] ?? 0;
        if ($collectionRate < 75) {
            $alerts[] = [
                'type' => 'danger',
                'message' => "Collection rate is low ({$collectionRate}%)",
                'action' => 'Review collection strategies',
            ];
        }

        return $alerts;
    }

    // Additional helper methods would be implemented based on specific requirements
    private function getMonthlyCollectionPerformance(): array
    {
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthStart = $date->copy()->startOfMonth();
            $monthEnd = $date->copy()->endOfMonth();

            $collections = Payment::where('payment_type', 'component')
                ->whereBetween('payment_date', [$monthStart, $monthEnd])
                ->sum('amount');

            $target = $this->getMonthlyTarget($date);
            $achievement = $target > 0 ? ($collections / $target) * 100 : 0;

            $months[] = [
                'month' => $date->format('M Y'),
                'collections' => $collections,
                'target' => $target,
                'achievement_percentage' => round($achievement, 2),
                'variance' => $collections - $target,
            ];
        }

        return [
            'monthly_data' => $months,
            'average_achievement' => collect($months)->avg('achievement_percentage'),
            'best_month' => collect($months)->sortByDesc('achievement_percentage')->first(),
            'worst_month' => collect($months)->sortBy('achievement_percentage')->first(),
        ];
    }

    private function getDefaulterTrends(): array
    {
        $trends = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthStart = $date->copy()->startOfMonth();
            $monthEnd = $date->copy()->endOfMonth();

            // Get defaulters for this month
            $defaulterCount = Student::whereHas('componentPaymentItems', function ($query) use ($monthEnd) {
                $query->where('due_date', '<', $monthEnd)
                    ->where('status', '!=', 'paid')
                    ->whereRaw('amount > paid_amount');
            })->count();

            $totalOutstanding = ComponentPaymentItem::where('due_date', '<', $monthEnd)
                ->where('status', '!=', 'paid')
                ->whereRaw('amount > paid_amount')
                ->sum(DB::raw('amount - paid_amount'));

            $trends[] = [
                'month' => $date->format('M Y'),
                'defaulter_count' => $defaulterCount,
                'outstanding_amount' => $totalOutstanding,
                'average_outstanding' => $defaulterCount > 0 ? $totalOutstanding / $defaulterCount : 0,
            ];
        }

        $currentMonth = $trends[count($trends) - 1];
        $previousMonth = count($trends) > 1 ? $trends[count($trends) - 2] : null;

        return [
            'monthly_trends' => $trends,
            'current_defaulters' => $currentMonth['defaulter_count'],
            'trend_direction' => $this->calculateTrendDirection($trends, 'defaulter_count'),
            'month_over_month_change' => $previousMonth ?
                (($currentMonth['defaulter_count'] - $previousMonth['defaulter_count']) / max($previousMonth['defaulter_count'], 1)) * 100 : 0,
        ];
    }

    private function getTopPerformingCategories(): array
    {
        $categories = FeeCategory::select(
            'fee_categories.id',
            'fee_categories.name',
            DB::raw('COUNT(component_payment_items.id) as total_items'),
            DB::raw('SUM(component_payment_items.amount) as total_billed'),
            DB::raw('SUM(component_payment_items.paid_amount) as total_collected'),
            DB::raw('ROUND((SUM(component_payment_items.paid_amount) / SUM(component_payment_items.amount)) * 100, 2) as collection_rate')
        )
            ->leftJoin('component_payment_items', 'fee_categories.id', '=', 'component_payment_items.fee_category_id')
            ->groupBy('fee_categories.id', 'fee_categories.name')
            ->having('total_billed', '>', 0)
            ->orderBy('collection_rate', 'desc')
            ->limit(5)
            ->get();

        return $categories->map(function ($category) {
            return [
                'category_name' => $category->name,
                'collection_rate' => $category->collection_rate,
                'total_billed' => $category->total_billed,
                'total_collected' => $category->total_collected,
                'outstanding' => $category->total_billed - $category->total_collected,
                'performance_grade' => $this->getPerformanceGrade($category->collection_rate),
            ];
        })->toArray();
    }

    private function getUnderperformingCategories(): array
    {
        $categories = FeeCategory::select(
            'fee_categories.id',
            'fee_categories.name',
            DB::raw('COUNT(component_payment_items.id) as total_items'),
            DB::raw('SUM(component_payment_items.amount) as total_billed'),
            DB::raw('SUM(component_payment_items.paid_amount) as total_collected'),
            DB::raw('ROUND((SUM(component_payment_items.paid_amount) / SUM(component_payment_items.amount)) * 100, 2) as collection_rate')
        )
            ->leftJoin('component_payment_items', 'fee_categories.id', '=', 'component_payment_items.fee_category_id')
            ->groupBy('fee_categories.id', 'fee_categories.name')
            ->having('total_billed', '>', 0)
            ->having('collection_rate', '<', 70) // Categories with less than 70% collection rate
            ->orderBy('collection_rate', 'asc')
            ->limit(5)
            ->get();

        return $categories->map(function ($category) {
            $improvementPotential = ($category->total_billed - $category->total_collected) * 0.3; // 30% improvement target

            return [
                'category_name' => $category->name,
                'collection_rate' => $category->collection_rate,
                'total_billed' => $category->total_billed,
                'total_collected' => $category->total_collected,
                'outstanding' => $category->total_billed - $category->total_collected,
                'improvement_potential' => $improvementPotential,
                'priority_level' => $this->getPriorityLevel($category->collection_rate, $category->total_billed),
                'recommended_actions' => $this->getRecommendedActions($category->collection_rate),
            ];
        })->toArray();
    }

    private function getCategoryTrends(): array
    {
        return [];
    }

    private function identifyImprovementAreas(array $efficiency): array
    {
        return [];
    }

    private function getBenchmarkComparison(array $efficiency): array
    {
        return [];
    }

    private function calculateRevenueVsTarget(array $financialData): array
    {
        return [];
    }

    private function getYearOverYearComparison(): array
    {
        return [];
    }

    private function calculateRevenueGrowth(array $trends): array
    {
        return [];
    }

    private function getRevenueByCategoryAnalysis(): array
    {
        return [];
    }

    private function generateRevenueForecast(array $trends): array
    {
        return [];
    }

    private function calculateProfitabilityMetrics(array $financialData): array
    {
        return [];
    }

    private function analyzeFeeStructureEfficiency(): array
    {
        return [];
    }

    private function getPricingOptimizationSuggestions(): array
    {
        return [];
    }

    private function getComponentUtilizationStats(): array
    {
        return [];
    }

    private function generateShortTermForecast(array $trends): array
    {
        return [];
    }

    private function generateLongTermForecast(array $trends): array
    {
        return [];
    }

    private function getScenarioAnalysis(array $trends): array
    {
        return [];
    }

    private function performRiskAssessment(): array
    {
        return [];
    }

    private function getStudentPaymentSegmentation(): array
    {
        return [];
    }

    private function getPaymentTimingAnalysis(): array
    {
        return [];
    }

    private function generateBehavioralInsights(array $behavior): array
    {
        return [];
    }

    private function getOutstandingTrends(): array
    {
        return [];
    }

    private function calculateRecoveryProbability(array $outstanding): array
    {
        return [];
    }

    private function generateActionRecommendations(array $outstanding): array
    {
        return [];
    }

    private function determineTrendDirection(array $growthRates): string
    {
        return 'stable';
    }

    private function calculateVolatility(array $growthRates): float
    {
        return 0;
    }

    private function calculateGrowthConsistency(array $growthRates): float
    {
        return 0;
    }

    private function getMonthlyAchievement(float $monthlyTarget): float
    {
        return 0;
    }

    /**
     * Helper methods for the implemented functions
     */
    private function getMonthlyTarget($date): float
    {
        // Calculate monthly target based on historical data or set targets
        // This could be configurable or based on business rules
        $baseTarget = 500000; // Base monthly target

        // Adjust for seasonal variations (e.g., higher in admission months)
        $month = $date->month;
        $seasonalMultiplier = in_array($month, [4, 5, 6, 7]) ? 1.3 : 1.0; // Higher in admission season

        return $baseTarget * $seasonalMultiplier;
    }

    private function calculateTrendDirection(array $trends, string $field): string
    {
        if (count($trends) < 2) {
            return 'stable';
        }

        $recent = array_slice($trends, -3); // Last 3 months
        $values = array_column($recent, $field);

        $increasing = 0;
        $decreasing = 0;

        for ($i = 1; $i < count($values); $i++) {
            if ($values[$i] > $values[$i - 1]) {
                $increasing++;
            } elseif ($values[$i] < $values[$i - 1]) {
                $decreasing++;
            }
        }

        if ($increasing > $decreasing) {
            return 'increasing';
        } elseif ($decreasing > $increasing) {
            return 'decreasing';
        }

        return 'stable';
    }

    private function getPerformanceGrade(float $collectionRate): string
    {
        if ($collectionRate >= 95) {
            return 'Excellent';
        }
        if ($collectionRate >= 85) {
            return 'Good';
        }
        if ($collectionRate >= 70) {
            return 'Average';
        }
        if ($collectionRate >= 50) {
            return 'Below Average';
        }

        return 'Poor';
    }

    private function getPriorityLevel(float $collectionRate, float $totalBilled): string
    {
        if ($collectionRate < 50 && $totalBilled > 100000) {
            return 'Critical';
        }
        if ($collectionRate < 60 && $totalBilled > 50000) {
            return 'High';
        }
        if ($collectionRate < 70) {
            return 'Medium';
        }

        return 'Low';
    }

    private function getRecommendedActions(float $collectionRate): array
    {
        $actions = [];

        if ($collectionRate < 50) {
            $actions[] = 'Immediate intervention required';
            $actions[] = 'Review fee structure and payment terms';
            $actions[] = 'Implement aggressive collection strategy';
        } elseif ($collectionRate < 70) {
            $actions[] = 'Increase follow-up frequency';
            $actions[] = 'Offer payment plan options';
            $actions[] = 'Review and update collection processes';
        } else {
            $actions[] = 'Monitor regularly';
            $actions[] = 'Maintain current collection practices';
        }

        return $actions;
    }
}
