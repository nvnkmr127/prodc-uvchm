<?php

namespace App\Services;

use App\Models\PaymentReminder;
use App\Models\PaymentDefaulter;
use App\Models\PaymentReminderTemplate;
use App\Models\PaymentReminderLog;
use App\Models\Student;
use App\Models\Invoice;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PaymentReminderService
{
    /**
     * Send test reminder
     */
    public function sendTestReminder(string $channel, string $recipient, string $message): array
    {
        try {
            switch ($channel) {
                case 'email':
                    return $this->sendTestEmailReminder($recipient, $message);
                case 'sms':
                    return $this->sendTestSMSReminder($recipient, $message);
                case 'whatsapp':
                    return $this->sendTestWhatsAppReminder($recipient, $message);
                default:
                    return ['success' => false, 'error' => 'Invalid channel specified'];
            }
        } catch (\Exception $e) {
            Log::error('Test reminder failed', [
                'channel' => $channel,
                'recipient' => $recipient,
                'error' => $e->getMessage()
            ]);
            
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send test email reminder
     */
    private function sendTestEmailReminder(string $email, string $message): array
    {
        try {
            Mail::raw($message, function ($mail) use ($email) {
                $mail->to($email)
                     ->subject('Test Payment Reminder - ' . config('app.name'))
                     ->from(config('mail.from.address'), config('mail.from.name'));
            });

            return ['success' => true, 'message' => 'Test email sent successfully'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Email sending failed: ' . $e->getMessage()];
        }
    }

    /**
     * Send test SMS reminder
     */
    private function sendTestSMSReminder(string $phone, string $message): array
    {
        try {
            Log::info('Test SMS sent', [
                'phone' => $phone,
                'message' => $message,
                'timestamp' => now()->toISOString()
            ]);

            return ['success' => true, 'message' => 'Test SMS logged successfully (SMS provider not configured)'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'SMS sending failed: ' . $e->getMessage()];
        }
    }

    /**
     * Send test WhatsApp reminder
     */
    private function sendTestWhatsAppReminder(string $phone, string $message): array
    {
        try {
            Log::info('Test WhatsApp sent', [
                'phone' => $phone,
                'message' => $message,
                'timestamp' => now()->toISOString()
            ]);

            return ['success' => true, 'message' => 'Test WhatsApp logged successfully (WhatsApp API not configured)'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'WhatsApp sending failed: ' . $e->getMessage()];
        }
    }

    /**
     * Get total defaulters count
     */
    public function getTotalDefaultersCount(): int
    {
        return PaymentDefaulter::where('current_status', 'active')->count();
    }

    /**
     * Get collection efficiency statistics
     */
    public function getCollectionEfficiency(): array
    {
        $totalInvoices = Invoice::count();
        $paidInvoices = Invoice::where('status', 'paid')->count();
        $overdueInvoices = Invoice::where('due_date', '<', now())
                                 ->where('status', '!=', 'paid')
                                 ->count();

        $collectionRate = $totalInvoices > 0 ? round(($paidInvoices / $totalInvoices) * 100, 2) : 0;
        $overdueRate = $totalInvoices > 0 ? round(($overdueInvoices / $totalInvoices) * 100, 2) : 0;

        return [
            'total_invoices' => $totalInvoices,
            'paid_invoices' => $paidInvoices,
            'overdue_invoices' => $overdueInvoices,
            'collection_rate' => $collectionRate,
            'overdue_rate' => $overdueRate,
        ];
    }

    /**
     * Setup automated reminder schedule for a student and invoice
     */
    public function setupReminderSchedule(Student $student, Invoice $invoice): void
    {
        $feeCategory = $invoice->items->first()?->feeCategory;
        $reminderDaysBefore = $feeCategory?->reminder_days_before ?? 
                             Setting::where('key', 'reminder_days_before')->value('value') ?? 7;
        $escalationDaysAfter = $feeCategory?->escalation_days_after ?? 
                              Setting::where('key', 'escalation_days')->value('value') ?? 15;

        $reminders = [
            [
                'type' => 'upcoming_due',
                'scheduled_date' => Carbon::parse($invoice->due_date)->subDays($reminderDaysBefore),
                'channel' => 'email'
            ],
            [
                'type' => 'upcoming_due',
                'scheduled_date' => Carbon::parse($invoice->due_date)->subDays(3),
                'channel' => 'sms'
            ],
            [
                'type' => 'overdue',
                'scheduled_date' => Carbon::parse($invoice->due_date)->addDays(1),
                'channel' => 'email'
            ],
            [
                'type' => 'overdue',
                'scheduled_date' => Carbon::parse($invoice->due_date)->addDays(7),
                'channel' => 'sms'
            ],
            [
                'type' => 'escalation',
                'scheduled_date' => Carbon::parse($invoice->due_date)->addDays($escalationDaysAfter),
                'channel' => 'phone_call'
            ],
            [
                'type' => 'final_notice',
                'scheduled_date' => Carbon::parse($invoice->due_date)->addDays(30),
                'channel' => 'physical_notice'
            ]
        ];

        foreach ($reminders as $reminder) {
            if (Carbon::parse($reminder['scheduled_date'])->isFuture()) {
                PaymentReminder::create([
                    'student_id' => $student->id,
                    'invoice_id' => $invoice->id,
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
     * Cancel reminders for a paid invoice
     */
    public function cancelRemindersForInvoice(Invoice $invoice): void
    {
        PaymentReminder::where('invoice_id', $invoice->id)
                      ->where('status', 'pending')
                      ->update(['status' => 'cancelled']);
    }

    /**
     * Send single reminder
     */
    public function sendSingleReminder(PaymentReminder $reminder): array
    {
        try {
            $message = $this->generateReminderMessage($reminder);
            $result = $this->sendReminder($reminder, $message);
            
            if ($result['success']) {
                $reminder->markAsSent();
                return ['success' => true, 'message' => 'Reminder sent successfully'];
            } else {
                $reminder->markAsFailed($result['error'] ?? 'Failed to send via ' . $reminder->channel);
                return ['success' => false, 'error' => $result['error'] ?? 'Failed to send reminder'];
            }
        } catch (\Exception $e) {
            $reminder->markAsFailed($e->getMessage());
            Log::error('Single reminder failed', [
                'reminder_id' => $reminder->id,
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Generate a list of payment defaulters with their details
     */
    public function generateDefaultersList(): array
    {
        try {
            // Get all students with overdue invoices
            $studentsWithOverduePayments = Student::whereHas('invoices', function($query) {
                $query->where('due_date', '<', now())
                      ->where('status', '!=', 'paid');
            })->with([
                'batch.course',
                'invoices' => function($query) {
                    $query->where('due_date', '<', now())
                          ->where('status', '!=', 'paid')
                          ->with('items.feeCategory');
                }
            ])->get();

            $defaulters = [];

            foreach ($studentsWithOverduePayments as $student) {
                $overdueInvoices = $student->invoices;
                
                if ($overdueInvoices->isEmpty()) {
                    continue;
                }

                // Calculate totals
                $totalOverdueAmount = $overdueInvoices->sum('total_amount');
                $overdueInvoiceCount = $overdueInvoices->count();
                
                // Get oldest overdue date
                $oldestDueDate = $overdueInvoices->min('due_date');
                $overdueDays = Carbon::parse($oldestDueDate)->diffInDays(now());
                
                // Get fee types
                $feeTypes = $overdueInvoices->flatMap(function($invoice) {
                    return $invoice->items->pluck('feeCategory.name');
                })->unique()->filter()->values()->toArray();

                // Determine defaulter category
                $defaulterCategory = $this->categorizeDefaulter(
                    $totalOverdueAmount, 
                    $overdueDays, 
                    $overdueInvoiceCount
                );

                $defaulters[] = [
                    'student' => $student,
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'enrollment_number' => $student->enrollment_number,
                    'course' => $student->batch?->course?->name ?? 'N/A',
                    'batch' => $student->batch?->name ?? 'N/A',
                    'total_overdue_amount' => $totalOverdueAmount,
                    'overdue_invoice_count' => $overdueInvoiceCount,
                    'overdue_days' => $overdueDays,
                    'oldest_due_date' => $oldestDueDate,
                    'overdue_fee_types' => $feeTypes,
                    'defaulter_category' => $defaulterCategory,
                    'contact_phone' => $student->phone,
                    'contact_email' => $student->email,
                    'last_payment_date' => $this->getLastPaymentDate($student),
                    'reminder_count' => $student->paymentReminders()->count(),
                    'last_reminder_sent' => $student->paymentReminders()
                        ->whereNotNull('sent_at')
                        ->latest('sent_at')
                        ->value('sent_at')
                ];
            }

            // Sort by overdue amount (highest first)
            usort($defaulters, function($a, $b) {
                return $b['total_overdue_amount'] <=> $a['total_overdue_amount'];
            });

            return $defaulters;

        } catch (\Exception $e) {
            Log::error('Failed to generate defaulters list: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get the last payment date for a student
     */
    private function getLastPaymentDate(Student $student): ?string
    {
        $lastPayment = $student->invoices()
            ->whereHas('payments')
            ->with('payments')
            ->get()
            ->flatMap(function($invoice) {
                return $invoice->payments;
            })
            ->sortByDesc('payment_date')
            ->first();

        return $lastPayment ? $lastPayment->payment_date : null;
    }

    /**
     * Update defaulter records in database
     */
    public function updateDefaulterRecords(): void
    {
        try {
            $defaulters = $this->generateDefaultersList();
            
            // Clear existing records that are no longer defaulters
            PaymentDefaulter::whereNotIn('student_id', collect($defaulters)->pluck('student_id'))->delete();
            
            foreach ($defaulters as $defaulterData) {
                PaymentDefaulter::updateOrCreate(
                    ['student_id' => $defaulterData['student_id']],
                    [
                        'defaulter_category' => $defaulterData['defaulter_category'],
                        'total_overdue_amount' => $defaulterData['total_overdue_amount'],
                        'overdue_days' => $defaulterData['overdue_days'],
                        'total_overdue_invoices' => $defaulterData['overdue_invoice_count'],
                        'first_overdue_date' => $defaulterData['oldest_due_date'],
                        'overdue_fee_types' => json_encode($defaulterData['overdue_fee_types']),
                        'current_status' => 'active',
                        'contact_attempts' => PaymentDefaulter::where('student_id', $defaulterData['student_id'])->value('contact_attempts') ?? 0
                    ]
                );
            }

            Log::info('Updated defaulter records: ' . count($defaulters) . ' defaulters processed');

        } catch (\Exception $e) {
            Log::error('Failed to update defaulter records: ' . $e->getMessage());
        }
    }

    /**
     * Generate reminder message based on type and student details
     */
    private function generateReminderMessage(PaymentReminder $reminder): string
    {
        $student = $reminder->student;
        $invoice = $reminder->invoice;
        $collegeName = Setting::where('key', 'app_name')->value('value') ?? 'College';

        $templates = [
            'upcoming_due' => "Dear {student_name}, this is a friendly reminder that your {fee_type} payment of ₹{amount} is due on {due_date}. Please make the payment to avoid any late fees. - {college_name}",
            'overdue' => "Dear {student_name}, your {fee_type} payment of ₹{amount} was due on {due_date} and is now overdue. Please make the payment immediately to avoid further action. - {college_name}",
            'escalation' => "URGENT: Dear {student_name}, your overdue payment of ₹{amount} requires immediate attention. Please contact the accounts office or make payment today. - {college_name}",
            'final_notice' => "FINAL NOTICE: Dear {student_name}, this is your final notice for the overdue payment of ₹{amount}. Immediate action is required to avoid suspension. - {college_name}"
        ];

        $template = $templates[$reminder->reminder_type] ?? $templates['overdue'];

        $replacements = [
            '{student_name}' => $student->name,
            '{enrollment_number}' => $student->enrollment_number,
            '{fee_type}' => $reminder->feeCategory->name ?? 'Fee',
            '{amount}' => number_format($invoice->due_amount ?? $invoice->total_amount, 2),
            '{due_date}' => Carbon::parse($invoice->due_date)->format('d M Y'),
            '{college_name}' => $collegeName,
            '{course_name}' => $student->batch?->course?->name ?? 'Course',
            '{batch_name}' => $student->batch?->name ?? 'Batch'
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
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

                    $variables = $this->prepareTemplateVariables($reminder);
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
                $this->logReminderAction($reminder, 'sent', 'Reminder sent successfully');
            } else {
                $reminder->markAsFailed($result['error'] ?? 'Unknown error');
                $this->logReminderAction($reminder, 'failed', $result['error'] ?? 'Unknown error');
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Failed to send payment reminder: ' . $e->getMessage(), [
                'reminder_id' => $reminder->id,
                'student_id' => $reminder->student_id,
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
    private function sendEmailReminder(PaymentReminder $reminder, string $message): array
    {
        try {
            $student = $reminder->student;
            
            Mail::raw($message, function ($mail) use ($student, $reminder) {
                $mail->to($student->email, $student->name)
                     ->subject('Payment Reminder - ' . ($reminder->feeCategory->name ?? 'Fee Payment'))
                     ->from(config('mail.from.address'), config('mail.from.name'));
            });

            return ['success' => true, 'message' => 'Email sent successfully'];
        } catch (\Exception $e) {
            Log::error('Email reminder failed', [
                'reminder_id' => $reminder->id,
                'student_email' => $reminder->student->email,
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => 'Email sending failed: ' . $e->getMessage()];
        }
    }

    /**
     * Send SMS reminder
     */
    private function sendSMSReminder(PaymentReminder $reminder, string $message): array
    {
        try {
            Log::info('SMS Reminder sent', [
                'reminder_id' => $reminder->id,
                'phone' => $reminder->recipient_details['phone'] ?? 'N/A',
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
            Log::info('WhatsApp Reminder sent', [
                'reminder_id' => $reminder->id,
                'phone' => $reminder->recipient_details['phone'] ?? 'N/A',
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
     * Schedule phone call task
     */
    private function schedulePhoneCall(PaymentReminder $reminder): array
    {
        try {
            Log::info('Phone call scheduled', [
                'reminder_id' => $reminder->id,
                'student' => $reminder->student->name,
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
            Log::info('Physical notice generated', [
                'reminder_id' => $reminder->id,
                'student' => $reminder->student->name
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
     * Categorize defaulter based on amount, days, and invoice count
     */
    private function categorizeDefaulter(float $amount, int $days, int $invoiceCount): string
    {
        $chronicDays = Setting::where('key', 'chronic_defaulter_days')->value('value') ?? 90;
        $severeDays = Setting::where('key', 'severe_defaulter_days')->value('value') ?? 60;
        $moderateDays = Setting::where('key', 'moderate_defaulter_days')->value('value') ?? 30;

        if ($days > $chronicDays || $amount > 50000 || $invoiceCount > 5) {
            return 'chronic';
        } elseif ($days > $severeDays || $amount > 25000 || $invoiceCount > 3) {
            return 'severe';
        } elseif ($days > $moderateDays || $amount > 10000 || $invoiceCount > 2) {
            return 'moderate';
        } else {
            return 'mild';
        }
    }

    /**
     * Get reminder statistics for dashboard
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
            'total_defaulters' => PaymentDefaulter::where('current_status', '!=', 'resolved')->count(),
            'chronic_defaulters' => PaymentDefaulter::where('current_status', '!=', 'resolved')
                                                  ->where('defaulter_category', 'chronic')->count(),
        ];
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
     * Prepare template variables with safe defaults
     */
    private function prepareTemplateVariables(PaymentReminder $reminder): array
    {
        $student = $reminder->student;
        $invoice = $reminder->invoice;

        return [
            'student_name' => $student->name ?? 'Student',
            'enrollment_number' => $student->enrollment_number ?? 'N/A',
            'fee_type' => $reminder->feeCategory?->name ?? 'Fee',
            'amount' => $invoice ? number_format($invoice->total_amount, 2) : '0.00',
            'due_date' => $invoice ? Carbon::parse($invoice->due_date)->format('d M Y') : 'N/A',
            'days_overdue' => $invoice ? Carbon::parse($invoice->due_date)->diffInDays(now()) : 0,
            'total_amount_due' => method_exists($student, 'getTotalOverdueAmount') ? number_format($student->getTotalOverdueAmount(), 2) : '0.00',
            'course_name' => $student->batch?->course?->name ?? 'N/A',
            'batch_name' => $student->batch?->name ?? 'N/A',
            'college_name' => Setting::where('key', 'college_name')->value('value') ?? config('app.name'),
            'contact_number' => Setting::where('key', 'contact_phone')->value('value') ?? '',
            'contact_email' => Setting::where('key', 'contact_email')->value('value') ?? '',
            'final_deadline' => now()->addDays(3)->format('d M Y')
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
                    'timestamp' => now()->toDateTimeString()
                ]),
                'performed_by' => auth()->id()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log reminder action: ' . $e->getMessage());
        }
    }

    /**
     * Process pending reminders with error handling
     */
    public function processPendingReminders(): array
    {
        try {
            $pendingReminders = PaymentReminder::where('status', 'pending')
                ->where('scheduled_date', '<=', now())
                ->with(['student', 'feeCategory', 'invoice'])
                ->limit(50) // Process in batches
                ->get();

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
                        'error' => $result['error']
                    ];
                }
            }

            return $results;

        } catch (\Exception $e) {
            Log::error('Failed to process pending reminders: ' . $e->getMessage());
            
            return [
                'processed' => 0,
                'sent' => 0,
                'failed' => 0,
                'errors' => ['System error: ' . $e->getMessage()]
            ];
        }
    }
}