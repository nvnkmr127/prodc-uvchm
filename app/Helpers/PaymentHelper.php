<?php

namespace App\Helpers;

use App\Models\Batch;
use App\Models\FeeCategory;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentFee;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PaymentHelper
{
    /**
     * Get payment priority based on fee category and overdue days
     * ✅ FIXED: Added proper parameter types and error handling
     */
    public static function getPaymentPriority($feeCategory, int $overdueDays): string
    {
        // Handle both string and FeeCategory object
        if (is_string($feeCategory)) {
            $categoryType = $feeCategory;
        } elseif ($feeCategory instanceof FeeCategory) {
            $categoryType = $feeCategory->category_type;
        } else {
            return 'medium'; // Default fallback
        }

        $config = config('payment_reminders.fee_type_priorities', []);
        $feeConfig = $config[$categoryType] ?? ['priority' => 'medium'];

        $basePriority = $feeConfig['priority'];

        // Escalate priority based on overdue days
        if ($overdueDays > 60) {
            return 'critical';
        } elseif ($overdueDays > 30) {
            return match ($basePriority) {
                'low' => 'medium',
                'medium' => 'high',
                'high' => 'critical',
                default => 'high'
            };
        } elseif ($overdueDays > 15) {
            return match ($basePriority) {
                'low' => 'low',
                'medium' => 'medium',
                'high' => 'high',
                default => 'medium'
            };
        }

        return $basePriority;
    }

    /**
     * Calculate late fee based on settings and overdue amount
     * ✅ FIXED: Added error handling for missing setting function
     */
    public static function calculateLateFee(float $amount, int $overdueDays): float
    {
        // Handle cases where setting() function might not exist
        $lateFeePer = function_exists('setting') ?
            (float) setting('late_fee_percentage', 5) : 5.0;
        $graceDays = function_exists('setting') ?
            (int) setting('late_fee_grace_days', 7) : 7;

        if ($overdueDays <= $graceDays) {
            return 0;
        }

        // Calculate progressive late fee
        $lateFee = ($amount * $lateFeePer) / 100;

        // Additional charges for chronic defaulters
        if ($overdueDays > 90) {
            $lateFee *= 2; // Double late fee for chronic cases
        } elseif ($overdueDays > 60) {
            $lateFee *= 1.5; // 1.5x late fee for severe cases
        }

        return round($lateFee, 2);
    }

    /**
     * Get next reminder date based on current reminder count and due date
     * ✅ FIXED: Added error handling for missing config
     */
    public static function getNextReminderDate(int $reminderCount, Carbon $dueDate): Carbon
    {
        $schedule = config('payment_reminders.schedule', [
            'before_due_days' => [
                'first_reminder' => 7,
                'second_reminder' => 3,
                'final_reminder' => 1,
            ],
            'after_due_days' => [
                'first_overdue' => 1,
                'second_overdue' => 7,
                'third_overdue' => 15,
                'escalation' => 30,
            ],
        ]);

        switch ($reminderCount) {
            case 0:
                return $dueDate->copy()->subDays($schedule['before_due_days']['first_reminder'] ?? 7);
            case 1:
                return $dueDate->copy()->subDays($schedule['before_due_days']['second_reminder'] ?? 3);
            case 2:
                return $dueDate->copy()->subDays($schedule['before_due_days']['final_reminder'] ?? 1);
            case 3:
                return $dueDate->copy()->addDays($schedule['after_due_days']['first_overdue'] ?? 1);
            case 4:
                return $dueDate->copy()->addDays($schedule['after_due_days']['second_overdue'] ?? 7);
            case 5:
                return $dueDate->copy()->addDays($schedule['after_due_days']['third_overdue'] ?? 15);
            case 6:
                return $dueDate->copy()->addDays($schedule['after_due_days']['escalation'] ?? 30);
            default:
                // Weekly reminders after escalation
                return $dueDate->copy()->addDays(30 + (($reminderCount - 6) * 7));
        }
    }

    /**
     * Format amount with currency symbol
     * ✅ FIXED: Added error handling for missing setting function
     */
    public static function formatAmount(float $amount): string
    {
        $symbol = function_exists('setting') ?
            setting('currency_symbol', '₹') : '₹';

        if ($amount >= 10000000) { // 1 crore
            return $symbol.number_format($amount / 10000000, 2).' Cr';
        } elseif ($amount >= 100000) { // 1 lakh
            return $symbol.number_format($amount / 100000, 2).' L';
        } elseif ($amount >= 1000) {
            return $symbol.number_format($amount / 1000, 1).'K';
        } else {
            return $symbol.number_format($amount, 2);
        }
    }

    /**
     * ✅ FIXED: Get detailed student risk score with error handling
     */
    public static function getStudentRiskScore(Student $student): array
    {
        try {
            // Check if required methods exist on Student model
            if (! method_exists($student, 'studentFees')) {
                return [
                    'score' => 0,
                    'level' => 'unknown',
                    'factors' => ['Unable to calculate - missing student fees relationship'],
                    'recommendations' => [],
                ];
            }

            $totalFees = $student->studentFees()->count();

            // Use safe method calls with fallbacks
            $overdueFees = method_exists($student, 'getOverdueFees') ?
                $student->getOverdueFees()->count() :
                $student->studentFees()->where('due_date', '<', now())
                    ->whereIn('status', ['unpaid', 'partial', 'overdue'])->count();

            $totalOverdueAmount = method_exists($student, 'getTotalOverdueAmount') ?
                $student->getTotalOverdueAmount() : 0;

            $avgPaymentDelay = static::getAveragePaymentDelay($student);

            $score = 0;
            $factors = [];
            $recommendations = [];

            // Factor 1: Overdue ratio (40% weight)
            if ($totalFees > 0) {
                $overdueRatio = $overdueFees / $totalFees;
                $overdueScore = $overdueRatio * 40;
                $score += $overdueScore;

                if ($overdueRatio > 0.7) {
                    $factors[] = 'Very high overdue ratio: '.round($overdueRatio * 100, 1).'% of fee components are overdue';
                    $recommendations[] = 'Immediate intervention required';
                } elseif ($overdueRatio > 0.4) {
                    $factors[] = 'High overdue ratio: '.round($overdueRatio * 100, 1).'%';
                    $recommendations[] = 'Enhanced monitoring needed';
                }
            }

            // Factor 2: Amount factor (30% weight)
            if ($totalOverdueAmount > 50000) {
                $score += 30;
                $factors[] = 'Very high overdue amount: '.static::formatAmount($totalOverdueAmount);
                $recommendations[] = 'Consider payment plan arrangement';
            } elseif ($totalOverdueAmount > 25000) {
                $score += 20;
                $factors[] = 'High overdue amount: '.static::formatAmount($totalOverdueAmount);
                $recommendations[] = 'Escalate to management';
            } elseif ($totalOverdueAmount > 10000) {
                $score += 10;
            }

            // Factor 3: Payment behavior (20% weight)
            if ($avgPaymentDelay > 30) {
                $score += 20;
                $factors[] = 'Consistently late payments (avg '.$avgPaymentDelay.' days)';
                $recommendations[] = 'Setup automated reminders';
            } elseif ($avgPaymentDelay > 15) {
                $score += 10;
            }

            // Factor 4: Recent activity (10% weight)
            $recentPayments = 0;
            if (method_exists($student, 'componentPayments')) {
                $recentPayments = $student->componentPayments()
                    ->where('payment_date', '>=', now()->subMonths(6))
                    ->count();
            }

            if ($recentPayments == 0 && $totalFees > 0) {
                $score += 10;
                $factors[] = 'No recent payments in last 6 months';
                $recommendations[] = 'Contact student/parent immediately';
            }

            // Determine risk level
            $riskLevel = match (true) {
                $score >= 80 => 'critical',
                $score >= 60 => 'high',
                $score >= 40 => 'medium',
                $score >= 20 => 'low',
                default => 'minimal'
            };

            return [
                'score' => round($score, 1),
                'level' => $riskLevel,
                'factors' => $factors,
                'recommendations' => $recommendations,
                'total_fee_components' => $totalFees,
                'overdue_components' => $overdueFees,
                'overdue_amount' => $totalOverdueAmount,
                'avg_payment_delay' => $avgPaymentDelay,
            ];

        } catch (\Exception $e) {
            return [
                'score' => 0,
                'level' => 'error',
                'factors' => ['Error calculating risk score: '.$e->getMessage()],
                'recommendations' => ['Review student data integrity'],
            ];
        }
    }

    /**
     * ✅ FIXED: Get average payment delay with error handling
     */
    public static function getAveragePaymentDelay(Student $student): float
    {
        try {
            if (! method_exists($student, 'componentPayments')) {
                return 0.0;
            }

            $payments = $student->componentPayments()
                ->with('componentItems.studentFee')
                ->whereHas('componentItems.studentFee')
                ->get();

            if ($payments->isEmpty()) {
                return 0.0;
            }

            $totalDelayDays = 0;
            $validPayments = 0;

            foreach ($payments as $payment) {
                if (! $payment->componentItems) {
                    continue;
                }

                foreach ($payment->componentItems as $item) {
                    if ($item->studentFee && $item->studentFee->due_date) {
                        $delayDays = Carbon::parse($payment->payment_date)
                            ->diffInDays(Carbon::parse($item->studentFee->due_date), false);

                        if ($delayDays > 0) { // Only count late payments
                            $totalDelayDays += $delayDays;
                            $validPayments++;
                        }
                    }
                }
            }

            return $validPayments > 0 ? round($totalDelayDays / $validPayments, 1) : 0.0;

        } catch (\Exception $e) {
            return 0.0;
        }
    }

    /**
     * ✅ FIXED: Get collection efficiency with error handling
     */
    public static function getCollectionEfficiency(Carbon $startDate, Carbon $endDate): array
    {
        try {
            $fees = StudentFee::whereBetween('due_date', [$startDate, $endDate])->get();
            $netCollectable = $fees->sum(function ($fee) {
                return ($fee->amount ?? 0) - ($fee->concession_amount ?? 0);
            });

            $totalCollected = Payment::where('payment_type', 'component')
                ->whereBetween('payment_date', [$startDate, $endDate])
                ->sum('amount');

            $totalPending = $netCollectable - $totalCollected;
            $efficiency = $netCollectable > 0 ? ($totalCollected / $netCollectable) * 100 : 0;

            return [
                'period' => [
                    'start_date' => $startDate->format('d-m-Y'),
                    'end_date' => $endDate->format('d-m-Y'),
                ],
                'amounts' => [
                    'net_collectable' => $netCollectable,
                    'total_collected' => $totalCollected,
                    'total_pending' => $totalPending,
                    'total_concessions' => $fees->sum('concession_amount'),
                ],
                'percentages' => [
                    'efficiency_percentage' => round($efficiency, 2),
                    'collection_rate' => round($efficiency, 2),
                ],
            ];

        } catch (\Exception $e) {
            return [
                'period' => [
                    'start_date' => $startDate->format('d-m-Y'),
                    'end_date' => $endDate->format('d-m-Y'),
                ],
                'amounts' => [
                    'net_collectable' => 0,
                    'total_collected' => 0,
                    'total_pending' => 0,
                    'total_concessions' => 0,
                ],
                'percentages' => [
                    'efficiency_percentage' => 0,
                    'collection_rate' => 0,
                ],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Categorize defaulter based on amount and days
     */
    public static function categorizeDefaulter(float $amount, int $days, int $feeCount): string
    {
        if ($days >= 90 || $amount >= 50000) {
            return 'chronic';
        } elseif ($days >= 60 || $amount >= 25000) {
            return 'severe';
        } elseif ($days >= 30 || $amount >= 10000) {
            return 'moderate';
        } else {
            return 'mild';
        }
    }

    /**
     * ✅ FIXED: Get payment behavior insights with error handling
     */
    public static function getPaymentBehaviorInsights(Student $student): array
    {
        try {
            if (! method_exists($student, 'componentPayments')) {
                return [
                    'behavior_type' => 'no_payment_history',
                    'insights' => ['No payment history available - missing component payments relationship'],
                ];
            }

            $payments = $student->componentPayments()->with('componentItems.studentFee')->get();

            if ($payments->isEmpty()) {
                return [
                    'behavior_type' => 'no_payment_history',
                    'insights' => ['No payment history available'],
                ];
            }

            $earlyPayments = 0;
            $latePayments = 0;
            $onTimePayments = 0;
            $totalDelayDays = 0;
            $totalItems = 0;

            foreach ($payments as $payment) {
                if (! $payment->componentItems) {
                    continue;
                }

                foreach ($payment->componentItems as $item) {
                    if ($item->studentFee && $item->studentFee->due_date) {
                        $totalItems++;
                        $daysDiff = Carbon::parse($payment->payment_date)
                            ->diffInDays(Carbon::parse($item->studentFee->due_date), false);

                        if ($daysDiff < -1) {
                            $earlyPayments++;
                        } elseif ($daysDiff > 1) {
                            $latePayments++;
                            $totalDelayDays += $daysDiff;
                        } else {
                            $onTimePayments++;
                        }
                    }
                }
            }

            $avgDelay = $latePayments > 0 ? $totalDelayDays / $latePayments : 0;
            $lateRate = $totalItems > 0 ? ($latePayments / $totalItems) * 100 : 0;

            $behaviorType = match (true) {
                $lateRate > 50 => 'chronic_late_payer',
                $avgDelay > 15 => 'delayed_payer',
                $earlyPayments > $latePayments => 'early_payer',
                default => 'regular_payer'
            };

            return [
                'behavior_type' => $behaviorType,
                'average_delay_days' => round($avgDelay),
                'late_payment_rate' => round($lateRate).'%',
                'early_payments' => $earlyPayments,
                'on_time_payments' => $onTimePayments,
                'late_payments' => $latePayments,
                'insights' => static::generateBehaviorInsights($behaviorType, $lateRate, $avgDelay),
            ];

        } catch (\Exception $e) {
            return [
                'behavior_type' => 'error',
                'insights' => ['Error analyzing payment behavior: '.$e->getMessage()],
            ];
        }
    }

    /**
     * ✅ NEW: Generate behavior insights based on payment patterns
     */
    private static function generateBehaviorInsights(string $behaviorType, float $lateRate, float $avgDelay): array
    {
        return match ($behaviorType) {
            'chronic_late_payer' => [
                'This student frequently pays late ('.round($lateRate).'% of payments)',
                'Consider setting up automated reminders',
                'May benefit from payment plan arrangement',
            ],
            'delayed_payer' => [
                'Student tends to pay late with average delay of '.round($avgDelay).' days',
                'Early reminder system recommended',
            ],
            'early_payer' => [
                'Excellent payment behavior - pays before due dates',
                'Low risk student, minimal monitoring required',
            ],
            'regular_payer' => [
                'Good payment behavior with timely payments',
                'Standard reminder schedule sufficient',
            ],
            default => ['Unable to determine clear payment pattern']
        };
    }

    /**
     * ✅ FIXED: Helper methods with error handling
     */
    private static function getNewDefaultersCount(Carbon $date): int
    {
        try {
            return StudentFee::where('due_date', $date->format('Y-m-d'))
                ->whereIn('status', ['unpaid', 'partial'])
                ->distinct('student_id')
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private static function getTotalDefaultersCount(): int
    {
        try {
            return StudentFee::where('due_date', '<', now())
                ->whereIn('status', ['unpaid', 'partial'])
                ->distinct('student_id')
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private static function getCriticalCasesCount(): int
    {
        try {
            return StudentFee::where('due_date', '<', now()->subDays(60))
                ->whereIn('status', ['unpaid', 'partial'])
                ->distinct('student_id')
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private static function getOverallCollectionRate(): float
    {
        try {
            $netCollectable = StudentFee::sum(DB::raw('amount - concession_amount'));
            $totalCollected = StudentFee::sum('paid_amount');

            return $netCollectable > 0 ? ($totalCollected / $netCollectable) * 100 : 0;
        } catch (\Exception $e) {
            return 0.0;
        }
    }

    /**
     * ✅ NEW: Get seasonal payment trends
     */
    public static function getSeasonalTrends(): array
    {
        try {
            return Payment::where('payment_type', 'component')
                ->where('payment_date', '>=', now()->subYear())
                ->selectRaw('DATE_FORMAT(payment_date, "%b %Y") as month, SUM(amount) as collections, COUNT(*) as transaction_count')
                ->groupBy('month')
                ->orderBy(DB::raw('MIN(payment_date)'))
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->month => [
                        'collections' => $item->collections,
                        'transaction_count' => $item->transaction_count,
                        'average_transaction' => $item->transaction_count > 0 ?
                            round($item->collections / $item->transaction_count, 2) : 0,
                    ]];
                })
                ->toArray();

        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * ✅ NEW: Get batch-wise payment performance
     */
    public static function getBatchWisePerformance(): array
    {
        try {
            return Batch::with(['students.studentFees', 'course'])->get()->map(function ($batch) {
                if ($batch->students->isEmpty()) {
                    return null;
                }

                $totalAmount = $batch->students->flatMap->studentFees->sum('amount');
                $totalCollected = $batch->students->flatMap->studentFees->sum('paid_amount');
                $totalConcessions = $batch->students->flatMap->studentFees->sum('concession_amount');
                $netAmount = $totalAmount - $totalConcessions;

                $defaulters = $batch->students->filter(function ($student) {
                    return method_exists($student, 'hasOverdueFees') ?
                        $student->hasOverdueFees() :
                        $student->studentFees()->where('due_date', '<', now())
                            ->whereIn('status', ['unpaid', 'partial', 'overdue'])->exists();
                })->count();

                $collectionRate = $netAmount > 0 ? ($totalCollected / $netAmount) * 100 : 0;
                $defaulterRate = $batch->students->count() > 0 ?
                    ($defaulters / $batch->students->count()) * 100 : 0;

                return [
                    'id' => $batch->id,
                    'name' => $batch->name,
                    'course' => $batch->course->name ?? 'N/A',
                    'total_students' => $batch->students->count(),
                    'total_amount' => $totalAmount,
                    'collected_amount' => $totalCollected,
                    'outstanding_amount' => $netAmount - $totalCollected,
                    'collection_rate' => round($collectionRate, 2),
                    'defaulters_count' => $defaulters,
                    'defaulter_rate' => round($defaulterRate, 2),
                    'performance_grade' => static::getPerformanceGrade($collectionRate),
                ];
            })->filter()->sortByDesc('collection_rate')->values()->toArray();

        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * ✅ NEW: Get performance grade based on collection rate
     */
    private static function getPerformanceGrade(float $collectionRate): string
    {
        return match (true) {
            $collectionRate >= 95 => 'A+',
            $collectionRate >= 90 => 'A',
            $collectionRate >= 80 => 'B+',
            $collectionRate >= 70 => 'B',
            $collectionRate >= 60 => 'C+',
            $collectionRate >= 50 => 'C',
            default => 'D'
        };
    }

    /**
     * ✅ NEW: Get payment method analysis
     */
    public static function getPaymentMethodAnalysis(): array
    {
        try {
            return Payment::where('payment_type', 'component')
                ->where('payment_date', '>=', now()->subMonths(6))
                ->selectRaw('payment_method, COUNT(*) as transaction_count, SUM(amount) as total_amount, AVG(amount) as average_amount')
                ->groupBy('payment_method')
                ->orderByDesc('total_amount')
                ->get()
                ->map(function ($item) {
                    return [
                        'method' => $item->payment_method,
                        'transaction_count' => $item->transaction_count,
                        'total_amount' => $item->total_amount,
                        'average_amount' => round($item->average_amount, 2),
                        'percentage_share' => 0, // Will be calculated after collection
                    ];
                })
                ->pipe(function ($collection) {
                    $totalAmount = $collection->sum('total_amount');

                    return $collection->map(function ($item) use ($totalAmount) {
                        $item['percentage_share'] = $totalAmount > 0 ?
                            round(($item['total_amount'] / $totalAmount) * 100, 2) : 0;

                        return $item;
                    });
                })
                ->toArray();

        } catch (\Exception $e) {
            return [];
        }
    }
}
