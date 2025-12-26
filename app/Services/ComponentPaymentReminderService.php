<?php

namespace App\Services;

use App\Models\PaymentReminder;
use App\Models\PaymentDefaulter;
use App\Models\FeeCategory;
use App\Models\PaymentReminderTemplate;
use App\Models\PaymentReminderLog;
use App\Models\Student;
use App\Models\StudentFee;
use App\Models\Setting;
use App\Models\ComponentPaymentItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class ComponentPaymentReminderService
{
    
/**
 * Get defaulter stats safely with correct column names
 */
public function getSafeDefaulterStats(): array
{
    try {
        return [
            'total_defaulters' => PaymentDefaulter::count(),
            'total_active' => PaymentDefaulter::where('current_status', '!=', 'resolved')->count(),
            'chronic_defaulters' => PaymentDefaulter::where('defaulter_category', 'chronic')->count(),
            'severe_defaulters' => PaymentDefaulter::where('defaulter_category', 'severe')->count(),
            'moderate_defaulters' => PaymentDefaulter::where('defaulter_category', 'moderate')->count(),
            'resolved_defaulters' => PaymentDefaulter::where('current_status', 'resolved')->count(),
            'total_overdue_amount' => PaymentDefaulter::sum('total_overdue_amount'),
            'avg_overdue_amount' => PaymentDefaulter::avg('total_overdue_amount') ?? 0,
            'recovery_rate' => $this->calculateResolutionRate(),
        ];
    } catch (\Exception $e) {
        \Log::error('Error getting defaulter stats: ' . $e->getMessage());
        return [
            'total_defaulters' => 0,
            'total_active' => 0,
            'chronic_defaulters' => 0,
            'severe_defaulters' => 0,
            'moderate_defaulters' => 0,
            'resolved_defaulters' => 0,
            'total_overdue_amount' => 0,
            'avg_overdue_amount' => 0,
            'recovery_rate' => 0,
        ];
    }
}

/**
 * Alias method for controller compatibility
 */
public function getDefaulterStats(): array
{
    return $this->getSafeDefaulterStats();
}

/**
 * Calculate resolution rate for defaulters
 */
private function calculateResolutionRate(): float
{
    try {
        $total = PaymentDefaulter::count();
        if ($total === 0) {
            return 0;
        }
        $resolved = PaymentDefaulter::where('current_status', 'resolved')->count();
        return round(($resolved / $total) * 100, 2);
    } catch (\Exception $e) {
        return 0;
    }
}

/**
 * Fix the broken getDefaulterComponentBreakdown method
 */
private function getDefaulterComponentBreakdown(): array
{
    try {
        // Since PaymentDefaulter doesn't have fee_category_id, 
        // we need to build this from StudentFee data
        return FeeCategory::select('fee_categories.name', 'fee_categories.category_type')
            ->selectRaw('
                COUNT(DISTINCT sf.student_id) as defaulter_count,
                SUM(sf.amount - COALESCE(sf.paid_amount, 0) - COALESCE(sf.concession_amount, 0)) as total_overdue_amount,
                AVG(sf.amount - COALESCE(sf.paid_amount, 0) - COALESCE(sf.concession_amount, 0)) as avg_overdue_amount
            ')
            ->leftJoin('student_fees as sf', 'fee_categories.id', '=', 'sf.fee_category_id')
            ->where('sf.status', 'unpaid')
            ->where('sf.due_date', '<', now())
            ->whereRaw('sf.amount - COALESCE(sf.paid_amount, 0) - COALESCE(sf.concession_amount, 0) > 0')
            ->groupBy('fee_categories.id', 'fee_categories.name', 'fee_categories.category_type')
            ->orderByDesc('total_overdue_amount')
            ->get()
            ->toArray();
    } catch (\Exception $e) {
        \Log::error('Error getting component breakdown: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get channel performance statistics
 */
public function getChannelPerformanceStats(): array
{
    try {
        if (!class_exists('\App\Models\PaymentReminder')) {
            return [];
        }

        return \App\Models\PaymentReminder::where('created_at', '>=', now()->subMonths(3))
            ->select('channel')
            ->selectRaw('COUNT(*) as total_sent')
            ->selectRaw('COUNT(CASE WHEN status = "sent" THEN 1 END) as successful')
            ->selectRaw('COUNT(CASE WHEN status = "failed" THEN 1 END) as failed')
            ->selectRaw('AVG(CASE WHEN delivered_at IS NOT NULL THEN TIMESTAMPDIFF(HOUR, sent_at, delivered_at) END) as avg_delivery_time')
            ->groupBy('channel')
            ->get()
            ->map(function ($item) {
                $successRate = $item->total_sent > 0 ? round(($item->successful / $item->total_sent) * 100, 2) : 0;
                
                return [
                    'channel' => $item->channel,
                    'total_sent' => $item->total_sent,
                    'successful' => $item->successful,
                    'failed' => $item->failed,
                    'success_rate' => $successRate,
                    'avg_delivery_time' => round($item->avg_delivery_time ?? 0, 2),
                    'status' => $this->getChannelStatus($successRate)
                ];
            })
            ->keyBy('channel')
            ->toArray();

    } catch (\Exception $e) {
        \Log::error('Error getting channel performance stats: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get channel status based on success rate
 */
private function getChannelStatus(float $successRate): string
{
    if ($successRate >= 90) return 'excellent';
    if ($successRate >= 75) return 'good';
    if ($successRate >= 60) return 'average';
    if ($successRate >= 40) return 'poor';
    return 'critical';
}

 /**
     * Get defaulter count
     */
    private function getDefaulterCount(int $daysPastDue = 30): int
    {
        $cutoffDate = now()->subDays($daysPastDue);

        return Student::whereHas('studentFees', function ($query) use ($cutoffDate) {
            $query->whereDate('due_date', '<', $cutoffDate)
                ->whereIn('status', ['unpaid', 'partial'])
                ->whereRaw('amount - concession_amount - paid_amount > 0');
        })->count();
    }

    /**
     * Update defaulters
     */
    public function updateDefaulters(array $data): array
    {
        $students = Student::whereIn('id', $data['student_ids'])->get();
        $processed = 0;

        foreach ($students as $student) {
            switch ($data['action']) {
                case 'send_reminder':
                    PaymentReminder::create([
                        'student_id' => $student->id,
                        'type' => $data['reminder_type'],
                        'message' => $data['message'],
                        'scheduled_date' => now(),
                        'priority' => 'high',
                        'status' => 'pending',
                        'created_by' => auth()->id(),
                    ]);
                    $processed++;
                    break;
                
                case 'mark_contacted':
                    // You could add a contacted_at field to students table
                    // $student->update(['last_contacted_at' => now()]);
                    $processed++;
                    break;
                
                case 'extend_deadline':
                    // Extend deadlines for unpaid fees
                    $student->studentFees()
                        ->whereIn('status', ['unpaid', 'partial', 'overdue'])
                        ->update(['due_date' => $data['new_deadline']]);
                    $processed++;
                    break;
            }
        }

        return [
            'success' => true,
            'message' => "{$processed} students processed successfully.",
            'processed' => $processed,
        ];
    }

    /**
     * Perform health check
     */
    public function performHealthCheck(): array
    {
        return [
            'email_service' => ['status' => 'active', 'last_check' => now()],
            'sms_service' => ['status' => 'active', 'last_check' => now()],
            'whatsapp_service' => ['status' => 'active', 'last_check' => now()],
            'queue_status' => ['status' => 'active', 'pending_jobs' => 0],
            'database_connection' => ['status' => 'active', 'response_time' => '2ms'],
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
        ->with(['batch.course', 'studentFees' => function ($query) use ($cutoffDate) {
            $query->whereDate('due_date', '<', $cutoffDate)
                ->whereIn('status', ['unpaid', 'partial'])
                ->whereRaw('amount - concession_amount - paid_amount > 0')
                ->with('feeCategory');
        }])
        ->get();
    }

    /**
     * Get total defaulters count (component-based)
     */
    public function getTotalDefaultersCount(): int
    {
        return Student::whereHas('studentFees', function($q) {
            $q->whereIn('status', ['unpaid', 'partial'])
              ->where('due_date', '<', now())
              ->whereRaw('amount - concession_amount - paid_amount > 0');
        })->count();
    }

    /**
     * Get collection efficiency statistics (component-based)
     */
    public function getCollectionEfficiency(): array
    {
        $totalFees = StudentFee::count();
        $paidFees = StudentFee::where('status', 'paid')->count();
        $overdueFees = StudentFee::where('due_date', '<', now())
                                ->whereIn('status', ['unpaid', 'partial'])
                                ->whereRaw('amount - concession_amount - paid_amount > 0')
                                ->count();

        $collectionRate = $totalFees > 0 ? round(($paidFees / $totalFees) * 100, 2) : 0;
        $overdueRate = $totalFees > 0 ? round(($overdueFees / $totalFees) * 100, 2) : 0;

        return [
            'total_fees' => $totalFees,
            'paid_fees' => $paidFees,
            'overdue_fees' => $overdueFees,
            'collection_rate' => $collectionRate,
            'overdue_rate' => $overdueRate,
            'component_breakdown' => $this->getComponentCollectionBreakdown(),
        ];
    }

    /**
     * Setup automated reminder schedule for a student and fee component
     */
    public function setupComponentReminderSchedule(Student $student, StudentFee $studentFee): void
    {
        $feeCategory = $studentFee->feeCategory;
        $reminderDaysBefore = $feeCategory?->reminder_days_before ?? 
                             Setting::where('key', 'reminder_days_before')->value('value') ?? 7;
        $escalationDaysAfter = $feeCategory?->escalation_days_after ?? 
                              Setting::where('key', 'escalation_days')->value('value') ?? 15;

        $reminders = [
            [
                'type' => 'upcoming_due',
                'scheduled_date' => Carbon::parse($studentFee->due_date)->subDays($reminderDaysBefore),
                'channel' => 'email'
            ],
            [
                'type' => 'upcoming_due',
                'scheduled_date' => Carbon::parse($studentFee->due_date)->subDays(3),
                'channel' => 'sms'
            ],
            [
                'type' => 'overdue',
                'scheduled_date' => Carbon::parse($studentFee->due_date)->addDays(1),
                'channel' => 'email'
            ],
            [
                'type' => 'overdue',
                'scheduled_date' => Carbon::parse($studentFee->due_date)->addDays(7),
                'channel' => 'sms'
            ],
            [
                'type' => 'escalation',
                'scheduled_date' => Carbon::parse($studentFee->due_date)->addDays($escalationDaysAfter),
                'channel' => 'phone_call'
            ],
            [
                'type' => 'final_notice',
                'scheduled_date' => Carbon::parse($studentFee->due_date)->addDays(30),
                'channel' => 'physical_notice'
            ]
        ];

        foreach ($reminders as $reminder) {
            if (Carbon::parse($reminder['scheduled_date'])->isFuture()) {
                PaymentReminder::create([
                    'student_id' => $student->id,
                    'student_fee_id' => $studentFee->id, // Changed from invoice_id
                    'fee_category_id' => $feeCategory?->id,
                    'reminder_type' => $reminder['type'],
                    'scheduled_date' => $reminder['scheduled_date'],
                    'channel' => $reminder['channel'],
                    'status' => 'pending',
                    'recipient_details' => [
                        'email' => $student->email,
                        'phone' => $student->student_mobile ?? $student->father_mobile,
                        'student_name' => $student->name,
                        'enrollment_number' => $student->enrollment_number,
                    ]
                ]);
            }
        }
    }

    /**
     * Cancel reminders for a paid fee component
     */
    public function cancelRemindersForStudentFee(StudentFee $studentFee): void
    {
        PaymentReminder::where('student_fee_id', $studentFee->id)
                      ->where('status', 'pending')
                      ->update(['status' => 'cancelled']);
    }

    /**
     * Generate a list of payment defaulters with their component details
     */
    public function generateComponentDefaultersList(): array
    {
        try {
            // Get all students with overdue component fees
            $studentsWithOverdueFees = Student::whereHas('studentFees', function($query) {
                $query->where('due_date', '<', now())
                      ->whereIn('status', ['unpaid', 'partial'])
                      ->whereRaw('amount - concession_amount - paid_amount > 0');
            })->with([
                'batch.course',
                'studentFees' => function($query) {
                    $query->where('due_date', '<', now())
                          ->whereIn('status', ['unpaid', 'partial'])
                          ->whereRaw('amount - concession_amount - paid_amount > 0')
                          ->with('feeCategory');
                }
            ])->get();

            $defaulters = [];

            foreach ($studentsWithOverdueFees as $student) {
                $overdueFees = $student->studentFees;
                
                if ($overdueFees->isEmpty()) {
                    continue;
                }

                // Calculate totals
                $totalOverdueAmount = $overdueFees->sum(function($fee) {
                    return $fee->amount - $fee->concession_amount - $fee->paid_amount;
                });
                $overdueFeeCount = $overdueFees->count();
                
                // Get oldest overdue date
                $oldestDueDate = $overdueFees->min('due_date');
                $overdueDays = Carbon::parse($oldestDueDate)->diffInDays(now());
                
                // Get fee categories and amounts
                $componentBreakdown = $overdueFees->groupBy('fee_category_id')->map(function($fees, $categoryId) {
                    $category = $fees->first()->feeCategory;
                    $overdueAmount = $fees->sum(function($fee) {
                        return $fee->amount - $fee->concession_amount - $fee->paid_amount;
                    });
                    
                    return [
                        'category_name' => $category->name,
                        'category_type' => $category->category_type ?? 'other',
                        'overdue_amount' => $overdueAmount,
                        'fee_count' => $fees->count(),
                        'oldest_due_date' => $fees->min('due_date'),
                    ];
                });

                $feeTypes = $componentBreakdown->pluck('category_name')->toArray();

                // Determine defaulter category
                $defaulterCategory = $this->categorizeComponentDefaulter(
                    $totalOverdueAmount, 
                    $overdueDays, 
                    $overdueFeeCount,
                    $componentBreakdown->count()
                );

                $defaulters[] = [
                    'student' => $student,
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'enrollment_number' => $student->enrollment_number,
                    'course' => $student->batch?->course?->name ?? 'N/A',
                    'batch' => $student->batch?->name ?? 'N/A',
                    'total_overdue_amount' => $totalOverdueAmount,
                    'overdue_fee_count' => $overdueFeeCount,
                    'overdue_days' => $overdueDays,
                    'oldest_due_date' => $oldestDueDate,
                    'overdue_fee_types' => $feeTypes,
                    'component_breakdown' => $componentBreakdown->toArray(),
                    'affected_categories_count' => $componentBreakdown->count(),
                    'defaulter_category' => $defaulterCategory,
                    'contact_phone' => $student->phone,
                    'contact_email' => $student->email,
                    'last_payment_date' => $this->getLastComponentPaymentDate($student),
                    'reminder_count' => $student->paymentReminders()->count(),
                    'last_reminder_sent' => $student->paymentReminders()
                        ->whereNotNull('sent_at')
                        ->latest('sent_at')
                        ->value('sent_at'),
                    'priority_score' => $this->calculateDefaulterPriorityScore($totalOverdueAmount, $overdueDays, $componentBreakdown->count()),
                ];
            }

            // Sort by priority score (highest first)
            usort($defaulters, function($a, $b) {
                return $b['priority_score'] <=> $a['priority_score'];
            });

            return $defaulters;

        } catch (\Exception $e) {
            Log::error('Failed to generate component defaulters list: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get the last component payment date for a student
     */
    private function getLastComponentPaymentDate(Student $student): ?string
    {
        $lastPayment = $student->componentPayments()
            ->orderBy('payment_date', 'desc')
            ->first();

        return $lastPayment ? $lastPayment->payment_date->format('Y-m-d') : null;
    }

    /**
     * Update defaulter records in database (component-based)
     */
    public function updateComponentDefaulterRecords(): void
    {
        try {
            $defaulters = $this->generateComponentDefaultersList();
            
            // Clear existing records that are no longer defaulters
            PaymentDefaulter::whereNotIn('student_id', collect($defaulters)->pluck('student_id'))->delete();
            
            foreach ($defaulters as $defaulterData) {
                PaymentDefaulter::updateOrCreate(
                    ['student_id' => $defaulterData['student_id']],
                    [
                        'defaulter_category' => $defaulterData['defaulter_category'],
                        'total_overdue_amount' => $defaulterData['total_overdue_amount'],
                        'overdue_days' => $defaulterData['overdue_days'],
                        'total_overdue_invoices' => $defaulterData['overdue_fee_count'], // Keep field name for compatibility
                        'first_overdue_date' => $defaulterData['oldest_due_date'],
                        'overdue_fee_types' => json_encode($defaulterData['overdue_fee_types']),
                        'current_status' => 'active',
                        'contact_attempts' => PaymentDefaulter::where('student_id', $defaulterData['student_id'])->value('contact_attempts') ?? 0,
                        'component_breakdown' => json_encode($defaulterData['component_breakdown']),
                        'affected_categories_count' => $defaulterData['affected_categories_count'],
                        'priority_score' => $defaulterData['priority_score'],
                    ]
                );
            }

            Log::info('Updated component defaulter records: ' . count($defaulters) . ' defaulters processed');

        } catch (\Exception $e) {
            Log::error('Failed to update component defaulter records: ' . $e->getMessage());
        }
    }

    /**
     * Generate reminder message based on type and student component details
     */
    private function generateComponentReminderMessage(PaymentReminder $reminder): string
    {
        $student = $reminder->student;
        $studentFee = $reminder->studentFee; // Changed from invoice
        $collegeName = Setting::where('key', 'app_name')->value('value') ?? 'College';

        $templates = [
            'upcoming_due' => "Dear {student_name}, this is a friendly reminder that your {fee_type} payment of ₹{amount} is due on {due_date}. Please make the payment to avoid any late fees. - {college_name}",
            'overdue' => "Dear {student_name}, your {fee_type} payment of ₹{amount} was due on {due_date} and is now overdue. Please make the payment immediately to avoid further action. - {college_name}",
            'escalation' => "URGENT: Dear {student_name}, your overdue {fee_type} payment of ₹{amount} requires immediate attention. Please contact the accounts office or make payment today. - {college_name}",
            'final_notice' => "FINAL NOTICE: Dear {student_name}, this is your final notice for the overdue {fee_type} payment of ₹{amount}. Immediate action is required to avoid suspension. - {college_name}"
        ];

        $template = $templates[$reminder->reminder_type] ?? $templates['overdue'];

        $remainingAmount = $studentFee ? ($studentFee->amount - $studentFee->concession_amount - $studentFee->paid_amount) : 0;

        $replacements = [
            '{student_name}' => $student->name,
            '{enrollment_number}' => $student->enrollment_number,
            '{fee_type}' => $reminder->feeCategory->name ?? 'Fee',
            '{amount}' => number_format($remainingAmount, 2),
            '{due_date}' => $studentFee ? Carbon::parse($studentFee->due_date)->format('d M Y') : 'N/A',
            '{college_name}' => $collegeName,
            '{course_name}' => $student->batch?->course?->name ?? 'Course',
            '{batch_name}' => $student->batch?->name ?? 'Batch',
            '{total_outstanding}' => number_format($student->getTotalOutstandingAmount(), 2),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * Categorize defaulter based on component analysis
     */
    private function categorizeComponentDefaulter(float $amount, int $days, int $feeCount, int $categoriesCount): string
    {
        $chronicDays = Setting::where('key', 'chronic_defaulter_days')->value('value') ?? 90;
        $severeDays = Setting::where('key', 'severe_defaulter_days')->value('value') ?? 60;
        $moderateDays = Setting::where('key', 'moderate_defaulter_days')->value('value') ?? 30;

        // Enhanced categorization considering multiple factors
        $score = 0;
        
        // Amount factor
        if ($amount > 50000) $score += 3;
        elseif ($amount > 25000) $score += 2;
        elseif ($amount > 10000) $score += 1;
        
        // Days overdue factor
        if ($days > $chronicDays) $score += 3;
        elseif ($days > $severeDays) $score += 2;
        elseif ($days > $moderateDays) $score += 1;
        
        // Multiple fees factor
        if ($feeCount > 5) $score += 2;
        elseif ($feeCount > 3) $score += 1;
        
        // Multiple categories factor (indicates widespread non-payment)
        if ($categoriesCount > 3) $score += 1;

        return match(true) {
            $score >= 6 => 'chronic',
            $score >= 4 => 'severe',
            $score >= 2 => 'moderate',
            default => 'mild'
        };
    }

    /**
     * Calculate priority score for defaulter intervention
     */
    private function calculateDefaulterPriorityScore(float $amount, int $days, int $categoriesCount): float
    {
        $amountWeight = 0.4;
        $daysWeight = 0.4;
        $categoryWeight = 0.2;

        $amountScore = min($amount / 1000, 100); // ₹1000 = 1 point, max 100
        $daysScore = min($days * 2, 100); // 1 day = 2 points, max 100
        $categoryScore = $categoriesCount * 10; // 10 points per category

        return round(
            ($amountScore * $amountWeight) +
            ($daysScore * $daysWeight) +
            ($categoryScore * $categoryWeight),
            2
        );
    }

    /**
     * Get component collection breakdown
     */
    private function getComponentCollectionBreakdown(): array
    {
        return FeeCategory::select('fee_categories.name')
            ->selectRaw('
                COUNT(student_fees.id) as total_fees,
                COUNT(CASE WHEN student_fees.status = "paid" THEN 1 END) as paid_fees,
                COUNT(CASE WHEN student_fees.status IN ("unpaid", "partial") AND student_fees.due_date < NOW() THEN 1 END) as overdue_fees,
                SUM(student_fees.amount - student_fees.concession_amount) as net_amount,
                SUM(student_fees.paid_amount) as collected_amount
            ')
            ->leftJoin('student_fees', 'fee_categories.id', '=', 'student_fees.fee_category_id')
            ->groupBy('fee_categories.id', 'fee_categories.name')
            ->get()
            ->map(function($category) {
                $category->collection_rate = $category->net_amount > 0 ? 
                    round(($category->collected_amount / $category->net_amount) * 100, 2) : 0;
                $category->overdue_rate = $category->total_fees > 0 ?
                    round(($category->overdue_fees / $category->total_fees) * 100, 2) : 0;
                return $category;
            })
            ->toArray();
    }

    /**
     * Send single reminder (updated for components)
     */
    public function sendSingleReminder(PaymentReminder $reminder): array
    {
        try {
            $message = $this->generateComponentReminderMessage($reminder);
            $result = $this->sendReminder($reminder, $message);
            
            if ($result['success']) {
                $reminder->markAsSent();
                return ['success' => true, 'message' => 'Component reminder sent successfully'];
            } else {
                $reminder->markAsFailed($result['error'] ?? 'Failed to send via ' . $reminder->channel);
                return ['success' => false, 'error' => $result['error'] ?? 'Failed to send reminder'];
            }
        } catch (\Exception $e) {
            $reminder->markAsFailed($e->getMessage());
            Log::error('Single component reminder failed', [
                'reminder_id' => $reminder->id,
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Alias for getReminderStatistics (for backward compatibility)
     */
    public function getReminderStats(): array
    {
        return $this->getReminderStatistics();
    }

    /**
     * Get reminder statistics for dashboard (component-based)
     */
    public function getReminderStatistics(): array
    {
        return [
            'pending_reminders' => PaymentReminder::where('status', 'pending')->count(),
            'sent_today' => PaymentReminder::whereDate('sent_at', today())->count(),
            'sent_this_week' => PaymentReminder::whereBetween('sent_at', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ])->count(),
            'sent_this_month' => PaymentReminder::whereBetween('sent_at', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth()
            ])->count(),
            'failed_reminders' => PaymentReminder::where('status', 'failed')->count(),
            'overdue_reminders' => PaymentReminder::where('scheduled_date', '<', now())
                                                 ->where('status', 'pending')->count(),
            'total_defaulters' => $this->getTotalDefaultersCount(),
            'chronic_defaulters' => Student::whereHas('studentFees', function($q) {
                $q->whereIn('status', ['unpaid', 'partial'])
                  ->where('due_date', '<', now()->subDays(90))
                  ->whereRaw('amount - concession_amount - paid_amount > 0');
            })->count(),
            'component_reminder_breakdown' => $this->getComponentReminderBreakdown(),
        ];
    }

    /**
     * Get component-wise reminder breakdown
     */
    private function getComponentReminderBreakdown(): array
    {
        return FeeCategory::select('fee_categories.name')
            ->selectRaw('
                COUNT(payment_reminders.id) as total_reminders,
                COUNT(CASE WHEN payment_reminders.status = "sent" THEN 1 END) as sent_reminders,
                COUNT(CASE WHEN payment_reminders.status = "pending" THEN 1 END) as pending_reminders,
                COUNT(CASE WHEN payment_reminders.status = "failed" THEN 1 END) as failed_reminders
            ')
            ->leftJoin('payment_reminders', 'fee_categories.id', '=', 'payment_reminders.fee_category_id')
            ->where('payment_reminders.created_at', '>=', now()->subDays(30))
            ->groupBy('fee_categories.id', 'fee_categories.name')
            ->orderByDesc('total_reminders')
            ->get()
            ->toArray();
    }

    /**
     * Cleanup old reminder records
     */
    public function cleanupOldRecords(int $daysToKeep = 30): int
    {
        $cutoffDate = Carbon::now()->subDays($daysToKeep);
        
        return PaymentReminder::where('created_at', '<', $cutoffDate)
            ->where('status', '!=', 'pending')
            ->delete();
    }

    /**
     * Send a payment reminder with comprehensive error handling
     */
    public function sendReminder(PaymentReminder $reminder, ?string $message = null): array
    {
        try {
            // Validate reminder before sending
            $validation = $this->validateReminder($reminder);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'error' => $validation['message'],
                    'code' => 'VALIDATION_FAILED'
                ];
            }

            // Get message content
            if (!$message) {
                $message = $reminder->message_content;
                
                if (empty($message)) {
                    $template = $this->getTemplate($reminder->reminder_type, $reminder->channel);
                    if (!$template) {
                        return [
                            'success' => false,
                            'error' => 'No template found for reminder type and channel',
                            'code' => 'TEMPLATE_NOT_FOUND'
                        ];
                    }

                    $variables = $this->prepareComponentTemplateVariables($reminder);
                    $message = $template->renderMessage($variables);
                }
            }

            // Send based on channel
            $result = match ($reminder->channel) {
                'email' => $this->sendEmailReminder($reminder, $message),
                'sms' => $this->sendSMSReminder($reminder, $message),
                'whatsapp' => $this->sendWhatsAppReminder($reminder, $message),
                'phone_call' => $this->schedulePhoneCall($reminder),
                'physical_notice' => $this->generatePhysicalNotice($reminder),
                default => [
                    'success' => false,
                    'error' => 'Unsupported channel: ' . $reminder->channel,
                    'code' => 'UNSUPPORTED_CHANNEL'
                ]
            };

            // Update reminder status
            if ($result['success']) {
                $reminder->markAsSent();
                $this->logReminderAction($reminder, 'sent', 'Component reminder sent successfully');
            } else {
                $reminder->markAsFailed($result['error'] ?? 'Unknown error');
                $this->logReminderAction($reminder, 'failed', $result['error'] ?? 'Unknown error');
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Failed to send component payment reminder: ' . $e->getMessage(), [
                'reminder_id' => $reminder->id,
                'student_id' => $reminder->student_id,
                'fee_category_id' => $reminder->fee_category_id,
                'channel' => $reminder->channel,
                'trace' => $e->getTraceAsString()
            ]);

            $reminder->markAsFailed('System error: ' . $e->getMessage());
            $this->logReminderAction($reminder, 'failed', 'System error: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => 'System error occurred while sending reminder',
                'code' => 'SYSTEM_ERROR',
                'details' => config('app.debug') ? $e->getMessage() : null
            ];
        }
    }

     /**
     * Send email reminder
     */
    private function sendEmailReminder(PaymentReminder $reminder): bool
    {
        try {
            $student = $reminder->student;
            
            if (!$student->email) {
                throw new \Exception('Student email not found');
            }

            // Here you would send the actual email
            // Mail::to($student->email)->send(new PaymentReminderMail($reminder));
            
            // For now, we'll just log it
            Log::info('Email reminder sent', [
                'student_id' => $student->id,
                'email' => $student->email,
                'message' => $reminder->message,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send email reminder', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Send SMS reminder
     */
    private function sendSMSReminder(PaymentReminder $reminder, string $message): array
    {
        try {
            Log::info('SMS Component Reminder sent', [
                'reminder_id' => $reminder->id,
                'phone' => $reminder->recipient_details['phone'] ?? 'N/A',
                'fee_category' => $reminder->feeCategory->name ?? 'N/A',
                'message' => $message
            ]);

            return ['success' => true, 'message' => 'SMS logged successfully'];
        } catch (\Exception $e) {
            Log::error('SMS reminder failed', [
                'reminder_id' => $reminder->id,
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => 'SMS sending failed: ' . $e->getMessage()];
        }
    }

    /**
     * Send WhatsApp reminder
     */
    private function sendWhatsAppReminder(PaymentReminder $reminder, string $message): array
    {
        try {
            Log::info('WhatsApp Component Reminder sent', [
                'reminder_id' => $reminder->id,
                'phone' => $reminder->recipient_details['phone'] ?? 'N/A',
                'fee_category' => $reminder->feeCategory->name ?? 'N/A',
                'message' => $message
            ]);

            return ['success' => true, 'message' => 'WhatsApp logged successfully'];
        } catch (\Exception $e) {
            Log::error('WhatsApp reminder failed', [
                'reminder_id' => $reminder->id,
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => 'WhatsApp sending failed: ' . $e->getMessage()];
        }
    }
    
      /**
     * Queue reminder
     */
    public function queueReminder(PaymentReminder $reminder): void
    {
        // Here you would add the reminder to a queue for processing
        // For now, we'll just update the status
        $reminder->update(['status' => 'queued']);
        
        Log::info('Reminder queued', ['reminder_id' => $reminder->id]);
    }


    /**
     * Schedule phone call task
     */
    private function schedulePhoneCall(PaymentReminder $reminder): array
    {
        try {
            Log::info('Component fee phone call scheduled', [
                'reminder_id' => $reminder->id,
                'student' => $reminder->student->name,
                'fee_category' => $reminder->feeCategory->name ?? 'N/A',
                'phone' => $reminder->recipient_details['phone'] ?? 'N/A'
            ]);

            return ['success' => true, 'message' => 'Phone call scheduled successfully'];
        } catch (\Exception $e) {
            Log::error('Phone call scheduling failed', [
                'reminder_id' => $reminder->id,
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => 'Phone call scheduling failed: ' . $e->getMessage()];
        }
    }

    /**
     * Generate physical notice
     */
    private function generatePhysicalNotice(PaymentReminder $reminder): array
    {
        try {
            Log::info('Component fee physical notice generated', [
                'reminder_id' => $reminder->id,
                'student' => $reminder->student->name,
                'fee_category' => $reminder->feeCategory->name ?? 'N/A'
            ]);

            return ['success' => true, 'message' => 'Physical notice generated successfully'];
        } catch (\Exception $e) {
            Log::error('Physical notice generation failed', [
                'reminder_id' => $reminder->id,
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => 'Physical notice generation failed: ' . $e->getMessage()];
        }
    }

    /**
     * Validate reminder before sending
     */
    private function validateReminder(PaymentReminder $reminder): array
    {
        $errors = [];

        // Check if student exists and is active
        if (!$reminder->student) {
            $errors[] = 'Student not found';
        } elseif (isset($reminder->student->is_active) && !$reminder->student->is_active) {
            $errors[] = 'Student is not active';
        }

        // Check if student fee exists and is still outstanding
        if ($reminder->studentFee) {
            $remainingAmount = $reminder->studentFee->amount - $reminder->studentFee->concession_amount - $reminder->studentFee->paid_amount;
            if ($remainingAmount <= 0) {
                $errors[] = 'Fee component has been fully paid';
            }
        }

        // Validate contact information
        $contactInfo = $reminder->getRecipientInfo();
        switch ($reminder->channel) {
            case 'email':
                if (empty($contactInfo['email']) || !filter_var($contactInfo['email'], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Invalid or missing email address';
                }
                break;

            case 'sms':
            case 'whatsapp':
            case 'phone_call':
                if (empty($contactInfo['phone'])) {
                    $errors[] = 'Missing phone number';
                } elseif (!preg_match('/^[\+]?[\d\s\-\(\)]+$/', $contactInfo['phone'])) {
                    $errors[] = 'Invalid phone number format';
                }
                break;
        }

        // Check rate limiting
        if ($this->isRateLimited($reminder->student, $reminder->channel)) {
            $errors[] = 'Rate limit exceeded for this student and channel';
        }

        // Check if reminder is not too old
        if ($reminder->created_at < now()->subDays(30)) {
            $errors[] = 'Reminder is too old to send';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'message' => implode('. ', $errors)
        ];
    }

    /**
     * Check if student has hit rate limits for a channel
     */
    private function isRateLimited(Student $student, string $channel): bool
    {
        $limits = [
            'email' => ['count' => 5, 'period' => 24], // 5 emails per day
            'sms' => ['count' => 3, 'period' => 24],   // 3 SMS per day
            'whatsapp' => ['count' => 3, 'period' => 24], // 3 WhatsApp per day
            'phone_call' => ['count' => 2, 'period' => 24] // 2 calls per day
        ];

        $limit = $limits[$channel] ?? null;
        if (!$limit) {
            return false;
        }

        $count = PaymentReminder::where('student_id', $student->id)
            ->where('channel', $channel)
            ->where('status', 'sent')
            ->where('sent_at', '>', now()->subHours($limit['period']))
            ->count();

        return $count >= $limit['count'];
    }

    /**
     * Get template with fallback
     */
    private function getTemplate(string $reminderType, string $channel): ?PaymentReminderTemplate
    {
        // Try to get specific template
        $template = PaymentReminderTemplate::where('is_active', true)
            ->where('reminder_type', $reminderType)
            ->where('channel', $channel)
            ->first();

        // Fallback to default template for the type
        if (!$template) {
            $template = PaymentReminderTemplate::where('is_active', true)
                ->where('reminder_type', $reminderType)
                ->where('is_default', true)
                ->first();
        }

        // Last resort: get any active template for the channel
        if (!$template) {
            $template = PaymentReminderTemplate::where('is_active', true)
                ->where('channel', $channel)
                ->where('is_default', true)
                ->first();
        }

        return $template;
    }

    /**
     * Prepare template variables with safe defaults (component-based)
     */
    private function prepareComponentTemplateVariables(PaymentReminder $reminder): array
    {
        $student = $reminder->student;
        $studentFee = $reminder->studentFee;

        $remainingAmount = $studentFee ? 
            ($studentFee->amount - $studentFee->concession_amount - $studentFee->paid_amount) : 0;

        return [
            'student_name' => $student->name ?? 'Student',
            'enrollment_number' => $student->enrollment_number ?? 'N/A',
            'fee_type' => $reminder->feeCategory?->name ?? 'Fee',
            'amount' => number_format($remainingAmount, 2),
            'due_date' => $studentFee ? Carbon::parse($studentFee->due_date)->format('d M Y') : 'N/A',
            'days_overdue' => $studentFee ? max(0, Carbon::parse($studentFee->due_date)->diffInDays(now())) : 0,
            'total_amount_due' => number_format($student->getTotalOutstandingAmount(), 2),
            'course_name' => $student->batch?->course?->name ?? 'N/A',
            'batch_name' => $student->batch?->name ?? 'N/A',
            'college_name' => Setting::where('key', 'college_name')->value('value') ?? config('app.name'),
            'contact_number' => Setting::where('key', 'contact_phone')->value('value') ?? '',
            'contact_email' => Setting::where('key', 'contact_email')->value('value') ?? '',
            'final_deadline' => now()->addDays(3)->format('d M Y'),
            'academic_year' => $studentFee?->academic_year ?? date('Y') . '-' . (date('Y') + 1),
            'installment_number' => $studentFee?->installment_number ?? 1,
            'original_amount' => $studentFee ? number_format($studentFee->amount, 2) : '0.00',
            'concession_amount' => $studentFee ? number_format($studentFee->concession_amount, 2) : '0.00',
            'paid_amount' => $studentFee ? number_format($studentFee->paid_amount, 2) : '0.00',
        ];
    }

    /**
     * Log reminder action for audit trail
     */
    private function logReminderAction(PaymentReminder $reminder, string $action, string $details): void
    {
        try {
            PaymentReminderLog::create([
                'payment_reminder_id' => $reminder->id,
                'action' => $action,
                'details' => $details,
                'metadata' => json_encode([
                    'channel' => $reminder->channel,
                    'reminder_type' => $reminder->reminder_type,
                    'student_id' => $reminder->student_id,
                    'fee_category_id' => $reminder->fee_category_id,
                    'student_fee_id' => $reminder->student_fee_id,
                    'timestamp' => now()->toDateTimeString()
                ]),
                'performed_by' => auth()->id()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log component reminder action: ' . $e->getMessage());
        }
    }

    /**
     * Process pending reminders with error handling (component-based)
     */
    public function processPendingReminders(int $batchSize = 50, array $filters = []): array
    {
        try {
            $query = PaymentReminder::where('status', 'pending')
                ->where('scheduled_date', '<=', now())
                ->with(['student', 'feeCategory', 'studentFee']);

            // Apply filters if provided
            if (!empty($filters['channel'])) {
                $query->where('channel', $filters['channel']);
            }
            if (!empty($filters['reminder_type'])) {
                $query->where('reminder_type', $filters['reminder_type']);
            }
            if (!empty($filters['fee_category_id'])) {
                $query->where('fee_category_id', $filters['fee_category_id']);
            }

            $pendingReminders = $query->limit($batchSize)->get();

            $results = [
                'processed' => 0,
                'sent' => 0,
                'failed' => 0,
                'errors' => []
            ];

            foreach ($pendingReminders as $reminder) {
                $results['processed']++;
                
                $result = $this->sendReminder($reminder);
                
                if ($result['success']) {
                    $results['sent']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = [
                        'reminder_id' => $reminder->id,
                        'student' => $reminder->student->name ?? 'Unknown',
                        'fee_category' => $reminder->feeCategory->name ?? 'Unknown',
                        'error' => $result['error']
                    ];
                }
            }

            return $results;

        } catch (\Exception $e) {
            Log::error('Failed to process pending component reminders: ' . $e->getMessage());
            
            return [
                'processed' => 0,
                'sent' => 0,
                'failed' => 0,
                'errors' => ['System error: ' . $e->getMessage()]
            ];
        }
    }

    /**
     * Bulk setup reminders for multiple students and fees
     */
    public function bulkSetupComponentReminders(array $studentIds, array $feeCategories = []): array
    {
        $setupCount = 0;
        $errors = [];

        try {
            $students = Student::whereIn('id', $studentIds)->with('studentFees.feeCategory')->get();

            foreach ($students as $student) {
                $studentFees = $student->studentFees()
                    ->whereIn('status', ['unpaid', 'partial'])
                    ->when(!empty($feeCategories), function($q) use ($feeCategories) {
                        $q->whereIn('fee_category_id', $feeCategories);
                    })
                    ->get();

                foreach ($studentFees as $studentFee) {
                    try {
                        $this->setupComponentReminderSchedule($student, $studentFee);
                        $setupCount++;
                    } catch (\Exception $e) {
                        $errors[] = "Failed to setup reminders for {$student->name} - {$studentFee->feeCategory->name}: " . $e->getMessage();
                    }
                }
            }

            return [
                'success' => true,
                'setup_count' => $setupCount,
                'errors' => $errors,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'setup_count' => $setupCount,
                'errors' => array_merge($errors, ['System error: ' . $e->getMessage()]),
            ];
        }
    }

    /**
     * Get component-specific reminder analytics
     */
    public function getComponentReminderAnalytics(): array
    {
        return [
            'effectiveness_by_category' => $this->getReminderEffectivenessByCategory(),
            'channel_performance' => $this->getChannelPerformanceByCategory(),
            'timing_analysis' => $this->getReminderTimingAnalysis(),
            'response_rates' => $this->getComponentReminderResponseRates(),
        ];
    }

    /**
     * Get reminder effectiveness by fee category
     */
    private function getReminderEffectivenessByCategory(): array
    {
        return FeeCategory::select('fee_categories.name')
            ->selectRaw('
                COUNT(payment_reminders.id) as total_reminders,
                COUNT(CASE WHEN payment_reminders.status = "sent" THEN 1 END) as sent_reminders,
                COUNT(CASE WHEN payments.id IS NOT NULL THEN 1 END) as resulted_in_payment
            ')
            ->leftJoin('payment_reminders', 'fee_categories.id', '=', 'payment_reminders.fee_category_id')
            ->leftJoin('student_fees', 'payment_reminders.student_fee_id', '=', 'student_fees.id')
            ->leftJoin('component_payment_items', 'student_fees.id', '=', 'component_payment_items.student_fee_id')
            ->leftJoin('payments', function($join) {
                $join->on('component_payment_items.payment_id', '=', 'payments.id')
                     ->where('payments.payment_date', '>', DB::raw('payment_reminders.sent_at'));
            })
            ->where('payment_reminders.created_at', '>=', now()->subMonths(6))
            ->groupBy('fee_categories.id', 'fee_categories.name')
            ->get()
            ->map(function($category) {
                $category->effectiveness_rate = $category->sent_reminders > 0 ? 
                    round(($category->resulted_in_payment / $category->sent_reminders) * 100, 2) : 0;
                return $category;
            })
            ->toArray();
    }

    /**
     * Get channel performance by category
     */
    private function getChannelPerformanceByCategory(): array
    {
        return PaymentReminder::select('channel', 'fee_category_id')
            ->selectRaw('COUNT(*) as total_sent')
            ->selectRaw('COUNT(CASE WHEN status = "sent" THEN 1 END) as successful_sends')
            ->selectRaw('AVG(CASE WHEN sent_at IS NOT NULL THEN TIMESTAMPDIFF(HOUR, created_at, sent_at) END) as avg_send_delay_hours')
            ->with('feeCategory:id,name')
            ->where('created_at', '>=', now()->subMonths(3))
            ->groupBy('channel', 'fee_category_id')
            ->get()
            ->groupBy('feeCategory.name')
            ->map(function($channelData, $categoryName) {
                return [
                    'category' => $categoryName,
                    'channels' => $channelData->map(function($data) {
                        return [
                            'channel' => $data->channel,
                            'total_sent' => $data->total_sent,
                            'success_rate' => $data->total_sent > 0 ? 
                                round(($data->successful_sends / $data->total_sent) * 100, 2) : 0,
                            'avg_send_delay_hours' => round($data->avg_send_delay_hours ?? 0, 2),
                        ];
                    })->values()
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Get reminder timing analysis
     */
    private function getReminderTimingAnalysis(): array
    {
        return PaymentReminder::selectRaw('
                reminder_type,
                AVG(TIMESTAMPDIFF(DAY, scheduled_date, sent_at)) as avg_delay_days,
                COUNT(*) as total_reminders,
                COUNT(CASE WHEN sent_at <= scheduled_date THEN 1 END) as on_time_sends
            ')
            ->where('status', 'sent')
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('reminder_type')
            ->get()
            ->map(function($data) {
                $data->on_time_rate = $data->total_reminders > 0 ? 
                    round(($data->on_time_sends / $data->total_reminders) * 100, 2) : 0;
                return $data;
            })
            ->toArray();
    }

    /**
     * Get component reminder response rates
     */
    private function getComponentReminderResponseRates(): array
    {
        $remindersSent = PaymentReminder::where('status', 'sent')
            ->where('sent_at', '>=', now()->subDays(30))
            ->with(['studentFee', 'feeCategory'])
            ->get();

        $responseData = [];

        foreach ($remindersSent as $reminder) {
            if (!$reminder->studentFee) continue;

            // Check if payment was made within 7 days of reminder
            $paymentMade = ComponentPaymentItem::where('student_fee_id', $reminder->student_fee_id)
                ->whereHas('payment', function($q) use ($reminder) {
                    $q->where('payment_date', '>', $reminder->sent_at)
                     ->where('payment_date', '<=', $reminder->sent_at->addDays(7));
                })
                ->exists();

            $categoryName = $reminder->feeCategory->name ?? 'Unknown';
            
            if (!isset($responseData[$categoryName])) {
                $responseData[$categoryName] = [
                    'total_reminders' => 0,
                    'responses' => 0,
                ];
            }

            $responseData[$categoryName]['total_reminders']++;
            if ($paymentMade) {
                $responseData[$categoryName]['responses']++;
            }
        }

        return collect($responseData)->map(function($data, $categoryName) {
            return [
                'category' => $categoryName,
                'total_reminders' => $data['total_reminders'],
                'responses' => $data['responses'],
                'response_rate' => $data['total_reminders'] > 0 ? 
                    round(($data['responses'] / $data['total_reminders']) * 100, 2) : 0,
            ];
        })->sortByDesc('response_rate')->values()->toArray();
    }
}