<?php

namespace App\Services;

use App\Models\Student;
use App\Models\StudentFee;
use App\Models\Payment;
use App\Models\FeeCategory;
use App\Models\PaymentReminder;
use App\Models\StudentConcession;
use App\Models\ComponentPaymentItem;
use App\Models\Batch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Carbon\Carbon;

class ComponentPaymentService
{
    /**
     * Process component payment for a student
     */
    public function processPayment(Student $student, array $components, array $paymentData)
    {
        DB::beginTransaction();
        try {
            // Validate component amounts
            $this->validateComponentAmounts($student, $components);

            // Calculate total payment amount
            $totalAmount = collect($components)->sum('amount');

            // Create payment record
            $payment = Payment::create([
                'student_id' => $student->id,
                'amount' => $totalAmount,
                'payment_date' => $paymentData['payment_date'] ?? now(),
                'payment_method' => $paymentData['payment_method'],
                'payment_type' => 'component',
                'component_details' => $components,
                'transaction_id' => $paymentData['transaction_id'] ?? null,
                'notes' => $paymentData['notes'] ?? null,
                // [FIX] Populate academic_year for filtering
                'academic_year' => $student->batch?->academicYear?->name ?? null,
            ]);

            // Apply payments to individual components
            $appliedAmounts = [];
            foreach ($components as $component) {
                $appliedAmount = $this->applyPaymentToComponent(
                    $student,
                    $component['fee_category_id'],
                    $component['amount'],
                    $payment
                );
                $appliedAmounts[] = $appliedAmount;
            }

            // Log activity
            activity()
                ->performedOn($student)
                ->causedBy(auth()->user())
                ->withProperties([
                    'payment_id' => $payment->id,
                    'receipt_number' => $payment->receipt_number,
                    'amount' => $payment->amount,
                    'components' => $components
                ])
                ->log("Component payment of ₹{$payment->amount} processed");

            DB::commit();

            return [
                'success' => true,
                'payment' => $payment,
                'applied_amounts' => $appliedAmounts,
                'message' => "Payment of ₹{$payment->amount} processed successfully."
            ];

        } catch (Exception $e) {
            DB::rollback();
            Log::error('Component payment failed:', [
                'student_id' => $student->id,
                'error' => $e->getMessage(),
                'components' => $components
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Payment processing failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Apply concession to a student's fee component
     */
    public function applyConcession(Student $student, $categoryId, $amount, $reason, $approvedBy = null)
    {
        DB::beginTransaction();
        try {
            $studentFees = $student->studentFees()
                ->where('fee_category_id', $categoryId)
                ->whereIn('status', ['unpaid', 'partial'])
                ->get();

            if ($studentFees->isEmpty()) {
                throw new Exception('No unpaid fees found for this category');
            }

            $totalConcessionApplied = 0;
            $remainingConcession = $amount;

            foreach ($studentFees as $fee) {
                if ($remainingConcession <= 0)
                    break;

                $maxConcessionForThisFee = $fee->amount - $fee->concession_amount - $fee->paid_amount;
                $concessionToApply = min($remainingConcession, $maxConcessionForThisFee);

                if ($concessionToApply > 0) {
                    $fee->update([
                        'concession_amount' => $fee->concession_amount + $concessionToApply,
                        'concession_reason' => $reason,
                        'concession_approved_by' => $approvedBy ?? auth()->id(),
                        'concession_approved_at' => now()
                    ]);

                    $totalConcessionApplied += $concessionToApply;
                    $remainingConcession -= $concessionToApply;
                }
            }

            DB::commit();

            return [
                'success' => true,
                'amount_applied' => $totalConcessionApplied,
                'message' => "Concession of ₹{$totalConcessionApplied} applied successfully."
            ];

        } catch (Exception $e) {
            DB::rollback();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Reverse a component payment
     */
    public function reversePayment(Payment $payment)
    {
        DB::beginTransaction();
        try {
            if (!$payment->isComponentPayment()) {
                throw new Exception('Only component payments can be reversed through this service.');
            }

            // 1. Revert each component item's impact on StudentFee
            foreach ($payment->componentItems as $item) {
                if ($item->studentFee) {
                    $item->studentFee->reversePayment($item->amount_paid);
                }
            }

            // 2. Update payment status
            $payment->update([
                'status' => 'refunded',
                'notes' => ($payment->notes ? $payment->notes . "\n" : "") . "Payment reversed on " . now()->format('Y-m-d H:i')
            ]);

            // 3. Log activity
            activity()
                ->performedOn($payment->student)
                ->causedBy(auth()->user())
                ->withProperties(['payment_id' => $payment->id])
                ->log("Payment of ₹{$payment->amount} reversed (Receipt: {$payment->receipt_number})");

            DB::commit();

            return [
                'success' => true,
                'message' => 'Payment reversed successfully.'
            ];

        } catch (Exception $e) {
            DB::rollback();
            Log::error('Payment reversal failed:', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get component reminder overview for dashboard
     */
    public function getComponentReminderOverview(): array
    {
        try {
            return [
                'total_reminders' => PaymentReminder::count(), // ✅ Fixed - using Model not Service
                'pending_reminders' => PaymentReminder::where('status', 'pending')->count(),
                'sent_reminders' => PaymentReminder::where('status', 'sent')->count(),
                'failed_reminders' => PaymentReminder::where('status', 'failed')->count(),
                'success_rate' => $this->calculateReminderSuccessRate(),
                'component_breakdown' => $this->getRemindersByComponent(),
                'channel_performance' => $this->getReminderChannelPerformance(),
                'recent_activity' => $this->getRecentReminderActivity(),
            ];
        } catch (\Exception $e) {
            \Log::error('Error getting component reminder overview: ' . $e->getMessage());
            return [
                'total_reminders' => 0,
                'pending_reminders' => 0,
                'sent_reminders' => 0,
                'failed_reminders' => 0,
                'success_rate' => 0,
                'component_breakdown' => [],
                'channel_performance' => [],
                'recent_activity' => [],
            ];
        }
    }


    /**
     * Calculate reminder success rate
     */
    private function calculateReminderSuccessRate(): float
    {
        $total = PaymentReminder::where('status', '!=', 'pending')->count();
        $successful = PaymentReminder::where('status', 'sent')->count();

        return $total > 0 ? round(($successful / $total) * 100, 2) : 0;
    }

    /**
     * Get component statistics for a batch (replaces getBatchInvoiceStats)
     */
    public function getBatchComponentStats(int $batchId): array
    {
        $students = Student::where('batch_id', $batchId)->pluck('id');
        $fees = StudentFee::whereIn('student_id', $students)->get();

        return [
            'total_components' => $fees->count(),
            'total_amount' => $fees->sum('amount'),
            'paid_amount' => $fees->sum('paid_amount'),
            'due_amount' => $fees->sum(fn($fee) => $fee->getRemainingAmount()),
            'pending_count' => $fees->whereIn('status', ['unpaid', 'partial'])->count(),
            'paid_count' => $fees->where('status', 'paid')->count(),
            'overdue_count' => $fees->where('status', 'overdue')->count(),
            'concession_amount' => $fees->sum('concession_amount'),
            'net_amount' => $fees->sum(fn($fee) => $fee->getNetAmount()),
            'collection_percentage' => $fees->sum('amount') > 0 ?
                round(($fees->sum('paid_amount') / $fees->sum('amount')) * 100, 2) : 0
        ];
    }

    /**
     * Get dashboard financial data
     */
    public function getDashboardFinancialData(): array
    {
        $studentFees = StudentFee::with('feeCategory');

        return [
            'total_revenue' => $studentFees->sum('paid_amount'),
            'total_fees' => $studentFees->sum('amount'),
            'pending_amount' => $studentFees->sum(DB::raw('amount - concession_amount - paid_amount')),
            'overdue_amount' => $studentFees->where('status', 'overdue')->sum(DB::raw('amount - concession_amount - paid_amount')),
            'monthly_collection' => Payment::where('payment_type', 'component')
                ->whereMonth('payment_date', now()->month)
                ->sum('amount'),
            'total_concessions' => $studentFees->sum('concession_amount'),
            'collection_rate' => $this->calculateCollectionRate()
        ];
    }

    /**
     * Get student overdue amount, optionally for a specific category
     */
    public function getStudentOverdueAmount(Student $student, ?int $feeCategoryId = null): float
    {
        $query = $student->studentFees()
            ->whereDate('due_date', '<', now())
            ->whereIn('status', ['unpaid', 'partial']);

        if ($feeCategoryId) {
            $query->where('fee_category_id', $feeCategoryId);
        }

        return $query->get()
            ->sum(function ($fee) {
                return max(0, $fee->amount - $fee->concession_amount - $fee->paid_amount);
            });
    }

    // PRIVATE HELPER METHODS

    private function calculateCollectionRate(): float
    {
        $totalExpected = StudentFee::sum(DB::raw('amount - concession_amount'));
        $totalCollected = StudentFee::sum('paid_amount');

        return $totalExpected > 0 ? round(($totalCollected / $totalExpected) * 100, 2) : 0;
    }

    /**
     * Get reminders breakdown by component
     */
    private function getRemindersByComponent(): array
    {
        return FeeCategory::select('fee_categories.name')
            ->selectRaw('
                COUNT(payment_reminders.id) as total_reminders,
                COUNT(CASE WHEN payment_reminders.status = "sent" THEN 1 END) as sent_count,
                COUNT(CASE WHEN payment_reminders.status = "pending" THEN 1 END) as pending_count
            ')
            ->leftJoin('payment_reminders', 'fee_categories.id', '=', 'payment_reminders.fee_category_id')
            ->groupBy('fee_categories.id', 'fee_categories.name')
            ->orderByDesc('total_reminders')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Get reminder channel performance
     */
    private function getReminderChannelPerformance(): array
    {
        return PaymentReminder::select('channel')
            ->selectRaw('
                COUNT(*) as total_sent,
                COUNT(CASE WHEN status = "sent" THEN 1 END) as successful,
                COUNT(CASE WHEN status = "failed" THEN 1 END) as failed
            ')
            ->whereIn('status', ['sent', 'failed'])
            ->groupBy('channel')
            ->get()
            ->map(function ($item) {
                $item->success_rate = $item->total_sent > 0 ?
                    round(($item->successful / $item->total_sent) * 100, 2) : 0;
                return $item;
            })
            ->toArray();
    }

    /**
     * Get recent reminder activity
     */
    private function getRecentReminderActivity(): array
    {
        return PaymentReminder::with(['student', 'feeCategory'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($reminder) {
                return [
                    'id' => $reminder->id,
                    'student_name' => $reminder->student->name ?? 'Unknown',
                    'fee_category' => $reminder->feeCategory->name ?? 'General',
                    'channel' => $reminder->channel,
                    'status' => $reminder->status,
                    'created_at' => $reminder->created_at->format('Y-m-d H:i'),
                ];
            })
            ->toArray();
    }


    /**
     * Get monthly collection trends
     */
    public function getMonthlyCollectionTrends(int $months = 12): array
    {
        $trends = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $amount = Payment::where('payment_type', 'component')
                ->whereYear('payment_date', $date->year)
                ->whereMonth('payment_date', $date->month)
                ->sum('amount');

            $trends[] = [
                'month' => $date->format('M Y'),
                'amount' => $amount,
                'date' => $date->format('Y-m-d')
            ];
        }

        return $trends;
    }

    /**
     * Get financial summary for a student
     */
    public function getStudentFinancialSummary(Student $student): array
    {
        $studentFees = $student->studentFees;

        $totalAmount = $studentFees->sum('amount');
        $concessionAmount = $studentFees->sum('concession_amount');
        $paidAmount = $studentFees->sum('paid_amount');
        $dueAmount = $totalAmount - $concessionAmount - $paidAmount;

        return [
            'total_amount' => $totalAmount,
            'concession_amount' => $concessionAmount,
            'paid_amount' => $paidAmount,
            'due_amount' => max(0, $dueAmount),
            'overdue_amount' => $this->getOverdueAmount($student),
        ];
    }

    /**
     * Get overdue amount for a student
     */
    public function getOverdueAmount(Student $student): float
    {
        return $student->studentFees()
            ->whereDate('due_date', '<', now())
            ->whereIn('status', ['unpaid', 'partial'])
            ->get()
            ->sum(function ($fee) {
                return max(0, $fee->amount - $fee->concession_amount - $fee->paid_amount);
            });
    }


    /**
     * Check if student has outstanding fees (replaces hasUnpaidFees)
     */
    public function hasOutstandingFees(Student $student): bool
    {
        return $student->studentFees()
            ->whereRaw('amount - concession_amount - paid_amount > 0')
            ->exists();
    }

    /**
     * Get overdue fees for student (replaces getOverdueInvoices)
     */
    public function getOverdueFees(Student $student)
    {
        return $student->studentFees()
            ->where(function ($q) {
                $q->where('status', 'overdue')
                    ->orWhere(function ($subQ) {
                        $subQ->whereIn('status', ['unpaid', 'partial'])
                            ->where('due_date', '<', now());
                    });
            })
            ->with('feeCategory')
            ->orderBy('due_date')
            ->get();
    }

    /**
     * Get latest payment for student (replaces getLatestInvoice)
     */
    public function getLatestPayment(Student $student)
    {
        return $student->componentPayments()
            ->with('componentItems.studentFee.feeCategory')
            ->latest('payment_date')
            ->first();
    }

    /**
     * Get collection efficiency metrics
     */
    public function getCollectionEfficiency(): array
    {
        $totalExpected = StudentFee::sum(DB::raw('amount - concession_amount'));
        $totalCollected = StudentFee::sum('paid_amount');
        $collectionRate = $totalExpected > 0 ? ($totalCollected / $totalExpected) * 100 : 0;

        return [
            'collection_rate' => round($collectionRate, 2),
            'timeliness_score' => $this->calculateTimelinessScore(),
            'consistency_score' => $this->calculateConsistencyScore(),
            'overall_score' => round(($collectionRate + $this->calculateTimelinessScore() + $this->calculateConsistencyScore()) / 3, 2)
        ];
    }

    private function calculateConsistencyScore(): float
    {
        // Placeholder - implement based on payment consistency logic
        return 85.0;
    }


    private function calculateTimelinessScore(): float
    {
        $onTimePayments = Payment::where('payment_type', 'component')
            ->whereHas('componentItems.studentFee', function ($q) {
                $q->whereRaw('payment_date <= due_date');
            })->count();

        $totalPayments = Payment::where('payment_type', 'component')->count();

        return $totalPayments > 0 ? round(($onTimePayments / $totalPayments) * 100, 2) : 100;
    }


    /**
     * Get payment behavior analytics
     */
    public function getPaymentBehaviorAnalytics(): array
    {
        $avgDaysToPay = StudentFee::whereNotNull('paid_amount')
            ->where('paid_amount', '>', 0)
            ->avg(DB::raw('DATEDIFF(updated_at, created_at)')) ?? 30;

        return [
            'average_days_to_pay' => round($avgDaysToPay, 1),
            'payment_frequency' => $this->getPaymentFrequency(),
            'preferred_methods' => $this->getPreferredPaymentMethods(),
            'seasonal_patterns' => $this->getSeasonalPatterns()
        ];
    }

    /**
     * Get outstanding fees summary
     */
    public function getOutstandingFeesSummary(): array
    {
        $outstandingFees = StudentFee::whereRaw('amount - concession_amount - paid_amount > 0')->get();

        $byCategory = $outstandingFees->groupBy('fee_category_id')->map(function ($fees, $categoryId) {
            $category = FeeCategory::find($categoryId);
            return [
                'category_name' => $category ? $category->name : 'Unknown',
                'total_amount' => $fees->sum(fn($f) => $f->amount - $f->concession_amount - $f->paid_amount),
                'count' => $fees->count()
            ];
        });

        $byAging = $this->calculateAgingAnalysis($outstandingFees);

        return [
            'total_outstanding' => $outstandingFees->sum(fn($f) => $f->amount - $f->concession_amount - $f->paid_amount),
            'total_overdue' => $outstandingFees->where('status', 'overdue')->sum(fn($f) => $f->amount - $f->concession_amount - $f->paid_amount),
            'total_amount' => $outstandingFees->sum('amount'),
            'by_category' => $byCategory->toArray(),
            'by_aging' => $byAging
        ];
    }


    private function calculateAgingAnalysis($outstandingFees): array
    {
        $aging = [
            '0-30' => 0,
            '31-60' => 0,
            '61-90' => 0,
            '90+' => 0
        ];

        foreach ($outstandingFees as $fee) {
            if (!$fee->due_date)
                continue;

            $daysOverdue = now()->diffInDays($fee->due_date, false);
            $amount = $fee->amount - $fee->concession_amount - $fee->paid_amount;

            if ($daysOverdue <= 30) {
                $aging['0-30'] += $amount;
            } elseif ($daysOverdue <= 60) {
                $aging['31-60'] += $amount;
            } elseif ($daysOverdue <= 90) {
                $aging['61-90'] += $amount;
            } else {
                $aging['90+'] += $amount;
            }
        }

        return $aging;
    }


    /**
     * Update overdue fee statuses (replaces updateOverdueInvoices)
     */
    public function updateOverdueFees(): int
    {
        return StudentFee::whereIn('status', ['unpaid', 'partial'])
            ->where('due_date', '<', now())
            ->update(['status' => 'overdue']);
    }

    /**
     * Get payment statistics for dashboard
     */
    public function getPaymentStatistics(): array
    {
        $today = now()->startOfDay();
        $thisMonth = now()->startOfMonth();
        $thisYear = now()->startOfYear();

        return [
            'today' => [
                'count' => Payment::where('payment_type', 'component')
                    ->whereDate('payment_date', $today)
                    ->count(),
                'amount' => Payment::where('payment_type', 'component')
                    ->whereDate('payment_date', $today)
                    ->sum('amount'),
            ],
            'this_month' => [
                'count' => Payment::where('payment_type', 'component')
                    ->whereDate('payment_date', '>=', $thisMonth)
                    ->count(),
                'amount' => Payment::where('payment_type', 'component')
                    ->whereDate('payment_date', '>=', $thisMonth)
                    ->sum('amount'),
            ],
            'this_year' => [
                'count' => Payment::where('payment_type', 'component')
                    ->whereDate('payment_date', '>=', $thisYear)
                    ->count(),
                'amount' => Payment::where('payment_type', 'component')
                    ->whereDate('payment_date', '>=', $thisYear)
                    ->sum('amount'),
            ],
        ];
    }

    /**
     * Get defaulter students
     */
    public function getDefaulterStudents(int $daysPastDue = 30): \Illuminate\Database\Eloquent\Collection
    {
        $cutoffDate = now()->subDays($daysPastDue);

        return Student::whereHas('studentFees', function ($query) use ($cutoffDate) {
            $query->whereDate('due_date', '<', $cutoffDate)
                ->whereIn('status', ['unpaid', 'partial'])
                ->whereRaw('amount - concession_amount - paid_amount > 0');
        })
            ->with([
                'batch.course',
                'studentFees' => function ($query) use ($cutoffDate) {
                    $query->whereDate('due_date', '<', $cutoffDate)
                        ->whereIn('status', ['unpaid', 'partial'])
                        ->whereRaw('amount - concession_amount - paid_amount > 0')
                        ->with('feeCategory');
                }
            ])
            ->get();
    }

    /**
     * Get collection trends for reporting
     */
    public function getCollectionTrends(int $months = 12): array
    {
        $startDate = now()->subMonths($months)->startOfMonth();

        $collections = Payment::where('payment_type', 'component')
            ->whereDate('payment_date', '>=', $startDate)
            ->selectRaw('DATE_FORMAT(payment_date, "%Y-%m") as month, SUM(amount) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $trends = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = now()->subMonths($i)->format('Y-m');
            $trends[$month] = $collections->where('month', $month)->first()->total ?? 0;
        }

        return $trends;
    }

    /**
     * Get recovery trends (NEW METHOD)
     */
    public function getRecoveryTrends(int $months = 12): array
    {
        $startDate = now()->subMonths($months)->startOfMonth();

        // Get monthly recovery data
        $recoveries = Payment::where('payment_type', 'component')
            ->whereDate('payment_date', '>=', $startDate)
            ->selectRaw('
                DATE_FORMAT(payment_date, "%Y-%m") as month,
                COUNT(*) as payment_count,
                SUM(amount) as total_amount,
                COUNT(DISTINCT student_id) as unique_students
            ')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        // Get outstanding amounts by month
        $outstanding = StudentFee::selectRaw('
                DATE_FORMAT(due_date, "%Y-%m") as month,
                SUM(amount - concession_amount - paid_amount) as outstanding_amount
            ')
            ->whereDate('due_date', '>=', $startDate)
            ->whereRaw('amount - concession_amount - paid_amount > 0')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $trends = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = now()->subMonths($i)->format('Y-m');
            $recovery = $recoveries->get($month);
            $outstandingData = $outstanding->get($month);

            $trends[] = [
                'month' => $month,
                'month_name' => now()->subMonths($i)->format('M Y'),
                'recovered_amount' => $recovery->total_amount ?? 0,
                'payment_count' => $recovery->payment_count ?? 0,
                'unique_students' => $recovery->unique_students ?? 0,
                'outstanding_amount' => $outstandingData->outstanding_amount ?? 0,
                'recovery_rate' => $this->calculateRecoveryRate($recovery, $outstandingData),
            ];
        }

        return $trends;
    }



    /**
     * Calculate recovery rate
     */
    private function calculateRecoveryRate($recovery, $outstanding): float
    {
        if (!$recovery || !$outstanding) {
            return 0;
        }

        $totalDue = ($recovery->total_amount ?? 0) + ($outstanding->outstanding_amount ?? 0);

        if ($totalDue <= 0) {
            return 0;
        }

        return round((($recovery->total_amount ?? 0) / $totalDue) * 100, 2);
    }



    /**
     * Get fee category wise collection summary
     */
    public function getFeeCategoryWiseCollection(): array
    {
        return FeeCategory::withCount('studentFees')
            ->withSum('studentFees', 'amount')
            ->withSum('studentFees', 'paid_amount')
            ->withSum('studentFees', 'concession_amount')
            ->get()
            ->map(function ($category) {
                $totalAmount = $category->student_fees_sum_amount ?? 0;
                $paidAmount = $category->student_fees_sum_paid_amount ?? 0;
                $concessionAmount = $category->student_fees_sum_concession_amount ?? 0;
                $outstandingAmount = $totalAmount - $concessionAmount - $paidAmount;

                return [
                    'name' => $category->name,
                    'total_amount' => $totalAmount,
                    'paid_amount' => $paidAmount,
                    'concession_amount' => $concessionAmount,
                    'outstanding_amount' => max(0, $outstandingAmount),
                    'collection_percentage' => $totalAmount > 0 ? round(($paidAmount / $totalAmount) * 100, 2) : 0,
                    'student_count' => $category->student_fees_count,
                ];
            })
            ->toArray();
    }


    /**
     * Get collection summary
     */
    public function getCollectionSummary(): array
    {
        $totalExpected = StudentFee::sum(DB::raw('amount - concession_amount'));
        $totalCollected = StudentFee::sum('paid_amount');
        $totalConcessions = StudentFee::sum('concession_amount');

        return [
            'total_expected' => $totalExpected,
            'total_collected' => $totalCollected,
            'total_concessions' => $totalConcessions,
            'net_expected' => $totalExpected,
            'remaining' => $totalExpected - $totalCollected,
            'collection_percentage' => $totalExpected > 0 ? round(($totalCollected / $totalExpected) * 100, 2) : 0
        ];
    }

    /**
     * Get students with outstanding fees (replaces scope)
     */
    public function getStudentsWithOutstandingFees()
    {
        return Student::whereHas('studentFees', function ($q) {
            $q->whereRaw('amount - concession_amount - paid_amount > 0');
        })->with([
                    'batch.course',
                    'studentFees' => function ($q) {
                        $q->whereRaw('amount - concession_amount - paid_amount > 0')
                            ->with('feeCategory');
                    }
                ]);
    }

    /**
     * Get students with overdue fees
     */
    public function getStudentsWithOverdueFees()
    {
        return Student::whereHas('studentFees', function ($q) {
            $q->where(function ($subQ) {
                $subQ->where('status', 'overdue')
                    ->orWhere(function ($deepQ) {
                        $deepQ->whereIn('status', ['unpaid', 'partial'])
                            ->where('due_date', '<', now());
                    });
            });
        })->with([
                    'batch.course',
                    'studentFees' => function ($q) {
                        $q->where(function ($subQ) {
                            $subQ->where('status', 'overdue')
                                ->orWhere(function ($deepQ) {
                                    $deepQ->whereIn('status', ['unpaid', 'partial'])
                                        ->where('due_date', '<', now());
                                });
                        })->with('feeCategory');
                    }
                ]);
    }

    /**
     * Generate payment report
     */
    public function generatePaymentReport($filters = [])
    {
        $payments = Payment::componentPayments()
            ->with(['student', 'componentItems.studentFee.feeCategory']);

        if (isset($filters['start_date'])) {
            $payments->whereDate('payment_date', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $payments->whereDate('payment_date', '<=', $filters['end_date']);
        }

        if (isset($filters['fee_category_id'])) {
            $payments->whereHas('componentItems.studentFee', function ($q) use ($filters) {
                $q->where('fee_category_id', $filters['fee_category_id']);
            });
        }

        $payments = $payments->orderBy('payment_date', 'desc')->get();

        return [
            'payments' => $payments->map(function ($payment) {
                return [
                    'receipt_number' => $payment->receipt_number,
                    'payment_date' => $payment->payment_date->format('Y-m-d'),
                    'student_name' => $payment->student->name,
                    'enrollment_number' => $payment->student->enrollment_number,
                    'amount' => $payment->amount,
                    'payment_method' => $payment->payment_method,
                    'components' => $payment->componentItems->map(function ($item) {
                        return [
                            'category' => $item->studentFee->feeCategory->name,
                            'amount_paid' => $item->amount_paid
                        ];
                    })
                ];
            }),
            'summary' => [
                'total_payments' => $payments->count(),
                'total_amount' => $payments->sum('amount'),
                'average_amount' => $payments->avg('amount'),
                'by_method' => $payments->groupBy('payment_method')->map(function ($group) {
                    return [
                        'count' => $group->count(),
                        'amount' => $group->sum('amount')
                    ];
                })
            ]
        ];
    }

    /**
     * Create fee components for students in batch
     */
    public function createFeeComponentsForBatch($batchId, $feeStructureId = null, $academicYear = null)
    {
        DB::beginTransaction();
        try {
            $batch = Batch::with(['students', 'feeStructure.feeCategories'])->find($batchId);

            if (!$batch) {
                throw new Exception('Batch not found');
            }

            $feeStructure = $feeStructureId ?
                \App\Models\FeeStructure::with('feeCategories')->find($feeStructureId) :
                $batch->feeStructure;

            if (!$feeStructure) {
                throw new Exception('Fee Structure not found');
            }

            $academicYear = $academicYear ?? date('Y') . '-' . (date('Y') + 1);
            $createdCount = 0;

            foreach ($batch->students as $student) {
                foreach ($feeStructure->feeCategories as $category) {
                    // Check if component already exists
                    $existingFee = StudentFee::where([
                        'student_id' => $student->id,
                        'fee_category_id' => $category->id,
                        'academic_year' => $academicYear
                    ])->first();

                    if (!$existingFee) {
                        StudentFee::create([
                            'student_id' => $student->id,
                            'fee_structure_id' => $feeStructure->id,
                            'fee_category_id' => $category->id,
                            'academic_year' => $academicYear,
                            'amount' => $category->pivot->amount,
                            'due_date' => now()->addDays(30),
                            'status' => 'unpaid',
                            'installment_number' => 1,
                            'total_installments' => $feeStructure->payment_terms ?? 1
                        ]);
                        $createdCount++;
                    }
                }
            }

            DB::commit();

            return [
                'success' => true,
                'created_count' => $createdCount,
                'message' => "Created {$createdCount} fee components successfully."
            ];

        } catch (Exception $e) {
            DB::rollback();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }


    private function getPaymentFrequency(): array
    {
        // Calculate based on Fee Structure payment terms for active students
        $frequencies = DB::table('students')
            ->join('batches', 'students.batch_id', '=', 'batches.id')
            ->join('fee_structures', 'batches.id', '=', 'fee_structures.batch_id') // Assuming generic association
            // Note: Schema might technically link fee_structure to batch via batch_id in fee_structures table, 
            // or batch has fee_structure_id. Let's verify schema if this fails.
            // Based on earlier view, Batch 'has' feeStructure.
            ->select('fee_structures.payment_terms')
            ->get();

        $counts = [
            'monthly' => 0,
            'quarterly' => 0,
            'annually' => 0,
            'other' => 0
        ];

        foreach ($frequencies as $freq) {
            $terms = $freq->payment_terms;
            if ($terms >= 10) {
                $counts['monthly']++;
            } elseif ($terms == 1) {
                $counts['annually']++;
            } elseif ($terms >= 3 && $terms <= 4) {
                $counts['quarterly']++;
            } else {
                $counts['other']++;
            }
        }

        // Convert to percentages if needed, or just raw counts. The UI likely expects counts.
        return $counts;
    }

    private function getPreferredPaymentMethods(): array
    {
        return Payment::where('payment_type', 'component')
            ->groupBy('payment_method')
            ->selectRaw('payment_method, COUNT(*) as count')
            ->pluck('count', 'payment_method')
            ->toArray();
    }

    private function getSeasonalPatterns(): array
    {
        // Get total payments per month
        $monthlyTotals = Payment::where('payment_type', 'component')
            ->selectRaw('MONTH(payment_date) as month, SUM(amount) as total')
            ->groupBy('month')
            ->orderBy('total', 'desc')
            ->get();

        if ($monthlyTotals->isEmpty()) {
            return [
                'peak_months' => [],
                'low_months' => []
            ];
        }

        // Map month numbers to names
        $monthNames = [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December'
        ];

        // Top 3 are peak
        $peaks = $monthlyTotals->take(3)->map(fn($item) => $monthNames[$item->month])->values()->toArray();

        // Bottom 3 are low (reverse sort first)
        $lows = $monthlyTotals->sortBy('total')->take(3)->map(fn($item) => $monthNames[$item->month])->values()->toArray();

        return [
            'peak_months' => $peaks,
            'low_months' => $lows
        ];
    }

    private function getStudentPaymentHistory(Student $student): array
    {
        return Payment::where('student_id', $student->id)
            ->where('payment_type', 'component')
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($payment) {
                return [
                    'amount' => $payment->amount,
                    'date' => $payment->payment_date->format('Y-m-d'),
                    'method' => $payment->payment_method,
                    'receipt_number' => $payment->receipt_number
                ];
            })
            ->toArray();
    }

    private function getStudentUpcomingDues(Student $student): array
    {
        return $student->studentFees()
            ->whereIn('status', ['unpaid', 'partial'])
            ->where('due_date', '>', now())
            ->orderBy('due_date')
            ->limit(3)
            ->get()
            ->map(function ($fee) {
                return [
                    'category' => $fee->feeCategory->name,
                    'amount' => $fee->amount - $fee->concession_amount - $fee->paid_amount,
                    'due_date' => $fee->due_date->format('Y-m-d')
                ];
            })
            ->toArray();
    }

    /**
     * Private helper methods
     */
    private function validateComponentAmounts(Student $student, array $components)
    {
        foreach ($components as $component) {
            if ($component['amount'] <= 0) {
                throw new Exception('Payment amount must be greater than 0');
            }

            $availableAmount = $student->studentFees()
                ->where('fee_category_id', $component['fee_category_id'])
                ->whereIn('status', ['unpaid', 'partial'])
                ->get()
                ->sum(fn($fee) => $fee->getRemainingAmount());

            if ($component['amount'] > $availableAmount) {
                $category = FeeCategory::find($component['fee_category_id']);
                throw new Exception("Payment amount for {$category->name} exceeds available balance");
            }
        }
    }

    private function applyPaymentToComponent(Student $student, $categoryId, $amount, Payment $payment)
    {
        $studentFees = $student->studentFees()
            ->where('fee_category_id', $categoryId)
            ->whereIn('status', ['unpaid', 'partial'])
            ->orderBy('due_date')
            ->get();

        $remainingAmount = $amount;
        $appliedTotal = 0;

        foreach ($studentFees as $fee) {
            if ($remainingAmount <= 0)
                break;

            $appliedAmount = $fee->applyPayment($remainingAmount, $payment);
            $remainingAmount -= $appliedAmount;
            $appliedTotal += $appliedAmount;
        }

        return $appliedTotal;
    }
}