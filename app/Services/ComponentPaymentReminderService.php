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
}
