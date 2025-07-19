<?php

namespace App\Helpers;

use App\Models\Student;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\FeeCategory;
use App\Models\PaymentReminder;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PaymentHelper
{
    /**
     * Get payment priority based on fee type and overdue days
     */
    public static function getPaymentPriority(string $feeType, int $overdueDays): string
    {
        $config = config('payment_reminders.fee_type_priorities', []);
        $feeConfig = $config[$feeType] ?? ['priority' => 'medium'];
        
        $basePriority = $feeConfig['priority'];
        
        // Escalate priority based on overdue days
        if ($overdueDays > 60) {
            return 'critical';
        } elseif ($overdueDays > 30) {
            return match($basePriority) {
                'low' => 'medium',
                'medium' => 'high',
                'high' => 'critical',
                default => 'high'
            };
        } elseif ($overdueDays > 15) {
            return match($basePriority) {
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
     */
    public static function calculateLateFee(float $amount, int $overdueDays): float
    {
        $lateFeePer = (float) setting('late_fee_percentage', 5);
        $graceDays = (int) setting('late_fee_grace_days', 7);
        
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
     */
    public static function getNextReminderDate(int $reminderCount, Carbon $dueDate): Carbon
    {
        $schedule = config('payment_reminders.schedule', []);
        
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
     */
    public static function formatAmount(float $amount): string
    {
        $currency = setting('currency', 'INR');
        $symbol = match($currency) {
            'INR' => '₹',
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'AED' => 'AED ',
            'SAR' => 'SAR ',
            default => '₹'
        };
        
        // Format with appropriate decimal places
        if ($amount >= 10000000) { // 1 crore
            return $symbol . number_format($amount / 10000000, 2) . ' Cr';
        } elseif ($amount >= 100000) { // 1 lakh
            return $symbol . number_format($amount / 100000, 2) . ' L';
        } elseif ($amount >= 1000) { // 1 thousand
            return $symbol . number_format($amount / 1000, 1) . 'K';
        } else {
            return $symbol . number_format($amount, 2);
        }
    }

    /**
     * Get detailed student risk score based on payment history
     */
    public static function getStudentRiskScore(Student $student): array
    {
        $totalInvoices = $student->invoices()->count();
        $overdueInvoices = $student->invoices()
            ->where('due_date', '<', now())
            ->where('status', 'unpaid')
            ->count();
        $totalOverdueAmount = $student->invoices()
            ->where('due_date', '<', now())
            ->where('status', 'unpaid')
            ->sum('due_amount');
        $avgPaymentDelay = static::getAveragePaymentDelay($student);
        
        $score = 0;
        $factors = [];
        $recommendations = [];
        
        // Factor 1: Overdue ratio (40% weight)
        if ($totalInvoices > 0) {
            $overdueRatio = $overdueInvoices / $totalInvoices;
            $overdueScore = $overdueRatio * 40;
            $score += $overdueScore;
            
            if ($overdueRatio > 0.7) {
                $factors[] = "Very high overdue ratio: " . round($overdueRatio * 100, 1) . "%";
                $recommendations[] = "Immediate intervention required";
            } elseif ($overdueRatio > 0.4) {
                $factors[] = "High overdue ratio: " . round($overdueRatio * 100, 1) . "%";
                $recommendations[] = "Enhanced monitoring needed";
            } elseif ($overdueRatio > 0.2) {
                $factors[] = "Moderate overdue ratio: " . round($overdueRatio * 100, 1) . "%";
            }
        }
        
        // Factor 2: Amount factor (30% weight)
        if ($totalOverdueAmount > 50000) {
            $score += 30;
            $factors[] = "Very high overdue amount: " . static::formatAmount($totalOverdueAmount);
            $recommendations[] = "Consider payment plan arrangement";
        } elseif ($totalOverdueAmount > 25000) {
            $score += 20;
            $factors[] = "High overdue amount: " . static::formatAmount($totalOverdueAmount);
            $recommendations[] = "Escalate to management";
        } elseif ($totalOverdueAmount > 10000) {
            $score += 10;
            $factors[] = "Moderate overdue amount: " . static::formatAmount($totalOverdueAmount);
        }
        
        // Factor 3: Payment behavior (20% weight)
        if ($avgPaymentDelay > 30) {
            $score += 20;
            $factors[] = "Consistently late payments (avg " . $avgPaymentDelay . " days)";
            $recommendations[] = "Setup automated reminders";
        } elseif ($avgPaymentDelay > 15) {
            $score += 10;
            $factors[] = "Moderate payment delays (avg " . $avgPaymentDelay . " days)";
        }
        
        // Factor 4: Recent activity (10% weight)
        $recentPayments = $student->payments()
            ->where('payment_date', '>=', now()->subMonths(6))
            ->count();
        if ($recentPayments == 0 && $totalInvoices > 0) {
            $score += 10;
            $factors[] = "No recent payments in last 6 months";
            $recommendations[] = "Contact student/parent immediately";
        }
        
        // Determine risk level
        $riskLevel = match(true) {
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
            'total_invoices' => $totalInvoices,
            'overdue_invoices' => $overdueInvoices,
            'overdue_amount' => $totalOverdueAmount,
            'avg_payment_delay' => $avgPaymentDelay
        ];
    }

    /**
     * Get average payment delay for a student
     */
    public static function getAveragePaymentDelay(Student $student): float
    {
        $payments = $student->payments()
            ->with('invoice')
            ->whereHas('invoice')
            ->get();
            
        if ($payments->isEmpty()) {
            return 0;
        }
        
        $totalDelayDays = 0;
        $validPayments = 0;
        
        foreach ($payments as $payment) {
            if ($payment->invoice && $payment->invoice->due_date) {
                $delayDays = Carbon::parse($payment->payment_date)
                    ->diffInDays(Carbon::parse($payment->invoice->due_date), false);
                
                if ($delayDays > 0) { // Only count late payments
                    $totalDelayDays += $delayDays;
                    $validPayments++;
                }
            }
        }
        
        return $validPayments > 0 ? round($totalDelayDays / $validPayments, 1) : 0;
    }

    /**
     * Generate payment reminder message template
     */
    public static function generateReminderMessage(string $type, array $data): string
    {
        $templates = [
            'upcoming_due_email' => "Dear {name},\n\nThis is a friendly reminder that your {fee_type} payment of {amount} is due on {due_date}.\n\nPlease make the payment at your earliest convenience to avoid any late fees.\n\nPayment can be made:\n- Online through our student portal\n- At the college accounts office\n- Through bank transfer\n\nFor any queries, contact our accounts department at {contact_number}.\n\nBest regards,\n{college_name}",
            
            'upcoming_due_sms' => "Dear {name}, your {fee_type} payment of {amount} is due on {due_date}. Please pay to avoid late fees. For queries: {contact_number} - {college_name}",
            
            'overdue_email' => "Dear {name},\n\nYour {fee_type} payment of {amount} was due on {due_date} and is now {days_overdue} days overdue.\n\nOutstanding Amount: {amount}\nLate Fee: {late_fee}\nTotal Due: {total_due}\n\nPlease make the payment immediately to avoid:\n- Additional late charges\n- Academic restrictions\n- Library access suspension\n\nFor immediate assistance, contact our accounts department at {contact_number}.\n\nUrgent Action Required,\n{college_name}",
            
            'overdue_sms' => "URGENT: {name}, your {fee_type} payment of {amount} is {days_overdue} days overdue. Total due: {total_due}. Pay immediately to avoid restrictions. Contact: {contact_number} - {college_name}",
            
            'escalation_email' => "Dear {name},\n\nDespite multiple reminders, your {fee_type} payment of {amount} remains unpaid for {days_overdue} days.\n\nThis matter has been escalated to the management. The following actions will be taken if payment is not received within 7 days:\n\n- Academic suspension\n- Library access revocation\n- Exam participation restriction\n- Certificate withholding\n\nContact the Principal's office immediately at {contact_number}.\n\nFinal Warning,\n{college_name} Administration",
            
            'final_notice_email' => "FINAL NOTICE\n\nDear {name},\n\nThis is your FINAL NOTICE for the unpaid {fee_type} amount of {amount}, overdue by {days_overdue} days.\n\nTotal Outstanding: {total_due}\n\nIf payment is not received within 48 hours, the following actions will be implemented:\n\n✗ Academic suspension\n✗ Library access revoked\n✗ Exam participation denied\n✗ Certificate/transcript withholding\n✗ Legal action initiation\n\nContact administration immediately: {contact_number}\n\n{college_name} Administration\n\nThis is an automated system-generated notice.",
            
            'whatsapp_reminder' => "🔔 Payment Reminder\n\nHello {name},\n\nYour {fee_type} payment of {amount} is due on {due_date}.\n\n💳 Pay online: [Payment Link]\n🏢 Visit: Accounts Office\n📞 Query: {contact_number}\n\nThank you!\n{college_name}",
            
            'parent_notification' => "Dear Parent/Guardian,\n\nThis is to inform you that {name}'s {fee_type} payment of {amount} is overdue by {days_overdue} days.\n\nWe request your immediate attention to clear the outstanding dues to avoid academic disruptions.\n\nContact us: {contact_number}\n\nRegards,\n{college_name}"
        ];
        
        $template = $templates[$type] ?? $templates['overdue_sms'];
        
        // Add calculated fields to data
        if (isset($data['amount']) && isset($data['days_overdue'])) {
            $data['late_fee'] = static::formatAmount(
                static::calculateLateFee((float)$data['amount'], (int)$data['days_overdue'])
            );
            $data['total_due'] = static::formatAmount(
                (float)$data['amount'] + static::calculateLateFee((float)$data['amount'], (int)$data['days_overdue'])
            );
        }
        
        // Add default values
        $data['college_name'] = $data['college_name'] ?? setting('app_name', 'College Management System');
        $data['contact_number'] = $data['contact_number'] ?? setting('contact_phone', '');
        
        // Replace placeholders
        foreach ($data as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }
        
        return $template;
    }

    /**
     * Get collection efficiency for a specific period
     */
    public static function getCollectionEfficiency(Carbon $startDate, Carbon $endDate): array
    {
        $totalInvoiced = Invoice::whereBetween('issue_date', [$startDate, $endDate])
            ->sum('total_amount');
        $totalCollected = Payment::whereBetween('payment_date', [$startDate, $endDate])
            ->sum('amount');
        $totalPending = Invoice::whereBetween('issue_date', [$startDate, $endDate])
            ->sum('due_amount');
        $totalConcessions = Invoice::whereBetween('issue_date', [$startDate, $endDate])
            ->sum('concession_amount');
        
        $efficiency = $totalInvoiced > 0 ? ($totalCollected / $totalInvoiced) * 100 : 0;
        $netCollectable = $totalInvoiced - $totalConcessions;
        $netEfficiency = $netCollectable > 0 ? ($totalCollected / $netCollectable) * 100 : 0;
        
        return [
            'period' => [
                'start_date' => $startDate->format('d-m-Y'),
                'end_date' => $endDate->format('d-m-Y'),
                'days' => $startDate->diffInDays($endDate) + 1
            ],
            'amounts' => [
                'total_invoiced' => $totalInvoiced,
                'total_collected' => $totalCollected,
                'total_pending' => $totalPending,
                'total_concessions' => $totalConcessions,
                'net_collectable' => $netCollectable
            ],
            'formatted_amounts' => [
                'total_invoiced' => static::formatAmount($totalInvoiced),
                'total_collected' => static::formatAmount($totalCollected),
                'total_pending' => static::formatAmount($totalPending),
                'total_concessions' => static::formatAmount($totalConcessions),
                'net_collectable' => static::formatAmount($netCollectable)
            ],
            'percentages' => [
                'efficiency_percentage' => round($efficiency, 2),
                'net_efficiency_percentage' => round($netEfficiency, 2),
                'collection_rate' => round($efficiency, 2),
                'pending_percentage' => round(($totalPending / $totalInvoiced) * 100, 2)
            ]
        ];
    }

    /**
     * Get payment statistics for dashboard
     */
    public static function getDashboardStats(): array
    {
        $today = now();
        $thisMonth = $today->startOfMonth();
        $lastMonth = $today->copy()->subMonth();
        
        return [
            'today' => [
                'collections' => Payment::whereDate('payment_date', $today)->sum('amount'),
                'reminders_sent' => PaymentReminder::whereDate('sent_at', $today)->count(),
                'new_defaulters' => static::getNewDefaultersCount($today)
            ],
            'this_month' => [
                'collections' => Payment::where('payment_date', '>=', $thisMonth)->sum('amount'),
                'target' => setting('monthly_collection_target', 1000000),
                'efficiency' => static::getCollectionEfficiency($thisMonth, $today)['percentages']['efficiency_percentage']
            ],
            'overview' => [
                'total_students' => Student::where('status', 'active')->count(),
                'total_defaulters' => static::getTotalDefaultersCount(),
                'critical_cases' => static::getCriticalCasesCount(),
                'collection_rate' => static::getOverallCollectionRate()
            ]
        ];
    }

    /**
     * Get fee type statistics
     */
    public static function getFeeTypeStats(): array
    {
        $feeCategories = FeeCategory::all();
        $stats = [];
        
        foreach ($feeCategories as $category) {
            $totalInvoiced = Invoice::whereHas('items.feeCategory', function($q) use ($category) {
                $q->where('id', $category->id);
            })->sum('total_amount');
            
            $totalCollected = Payment::whereHas('invoice.items.feeCategory', function($q) use ($category) {
                $q->where('id', $category->id);
            })->sum('amount');
            
            $unpaidCount = Invoice::whereHas('items.feeCategory', function($q) use ($category) {
                $q->where('id', $category->id);
            })->where('status', 'unpaid')->count();
            
            $stats[$category->category_type] = [
                'name' => $category->name,
                'total_invoiced' => $totalInvoiced,
                'total_collected' => $totalCollected,
                'collection_rate' => $totalInvoiced > 0 ? ($totalCollected / $totalInvoiced) * 100 : 0,
                'unpaid_count' => $unpaidCount,
                'priority' => config("payment_reminders.fee_type_priorities.{$category->category_type}.priority", 'medium')
            ];
        }
        
        return $stats;
    }

    /**
     * Get reminder channel statistics
     */
    public static function getReminderChannelStats(): array
    {
        $channels = ['email', 'sms', 'whatsapp', 'phone_call'];
        $stats = [];
        
        foreach ($channels as $channel) {
            $sent = PaymentReminder::where('channel', $channel)
                ->where('status', 'sent')
                ->whereDate('sent_at', '>=', now()->subDays(30))
                ->count();
                
            $delivered = PaymentReminder::where('channel', $channel)
                ->where('status', 'delivered')
                ->whereDate('delivered_at', '>=', now()->subDays(30))
                ->count();
                
            $failed = PaymentReminder::where('channel', $channel)
                ->where('status', 'failed')
                ->whereDate('created_at', '>=', now()->subDays(30))
                ->count();
            
            $stats[$channel] = [
                'sent' => $sent,
                'delivered' => $delivered,
                'failed' => $failed,
                'delivery_rate' => $sent > 0 ? ($delivered / $sent) * 100 : 0,
                'failure_rate' => $sent > 0 ? ($failed / $sent) * 100 : 0
            ];
        }
        
        return $stats;
    }

    /**
     * Categorize defaulter based on amount and days
     */
    public static function categorizeDefaulter(float $amount, int $days, int $invoiceCount): string
    {
        $categories = config('payment_reminders.defaulter_categories');
        
        foreach (['chronic', 'severe', 'moderate', 'mild'] as $category) {
            $config = $categories[$category];
            if ($days >= $config['days'] || $amount >= $config['amount_threshold']) {
                return $category;
            }
        }
        
        return 'mild';
    }

    /**
     * Get payment behavior insights for a student
     */
    public static function getPaymentBehaviorInsights(Student $student): array
    {
        $payments = $student->payments()->with('invoice')->get();
        $invoices = $student->invoices()->get();
        
        if ($payments->isEmpty()) {
            return [
                'behavior_type' => 'no_payment_history',
                'insights' => ['No payment history available'],
                'recommendations' => ['Monitor initial payment behavior']
            ];
        }
        
        $earlyPayments = 0;
        $latePayments = 0;
        $onTimePayments = 0;
        $totalDelayDays = 0;
        
        foreach ($payments as $payment) {
            if ($payment->invoice) {
                $daysDiff = Carbon::parse($payment->payment_date)
                    ->diffInDays(Carbon::parse($payment->invoice->due_date), false);
                
                if ($daysDiff < 0) {
                    $earlyPayments++;
                } elseif ($daysDiff == 0) {
                    $onTimePayments++;
                } else {
                    $latePayments++;
                    $totalDelayDays += $daysDiff;
                }
            }
        }
        
        $totalPayments = $payments->count();
        $avgDelay = $latePayments > 0 ? $totalDelayDays / $latePayments : 0;
        
        // Determine behavior type
        $earlyRate = ($earlyPayments / $totalPayments) * 100;
        $lateRate = ($latePayments / $totalPayments) * 100;
        
        $behaviorType = match(true) {
            $earlyRate >= 70 => 'early_payer',
            $lateRate >= 70 => 'chronic_late_payer',
            $lateRate >= 40 => 'frequent_late_payer',
            $avgDelay > 15 => 'delayed_payer',
            default => 'regular_payer'
        };
        
        $insights = [];
        $recommendations = [];
        
        switch ($behaviorType) {
            case 'early_payer':
                $insights[] = "Pays {$earlyRate}% of fees before due date";
                $insights[] = "Excellent payment discipline";
                $recommendations[] = "Consider offering early payment discounts";
                break;
                
            case 'chronic_late_payer':
                $insights[] = "Pays {$lateRate}% of fees late (avg {$avgDelay} days)";
                $insights[] = "Requires immediate intervention";
                $recommendations[] = "Setup aggressive reminder schedule";
                $recommendations[] = "Consider payment plan arrangement";
                break;
                
            case 'frequent_late_payer':
                $insights[] = "Pays {$lateRate}% of fees late";
                $insights[] = "Shows concerning payment pattern";
                $recommendations[] = "Increase reminder frequency";
                $recommendations[] = "Parent/guardian involvement needed";
                break;
                
            case 'delayed_payer':
                $insights[] = "Average payment delay: {$avgDelay} days";
                $insights[] = "Consistent but delayed payment pattern";
                $recommendations[] = "Earlier reminder scheduling";
                break;
                
            default:
                $insights[] = "Regular payment behavior";
                $insights[] = "Maintains reasonable payment schedule";
                $recommendations[] = "Continue standard reminder schedule";
        }
        
        return [
            'behavior_type' => $behaviorType,
            'statistics' => [
                'total_payments' => $totalPayments,
                'early_payments' => $earlyPayments,
                'on_time_payments' => $onTimePayments,
                'late_payments' => $latePayments,
                'average_delay_days' => round($avgDelay, 1),
                'early_rate' => round($earlyRate, 1),
                'late_rate' => round($lateRate, 1)
            ],
            'insights' => $insights,
            'recommendations' => $recommendations
        ];
    }

    /**
     * Generate payment performance score for a student
     */
    public static function getPaymentPerformanceScore(Student $student): array
    {
        $invoices = $student->invoices()->count();
        $paidInvoices = $student->invoices()->where('status', 'paid')->count();
        $overdueInvoices = $student->invoices()
            ->where('due_date', '<', now())
            ->where('status', 'unpaid')
            ->count();
        
        $score = 100; // Start with perfect score
        
        // Deduct points for overdue invoices
        if ($invoices > 0) {
            $overdueRate = ($overdueInvoices / $invoices) * 100;
            $score -= $overdueRate * 0.8; // Heavy penalty for overdue
        }
        
        // Deduct points for payment delays
        $avgDelay = static::getAveragePaymentDelay($student);
        if ($avgDelay > 0) {
            $score -= min($avgDelay * 2, 30); // Max 30 points deduction
        }
        
        // Bonus points for early payments
        $behavior = static::getPaymentBehaviorInsights($student);
        if (isset($behavior['statistics']['early_rate']) && $behavior['statistics']['early_rate'] > 50) {
            $score += 10; // Bonus for early payers
        }
        
        $score = max(0, min(100, $score)); // Keep score between 0-100
        
        $grade = match(true) {
            $score >= 90 => 'A+',
            $score >= 80 => 'A',
            $score >= 70 => 'B',
            $score >= 60 => 'C',
            $score >= 50 => 'D',
            default => 'F'
        };
        
        return [
            'score' => round($score, 1),
            'grade' => $grade,
            'total_invoices' => $invoices,
            'paid_invoices' => $paidInvoices,
            'overdue_invoices' => $overdueInvoices,
            'average_delay' => $avgDelay
        ];
    }

    /**
     * Get seasonal payment trends
     */
    public static function getSeasonalTrends(): array
    {
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthName = $month->format('M Y');
            
            $collections = Payment::whereYear('payment_date', $month->year)
                ->whereMonth('payment_date', $month->month)
                ->sum('amount');
                
            $remindersSent = PaymentReminder::whereYear('sent_at', $month->year)
                ->whereMonth('sent_at', $month->month)
                ->where('status', 'sent')
                ->count();
            
            $months[$monthName] = [
                'collections' => $collections,
                'reminders_sent' => $remindersSent,
                'formatted_collections' => static::formatAmount($collections)
            ];
        }
        
        return $months;
    }

    /**
     * Get batch-wise payment performance
     */
    public static function getBatchWisePerformance(): array
    {
        $batches = \App\Models\Batch::with('course')->get();
        $performance = [];
        
        foreach ($batches as $batch) {
            $students = $batch->students();
            $totalStudents = $students->count();
            
            if ($totalStudents == 0) continue;
            
            $totalInvoiced = Invoice::whereIn('student_id', $students->pluck('id'))->sum('total_amount');
            $totalCollected = Payment::whereIn('student_id', $students->pluck('id'))->sum('amount');
            $defaulters = $students->whereHas('invoices', function($q) {
                $q->where('due_date', '<', now())->where('status', 'unpaid');
            })->count();
            
            $collectionRate = $totalInvoiced > 0 ? ($totalCollected / $totalInvoiced) * 100 : 0;
            $defaulterRate = ($defaulters / $totalStudents) * 100;
            
            $performance[] = [
                'batch' => $batch,
                'total_students' => $totalStudents,
                'total_invoiced' => $totalInvoiced,
                'total_collected' => $totalCollected,
                'collection_rate' => round($collectionRate, 2),
                'defaulters' => $defaulters,
                'defaulter_rate' => round($defaulterRate, 2),
                'performance_grade' => static::getPerformanceGrade($collectionRate, $defaulterRate)
            ];
        }
        
        // Sort by collection rate descending
        usort($performance, function($a, $b) {
            return $b['collection_rate'] <=> $a['collection_rate'];
        });
        
        return $performance;
    }

    /**
     * Get performance grade based on collection and defaulter rates
     */
    private static function getPerformanceGrade(float $collectionRate, float $defaulterRate): string
    {
        if ($collectionRate >= 95 && $defaulterRate <= 5) {
            return 'Excellent';
        } elseif ($collectionRate >= 85 && $defaulterRate <= 15) {
            return 'Good';
        } elseif ($collectionRate >= 70 && $defaulterRate <= 25) {
            return 'Average';
        } elseif ($collectionRate >= 50 && $defaulterRate <= 40) {
            return 'Below Average';
        } else {
            return 'Poor';
        }
    }

    /**
     * Generate payment forecast based on historical data
     */
    public static function generatePaymentForecast(int $months = 3): array
    {
        $historicalData = [];
        
        // Get last 12 months of data
        for ($i = 12; $i >= 1; $i--) {
            $month = now()->subMonths($i);
            $collections = Payment::whereYear('payment_date', $month->year)
                ->whereMonth('payment_date', $month->month)
                ->sum('amount');
            $historicalData[] = $collections;
        }
        
        // Simple linear regression for forecasting
        $forecast = [];
        $trend = static::calculateTrend($historicalData);
        $lastValue = end($historicalData);
        
        for ($i = 1; $i <= $months; $i++) {
            $forecastValue = $lastValue + ($trend * $i);
            $forecast[] = max(0, $forecastValue); // Ensure non-negative
        }
        
        return [
            'historical_data' => $historicalData,
            'forecast' => $forecast,
            'trend' => $trend > 0 ? 'increasing' : ($trend < 0 ? 'decreasing' : 'stable'),
            'confidence' => static::calculateForecastConfidence($historicalData)
        ];
    }

    /**
     * Calculate trend from historical data
     */
    private static function calculateTrend(array $data): float
    {
        $n = count($data);
        if ($n < 2) return 0;
        
        $sumX = array_sum(range(1, $n));
        $sumY = array_sum($data);
        $sumXY = 0;
        $sumX2 = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $x = $i + 1;
            $y = $data[$i];
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        }
        
        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        return $slope;
    }

    /**
     * Calculate forecast confidence based on historical variance
     */
    private static function calculateForecastConfidence(array $data): string
    {
        $mean = array_sum($data) / count($data);
        $variance = array_sum(array_map(function($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $data)) / count($data);
        
        $coefficientOfVariation = $mean > 0 ? sqrt($variance) / $mean : 1;
        
        if ($coefficientOfVariation < 0.1) {
            return 'high';
        } elseif ($coefficientOfVariation < 0.3) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    // Helper methods for dashboard stats
    private static function getNewDefaultersCount(Carbon $date): int
    {
        return Student::whereHas('invoices', function($q) use ($date) {
            $q->where('due_date', $date->format('Y-m-d'))
              ->where('status', 'unpaid');
        })->count();
    }

    private static function getTotalDefaultersCount(): int
    {
        return Student::whereHas('invoices', function($q) {
            $q->where('due_date', '<', now())
              ->where('status', 'unpaid');
        })->count();
    }

    private static function getCriticalCasesCount(): int
    {
        return Student::whereHas('invoices', function($q) {
            $q->where('due_date', '<', now()->subDays(60))
              ->where('status', 'unpaid');
        })->count();
    }

    private static function getOverallCollectionRate(): float
    {
        $totalInvoiced = Invoice::sum('total_amount');
        $totalCollected = Payment::sum('amount');
        
        return $totalInvoiced > 0 ? ($totalCollected / $totalInvoiced) * 100 : 0;
    }

    /**
     * Export helper for generating CSV data
     */
    public static function generateCSVHeaders(string $reportType): array
    {
        return match($reportType) {
            'defaulters' => [
                'Student Name', 'Enrollment Number', 'Course', 'Batch', 
                'Overdue Amount', 'Days Overdue', 'Category', 'Fee Types', 
                'Contact', 'Last Payment Date', 'Risk Score'
            ],
            'collections' => [
                'Date', 'Student', 'Enrollment', 'Amount', 'Payment Method', 
                'Invoice Number', 'Fee Type', 'Batch', 'Receipt Number'
            ],
            'reminders' => [
                'Student Name', 'Enrollment', 'Reminder Type', 'Channel', 
                'Sent Date', 'Status', 'Fee Type', 'Amount', 'Due Date'
            ],
            'analytics' => [
                'Student', 'Total Invoices', 'Paid Invoices', 'Overdue Invoices',
                'Total Amount', 'Paid Amount', 'Overdue Amount', 'Performance Score',
                'Risk Level', 'Average Delay Days'
            ],
            default => ['Data']
        };
    }

    /**
     * Format data row for CSV export
     */
    public static function formatCSVRow(array $data, string $reportType): array
    {
        return match($reportType) {
            'defaulters' => [
                $data['student_name'] ?? '',
                $data['enrollment_number'] ?? '',
                $data['course'] ?? '',
                $data['batch'] ?? '',
                static::formatAmount($data['overdue_amount'] ?? 0),
                $data['days_overdue'] ?? 0,
                ucfirst($data['category'] ?? ''),
                implode(', ', $data['fee_types'] ?? []),
                $data['contact'] ?? '',
                $data['last_payment_date'] ?? 'N/A',
                $data['risk_score'] ?? 'N/A'
            ],
            'collections' => [
                $data['date'] ?? '',
                $data['student_name'] ?? '',
                $data['enrollment_number'] ?? '',
                static::formatAmount($data['amount'] ?? 0),
                $data['payment_method'] ?? '',
                $data['invoice_number'] ?? '',
                $data['fee_type'] ?? '',
                $data['batch'] ?? '',
                $data['receipt_number'] ?? ''
            ],
            default => array_values($data)
        };
    }
}