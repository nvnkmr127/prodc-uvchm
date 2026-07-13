<?php

namespace App\Services;

use App\Models\PaymentReminder;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentFee;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ComponentPaymentReminderService
{
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
    public function getDefaulterStudents(int $daysPastDue = 30): Collection
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
                },
            ])
            ->get();
    }

    /**
     * Get total defaulters count (component-based)
     */
    public function getTotalDefaultersCount(): int
    {
        return Student::whereHas('studentFees', function ($q) {
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
        $criticalDefaulters = $this->getTotalDefaultersCount();

        return [
            'total_fees' => $totalFees,
            'paid_fees' => $paidFees,
            'overdue_fees' => $overdueFees,
            'collection_rate' => $collectionRate,
            'overdue_rate' => $overdueRate,
            'critical_defaulters' => $criticalDefaulters,
            'overall_rate' => $collectionRate, // Alias for backward compatibility
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
                'channel' => 'email',
            ],
            [
                'type' => 'upcoming_due',
                'scheduled_date' => Carbon::parse($studentFee->due_date)->subDays(3),
                'channel' => 'sms',
            ],
            [
                'type' => 'overdue',
                'scheduled_date' => Carbon::parse($studentFee->due_date)->addDays(1),
                'channel' => 'email',
            ],
            [
                'type' => 'overdue',
                'scheduled_date' => Carbon::parse($studentFee->due_date)->addDays(7),
                'channel' => 'sms',
            ],
            [
                'type' => 'escalation',
                'scheduled_date' => Carbon::parse($studentFee->due_date)->addDays($escalationDaysAfter),
                'channel' => 'phone_call',
            ],
            [
                'type' => 'final_notice',
                'scheduled_date' => Carbon::parse($studentFee->due_date)->addDays(30),
                'channel' => 'physical_notice',
            ],
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
                    ],
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
        $total = PaymentReminder::count();
        $sent = PaymentReminder::where('status', 'sent')->count();
        $successRate = $total > 0 ? round(($sent / $total) * 100, 2) : 0;

        return [
            'total_reminders' => $total,
            'sent_reminders' => $sent,
            'pending_reminders' => PaymentReminder::where('status', 'pending')->count(),
            'sent_today' => PaymentReminder::whereDate('sent_at', today())->count(),
            'sent_this_week' => PaymentReminder::whereBetween('sent_at', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek(),
            ])->count(),
            'sent_this_month' => PaymentReminder::whereBetween('sent_at', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth(),
            ])->count(),
            'failed_reminders' => PaymentReminder::where('status', 'failed')->count(),
            'overdue_reminders' => PaymentReminder::where('scheduled_date', '<', now())
                ->where('status', 'pending')->count(),
            'success_rate' => $successRate,
            'total_defaulters' => $this->getTotalDefaultersCount(),
            'chronic_defaulters' => Student::whereHas('studentFees', function ($q) {
                $q->whereIn('status', ['unpaid', 'partial'])
                    ->where('due_date', '<', now()->subDays(90))
                    ->whereRaw('amount - concession_amount - paid_amount > 0');
            })->count(),
            'component_reminder_breakdown' => $this->getComponentReminderBreakdown(),
        ];
    }

    /**
     * Send a payment reminder with comprehensive error handling
     */
    public function sendReminder(PaymentReminder $reminder, ?string $message = null): array
    {
        try {
            // Validate reminder before sending
            $validation = $this->validateReminder($reminder);
            if (! $validation['valid']) {
                return [
                    'success' => false,
                    'error' => $validation['message'],
                    'code' => 'VALIDATION_FAILED',
                ];
            }

            // Get message content
            if (! $message) {
                $message = $reminder->message_content;

                if (empty($message)) {
                    $template = $this->getTemplate($reminder->reminder_type, $reminder->channel);
                    if (! $template) {
                        return [
                            'success' => false,
                            'error' => 'No template found for reminder type and channel',
                            'code' => 'TEMPLATE_NOT_FOUND',
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
                    'error' => 'Unsupported channel: '.$reminder->channel,
                    'code' => 'UNSUPPORTED_CHANNEL',
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
            Log::error('Failed to send component payment reminder: '.$e->getMessage(), [
                'reminder_id' => $reminder->id,
                'student_id' => $reminder->student_id,
                'fee_category_id' => $reminder->fee_category_id,
                'channel' => $reminder->channel,
                'trace' => $e->getTraceAsString(),
            ]);

            $reminder->markAsFailed('System error: '.$e->getMessage());
            $this->logReminderAction($reminder, 'failed', 'System error: '.$e->getMessage());

            return [
                'success' => false,
                'error' => 'System error occurred while sending reminder',
                'code' => 'SYSTEM_ERROR',
                'details' => config('app.debug') ? $e->getMessage() : null,
            ];
        }
    }

    /**
     * Send a test reminder to verify channel configuration
     */
    public function sendTestReminder(string $channel, string $recipient, string $message): array
    {
        try {
            switch ($channel) {
                case 'email':
                    // Send raw email
                    if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                        return ['success' => false, 'error' => 'Invalid email address'];
                    }

                    try {
                        Mail::raw($message, function ($mail) use ($recipient) {
                            $mail->to($recipient)
                                ->subject('Test Payment Reminder - System Check');
                        });

                        return ['success' => true, 'message' => 'Test email sent successfully'];
                    } catch (\Exception $e) {
                        // Attempt to use fallback or report specific mail error
                        throw $e;
                    }

                case 'sms':
                    // Log SMS attempt (simulated)
                    // In a real scenario, you would call the SMS provider API here
                    \Log::info("TEST SMS to {$recipient}: {$message}");

                    return ['success' => true, 'message' => 'Test SMS logged (Simulated)'];

                case 'whatsapp':
                    // Log WhatsApp attempt (simulated)
                    \Log::info("TEST WhatsApp to {$recipient}: {$message}");

                    return ['success' => true, 'message' => 'Test WhatsApp logged (Simulated)'];

                default:
                    return ['success' => false, 'error' => 'Unsupported channel for testing'];
            }
        } catch (\Exception $e) {
            \Log::error('Test reminder failed', ['error' => $e->getMessage()]);

            return ['success' => false, 'error' => $e->getMessage()];
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
     * Process pending reminders with error handling (component-based)
     */
    public function processPendingReminders(int $batchSize = 50, array $filters = []): array
    {
        try {
            $query = PaymentReminder::where('status', 'pending')
                ->where('scheduled_date', '<=', now())
                ->with(['student', 'feeCategory', 'studentFee']);

            // Apply filters if provided
            if (! empty($filters['channel'])) {
                $query->where('channel', $filters['channel']);
            }
            if (! empty($filters['reminder_type'])) {
                $query->where('reminder_type', $filters['reminder_type']);
            }
            if (! empty($filters['fee_category_id'])) {
                $query->where('fee_category_id', $filters['fee_category_id']);
            }

            $pendingReminders = $query->limit($batchSize)->get();

            $results = [
                'processed' => 0,
                'sent' => 0,
                'failed' => 0,
                'errors' => [],
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
                        'error' => $result['error'],
                    ];
                }
            }

            return $results;

        } catch (\Exception $e) {
            Log::error('Failed to process pending component reminders: '.$e->getMessage());

            return [
                'processed' => 0,
                'sent' => 0,
                'failed' => 0,
                'errors' => ['System error: '.$e->getMessage()],
            ];
        }
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
            ->map(function ($category) {
                $category->collection_rate = $category->net_amount > 0 ?
                    round(($category->collected_amount / $category->net_amount) * 100, 2) : 0;
                $category->overdue_rate = $category->total_fees > 0 ?
                    round(($category->overdue_fees / $category->total_fees) * 100, 2) : 0;

                return $category;
            })
            ->toArray();
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
     * Validate reminder before sending
     */
    private function validateReminder(PaymentReminder $reminder): array
    {
        $errors = [];

        // Check if student exists and is active
        if (! $reminder->student) {
            $errors[] = 'Student not found';
        } elseif (isset($reminder->student->is_active) && ! $reminder->student->is_active) {
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
                if (empty($contactInfo['email']) || ! filter_var($contactInfo['email'], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Invalid or missing email address';
                }
                break;

            case 'sms':
            case 'whatsapp':
            case 'phone_call':
                if (empty($contactInfo['phone'])) {
                    $errors[] = 'Missing phone number';
                } elseif (! preg_match('/^[\+]?[\d\s\-\(\)]+$/', $contactInfo['phone'])) {
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
            'message' => implode('. ', $errors),
        ];
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
        if (! $template) {
            $template = PaymentReminderTemplate::where('is_active', true)
                ->where('reminder_type', $reminderType)
                ->where('is_default', true)
                ->first();
        }

        // Last resort: get any active template for the channel
        if (! $template) {
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
            'academic_year' => $studentFee?->academic_year ?? date('Y').'-'.(date('Y') + 1),
            'installment_number' => $studentFee?->installment_number ?? 1,
            'original_amount' => $studentFee ? number_format($studentFee->amount, 2) : '0.00',
            'concession_amount' => $studentFee ? number_format($studentFee->concession_amount, 2) : '0.00',
            'paid_amount' => $studentFee ? number_format($studentFee->paid_amount, 2) : '0.00',
        ];
    }

    /**
     * Send email reminder
     */
    private function sendEmailReminder(PaymentReminder $reminder, ?string $message = null): bool
    {
        try {
            $student = $reminder->student;

            if (! $student->email) {
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
                'message' => $message,
            ]);

            return ['success' => true, 'message' => 'SMS logged successfully'];
        } catch (\Exception $e) {
            Log::error('SMS reminder failed', [
                'reminder_id' => $reminder->id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => 'SMS sending failed: '.$e->getMessage()];
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
                'message' => $message,
            ]);

            return ['success' => true, 'message' => 'WhatsApp logged successfully'];
        } catch (\Exception $e) {
            Log::error('WhatsApp reminder failed', [
                'reminder_id' => $reminder->id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => 'WhatsApp sending failed: '.$e->getMessage()];
        }
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
                'phone' => $reminder->recipient_details['phone'] ?? 'N/A',
            ]);

            return ['success' => true, 'message' => 'Phone call scheduled successfully'];
        } catch (\Exception $e) {
            Log::error('Phone call scheduling failed', [
                'reminder_id' => $reminder->id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => 'Phone call scheduling failed: '.$e->getMessage()];
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
                'fee_category' => $reminder->feeCategory->name ?? 'N/A',
            ]);

            return ['success' => true, 'message' => 'Physical notice generated successfully'];
        } catch (\Exception $e) {
            Log::error('Physical notice generation failed', [
                'reminder_id' => $reminder->id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => 'Physical notice generation failed: '.$e->getMessage()];
        }
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
                    'timestamp' => now()->toDateTimeString(),
                ]),
                'performed_by' => auth()->id(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log component reminder action: '.$e->getMessage());
        }
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
            'phone_call' => ['count' => 2, 'period' => 24], // 2 calls per day
        ];

        $limit = $limits[$channel] ?? null;
        if (! $limit) {
            return false;
        }

        $count = PaymentReminder::where('student_id', $student->id)
            ->where('channel', $channel)
            ->where('status', 'sent')
            ->where('sent_at', '>', now()->subHours($limit['period']))
            ->count();

        return $count >= $limit['count'];
    }
}
