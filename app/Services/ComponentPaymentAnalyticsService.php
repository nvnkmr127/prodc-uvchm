<?php

// =============================================================================
// MIGRATED COMPONENT-BASED PAYMENT ANALYTICS SERVICE
// =============================================================================

namespace App\Services;

use App\Models\Student;
use App\Models\StudentFee;
use App\Models\Payment;
use App\Models\FeeCategory;
use App\Models\Batch;
use App\Models\ComponentPaymentItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ComponentPaymentAnalyticsService
{
    /**
     * Get payment behavior insights for component-based system
     */
    public function getPaymentBehaviorInsights(): array
    {
        return [
            'early_payers' => $this->getEarlyPayers(),
            'late_payers' => $this->getLatePayers(),
            'consistent_defaulters' => $this->getConsistentDefaulters(),
            'payment_patterns' => $this->getPaymentPatterns(),
            'seasonal_trends' => $this->getSeasonalTrends(),
            'risk_assessment' => $this->getRiskAssessment(),
            'component_insights' => $this->getComponentInsights()
        ];
    }

    /**
     * Get students who consistently pay early (component-based)
     */
    private function getEarlyPayers(): array
    {
        return Student::select('students.*')
            ->selectRaw('AVG(DATEDIFF(payments.payment_date, student_fees.due_date)) as avg_early_days')
            ->selectRaw('COUNT(component_payment_items.id) as total_component_payments')
            ->join('student_fees', 'students.id', '=', 'student_fees.student_id')
            ->join('component_payment_items', 'student_fees.id', '=', 'component_payment_items.student_fee_id')
            ->join('payments', 'component_payment_items.payment_id', '=', 'payments.id')
            ->where('payments.payment_type', 'component')
            ->where('payments.payment_date', '<', DB::raw('student_fees.due_date'))
            ->groupBy('students.id')
            ->havingRaw('COUNT(component_payment_items.id) >= 3') // At least 3 component payments
            ->havingRaw('AVG(DATEDIFF(payments.payment_date, student_fees.due_date)) <= -3') // Average 3+ days early
            ->orderByDesc('avg_early_days')
            ->limit(20)
            ->with(['batch.course'])
            ->get()
            ->map(function ($student) {
                return [
                    'student' => $student,
                    'avg_early_days' => abs($student->avg_early_days),
                    'total_component_payments' => $student->total_component_payments,
                    'consistency_score' => $this->calculateConsistencyScore($student, 'early'),
                    'preferred_components' => $this->getPreferredPaymentComponents($student->id),
                ];
            })
            ->toArray();
    }

    /**
     * Get students who consistently pay late (component-based)
     */
    private function getLatePayers(): array
    {
        return Student::select('students.*')
            ->selectRaw('AVG(DATEDIFF(payments.payment_date, student_fees.due_date)) as avg_late_days')
            ->selectRaw('COUNT(component_payment_items.id) as total_component_payments')
            ->selectRaw('SUM(component_payment_items.amount_paid) as total_late_payments')
            ->join('student_fees', 'students.id', '=', 'student_fees.student_id')
            ->join('component_payment_items', 'student_fees.id', '=', 'component_payment_items.student_fee_id')
            ->join('payments', 'component_payment_items.payment_id', '=', 'payments.id')
            ->where('payments.payment_type', 'component')
            ->where('payments.payment_date', '>', DB::raw('student_fees.due_date'))
            ->groupBy('students.id')
            ->havingRaw('COUNT(component_payment_items.id) >= 2')
            ->havingRaw('AVG(DATEDIFF(payments.payment_date, student_fees.due_date)) >= 7') // Average 7+ days late
            ->orderByDesc('avg_late_days')
            ->limit(50)
            ->with(['batch.course'])
            ->get()
            ->map(function ($student) {
                return [
                    'student' => $student,
                    'avg_late_days' => $student->avg_late_days,
                    'total_component_payments' => $student->total_component_payments,
                    'total_late_payments' => $student->total_late_payments,
                    'risk_score' => $this->calculateRiskScore($student),
                    'problematic_components' => $this->getProblematicComponents($student->id),
                ];
            })
            ->toArray();
    }

    /**
     * Get students who are consistent defaulters (component-based)
     */
    private function getConsistentDefaulters(): array
    {
        return Student::select('students.*')
            ->selectRaw('COUNT(student_fees.id) as total_fees')
            ->selectRaw('COUNT(CASE WHEN student_fees.status IN ("unpaid", "partial") AND student_fees.due_date < NOW() THEN 1 END) as overdue_fees')
            ->selectRaw('SUM(CASE WHEN student_fees.status IN ("unpaid", "partial") THEN (student_fees.amount - student_fees.concession_amount - student_fees.paid_amount) ELSE 0 END) as total_overdue_amount')
            ->selectRaw('COUNT(DISTINCT student_fees.fee_category_id) as affected_categories')
            ->join('student_fees', 'students.id', '=', 'student_fees.student_id')
            ->groupBy('students.id')
            ->havingRaw('COUNT(student_fees.id) >= 3')
            ->havingRaw('(COUNT(CASE WHEN student_fees.status IN ("unpaid", "partial") AND student_fees.due_date < NOW() THEN 1 END) / COUNT(student_fees.id)) >= 0.5')
            ->orderByDesc('total_overdue_amount')
            ->limit(30)
            ->with(['batch.course'])
            ->get()
            ->map(function ($student) {
                return [
                    'student' => $student,
                    'total_fees' => $student->total_fees,
                    'overdue_fees' => $student->overdue_fees,
                    'total_overdue_amount' => $student->total_overdue_amount,
                    'affected_categories' => $student->affected_categories,
                    'defaulter_severity' => $this->calculateDefaulterSeverity($student),
                    'category_breakdown' => $this->getDefaulterCategoryBreakdown($student->id),
                ];
            })
            ->toArray();
    }

    /**
     * Analyze payment patterns by month/season (component-based)
     */
    private function getPaymentPatterns(): array
    {
        $monthlyPatterns = Payment::selectRaw('MONTH(payment_date) as month, COUNT(*) as payment_count, SUM(amount) as total_amount')
            ->selectRaw('COUNT(DISTINCT student_id) as unique_students')
            ->where('payment_type', 'component')
            ->where('payment_date', '>=', now()->subYear())
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month')
            ->toArray();

        $weeklyPatterns = Payment::selectRaw('DAYOFWEEK(payment_date) as day_of_week, COUNT(*) as payment_count, AVG(amount) as avg_amount')
            ->where('payment_type', 'component')
            ->where('payment_date', '>=', now()->subMonths(3))
            ->groupBy('day_of_week')
            ->orderBy('day_of_week')
            ->get()
            ->keyBy('day_of_week')
            ->toArray();

        // Component-wise payment patterns
        $componentPatterns = $this->getComponentPaymentPatterns();

        return [
            'monthly' => $monthlyPatterns,
            'weekly' => $weeklyPatterns,
            'component_patterns' => $componentPatterns,
            'peak_payment_months' => $this->getPeakPaymentMonths($monthlyPatterns),
            'preferred_payment_days' => $this->getPreferredPaymentDays($weeklyPatterns),
            'component_preferences' => $this->getComponentPreferences(),
        ];
    }

    /**
     * Get seasonal payment trends (component-based)
     */
    private function getSeasonalTrends(): array
    {
        return [
            'admission_season_collection' => $this->getSeasonalComponentCollection([6, 7, 8]), // June-August
            'mid_year_collection' => $this->getSeasonalComponentCollection([10, 11, 12]), // Oct-Dec
            'year_end_collection' => $this->getSeasonalComponentCollection([1, 2, 3]), // Jan-March
            'summer_collection' => $this->getSeasonalComponentCollection([4, 5]), // April-May
            'component_seasonality' => $this->getComponentSeasonality(),
        ];
    }

    /**
     * Get seasonal collection data for components
     */
    private function getSeasonalComponentCollection(array $months): array
    {
        return Payment::selectRaw('YEAR(payment_date) as year, SUM(amount) as total_amount, COUNT(*) as payment_count')
            ->selectRaw('COUNT(DISTINCT student_id) as unique_students')
            ->where('payment_type', 'component')
            ->whereIn(DB::raw('MONTH(payment_date)'), $months)
            ->where('payment_date', '>=', now()->subYears(3))
            ->groupBy('year')
            ->orderBy('year')
            ->get()
            ->toArray();
    }

    /**
     * Risk assessment for component-based fee collection
     */
    private function getRiskAssessment(): array
    {
        $totalStudents = Student::count();
        $activeStudents = Student::where('status', 'active')->count();
        
        // Component-based overdue calculation
        $totalOverdue = StudentFee::whereIn('status', ['unpaid', 'partial'])
            ->where('due_date', '<', now())
            ->get()
            ->sum(function ($fee) {
                return $fee->amount - $fee->concession_amount - $fee->paid_amount;
            });
        
        $riskFactors = [
            'high_risk_students' => Student::whereHas('studentFees', function($q) {
                $q->where('due_date', '<', now()->subDays(60))
                  ->whereIn('status', ['unpaid', 'partial'])
                  ->whereRaw('amount - concession_amount - paid_amount > 0');
            })->count(),
            
            'medium_risk_students' => Student::whereHas('studentFees', function($q) {
                $q->where('due_date', '<', now()->subDays(30))
                  ->where('due_date', '>=', now()->subDays(60))
                  ->whereIn('status', ['unpaid', 'partial'])
                  ->whereRaw('amount - concession_amount - paid_amount > 0');
            })->count(),
            
            'collection_efficiency' => $this->calculateComponentCollectionEfficiency(),
            'default_rate' => $this->calculateComponentDefaultRate(),
            'recovery_rate' => $this->calculateComponentRecoveryRate(),
            'component_risk_breakdown' => $this->getComponentRiskBreakdown(),
        ];

        return [
            'overall_risk_score' => $this->calculateOverallRiskScore($riskFactors),
            'risk_factors' => $riskFactors,
            'recommendations' => $this->generateComponentRiskRecommendations($riskFactors),
            'total_overdue_amount' => $totalOverdue,
        ];
    }

    /**
     * Get component-specific insights
     */
    private function getComponentInsights(): array
    {
        return [
            'component_performance' => $this->getComponentPerformanceMetrics(),
            'payment_distribution' => $this->getComponentPaymentDistribution(),
            'partial_payment_analysis' => $this->getPartialPaymentAnalysis(),
            'category_correlation' => $this->getCategoryCorrelation(),
        ];
    }

    /**
     * Calculate component collection efficiency
     */
    private function calculateComponentCollectionEfficiency(): float
    {
        $totalFeeAmount = StudentFee::sum('amount');
        $totalConcessions = StudentFee::sum('concession_amount');
        $totalCollected = StudentFee::sum('paid_amount');
        
        $netFeeAmount = $totalFeeAmount - $totalConcessions;
        
        return $netFeeAmount > 0 ? round(($totalCollected / $netFeeAmount) * 100, 2) : 0;
    }

    /**
     * Calculate component default rate
     */
    private function calculateComponentDefaultRate(): float
    {
        $totalFees = StudentFee::count();
        $overdueFees = StudentFee::where('due_date', '<', now())
            ->whereIn('status', ['unpaid', 'partial'])
            ->whereRaw('amount - concession_amount - paid_amount > 0')
            ->count();
            
        return $totalFees > 0 ? round(($overdueFees / $totalFees) * 100, 2) : 0;
    }

    /**
     * Calculate component recovery rate
     */
    private function calculateComponentRecoveryRate(): float
    {
        $overdueFees = StudentFee::where('due_date', '<', now()->subDays(30))->get();
        $overdueValue = $overdueFees->sum('amount') - $overdueFees->sum('concession_amount');
        $recoveredFromOverdue = $overdueFees->sum('paid_amount');
        
        return $overdueValue > 0 ? round(($recoveredFromOverdue / $overdueValue) * 100, 2) : 0;
    }

    /**
     * Get component payment patterns
     */
    private function getComponentPaymentPatterns(): array
    {
        return FeeCategory::select('fee_categories.*')
            ->selectRaw('COUNT(component_payment_items.id) as payment_count')
            ->selectRaw('SUM(component_payment_items.amount_paid) as total_collected')
            ->selectRaw('AVG(component_payment_items.amount_paid) as avg_payment')
            ->selectRaw('COUNT(DISTINCT payments.student_id) as unique_payers')
            ->leftJoin('student_fees', 'fee_categories.id', '=', 'student_fees.fee_category_id')
            ->leftJoin('component_payment_items', 'student_fees.id', '=', 'component_payment_items.student_fee_id')
            ->leftJoin('payments', 'component_payment_items.payment_id', '=', 'payments.id')
            ->where('payments.payment_type', 'component')
            ->where('payments.payment_date', '>=', now()->subYear())
            ->groupBy('fee_categories.id')
            ->orderByDesc('total_collected')
            ->get()
            ->toArray();
    }

    /**
     * Get component performance metrics
     */
    private function getComponentPerformanceMetrics(): array
    {
        return FeeCategory::select('fee_categories.*')
            ->selectRaw('
                COUNT(student_fees.id) as total_fees,
                SUM(student_fees.amount) as total_amount,
                SUM(student_fees.concession_amount) as total_concessions,
                SUM(student_fees.paid_amount) as total_collected,
                COUNT(CASE WHEN student_fees.status = "paid" THEN 1 END) as paid_fees,
                COUNT(CASE WHEN student_fees.status IN ("unpaid", "partial") AND student_fees.due_date < NOW() THEN 1 END) as overdue_fees,
                AVG(DATEDIFF(COALESCE(payments.payment_date, NOW()), student_fees.due_date)) as avg_payment_delay
            ')
            ->leftJoin('student_fees', 'fee_categories.id', '=', 'student_fees.fee_category_id')
            ->leftJoin('component_payment_items', 'student_fees.id', '=', 'component_payment_items.student_fee_id')
            ->leftJoin('payments', 'component_payment_items.payment_id', '=', 'payments.id')
            ->groupBy('fee_categories.id')
            ->get()
            ->map(function ($category) {
                $netAmount = $category->total_amount - $category->total_concessions;
                $category->collection_rate = $netAmount > 0 ? 
                    round(($category->total_collected / $netAmount) * 100, 2) : 0;
                $category->default_rate = $category->total_fees > 0 ?
                    round(($category->overdue_fees / $category->total_fees) * 100, 2) : 0;
                return $category;
            })
            ->toArray();
    }

    /**
     * Get partial payment analysis
     */
    private function getPartialPaymentAnalysis(): array
    {
        $partialFees = StudentFee::where('status', 'partial')
            ->with(['feeCategory', 'student'])
            ->get();

        return [
            'total_partial_fees' => $partialFees->count(),
            'total_partial_amount' => $partialFees->sum(function ($fee) {
                return $fee->amount - $fee->concession_amount - $fee->paid_amount;
            }),
            'category_breakdown' => $partialFees->groupBy('fee_category_id')->map(function ($fees, $categoryId) {
                $category = $fees->first()->feeCategory;
                return [
                    'category_name' => $category->name,
                    'count' => $fees->count(),
                    'remaining_amount' => $fees->sum(function ($fee) {
                        return $fee->amount - $fee->concession_amount - $fee->paid_amount;
                    }),
                    'avg_completion_rate' => $fees->avg(function ($fee) {
                        $netAmount = $fee->amount - $fee->concession_amount;
                        return $netAmount > 0 ? ($fee->paid_amount / $netAmount) * 100 : 0;
                    }),
                ];
            }),
            'student_analysis' => $this->getPartialPaymentStudentAnalysis($partialFees),
        ];
    }

    /**
     * Generate fee-wise collection comparison (component-based)
     */
    public function getFeeWiseComparison(): array
    {
        return FeeCategory::select('fee_categories.*')
            ->selectRaw('
                SUM(CASE WHEN student_fees.status = "paid" THEN student_fees.amount ELSE 0 END) as collected,
                SUM(CASE WHEN student_fees.status IN ("unpaid", "partial") THEN (student_fees.amount - student_fees.concession_amount - student_fees.paid_amount) ELSE 0 END) as pending,
                SUM(student_fees.amount - student_fees.concession_amount) as net_total,
                SUM(student_fees.paid_amount) as total_paid,
                COUNT(CASE WHEN student_fees.status = "paid" THEN 1 END) as paid_count,
                COUNT(CASE WHEN student_fees.status IN ("unpaid", "partial") THEN 1 END) as unpaid_count,
                COUNT(student_fees.id) as total_count,
                AVG(student_fees.amount) as avg_fee_amount
            ')
            ->leftJoin('student_fees', 'fee_categories.id', '=', 'student_fees.fee_category_id')
            ->groupBy('fee_categories.id')
            ->get()
            ->map(function($category) {
                $category->collection_rate = $category->net_total > 0 ? 
                    round(($category->total_paid / $category->net_total) * 100, 2) : 0;
                $category->default_rate = $category->total_count > 0 ?
                    round(($category->unpaid_count / $category->total_count) * 100, 2) : 0;
                $category->efficiency_score = $this->calculateCategoryEfficiency($category);
                return $category;
            })
            ->toArray();
    }

    /**
     * Generate batch-wise payment performance (component-based)
     */
    public function getBatchWisePerformance(): array
    {
        return Batch::select('batches.*')
            ->selectRaw('
                COUNT(DISTINCT students.id) as total_students,
                SUM(student_fees.paid_amount) as collected,
                SUM(CASE WHEN student_fees.status IN ("unpaid", "partial") THEN (student_fees.amount - student_fees.concession_amount - student_fees.paid_amount) ELSE 0 END) as pending,
                SUM(student_fees.amount - student_fees.concession_amount) as total_net_fees,
                COUNT(CASE WHEN student_fees.status IN ("unpaid", "partial") AND student_fees.due_date < NOW() THEN 1 END) as overdue_fees,
                COUNT(DISTINCT CASE WHEN student_fees.status IN ("unpaid", "partial") AND student_fees.amount - student_fees.concession_amount - student_fees.paid_amount > 0 THEN students.id END) as students_with_dues
            ')
            ->leftJoin('students', 'batches.id', '=', 'students.batch_id')
            ->leftJoin('student_fees', 'students.id', '=', 'student_fees.student_id')
            ->with('course')
            ->groupBy('batches.id')
            ->get()
            ->map(function($batch) {
                $batch->collection_rate = $batch->total_net_fees > 0 ?
                    round(($batch->collected / $batch->total_net_fees) * 100, 2) : 0;
                $batch->per_student_collection = $batch->total_students > 0 ?
                    round($batch->collected / $batch->total_students, 2) : 0;
                $batch->defaulter_percentage = $batch->total_students > 0 ?
                    round(($batch->students_with_dues / $batch->total_students) * 100, 2) : 0;
                return $batch;
            })
            ->sortByDesc('collection_rate')
            ->values()
            ->toArray();
    }

    // Helper methods for calculations

    private function calculateConsistencyScore(Student $student, string $type): float
    {
        // Implementation for consistency scoring
        return 85.5; // Placeholder
    }

    private function getPreferredPaymentComponents(int $studentId): array
    {
        return ComponentPaymentItem::whereHas('studentFee', function($q) use ($studentId) {
                $q->where('student_id', $studentId);
            })
            ->with('studentFee.feeCategory')
            ->get()
            ->groupBy('studentFee.fee_category_id')
            ->map(function($items, $categoryId) {
                $category = $items->first()->studentFee->feeCategory;
                return [
                    'category_name' => $category->name,
                    'payment_count' => $items->count(),
                    'total_amount' => $items->sum('amount_paid'),
                ];
            })
            ->sortByDesc('payment_count')
            ->take(3)
            ->values()
            ->toArray();
    }

    private function calculateRiskScore(Student $student): float
    {
        // Implementation for risk scoring based on component payments
        return 72.3; // Placeholder
    }

    private function getProblematicComponents(int $studentId): array
    {
        return StudentFee::where('student_id', $studentId)
            ->whereIn('status', ['unpaid', 'partial'])
            ->where('due_date', '<', now())
            ->with('feeCategory')
            ->get()
            ->map(function($fee) {
                return [
                    'category_name' => $fee->feeCategory->name,
                    'overdue_amount' => $fee->amount - $fee->concession_amount - $fee->paid_amount,
                    'days_overdue' => now()->diffInDays($fee->due_date),
                ];
            })
            ->toArray();
    }

    private function calculateDefaulterSeverity(Student $student): string
    {
        if ($student->total_overdue_amount > 50000) return 'critical';
        if ($student->total_overdue_amount > 25000) return 'high';
        if ($student->total_overdue_amount > 10000) return 'medium';
        return 'low';
    }

    private function getDefaulterCategoryBreakdown(int $studentId): array
    {
        return StudentFee::where('student_id', $studentId)
            ->whereIn('status', ['unpaid', 'partial'])
            ->where('due_date', '<', now())
            ->with('feeCategory')
            ->get()
            ->groupBy('fee_category_id')
            ->map(function($fees, $categoryId) {
                $category = $fees->first()->feeCategory;
                return [
                    'category_name' => $category->name,
                    'overdue_amount' => $fees->sum(function($fee) {
                        return $fee->amount - $fee->concession_amount - $fee->paid_amount;
                    }),
                    'fee_count' => $fees->count(),
                ];
            })
            ->values()
            ->toArray();
    }

    private function calculateOverallRiskScore(array $factors): string
    {
        $score = 0;
        
        // High risk students contribute more to risk
        $totalStudents = Student::count();
        $score += ($factors['high_risk_students'] / $totalStudents) * 40;
        $score += ($factors['medium_risk_students'] / $totalStudents) * 20;
        $score += (100 - $factors['collection_efficiency']) * 0.3;
        $score += $factors['default_rate'] * 0.1;
        
        return match(true) {
            $score >= 30 => 'HIGH',
            $score >= 15 => 'MEDIUM',
            default => 'LOW'
        };
    }

    private function generateComponentRiskRecommendations(array $factors): array
    {
        $recommendations = [];

        if ($factors['high_risk_students'] > 0) {
            $recommendations[] = "Immediate action needed for {$factors['high_risk_students']} high-risk students with component defaults";
        }

        if ($factors['collection_efficiency'] < 80) {
            $recommendations[] = "Component collection efficiency is below optimal (80%). Review payment processes.";
        }

        if ($factors['default_rate'] > 10) {
            $recommendations[] = "Component default rate is concerning. Implement stricter follow-up procedures.";
        }

        // Add component-specific recommendations
        $componentRisks = $factors['component_risk_breakdown'] ?? [];
        foreach ($componentRisks as $risk) {
            if ($risk['risk_level'] === 'high') {
                $recommendations[] = "High risk detected in {$risk['category_name']} component. Review pricing and collection strategy.";
            }
        }

        return $recommendations;
    }

    private function getPeakPaymentMonths(array $monthlyData): array
    {
        $sorted = collect($monthlyData)->sortByDesc('total_amount')->take(3);
        return $sorted->map(function($data, $month) {
            return [
                'month' => Carbon::create(null, $month)->format('F'),
                'amount' => $data['total_amount'],
                'count' => $data['payment_count'],
                'unique_students' => $data['unique_students'] ?? 0
            ];
        })->values()->toArray();
    }

    private function getPreferredPaymentDays(array $weeklyData): array
    {
        $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        
        return collect($weeklyData)->map(function($data, $dayNumber) use ($dayNames) {
            return [
                'day' => $dayNames[$dayNumber - 1] ?? 'Unknown',
                'count' => $data['payment_count'],
                'avg_amount' => round($data['avg_amount'] ?? 0, 2)
            ];
        })->sortByDesc('count')->values()->toArray();
    }

    private function getComponentPreferences(): array
    {
        return FeeCategory::select('fee_categories.name')
            ->selectRaw('COUNT(component_payment_items.id) as payment_frequency')
            ->selectRaw('AVG(component_payment_items.amount_paid) as avg_payment')
            ->join('student_fees', 'fee_categories.id', '=', 'student_fees.fee_category_id')
            ->join('component_payment_items', 'student_fees.id', '=', 'component_payment_items.student_fee_id')
            ->join('payments', 'component_payment_items.payment_id', '=', 'payments.id')
            ->where('payments.payment_date', '>=', now()->subMonths(6))
            ->groupBy('fee_categories.id', 'fee_categories.name')
            ->orderByDesc('payment_frequency')
            ->take(10)
            ->get()
            ->toArray();
    }

    private function getComponentSeasonality(): array
    {
        return FeeCategory::select('fee_categories.name')
            ->selectRaw('MONTH(payments.payment_date) as month')
            ->selectRaw('COUNT(component_payment_items.id) as payment_count')
            ->selectRaw('SUM(component_payment_items.amount_paid) as total_amount')
            ->join('student_fees', 'fee_categories.id', '=', 'student_fees.fee_category_id')
            ->join('component_payment_items', 'student_fees.id', '=', 'component_payment_items.student_fee_id')
            ->join('payments', 'component_payment_items.payment_id', '=', 'payments.id')
            ->where('payments.payment_date', '>=', now()->subYear())
            ->groupBy('fee_categories.id', 'fee_categories.name', 'month')
            ->orderBy('fee_categories.name')
            ->orderBy('month')
            ->get()
            ->groupBy('name')
            ->map(function($categoryData, $categoryName) {
                return [
                    'category_name' => $categoryName,
                    'monthly_data' => $categoryData->keyBy('month')->toArray(),
                    'peak_month' => $categoryData->sortByDesc('total_amount')->first()->month ?? null,
                    'seasonal_variance' => $this->calculateSeasonalVariance($categoryData),
                ];
            })
            ->toArray();
    }

    private function getComponentRiskBreakdown(): array
    {
        return FeeCategory::select('fee_categories.*')
            ->selectRaw('
                COUNT(CASE WHEN student_fees.status IN ("unpaid", "partial") AND student_fees.due_date < NOW() THEN 1 END) as overdue_count,
                SUM(CASE WHEN student_fees.status IN ("unpaid", "partial") AND student_fees.due_date < NOW() THEN (student_fees.amount - student_fees.concession_amount - student_fees.paid_amount) ELSE 0 END) as overdue_amount,
                COUNT(student_fees.id) as total_fees,
                AVG(DATEDIFF(NOW(), student_fees.due_date)) as avg_overdue_days
            ')
            ->leftJoin('student_fees', 'fee_categories.id', '=', 'student_fees.fee_category_id')
            ->groupBy('fee_categories.id')
            ->get()
            ->map(function($category) {
                $riskLevel = 'low';
                if ($category->overdue_amount > 100000 || ($category->total_fees > 0 && ($category->overdue_count / $category->total_fees) > 0.3)) {
                    $riskLevel = 'high';
                } elseif ($category->overdue_amount > 50000 || ($category->total_fees > 0 && ($category->overdue_count / $category->total_fees) > 0.15)) {
                    $riskLevel = 'medium';
                }

                return [
                    'category_name' => $category->name,
                    'overdue_count' => $category->overdue_count,
                    'overdue_amount' => $category->overdue_amount,
                    'total_fees' => $category->total_fees,
                    'risk_level' => $riskLevel,
                    'avg_overdue_days' => round($category->avg_overdue_days ?? 0, 1),
                ];
            })
            ->toArray();
    }

    private function getComponentPaymentDistribution(): array
    {
        return ComponentPaymentItem::selectRaw('
                CASE 
                    WHEN amount_paid < 1000 THEN "Under ₹1,000"
                    WHEN amount_paid < 5000 THEN "₹1,000 - ₹5,000"
                    WHEN amount_paid < 10000 THEN "₹5,000 - ₹10,000"
                    WHEN amount_paid < 25000 THEN "₹10,000 - ₹25,000"
                    ELSE "Above ₹25,000"
                END as amount_range,
                COUNT(*) as payment_count,
                SUM(amount_paid) as total_amount
            ')
            ->groupBy('amount_range')
            ->orderBy(DB::raw('MIN(amount_paid)'))
            ->get()
            ->toArray();
    }

    private function getCategoryCorrelation(): array
    {
        // Analyze which fee categories are often paid together
        return Payment::select('payments.id')
            ->with(['componentItems.studentFee.feeCategory'])
            ->where('payment_type', 'component')
            ->where('payment_date', '>=', now()->subMonths(6))
            ->get()
            ->filter(function($payment) {
                return $payment->componentItems->count() > 1; // Multi-component payments
            })
            ->flatMap(function($payment) {
                $categories = $payment->componentItems->pluck('studentFee.feeCategory.name')->unique()->sort();
                $combinations = [];
                for ($i = 0; $i < $categories->count(); $i++) {
                    for ($j = $i + 1; $j < $categories->count(); $j++) {
                        $combinations[] = $categories[$i] . ' + ' . $categories[$j];
                    }
                }
                return $combinations;
            })
            ->countBy()
            ->sortDesc()
            ->take(10)
            ->map(function($count, $combination) {
                return [
                    'combination' => $combination,
                    'frequency' => $count,
                ];
            })
            ->values()
            ->toArray();
    }

    private function getPartialPaymentStudentAnalysis($partialFees): array
    {
        return $partialFees->groupBy('student_id')->map(function($fees, $studentId) {
            $student = $fees->first()->student;
            return [
                'student_name' => $student->name,
                'enrollment_number' => $student->enrollment_number,
                'partial_fees_count' => $fees->count(),
                'total_remaining' => $fees->sum(function($fee) {
                    return $fee->amount - $fee->concession_amount - $fee->paid_amount;
                }),
                'categories' => $fees->pluck('feeCategory.name')->unique()->implode(', '),
            ];
        })->sortByDesc('total_remaining')->take(20)->values()->toArray();
    }

    private function calculateCategoryEfficiency($category): float
    {
        // Calculate efficiency score based on collection rate, speed, and consistency
        $collectionWeight = 0.5;
        $speedWeight = 0.3;
        $consistencyWeight = 0.2;

        $collectionScore = $category->collection_rate;
        $speedScore = 100 - min($category->avg_payment_delay ?? 0, 100); // Lower delay = higher score
        $consistencyScore = 100 - $category->default_rate;

        return round(
            ($collectionScore * $collectionWeight) +
            ($speedScore * $speedWeight) +
            ($consistencyScore * $consistencyWeight),
            2
        );
    }

    private function calculateSeasonalVariance($categoryData): float
    {
        $amounts = $categoryData->pluck('total_amount')->toArray();
        if (count($amounts) < 2) return 0;

        $mean = array_sum($amounts) / count($amounts);
        $variance = array_sum(array_map(function($amount) use ($mean) {
            return pow($amount - $mean, 2);
        }, $amounts)) / count($amounts);

        return round(sqrt($variance), 2);
    }
}