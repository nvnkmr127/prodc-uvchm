<?php

namespace App\Services;

use App\Models\Student;
use App\Models\StudentFee;
use App\Models\Payment;
use App\Models\FeeCategory;
use App\Models\ComponentPaymentItem;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ComponentFinancialReportService
{
    /**
     * Generate component-based defaulter report
     * Replaces the old invoice-based defaulter report
     */
    public function generateDefaulterReport(array $filters = [])
    {
        $query = Student::with(['batch.course', 'studentFees.feeCategory'])
            ->whereHas('studentFees', function ($q) {
                $q->whereIn('status', ['unpaid', 'partial'])
                  ->whereRaw('amount - concession_amount - paid_amount > 0');
            });

        // Apply filters
        if (!empty($filters['fee_category_id'])) {
            $query->whereHas('studentFees', function ($q) use ($filters) {
                $q->where('fee_category_id', $filters['fee_category_id']);
            });
        }

        if (!empty($filters['batch_id'])) {
            $query->where('batch_id', $filters['batch_id']);
        }

        if (!empty($filters['min_amount'])) {
            $query->whereHas('studentFees', function ($q) use ($filters) {
                $q->whereRaw('amount - concession_amount - paid_amount >= ?', [$filters['min_amount']]);
            });
        }

        if (!empty($filters['overdue_only'])) {
            $query->whereHas('studentFees', function ($q) {
                $q->whereIn('status', ['unpaid', 'partial'])
                  ->where('due_date', '<', now());
            });
        }

        $defaulters = $query->get()->map(function ($student) use ($filters) {
            // Calculate component-wise outstanding amounts
            $studentFees = $student->studentFees()
                ->whereIn('status', ['unpaid', 'partial'])
                ->with('feeCategory')
                ->get();

            if (!empty($filters['fee_category_id'])) {
                $studentFees = $studentFees->where('fee_category_id', $filters['fee_category_id']);
            }

            $componentBreakdown = $studentFees->groupBy('fee_category_id')->map(function ($fees, $categoryId) {
                $category = $fees->first()->feeCategory;
                $totalAmount = $fees->sum('amount');
                $concessionAmount = $fees->sum('concession_amount');
                $paidAmount = $fees->sum('paid_amount');
                $outstandingAmount = $totalAmount - $concessionAmount - $paidAmount;

                return [
                    'category_id' => $categoryId,
                    'category_name' => $category->name,
                    'category_type' => $category->category_type ?? 'other',
                    'total_amount' => $totalAmount,
                    'concession_amount' => $concessionAmount,
                    'paid_amount' => $paidAmount,
                    'outstanding_amount' => $outstandingAmount,
                    'fees_count' => $fees->count(),
                    'overdue_fees_count' => $fees->filter(fn($fee) => $fee->due_date < now())->count(),
                    'oldest_due_date' => $fees->min('due_date'),
                    'latest_due_date' => $fees->max('due_date'),
                ];
            });

            $totalOutstanding = $componentBreakdown->sum('outstanding_amount');

            // Skip students with zero outstanding
            if ($totalOutstanding <= 0) {
                return null;
            }

            // Apply minimum amount filter
            if (!empty($filters['min_amount']) && $totalOutstanding < $filters['min_amount']) {
                return null;
            }

            // Calculate days overdue (oldest overdue fee)
            $oldestOverdueFee = $studentFees->filter(fn($fee) => $fee->due_date < now())->sortBy('due_date')->first();
            $daysOverdue = $oldestOverdueFee ? now()->diffInDays($oldestOverdueFee->due_date) : 0;

            // Get last payment date for this student
            $lastPayment = Payment::where('student_id', $student->id)
                ->where('payment_type', 'component')
                ->orderBy('payment_date', 'desc')
                ->first();

            return [
                'student_id' => $student->id,
                'student' => $student,
                'enrollment_number' => $student->enrollment_number,
                'name' => $student->name,
                'course' => $student->batch->course->name ?? 'N/A',
                'batch' => $student->batch->name ?? 'N/A',
                'total_outstanding' => $totalOutstanding,
                'component_breakdown' => $componentBreakdown,
                'overdue_components_count' => $componentBreakdown->sum('overdue_fees_count'),
                'total_fees_count' => $componentBreakdown->sum('fees_count'),
                'days_overdue' => $daysOverdue,
                'oldest_due_date' => $oldestOverdueFee?->due_date,
                'last_payment_date' => $lastPayment?->payment_date,
                'last_payment_amount' => $lastPayment?->amount ?? 0,
                'severity_level' => $this->calculateSeverityLevel($totalOutstanding, $daysOverdue),
                'risk_category' => $this->calculateRiskCategory($totalOutstanding, $daysOverdue, $componentBreakdown->count()),
            ];
        })->filter()->values();

        // Sort by total outstanding amount (descending)
        $defaulters = $defaulters->sortByDesc('total_outstanding')->values();

        return [
            'defaulters' => $defaulters,
            'summary' => $this->calculateDefaulterSummary($defaulters),
            'filters_applied' => $filters,
        ];
    }

    /**
     * Generate component-based collection report
     * Replaces the old invoice-based collection report
     */
    public function generateCollectionReport(Carbon $startDate, Carbon $endDate, array $filters = [])
    {
        $query = Payment::with([
            'student.batch.course',
            'componentItems.studentFee.feeCategory'
        ])
        ->where('payment_type', 'component')
        ->whereBetween('payment_date', [$startDate, $endDate]);

        // Apply filters
        if (!empty($filters['fee_category_id'])) {
            $query->whereHas('componentItems.studentFee', function ($q) use ($filters) {
                $q->where('fee_category_id', $filters['fee_category_id']);
            });
        }

        if (!empty($filters['batch_id'])) {
            $query->whereHas('student', function ($q) use ($filters) {
                $q->where('batch_id', $filters['batch_id']);
            });
        }

        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (!empty($filters['min_amount'])) {
            $query->where('amount', '>=', $filters['min_amount']);
        }

        $payments = $query->orderBy('payment_date', 'desc')->get();

        // Process payments to include component details
        $processedPayments = $payments->map(function ($payment) {
            $componentItems = $payment->componentItems->map(function ($item) {
                return [
                    'fee_category_id' => $item->studentFee->fee_category_id,
                    'category_name' => $item->studentFee->feeCategory->name,
                    'category_type' => $item->studentFee->feeCategory->category_type ?? 'other',
                    'amount_paid' => $item->amount_paid,
                    'academic_year' => $item->studentFee->academic_year,
                    'installment_number' => $item->studentFee->installment_number,
                    'due_date' => $item->studentFee->due_date,
                ];
            });

            return [
                'payment_id' => $payment->id,
                'receipt_number' => $payment->receipt_number,
                'student' => $payment->student,
                'enrollment_number' => $payment->student->enrollment_number,
                'student_name' => $payment->student->name,
                'course' => $payment->student->batch->course->name ?? 'N/A',
                'batch' => $payment->student->batch->name ?? 'N/A',
                'payment_date' => $payment->payment_date,
                'total_amount' => $payment->amount,
                'payment_method' => $payment->payment_method,
                'transaction_id' => $payment->transaction_id,
                'component_items' => $componentItems,
                'components_count' => $componentItems->count(),
                'categories_paid' => $componentItems->pluck('category_name')->unique()->implode(', '),
                'notes' => $payment->notes,
            ];
        });

        // Generate collection analytics
        $analytics = $this->generateCollectionAnalytics($payments, $startDate, $endDate);

        return [
            'payments' => $processedPayments,
            'analytics' => $analytics,
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'days' => $startDate->diffInDays($endDate) + 1,
            ],
            'filters_applied' => $filters,
        ];
    }

    /**
     * Calculate defaulter summary statistics
     */
    private function calculateDefaulterSummary($defaulters)
    {
        $totalCount = $defaulters->count();
        $totalOutstanding = $defaulters->sum('total_outstanding');

        // Group by severity levels
        $severityCounts = $defaulters->groupBy('severity_level')->map->count();

        // Group by risk categories
        $riskCounts = $defaulters->groupBy('risk_category')->map->count();

        // Component-wise breakdown
        $componentBreakdown = collect();
        foreach ($defaulters as $defaulter) {
            foreach ($defaulter['component_breakdown'] as $component) {
                $categoryName = $component['category_name'];
                if (!$componentBreakdown->has($categoryName)) {
                    $componentBreakdown[$categoryName] = [
                        'category_name' => $categoryName,
                        'category_type' => $component['category_type'],
                        'defaulters_count' => 0,
                        'total_outstanding' => 0,
                    ];
                }
                $componentBreakdown[$categoryName]['defaulters_count']++;
                $componentBreakdown[$categoryName]['total_outstanding'] += $component['outstanding_amount'];
            }
        }

        return [
            'total_defaulters' => $totalCount,
            'total_outstanding_amount' => $totalOutstanding,
            'average_outstanding_per_student' => $totalCount > 0 ? round($totalOutstanding / $totalCount, 2) : 0,
            'severity_breakdown' => $severityCounts,
            'risk_breakdown' => $riskCounts,
            'component_breakdown' => $componentBreakdown->values(),
            'top_defaulters' => $defaulters->take(10),
        ];
    }

    /**
     * Generate collection analytics
     */
    private function generateCollectionAnalytics($payments, Carbon $startDate, Carbon $endDate)
    {
        $totalAmount = $payments->sum('amount');
        $totalPayments = $payments->count();
        $uniqueStudents = $payments->pluck('student_id')->unique()->count();

        // Daily collection trend
        $dailyCollections = $payments->groupBy(function ($payment) {
            return $payment->payment_date->format('Y-m-d');
        })->map(function ($dayPayments) {
            return [
                'date' => $dayPayments->first()->payment_date->format('Y-m-d'),
                'amount' => $dayPayments->sum('amount'),
                'count' => $dayPayments->count(),
            ];
        })->sortBy('date')->values();

        // Payment method breakdown
        $paymentMethods = $payments->groupBy('payment_method')->map(function ($methodPayments, $method) {
            return [
                'method' => $method,
                'amount' => $methodPayments->sum('amount'),
                'count' => $methodPayments->count(),
                'percentage' => 0, // Will be calculated below
            ];
        });

        // Calculate percentages
        foreach ($paymentMethods as &$method) {
            $method['percentage'] = $totalAmount > 0 ? round(($method['amount'] / $totalAmount) * 100, 2) : 0;
        }

        // Component-wise collections
        $componentCollections = collect();
        foreach ($payments as $payment) {
            foreach ($payment->componentItems as $item) {
                $categoryName = $item->studentFee->feeCategory->name;
                if (!$componentCollections->has($categoryName)) {
                    $componentCollections[$categoryName] = [
                        'category_name' => $categoryName,
                        'category_type' => $item->studentFee->feeCategory->category_type ?? 'other',
                        'total_amount' => 0,
                        'payments_count' => 0,
                        'students_count' => collect(),
                    ];
                }
                $componentCollections[$categoryName]['total_amount'] += $item->amount_paid;
                $componentCollections[$categoryName]['payments_count']++;
                $componentCollections[$categoryName]['students_count']->push($payment->student_id);
            }
        }

        // Finalize component collections
        $componentCollections = $componentCollections->map(function ($component) use ($totalAmount) {
            $component['students_count'] = $component['students_count']->unique()->count();
            $component['percentage'] = $totalAmount > 0 ? round(($component['total_amount'] / $totalAmount) * 100, 2) : 0;
            return $component;
        })->sortByDesc('total_amount')->values();

        return [
            'total_amount' => $totalAmount,
            'total_payments' => $totalPayments,
            'unique_students' => $uniqueStudents,
            'average_payment_amount' => $totalPayments > 0 ? round($totalAmount / $totalPayments, 2) : 0,
            'daily_collections' => $dailyCollections,
            'payment_methods' => $paymentMethods->values(),
            'component_collections' => $componentCollections,
            'peak_collection_day' => $dailyCollections->sortByDesc('amount')->first(),
        ];
    }

    /**
     * Calculate severity level based on amount and days overdue
     */
    private function calculateSeverityLevel($amount, $daysOverdue)
    {
        if ($daysOverdue >= 90 || $amount >= 50000) {
            return 'critical';
        } elseif ($daysOverdue >= 60 || $amount >= 25000) {
            return 'high';
        } elseif ($daysOverdue >= 30 || $amount >= 10000) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Calculate risk category
     */
    private function calculateRiskCategory($amount, $daysOverdue, $componentsCount)
    {
        $score = 0;
        
        // Amount factor
        if ($amount >= 50000) $score += 3;
        elseif ($amount >= 25000) $score += 2;
        elseif ($amount >= 10000) $score += 1;
        
        // Days overdue factor
        if ($daysOverdue >= 90) $score += 3;
        elseif ($daysOverdue >= 60) $score += 2;
        elseif ($daysOverdue >= 30) $score += 1;
        
        // Multiple components factor
        if ($componentsCount >= 3) $score += 1;
        
        if ($score >= 5) return 'high_risk';
        elseif ($score >= 3) return 'medium_risk';
        else return 'low_risk';
    }
}