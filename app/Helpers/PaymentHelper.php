<?php

namespace App\Helpers;

use App\Models\FeeCategory;
use App\Models\Payment;
use App\Models\PaymentReminder;
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
     * Generate payment reminder message template
     * ✅ FIXED: Added more robust template handling
     */
    public static function generateReminderMessage(string $type, array $data): string
    {
        $templates = [
            'upcoming_due_sms' => 'Dear {name}, your {fee_type} payment of {amount} is due on {due_date}. Please pay to avoid late fees. - {college_name}',
            'overdue_sms' => 'URGENT: {name}, your {fee_type} payment of {amount} is {days_overdue} days overdue. Total due: {total_due}. Pay immediately. - {college_name}',
            'parent_notification' => "Dear Parent/Guardian, {name}'s {fee_type} payment of {amount} is overdue by {days_overdue} days. Please clear dues to avoid disruptions. - {college_name}",
            'email_reminder' => 'Dear {name}, this is a reminder that your {fee_type} payment of {amount} is due. Please log into the student portal to make payment.',
            'whatsapp_message' => 'Hi {name}! Your {fee_type} fee of {amount} is due on {due_date}. Click here to pay: {payment_link}',
        ];

        $template = $templates[$type] ?? $templates['overdue_sms'];

        // Calculate additional fields if not provided
        if (isset($data['amount'], $data['days_overdue'])) {
            $data['late_fee'] = static::formatAmount(static::calculateLateFee((float) $data['amount'], (int) $data['days_overdue']));
            $data['total_due'] = static::formatAmount((float) $data['amount'] + static::calculateLateFee((float) $data['amount'], (int) $data['days_overdue']));
        }

        // Set default values for missing data
        $defaults = [
            'college_name' => function_exists('setting') ? setting('app_name', 'College') : 'College',
            'payment_link' => '#',
            'due_date' => 'N/A',
            'name' => 'Student',
            'fee_type' => 'Fee',
            'amount' => '₹0',
            'days_overdue' => '0',
        ];

        foreach ($defaults as $key => $value) {
            if (! isset($data[$key])) {
                $data[$key] = $value;
            }
        }

        // Replace placeholders
        foreach ($data as $key => $value) {
            $template = str_replace('{'.$key.'}', (string) $value, $template);
        }

        return $template;
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
     * ✅ FIXED: Get dashboard statistics with error handling
     */
    public static function getDashboardStats(): array
    {
        try {
            $today = now();
            $thisMonthStart = $today->copy()->startOfMonth();

            return [
                'today' => [
                    'collections' => Payment::where('payment_type', 'component')
                        ->whereDate('payment_date', $today)
                        ->sum('amount'),
                    'reminders_sent' => class_exists('\App\Models\PaymentReminder') ?
                        PaymentReminder::whereDate('sent_at', $today)->count() : 0,
                    'new_defaulters' => static::getNewDefaultersCount($today),
                ],
                'this_month' => [
                    'collections' => Payment::where('payment_type', 'component')
                        ->where('payment_date', '>=', $thisMonthStart)
                        ->sum('amount'),
                    'target' => function_exists('setting') ?
                        setting('monthly_collection_target', 1000000) : 1000000,
                    'efficiency' => static::getCollectionEfficiency($thisMonthStart, $today)['percentages']['efficiency_percentage'],
                ],
                'overview' => [
                    'total_students' => Student::where('status', 'active')->count(),
                    'total_defaulters' => static::getTotalDefaultersCount(),
                    'critical_cases' => static::getCriticalCasesCount(),
                    'collection_rate' => static::getOverallCollectionRate(),
                ],
            ];

        } catch (\Exception $e) {
            return [
                'today' => ['collections' => 0, 'reminders_sent' => 0, 'new_defaulters' => 0],
                'this_month' => ['collections' => 0, 'target' => 1000000, 'efficiency' => 0],
                'overview' => ['total_students' => 0, 'total_defaulters' => 0, 'critical_cases' => 0, 'collection_rate' => 0],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * ✅ FIXED: Get fee type statistics with error handling
     */
    public static function getFeeTypeStats(): array
    {
        try {
            return FeeCategory::with('studentFees')->get()->map(function ($category) {
                $totalAmount = $category->studentFees->sum('amount');
                $totalCollected = $category->studentFees->sum('paid_amount');
                $unpaidCount = $category->studentFees->where('status', 'unpaid')->count();

                return [
                    'name' => $category->name,
                    'total_amount' => $totalAmount,
                    'total_collected' => $totalCollected,
                    'collection_rate' => $totalAmount > 0 ? ($totalCollected / $totalAmount) * 100 : 0,
                    'unpaid_count' => $unpaidCount,
                    'priority' => config("payment_reminders.fee_type_priorities.{$category->category_type}.priority", 'medium'),
                ];
            })->keyBy('name')->toArray();

        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * ✅ FIXED: Get reminder channel statistics with error handling
     */
    public static function getReminderChannelStats(): array
    {
        try {
            if (! class_exists('\App\Models\PaymentReminder')) {
                return [];
            }

            return PaymentReminder::where('sent_at', '>=', now()->subDays(30))
                ->selectRaw('channel, COUNT(*) as sent, SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed')
                ->groupBy('channel')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->channel => [
                        'sent' => $item->sent,
                        'failed' => $item->failed,
                        'success_rate' => $item->sent > 0 ? round((($item->sent - $item->failed) / $item->sent) * 100, 2) : 0,
                    ]];
                })->toArray();

        } catch (\Exception $e) {
            return [];
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
     * ✅ NEW: Get payment performance score for a student
     */
    public static function getPaymentPerformanceScore(Student $student): array
    {
        try {
            $totalFees = $student->studentFees()->count();
            $paidFees = $student->studentFees()->where('status', 'paid')->count();

            $overdueFees = method_exists($student, 'getOverdueFees') ?
                $student->getOverdueFees()->count() :
                $student->studentFees()->where('due_date', '<', now())
                    ->whereIn('status', ['unpaid', 'partial', 'overdue'])->count();

            $score = 100;

            if ($totalFees > 0) {
                $overdueRate = ($overdueFees / $totalFees) * 100;
                $score -= $overdueRate * 0.8;
            }

            $avgDelay = static::getAveragePaymentDelay($student);
            if ($avgDelay > 0) {
                $score -= min($avgDelay * 2, 30);
            }

            $score = max(0, min(100, $score));

            $grade = match (true) {
                $score >= 90 => 'A+',
                $score >= 80 => 'A',
                $score >= 70 => 'B',
                $score >= 60 => 'C',
                default => 'F'
            };

            return [
                'score' => round($score, 1),
                'grade' => $grade,
                'total_fee_components' => $totalFees,
                'paid_components' => $paidFees,
                'overdue_components' => $overdueFees,
                'average_delay' => $avgDelay,
            ];

        } catch (\Exception $e) {
            return [
                'score' => 0,
                'grade' => 'N/A',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * ✅ NEW: Export helper for generating CSV data
     */
    public static function generateCSVHeaders(string $reportType): array
    {
        return match ($reportType) {
            'defaulters' => ['Student Name', 'Enrollment Number', 'Overdue Amount', 'Days Overdue', 'Fee Types', 'Risk Level'],
            'collections' => ['Date', 'Student', 'Amount', 'Payment Method', 'Fee Types', 'Receipt Number'],
            'fee_wise' => ['Fee Category', 'Total Amount', 'Collected', 'Pending', 'Collection Rate'],
            'batch_wise' => ['Batch Name', 'Total Students', 'Defaulters', 'Collection Rate', 'Outstanding Amount'],
            'reminders' => ['Date', 'Student', 'Channel', 'Fee Type', 'Status', 'Amount Due'],
            default => ['Data']
        };
    }

    /**
     * ✅ NEW: Format data row for CSV export
     */
    public static function formatCSVRow(array $data, string $reportType): array
    {
        return match ($reportType) {
            'defaulters' => [
                $data['student_name'] ?? '',
                $data['enrollment_number'] ?? '',
                static::formatAmount($data['overdue_amount'] ?? 0),
                $data['days_overdue'] ?? 0,
                implode(', ', $data['fee_types'] ?? []),
                $data['risk_level'] ?? 'N/A',
            ],
            'collections' => [
                $data['date'] ?? '',
                $data['student_name'] ?? '',
                static::formatAmount($data['amount'] ?? 0),
                $data['payment_method'] ?? '',
                implode(', ', $data['fee_types'] ?? []),
                $data['receipt_number'] ?? '',
            ],
            'fee_wise' => [
                $data['category_name'] ?? '',
                static::formatAmount($data['total_amount'] ?? 0),
                static::formatAmount($data['collected_amount'] ?? 0),
                static::formatAmount($data['pending_amount'] ?? 0),
                round($data['collection_rate'] ?? 0, 2).'%',
            ],
            'batch_wise' => [
                $data['batch_name'] ?? '',
                $data['total_students'] ?? 0,
                $data['defaulters_count'] ?? 0,
                round($data['collection_rate'] ?? 0, 2).'%',
                static::formatAmount($data['outstanding_amount'] ?? 0),
            ],
            'reminders' => [
                $data['sent_date'] ?? '',
                $data['student_name'] ?? '',
                $data['channel'] ?? '',
                $data['fee_type'] ?? '',
                $data['status'] ?? '',
                static::formatAmount($data['amount_due'] ?? 0),
            ],
            default => array_values($data)
        };
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
            return \App\Models\Batch::with(['students.studentFees', 'course'])->get()->map(function ($batch) {
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

    /**
     * ✅ NEW: Get defaulter recovery statistics
     */
    public static function getDefaulterRecoveryStats(): array
    {
        try {
            $thirtyDaysAgo = now()->subDays(30);

            // Students who were defaulters 30 days ago
            $previousDefaulters = StudentFee::where('due_date', '<', $thirtyDaysAgo)
                ->whereIn('status', ['unpaid', 'partial', 'overdue'])
                ->distinct('student_id')
                ->pluck('student_id');

            // Of those, how many have made payments since then
            $recoveredCount = Payment::where('payment_type', 'component')
                ->where('payment_date', '>=', $thirtyDaysAgo)
                ->whereIn('student_id', $previousDefaulters)
                ->distinct('student_id')
                ->count();

            $recoveryRate = $previousDefaulters->count() > 0 ?
                ($recoveredCount / $previousDefaulters->count()) * 100 : 0;

            return [
                'previous_defaulters' => $previousDefaulters->count(),
                'recovered_students' => $recoveredCount,
                'recovery_rate' => round($recoveryRate, 2),
                'still_defaulting' => $previousDefaulters->count() - $recoveredCount,
                'period' => '30 days',
            ];

        } catch (\Exception $e) {
            return [
                'previous_defaulters' => 0,
                'recovered_students' => 0,
                'recovery_rate' => 0,
                'still_defaulting' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * ✅ NEW: Get top defaulters list
     */
    public static function getTopDefaulters(int $limit = 10): array
    {
        try {
            return Student::whereHas('studentFees', function ($q) {
                $q->where('due_date', '<', now())
                    ->whereIn('status', ['unpaid', 'partial', 'overdue']);
            })
                ->with(['batch.course', 'studentFees' => function ($q) {
                    $q->where('due_date', '<', now())
                        ->whereIn('status', ['unpaid', 'partial', 'overdue'])
                        ->with('feeCategory');
                }])
                ->get()
                ->map(function ($student) {
                    $overdueFees = $student->studentFees;
                    $totalOverdue = $overdueFees->sum(function ($fee) {
                        return method_exists($fee, 'getRemainingAmount') ?
                            $fee->getRemainingAmount() :
                            ($fee->amount - $fee->paid_amount);
                    });

                    $oldestDue = $overdueFees->min('due_date');
                    $daysSinceOldest = $oldestDue ? now()->diffInDays(Carbon::parse($oldestDue)) : 0;

                    return [
                        'id' => $student->id,
                        'name' => $student->name,
                        'enrollment_number' => $student->enrollment_number,
                        'batch' => $student->batch->name ?? 'N/A',
                        'course' => $student->batch->course->name ?? 'N/A',
                        'total_overdue' => $totalOverdue,
                        'days_overdue' => $daysSinceOldest,
                        'overdue_count' => $overdueFees->count(),
                        'risk_score' => static::getStudentRiskScore($student)['score'],
                        'category' => static::categorizeDefaulter($totalOverdue, $daysSinceOldest, $overdueFees->count()),
                    ];
                })
                ->sortByDesc('total_overdue')
                ->take($limit)
                ->values()
                ->toArray();

        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * ✅ NEW: Generate payment reminder schedule
     */
    public static function generateReminderSchedule(StudentFee $studentFee): array
    {
        try {
            $dueDate = Carbon::parse($studentFee->due_date);
            $today = now();

            $schedule = [];
            $reminderConfig = config('payment_reminders.schedule', [
                'before_due_days' => [7, 3, 1],
                'after_due_days' => [1, 7, 15, 30],
            ]);

            // Before due date reminders
            foreach ($reminderConfig['before_due_days'] as $days) {
                $reminderDate = $dueDate->copy()->subDays($days);
                if ($reminderDate->isFuture()) {
                    $schedule[] = [
                        'date' => $reminderDate,
                        'type' => 'pre_due',
                        'days_to_due' => $days,
                        'urgency' => $days <= 1 ? 'high' : 'medium',
                    ];
                }
            }

            // After due date reminders
            foreach ($reminderConfig['after_due_days'] as $days) {
                $reminderDate = $dueDate->copy()->addDays($days);
                $schedule[] = [
                    'date' => $reminderDate,
                    'type' => 'overdue',
                    'days_overdue' => $days,
                    'urgency' => $days >= 30 ? 'critical' : ($days >= 7 ? 'high' : 'medium'),
                ];
            }

            return $schedule;

        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * ✅ NEW: Validate payment data integrity
     */
    public static function validatePaymentIntegrity(): array
    {
        try {
            $issues = [];

            // Check for payments without corresponding student fees
            $orphanedPayments = Payment::where('payment_type', 'component')
                ->whereDoesntHave('componentItems.studentFee')
                ->count();

            if ($orphanedPayments > 0) {
                $issues[] = "Found {$orphanedPayments} component payments without valid student fees";
            }

            // Check for overpayments
            $overpayments = StudentFee::whereRaw('paid_amount > (amount - concession_amount)')
                ->count();

            if ($overpayments > 0) {
                $issues[] = "Found {$overpayments} student fees with overpayments";
            }

            // Check for negative amounts
            $negativeAmounts = StudentFee::where('amount', '<', 0)->count();
            if ($negativeAmounts > 0) {
                $issues[] = "Found {$negativeAmounts} student fees with negative amounts";
            }

            return [
                'status' => empty($issues) ? 'clean' : 'issues_found',
                'issues' => $issues,
                'total_issues' => count($issues),
                'checked_at' => now()->toDateTimeString(),
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
                'checked_at' => now()->toDateTimeString(),
            ];
        }
    }
}
