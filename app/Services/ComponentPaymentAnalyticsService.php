<?php

// =============================================================================
// MIGRATED COMPONENT-BASED PAYMENT ANALYTICS SERVICE
// =============================================================================

namespace App\Services;

use App\Models\Batch;
use App\Models\Payment;
use App\Models\Student;

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
            'component_insights' => $this->getComponentInsights(),
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
            ->map(function ($batch) {
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

    private function calculateDefaulterSeverity(Student $student): string
    {
        if ($student->total_overdue_amount > 50000) {
            return 'critical';
        }
        if ($student->total_overdue_amount > 25000) {
            return 'high';
        }
        if ($student->total_overdue_amount > 10000) {
            return 'medium';
        }

        return 'low';
    }

    private function calculateSeasonalVariance($categoryData): float
    {
        $amounts = $categoryData->pluck('total_amount')->toArray();
        if (count($amounts) < 2) {
            return 0;
        }

        $mean = array_sum($amounts) / count($amounts);
        $variance = array_sum(array_map(function ($amount) use ($mean) {
            return pow($amount - $mean, 2);
        }, $amounts)) / count($amounts);

        return round(sqrt($variance), 2);
    }
}
