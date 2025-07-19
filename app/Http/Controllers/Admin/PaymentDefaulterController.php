<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentDefaulter;
use App\Models\Student;
use App\Models\User;
use App\Services\PaymentReminderService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class PaymentDefaulterController extends Controller
{
    protected $reminderService;

    public function __construct(PaymentReminderService $reminderService)
    {
        $this->reminderService = $reminderService;
    }

    /**
     * Display defaulters listing
     */
    public function index(Request $request)
    {
        $query = PaymentDefaulter::with(['student.batch.course', 'assignedUser']);

        // Apply filters
        if ($request->filled('category')) {
            $query->byCategory($request->category);
        }

        if ($request->filled('status')) {
            $query->where('current_status', $request->status);
        }

        if ($request->filled('assigned_to')) {
            $query->assignedTo($request->assigned_to);
        }

        if ($request->filled('min_amount')) {
            $query->where('total_overdue_amount', '>=', $request->min_amount);
        }

        if ($request->filled('max_days')) {
            $query->where('overdue_days', '<=', $request->max_days);
        }

        if ($request->filled('search')) {
            $query->whereHas('student', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('enrollment_number', 'like', '%' . $request->search . '%');
            });
        }

        // Sorting
        $sortBy = $request->get('sort', 'priority_score');
        $sortDirection = $request->get('direction', 'desc');
        
        switch ($sortBy) {
            case 'amount':
                $query->orderBy('total_overdue_amount', $sortDirection);
                break;
            case 'days':
                $query->orderBy('overdue_days', $sortDirection);
                break;
            case 'category':
                $query->orderBy('defaulter_category', $sortDirection);
                break;
            default:
                // Priority score calculation in SQL
                $query->orderByRaw("
                    CASE defaulter_category 
                        WHEN 'chronic' THEN 40 
                        WHEN 'severe' THEN 30 
                        WHEN 'moderate' THEN 20 
                        WHEN 'mild' THEN 10 
                        ELSE 0 
                    END +
                    CASE 
                        WHEN total_overdue_amount > 50000 THEN 30
                        WHEN total_overdue_amount > 25000 THEN 20
                        WHEN total_overdue_amount > 10000 THEN 10
                        ELSE 0
                    END +
                    CASE
                        WHEN overdue_days > 90 THEN 20
                        WHEN overdue_days > 60 THEN 15
                        WHEN overdue_days > 30 THEN 10
                        ELSE 0
                    END - LEAST(contact_attempts * 2, 10) {$sortDirection}
                ");
        }

        $defaulters = $query->paginate(20);

        // Statistics
        $stats = [
            'total_active' => PaymentDefaulter::active()->count(),
            'total_amount' => PaymentDefaulter::active()->sum('total_overdue_amount'),
            'by_category' => PaymentDefaulter::active()
                                           ->selectRaw('defaulter_category, COUNT(*) as count, SUM(total_overdue_amount) as amount')
                                           ->groupBy('defaulter_category')
                                           ->get()
                                           ->keyBy('defaulter_category'),
            'needing_action' => PaymentDefaulter::needingAction()->count(),
            'assigned_count' => PaymentDefaulter::active()->whereNotNull('assigned_to')->count(),
        ];

        // Filter options
        $filterOptions = [
            'categories' => [
                'mild' => 'Mild (1-15 days)',
                'moderate' => 'Moderate (16-30 days)',
                'severe' => 'Severe (31-60 days)',
                'chronic' => 'Chronic (60+ days)'
            ],
            'statuses' => [
                'active' => 'Active',
                'contact_pending' => 'Contact Pending',
                'payment_promised' => 'Payment Promised',
                'escalated' => 'Escalated',
                'resolved' => 'Resolved'
            ],
            'staff' => User::role(['admin', 'staff'])->orderBy('name')->get(),
        ];

        return view('admin.payment_defaulters.index', compact('defaulters', 'stats', 'filterOptions'));
    }

    /**
     * Show defaulter details
     */
    public function show(Student $student)
    {
        $defaulter = PaymentDefaulter::where('student_id', $student->id)->first();
        
        if (!$defaulter) {
            return redirect()->route('admin.payment-defaulters.index')
                           ->with('error', 'Student is not currently a defaulter.');
        }

        $student->load(['batch.course', 'invoices.items.feeCategory', 'invoices.payments']);
        $defaulter->load(['assignedUser']);

        // Get overdue invoices
        $overdueInvoices = $student->invoices()
                                 ->where('due_date', '<', now())
                                 ->where('status', '!=', 'paid')
                                 ->with(['items.feeCategory', 'payments'])
                                 ->get();

        // Payment history
        $paymentHistory = $student->invoices()
                                ->with(['payments'])
                                ->whereHas('payments')
                                ->latest()
                                ->get();

        // Related reminders
        $reminders = $student->paymentReminders()
                           ->with(['feeCategory'])
                           ->latest()
                           ->limit(10)
                           ->get();

        return view('admin.payment_defaulters.show', compact(
            'student', 'defaulter', 'overdueInvoices', 'paymentHistory', 'reminders'
        ));
    }

/**
     * Send reminder to specific defaulter
     */
    public function sendReminder(Request $request, Student $student)
    {
        $request->validate([
            'reminder_type' => 'required|in:overdue,escalation,final_notice',
            'channel' => 'required|in:email,sms,whatsapp,phone_call',
            'message' => 'nullable|string|max:500',
            'schedule_for' => 'nullable|date|after_or_equal:now'
        ]);

        try {
            $defaulter = PaymentDefaulter::where('student_id', $student->id)->first();
            
            if (!$defaulter) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student is not a defaulter.'
                ], 422);
            }

            // Get the most relevant overdue invoice
            $invoice = $student->invoices()
                             ->where('due_date', '<', now())
                             ->where('status', '!=', 'paid')
                             ->orderBy('due_date')
                             ->first();

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'No overdue invoices found for this student.'
                ], 422);
            }

            $feeCategory = $invoice->items->first()?->feeCategory;
            $scheduledDate = $request->schedule_for ? 
                Carbon::parse($request->schedule_for) : 
                now();

            // Validate contact information based on channel
            $contactInfo = $this->validateContactInfo($student, $request->channel);
            if (!$contactInfo['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $contactInfo['message']
                ], 422);
            }

            // Get or create reminder template
            $template = PaymentReminderTemplate::active()
                ->forType($request->reminder_type)
                ->forChannel($request->channel)
                ->first();

            if (!$template) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active template found for this reminder type and channel.'
                ], 422);
            }

            // Prepare template variables
            $variables = [
                'student_name' => $student->name,
                'enrollment_number' => $student->enrollment_number,
                'fee_type' => $feeCategory?->name ?? 'Fee',
                'amount' => number_format($invoice->total_amount, 2),
                'due_date' => Carbon::parse($invoice->due_date)->format('d M Y'),
                'days_overdue' => Carbon::parse($invoice->due_date)->diffInDays(now()),
                'total_amount_due' => number_format($student->invoices()
                    ->where('status', '!=', 'paid')
                    ->sum('total_amount'), 2),
                'course_name' => $student->batch?->course?->name ?? 'N/A',
                'batch_name' => $student->batch?->name ?? 'N/A',
                'college_name' => Setting::get('college_name', config('app.name')),
                'contact_number' => Setting::get('contact_phone', ''),
                'contact_email' => Setting::get('contact_email', ''),
                'final_deadline' => now()->addDays(3)->format('d M Y')
            ];

            // Create the reminder
            $reminder = PaymentReminder::create([
                'student_id' => $student->id,
                'invoice_id' => $invoice->id,
                'fee_category_id' => $feeCategory?->id,
                'reminder_type' => $request->reminder_type,
                'channel' => $request->channel,
                'scheduled_date' => $scheduledDate,
                'recipient_details' => json_encode($contactInfo['details']),
                'message_content' => $request->message ?: $template->renderMessage($variables),
                'status' => $scheduledDate->isFuture() ? 'pending' : 'pending'
            ]);

            // If scheduled for immediate sending, try to send now
            if ($scheduledDate->isPast() || $scheduledDate->isCurrentMinute()) {
                $reminderService = app(PaymentReminderService::class);
                $sendResult = $reminderService->sendReminder($reminder);
                
                if (!$sendResult['success']) {
                    $reminder->update([
                        'status' => 'failed',
                        'error_message' => $sendResult['error']
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Reminder created but failed to send: ' . $sendResult['error']
                    ], 422);
                }
            }

            // Update defaulter contact information
            $defaulter->increment('contact_attempts');
            $defaulter->update([
                'last_contact_date' => now(),
                'next_action_date' => now()->addDays(7)
            ]);

            // Add note to defaulter record
            $notes = $defaulter->notes ? json_decode($defaulter->notes, true) : [];
            $notes[] = [
                'date' => now()->toDateTimeString(),
                'action' => 'reminder_sent',
                'details' => "Sent {$request->reminder_type} reminder via {$request->channel}",
                'reminder_id' => $reminder->id,
                'user_id' => auth()->id()
            ];
            $defaulter->update(['notes' => json_encode($notes)]);

            return response()->json([
                'success' => true,
                'message' => $scheduledDate->isFuture() 
                    ? 'Reminder scheduled successfully for ' . $scheduledDate->format('d M Y H:i')
                    : 'Reminder sent successfully',
                'reminder_id' => $reminder->id,
                'scheduled_for' => $scheduledDate->toDateTimeString()
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to send payment reminder: ' . $e->getMessage(), [
                'student_id' => $student->id,
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process reminder: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate contact information for the selected channel
     */
    private function validateContactInfo(Student $student, string $channel): array
    {
        switch ($channel) {
            case 'email':
                if (empty($student->email)) {
                    return [
                        'valid' => false,
                        'message' => 'Student email address is not available.'
                    ];
                }
                return [
                    'valid' => true,
                    'details' => ['email' => $student->email]
                ];

            case 'sms':
            case 'whatsapp':
                if (empty($student->phone)) {
                    return [
                        'valid' => false,
                        'message' => 'Student phone number is not available.'
                    ];
                }
                return [
                    'valid' => true,
                    'details' => ['phone' => $student->phone]
                ];

            case 'phone_call':
                if (empty($student->phone)) {
                    return [
                        'valid' => false,
                        'message' => 'Student phone number is not available for call.'
                    ];
                }
                return [
                    'valid' => true,
                    'details' => ['phone' => $student->phone]
                ];

            default:
                return [
                    'valid' => false,
                    'message' => 'Invalid communication channel selected.'
                ];
        }
    }

    /**
     * Bulk send reminders
     */
    public function bulkSendReminders(Request $request)
    {
        $request->validate([
            'defaulter_ids' => 'required|array|min:1',
            'defaulter_ids.*' => 'exists:payment_defaulters,id',
            'reminder_type' => 'required|in:overdue,escalation,final_notice',
            'channel' => 'required|in:email,sms,whatsapp,phone_call',
            'schedule_for' => 'nullable|date|after_or_equal:now'
        ]);

        try {
            $defaulters = PaymentDefaulter::with(['student'])
                                        ->whereIn('id', $request->defaulter_ids)
                                        ->get();

            $results = ['success' => 0, 'failed' => 0];
            $scheduledDate = $request->schedule_for ? Carbon::parse($request->schedule_for) : now();

            foreach ($defaulters as $defaulter) {
                try {
                    $student = $defaulter->student;
                    
                    // Get overdue invoice
                    $invoice = $student->invoices()
                                     ->where('due_date', '<', now())
                                     ->where('status', '!=', 'paid')
                                     ->orderBy('due_date')
                                     ->first();

                    if (!$invoice) {
                        $results['failed']++;
                        continue;
                    }

                    $feeCategory = $invoice->items->first()?->feeCategory;

                    PaymentReminder::create([
                        'student_id' => $student->id,
                        'invoice_id' => $invoice->id,
                        'fee_category_id' => $feeCategory?->id,
                        'reminder_type' => $request->reminder_type,
                        'channel' => $request->channel,
                        'scheduled_date' => $scheduledDate,
                        'status' => 'pending',
                        'recipient_details' => [
                            'email' => $student->email,
                            'phone' => $student->student_mobile ?? $student->father_mobile,
                            'student_name' => $student->name,
                            'enrollment_number' => $student->enrollment_number,
                        ]
                    ]);

                    $defaulter->recordContact($request->channel, 'bulk_reminder_scheduled', 'Bulk reminder scheduled');
                    $results['success']++;

                } catch (\Exception $e) {
                    Log::error("Bulk reminder failed for defaulter {$defaulter->id}: " . $e->getMessage());
                    $results['failed']++;
                }
            }

            $message = "Bulk reminders completed: {$results['success']} scheduled";
            if ($results['failed'] > 0) {
                $message .= ", {$results['failed']} failed";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'results' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk reminder operation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark defaulter as resolved
     */
    public function markResolved(Request $request, Student $student)
    {
        $request->validate([
            'resolution_note' => 'nullable|string|max:500'
        ]);

        try {
            $defaulter = PaymentDefaulter::where('student_id', $student->id)->first();
            
            if (!$defaulter) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student is not currently a defaulter.'
                ], 422);
            }

            $defaulter->markResolved($request->resolution_note);

            return response()->json([
                'success' => true,
                'message' => "Successfully marked {$student->name} as resolved."
            ]);

        } catch (\Exception $e) {
            Log::error('Mark resolved failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark as resolved: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add note to defaulter
     */
    public function addNote(Request $request, Student $student)
    {
        $request->validate([
            'note' => 'required|string|max:1000',
            'next_action_date' => 'nullable|date|after:today'
        ]);

        try {
            $defaulter = PaymentDefaulter::where('student_id', $student->id)->first();
            
            if (!$defaulter) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student is not currently a defaulter.'
                ], 422);
            }

            $defaulter->addNote($request->note);
            
            if ($request->next_action_date) {
                $defaulter->update([
                    'next_action_date' => $request->next_action_date,
                    'assigned_to' => auth()->id()
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Note added successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Add note failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to add note: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign defaulter to staff member
     */
    public function assign(Request $request, Student $student)
    {
        $request->validate([
            'assigned_to' => 'required|exists:users,id',
            'note' => 'nullable|string|max:500'
        ]);

        try {
            $defaulter = PaymentDefaulter::where('student_id', $student->id)->first();
            
            if (!$defaulter) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student is not currently a defaulter.'
                ], 422);
            }

            $assignedUser = User::find($request->assigned_to);
            
            $defaulter->update(['assigned_to' => $request->assigned_to]);
            
            if ($request->note) {
                $defaulter->addNote("Assigned to {$assignedUser->name}. " . $request->note);
            } else {
                $defaulter->addNote("Assigned to {$assignedUser->name}");
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully assigned to {$assignedUser->name}."
            ]);

        } catch (\Exception $e) {
            Log::error('Assignment failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update defaulter status
     */
    public function updateStatus(Request $request, Student $student)
    {
        $request->validate([
            'status' => 'required|in:active,contact_pending,payment_promised,escalated,resolved',
            'note' => 'nullable|string|max:500'
        ]);

        try {
            $defaulter = PaymentDefaulter::where('student_id', $student->id)->first();
            
            if (!$defaulter) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student is not currently a defaulter.'
                ], 422);
            }

            $oldStatus = $defaulter->current_status;
            $defaulter->update(['current_status' => $request->status]);
            
            $noteText = "Status changed from {$oldStatus} to {$request->status}";
            if ($request->note) {
                $noteText .= ". " . $request->note;
            }
            
            $defaulter->addNote($noteText);

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Status update failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export defaulters list
     */
    public function export(Request $request)
    {
        $filters = $request->only(['category', 'status', 'assigned_to', 'min_amount', 'max_days', 'search']);
        
        // For now, create a simple CSV export
        $defaulters = PaymentDefaulter::with(['student.batch.course', 'assignedUser'])
                                    ->when($request->category, fn($q) => $q->byCategory($request->category))
                                    ->when($request->status, fn($q) => $q->where('current_status', $request->status))
                                    ->when($request->assigned_to, fn($q) => $q->assignedTo($request->assigned_to))
                                    ->get();

        $filename = 'payment-defaulters-' . now()->format('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($defaulters) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'Student Name', 'Enrollment Number', 'Course', 'Batch',
                'Category', 'Status', 'Overdue Amount', 'Overdue Days',
                'Invoice Count', 'Assigned To', 'Last Contact', 'Contact Attempts'
            ]);

            // CSV data
            foreach ($defaulters as $defaulter) {
                fputcsv($file, [
                    $defaulter->student->name,
                    $defaulter->student->enrollment_number,
                    $defaulter->student->batch->course->name ?? '',
                    $defaulter->student->batch->name ?? '',
                    ucfirst($defaulter->defaulter_category),
                    ucfirst(str_replace('_', ' ', $defaulter->current_status)),
                    $defaulter->total_overdue_amount,
                    $defaulter->overdue_days,
                    $defaulter->overdue_invoice_count,
                    $defaulter->assignedUser->name ?? 'Unassigned',
                    $defaulter->last_contact_date ? $defaulter->last_contact_date->format('Y-m-d') : '',
                    $defaulter->contact_attempts
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Analytics dashboard
     */
    public function analytics()
    {
        $analytics = [
            'overview' => [
                'total_defaulters' => PaymentDefaulter::active()->count(),
                'total_overdue_amount' => PaymentDefaulter::active()->sum('total_overdue_amount'),
                'avg_overdue_days' => PaymentDefaulter::active()->avg('overdue_days'),
                'resolution_rate' => $this->calculateResolutionRate(),
            ],
            'by_category' => PaymentDefaulter::active()
                                           ->selectRaw('defaulter_category, COUNT(*) as count, SUM(total_overdue_amount) as amount, AVG(overdue_days) as avg_days')
                                           ->groupBy('defaulter_category')
                                           ->get(),
            'trending' => $this->getTrendingData(),
            'staff_performance' => $this->getStaffPerformance(),
            'recovery_insights' => $this->getRecoveryInsights(),
        ];

        return view('admin.payment_defaulters.analytics', compact('analytics'));
    }

    /**
     * Helper methods
     */
    private function calculateResolutionRate(): float
    {
        $totalResolved = PaymentDefaulter::resolved()->count();
        $totalEver = PaymentDefaulter::count();
        
        return $totalEver > 0 ? round(($totalResolved / $totalEver) * 100, 2) : 0;
    }

    private function getTrendingData(): array
    {
        $last12Months = collect(range(0, 11))->map(function($i) {
            $date = now()->subMonths($i);
            return [
                'month' => $date->format('M Y'),
                'new_defaulters' => PaymentDefaulter::whereYear('created_at', $date->year)
                                                  ->whereMonth('created_at', $date->month)
                                                  ->count(),
                'resolved' => PaymentDefaulter::whereYear('resolution_date', $date->year)
                                             ->whereMonth('resolution_date', $date->month)
                                             ->count(),
            ];
        })->reverse()->values();

        return $last12Months->toArray();
    }

    private function getStaffPerformance(): array
    {
        return PaymentDefaulter::resolved()
                              ->whereNotNull('assigned_to')
                              ->with('assignedUser')
                              ->get()
                              ->groupBy('assigned_to')
                              ->map(function($defaulters, $userId) {
                                  $user = $defaulters->first()->assignedUser;
                                  return [
                                      'name' => $user->name,
                                      'resolved_count' => $defaulters->count(),
                                      'total_recovered' => $defaulters->sum('total_overdue_amount'),
                                      'avg_resolution_days' => $defaulters->avg(function($d) {
                                          return $d->created_at->diffInDays($d->resolution_date);
                                      })
                                  ];
                              })
                              ->sortByDesc('resolved_count')
                              ->take(10)
                              ->values()
                              ->toArray();
    }

    private function getRecoveryInsights(): array
    {
        return [
            'best_performing_channel' => $this->getBestPerformingChannel(),
            'optimal_contact_frequency' => $this->getOptimalContactFrequency(),
            'seasonal_patterns' => $this->getSeasonalPatterns(),
        ];
    }

    private function getBestPerformingChannel(): string
    {
        // This would require tracking payment recovery after reminders
        // For now, return most used channel
        $channel = PaymentReminder::sent()
                                 ->selectRaw('channel, COUNT(*) as count')
                                 ->groupBy('channel')
                                 ->orderBy('count', 'desc')
                                 ->first();
        
        return $channel ? ucfirst($channel->channel) : 'Email';
    }

    private function getOptimalContactFrequency(): string
    {
        // Analyze resolved defaulters to find optimal contact patterns
        $resolved = PaymentDefaulter::resolved()->avg('contact_attempts');
        return $resolved ? round($resolved) . ' contacts' : '3-4 contacts';
    }

    private function getSeasonalPatterns(): array
    {
        // Analyze defaulter creation by month to identify patterns
        return PaymentDefaulter::selectRaw('MONTH(created_at) as month, COUNT(*) as count')
                              ->groupBy('month')
                              ->orderBy('month')
                              ->get()
                              ->map(function($item) {
                                  return [
                                      'month' => Carbon::create()->month($item->month)->format('M'),
                                      'count' => $item->count
                                  ];
                              })
                              ->toArray();
    }
}