<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendPaymentReminder;
use App\Models\FeeCategory;
use App\Models\PaymentReminder;
use App\Models\Student;
use App\Services\ComponentPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentReminderController extends Controller
{
    protected $reminderService;

    protected $paymentService;

    public function __construct(ComponentPaymentService $paymentService)
    {
        $this->paymentService = $paymentService;

        // ✅ FIXED: Use ComponentPaymentReminderService instead of PaymentReminderService
        if (class_exists('\App\Services\ComponentPaymentReminderService')) {
            $this->reminderService = app(\App\Services\ComponentPaymentReminderService::class);
        } else {
            $this->reminderService = null;
        }
    }

    /**
     * Dashboard with reminder statistics (Component-based)
     */
    public function dashboard()
    {
        $stats = [
            'total_reminders' => 0,
            'total' => 0,
            'sent_reminders' => 0,
            'pending_reminders' => 0,
            'failed_reminders' => 0,
            'total_active' => 0,
            'success_rate' => 0,
        ];

        try {
            $serviceStats = $this->reminderService->getReminderStats();
            $stats = array_merge($stats, $serviceStats);
            $stats['total'] = $stats['total_reminders'];
        } catch (\Exception $e) {
            \Log::error('Error in payment reminder dashboard: '.$e->getMessage());
        }

        $dashboardData = [
            'stats' => $stats,
            'collection_efficiency' => $this->getCollectionEfficiency(),
            'component_overview' => $this->paymentService->getComponentReminderOverview(),
            'recent_reminders' => $this->getRecentReminders(),
            'overdue_by_component' => $this->getOverdueByComponent(),
            'reminder_effectiveness' => $this->getReminderEffectiveness(),
        ];

        return view('admin.payment-reminders.dashboard', $dashboardData);
    }

    /**
     * Display a listing of payment reminders
     */
    public function index(Request $request)
    {
        // Initialize stats with ALL required keys
        $stats = [
            'total_reminders' => 0,
            'total' => 0, // ✅ Add missing 'total' key
            'sent_reminders' => 0,
            'pending_reminders' => 0,
            'failed_reminders' => 0,
            'total_active' => 0,
            'success_rate' => 0,
        ];

        try {
            // Get stats from service with fallback
            $serviceStats = $this->reminderService->getReminderStats();
            $stats = array_merge($stats, $serviceStats);

            // Ensure 'total' key is set (alias for total_reminders)
            $stats['total'] = $stats['total_reminders'];

        } catch (\Exception $e) {
            \Log::error('Error calculating payment reminder stats: '.$e->getMessage());
        }

        $reminders = collect();

        try {
            $remindersQuery = PaymentReminder::with(['student.batch.course', 'feeCategory'])
                ->orderBy('created_at', 'desc');

            // Apply filters
            if ($request->filled('status')) {
                $remindersQuery->where('status', $request->status);
            }

            if ($request->filled('channel')) {
                $remindersQuery->where('channel', $request->channel);
            }

            if ($request->filled('reminder_type')) {
                $remindersQuery->where('reminder_type', $request->reminder_type);
            }

            if ($request->filled('fee_category_id')) {
                $remindersQuery->where('fee_category_id', $request->fee_category_id);
            }

            $reminders = $remindersQuery->paginate(20);

        } catch (\Exception $e) {
            \Log::error('Error fetching payment reminders: '.$e->getMessage());
        }

        $feeCategories = FeeCategory::all();

        return view('admin.payment-reminders.index', compact('reminders', 'stats', 'feeCategories'));
    }

    /**
     * Show defaulters
     */
    public function defaulters(Request $request)
    {
        // Initialize stats with safe defaults
        $stats = [
            'total_defaulters' => 0,
            'total_active' => 0,
            'total_amount' => 0,
            'chronic_defaulters' => 0,
            'severe_defaulters' => 0,
            'moderate_defaulters' => 0,
            'resolved_defaulters' => 0,
            'total_overdue_amount' => 0,
            'recovery_rate' => 0,
        ];

        try {
            $serviceStats = $this->reminderService->getDefaulterStats();
            $stats = array_merge($stats, $serviceStats);
            $stats['total_amount'] = $stats['total_overdue_amount'];
        } catch (\Exception $e) {
            \Log::error('Error getting defaulter stats: '.$e->getMessage());
        }

        return view('admin.payment-defaulters.index', compact('stats'));
    }

    /**
     * Show the form for creating a new reminder (Component-based)
     */
    public function create(Request $request): View
    {
        $students = Student::with('batch.course')->orderBy('name')->get();
        $feeCategories = FeeCategory::orderBy('name')->get();

        // Pre-select student if provided in query parameter
        $selectedStudentId = $request->get('student_id');
        $selectedStudent = null;

        if ($selectedStudentId) {
            $selectedStudent = Student::with(['studentFees.feeCategory'])
                ->find($selectedStudentId);
        }

        return view('admin.payment-reminders.create', compact(
            'students',
            'feeCategories',
            'selectedStudentId',
            'selectedStudent'
        ));
    }

    /**
     * Store a newly created reminder (Component-based)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'student_fee_id' => 'nullable|exists:student_fees,id', // Component-based reference
            'fee_category_id' => 'nullable|exists:fee_categories,id',
            'reminder_type' => 'required|in:upcoming_due,overdue,escalation,final_notice',
            'channel' => 'required|in:email,sms,whatsapp,phone_call',
            'scheduled_date' => 'required|date|after_or_equal:now',
            'status' => 'required|in:pending,scheduled',
            'message_content' => 'nullable|string|max:1000',
        ]);

        $student = Student::findOrFail($validated['student_id']);

        // Generate recipient details
        $recipientDetails = [
            'email' => $student->email,
            'phone' => $student->student_mobile ?: $student->father_mobile,
            'student_name' => $student->name,
            'enrollment_number' => $student->enrollment_number,
        ];

        // Get overdue amount for specific component or total
        $overdueAmount = 0;
        if ($validated['fee_category_id']) {
            $overdueAmount = $this->paymentService->getStudentOverdueAmount($student, $validated['fee_category_id']);
        } else {
            $overdueAmount = method_exists($student, 'getTotalOverdueAmount') ?
                $student->getTotalOverdueAmount() : 0;
        }

        $reminder = PaymentReminder::create(array_merge($validated, [
            'recipient_details' => $recipientDetails,
            'overdue_amount' => $overdueAmount,
            'status' => $request->has('send_now') ? 'pending' : $validated['status'],
        ]));

        // If send_now is requested, attempt to send immediately
        if ($request->has('send_now') && $this->reminderService) {
            $result = $this->reminderService->sendReminder($reminder);

            if ($result['success']) {
                return redirect()->route('admin.payment-reminders.index')
                    ->with('success', 'Reminder created and sent successfully!');
            } else {
                return redirect()->route('admin.payment-reminders.index')
                    ->with('warning', 'Reminder created but failed to send: '.$result['error']);
            }
        }

        return redirect()->route('admin.payment-reminders.index')
            ->with('success', 'Payment reminder created successfully!');
    }

    /**
     * Show the form for editing the specified reminder (Component-based)
     */
    public function edit(PaymentReminder $paymentReminder): View
    {
        $paymentReminder->load(['student.studentFees.feeCategory', 'feeCategory']);
        $feeCategories = FeeCategory::orderBy('name')->get();

        // Get student's outstanding fees for context
        $outstandingFees = [];
        if (method_exists($this->paymentService, 'getStudentOutstandingFees')) {
            $outstandingFees = $this->paymentService->getStudentOutstandingFees($paymentReminder->student);
        }

        return view('admin.payment-reminders.edit', compact(
            'paymentReminder',
            'feeCategories',
            'outstandingFees'
        ))->with('reminder', $paymentReminder);
    }

    /**
     * Update the specified reminder
     */
    public function update(Request $request, PaymentReminder $paymentReminder)
    {
        $validated = $request->validate([
            'student_fee_id' => 'nullable|exists:student_fees,id',
            'fee_category_id' => 'nullable|exists:fee_categories,id',
            'reminder_type' => 'required|in:upcoming_due,overdue,escalation,final_notice',
            'channel' => 'required|in:email,sms,whatsapp,phone_call',
            'scheduled_date' => 'required|date',
            'status' => 'required|in:pending,scheduled,sent,failed,cancelled',
            'message_content' => 'nullable|string|max:1000',
        ]);

        $paymentReminder->update($validated);

        // Handle immediate actions
        $action = $request->input('action');
        if ($action && $this->reminderService) {
            $result = $this->handleReminderAction($paymentReminder, $action);

            if ($result['success']) {
                return redirect()->route('admin.payment-reminders.show', $paymentReminder)
                    ->with('success', "Reminder updated and {$action} successfully!");
            } else {
                return redirect()->route('admin.payment-reminders.show', $paymentReminder)
                    ->with('warning', "Reminder updated but failed to {$action}: ".$result['error']);
            }
        }

        return redirect()->route('admin.payment-reminders.show', $paymentReminder)
            ->with('success', 'Payment reminder updated successfully!');
    }

    /**
     * Display the specified reminder
     */
    public function show(PaymentReminder $paymentReminder): View
    {
        $paymentReminder->load([
            'student.batch.course',
            'student.studentFees.feeCategory',
            'feeCategory',
        ]);

        return view('admin.payment-reminders.show', compact('paymentReminder'));
    }

    /**
     * Remove the specified reminder
     */
    public function destroy(PaymentReminder $paymentReminder)
    {
        $paymentReminder->delete();

        return redirect()->route('admin.payment-reminders.index')
            ->with('success', 'Payment reminder deleted successfully!');
    }

    /**
     * Send test reminder
     */
    public function sendTestReminder(Request $request)
    {
        $request->validate([
            'channel' => 'required|in:email,sms,whatsapp',
            'recipient' => 'required|string',
            'test_message' => 'required|string',
        ]);

        try {
            $result = $this->reminderService->sendTestReminder(
                $request->channel,
                $request->recipient,
                $request->test_message
            );

            if ($result['success']) {
                return response()->json(['success' => true, 'message' => 'Test reminder sent successfully.']);
            } else {
                return response()->json(['success' => false, 'message' => $result['message']]);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Send a specific reminder
     */
    public function send(PaymentReminder $paymentReminder): JsonResponse
    {
        if (! $this->reminderService) {
            return response()->json([
                'success' => false,
                'message' => 'Reminder service not available',
            ], 500);
        }

        try {
            $result = $this->reminderService->sendReminder($paymentReminder);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['success'] ? 'Reminder sent successfully!' : $result['error'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send reminder: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel a specific reminder
     */
    public function cancel(PaymentReminder $paymentReminder): JsonResponse
    {
        try {
            $paymentReminder->update(['status' => 'cancelled']);

            return response()->json([
                'success' => true,
                'message' => 'Reminder cancelled successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel reminder: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Queue reminder for processing
     */
    public function queueReminder(PaymentReminder $paymentReminder): JsonResponse
    {
        try {
            if (class_exists('\App\Jobs\SendPaymentReminder')) {
                SendPaymentReminder::dispatch($paymentReminder);
                $paymentReminder->update(['status' => 'processing']);

                return response()->json([
                    'success' => true,
                    'message' => 'Reminder queued successfully',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Queue job not available',
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to queue reminder: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process pending reminders
     */
    public function processPending(): JsonResponse
    {
        if (! $this->reminderService) {
            return response()->json([
                'success' => false,
                'message' => 'Reminder service not available',
            ], 500);
        }

        try {
            if (method_exists($this->reminderService, 'processPendingReminders')) {
                $result = $this->reminderService->processPendingReminders();

                return response()->json([
                    'success' => true,
                    'message' => 'Processing completed',
                    'data' => $result,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'processPendingReminders method not available',
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process reminders: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get reminder statistics
     */
    private function getReminderStats(): array
    {
        try {
            if ($this->reminderService && method_exists($this->reminderService, 'getReminderStatistics')) {
                return $this->reminderService->getReminderStatistics();
            }

            // Fallback to basic stats
            return [
                'total_reminders' => PaymentReminder::count(),
                'pending_reminders' => PaymentReminder::where('status', 'pending')->count(),
                'sent_today' => PaymentReminder::where('status', 'sent')->whereDate('sent_at', today())->count(),
                'failed_today' => PaymentReminder::where('status', 'failed')->whereDate('updated_at', today())->count(),
            ];
        } catch (\Exception $e) {
            return [
                'total_reminders' => 0,
                'pending_reminders' => 0,
                'sent_today' => 0,
                'failed_today' => 0,
            ];
        }
    }

    /**
     * Get collection efficiency
     */
    private function getCollectionEfficiency(): array
    {
        try {
            if ($this->reminderService && method_exists($this->reminderService, 'getCollectionEfficiency')) {
                return $this->reminderService->getCollectionEfficiency();
            }

            // Fallback calculation
            return [
                'overall_rate' => 0,
                'collection_rate' => 0,
                'overdue_rate' => 0,
                'critical_defaulters' => 0,
                'this_month' => 0,
                'last_month' => 0,
            ];
        } catch (\Exception $e) {
            return [
                'overall_rate' => 0,
                'collection_rate' => 0,
                'overdue_rate' => 0,
                'critical_defaulters' => 0,
                'this_month' => 0,
                'last_month' => 0,
            ];
        }

    }

    /**
     * Get recent reminders
     */
    private function getRecentReminders()
    {
        try {
            return PaymentReminder::with(['student.batch.course', 'feeCategory'])
                ->latest('created_at')
                ->limit(10)
                ->get();
        } catch (\Exception $e) {
            return collect([]);
        }
    }

    /**
     * Get overdue by component
     */
    private function getOverdueByComponent(): array
    {
        try {
            if (method_exists($this->paymentService, 'getOverdueByComponent')) {
                return $this->paymentService->getOverdueByComponent();
            }

            // Fallback calculation
            return FeeCategory::with('studentFees')
                ->get()
                ->map(function ($category) {
                    $overdueFees = $category->studentFees()
                        ->where('due_date', '<', now())
                        ->whereIn('status', ['unpaid', 'partial', 'overdue'])
                        ->get();

                    return [
                        'category' => $category->name,
                        'count' => $overdueFees->count(),
                        'amount' => $overdueFees->sum(function ($fee) {
                            return ($fee->amount ?? 0) - ($fee->paid_amount ?? 0);
                        }),
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get reminder effectiveness
     */
    private function getReminderEffectiveness(): array
    {
        try {
            // Calculate basic effectiveness metrics
            $totalSent = PaymentReminder::where('status', 'sent')->count();
            $totalResponses = PaymentReminder::where('status', 'sent')
                ->whereHas('student.studentFees', function ($q) {
                    $q->where('paid_amount', '>', 0);
                })
                ->count();

            $effectiveness = $totalSent > 0 ? ($totalResponses / $totalSent) * 100 : 0;

            return [
                'overall_effectiveness' => round($effectiveness, 2),
                'total_sent' => $totalSent,
                'total_responses' => $totalResponses,
                'by_channel' => $this->getEffectivenessByChannel(),
            ];
        } catch (\Exception $e) {
            return [
                'overall_effectiveness' => 0,
                'total_sent' => 0,
                'total_responses' => 0,
                'by_channel' => [],
            ];
        }
    }

    /**
     * Get effectiveness by channel
     */
    private function getEffectivenessByChannel(): array
    {
        try {
            return PaymentReminder::selectRaw('
                    channel,
                    COUNT(*) as total_sent,
                    SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as successful_sent,
                    SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed
                ')
                ->groupBy('channel')
                ->get()
                ->mapWithKeys(function ($item) {
                    $successRate = $item->total_sent > 0 ?
                        ($item->successful_sent / $item->total_sent) * 100 : 0;

                    return [
                        $item->channel => [
                            'total_sent' => $item->total_sent,
                            'successful_sent' => $item->successful_sent,
                            'failed' => $item->failed,
                            'success_rate' => round($successRate, 2),
                        ],
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get upcoming reminders
     */
    private function getUpcomingReminders()
    {
        try {
            return PaymentReminder::with(['student.batch.course', 'feeCategory'])
                ->where('status', 'scheduled')
                ->where('scheduled_date', '>=', now())
                ->where('scheduled_date', '<=', now()->addDays(7))
                ->orderBy('scheduled_date')
                ->limit(20)
                ->get();
        } catch (\Exception $e) {
            return collect([]);
        }
    }

    /**
     * Get defaulter trends
     */
    private function getDefaulterTrends(): array
    {
        try {
            if (method_exists($this->paymentService, 'getDefaulterTrends')) {
                return $this->paymentService->getDefaulterTrends();
            }

            // Fallback calculation
            return [
                'this_month' => 0,
                'last_month' => 0,
                'trend' => 'stable',
            ];
        } catch (\Exception $e) {
            return [
                'this_month' => 0,
                'last_month' => 0,
                'trend' => 'stable',
            ];
        }
    }

    /**
     * Handle reminder actions
     */
    private function handleReminderAction(PaymentReminder $reminder, string $action): array
    {
        try {
            switch ($action) {
                case 'send':
                    if ($this->reminderService && method_exists($this->reminderService, 'sendReminder')) {
                        return $this->reminderService->sendReminder($reminder);
                    }
                    break;

                case 'cancel':
                    $reminder->update(['status' => 'cancelled']);

                    return ['success' => true, 'message' => 'Reminder cancelled'];

                case 'reschedule':
                    $reminder->update([
                        'status' => 'scheduled',
                        'scheduled_date' => now()->addHours(24),
                    ]);

                    return ['success' => true, 'message' => 'Reminder rescheduled'];

                default:
                    return ['success' => false, 'error' => 'Unknown action'];
            }

            return ['success' => false, 'error' => 'Action not available'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Reschedule a reminder
     */
    public function reschedule(Request $request, PaymentReminder $paymentReminder)
    {
        $request->validate([
            'scheduled_date' => 'required|date|after:now',
        ]);

        if ($paymentReminder->status === 'sent') {
            return back()->with('error', 'Cannot reschedule a sent reminder.');
        }

        $paymentReminder->update([
            'scheduled_date' => $request->scheduled_date,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Reminder rescheduled successfully.');
    }

    /**
     * Bulk operations
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reminder_ids' => 'required|array',
            'reminder_ids.*' => 'exists:payment_reminders,id',
            'action' => 'required|in:send,cancel,delete,reschedule',
        ]);

        try {
            $reminders = PaymentReminder::whereIn('id', $validated['reminder_ids'])->get();
            $results = ['success' => 0, 'failed' => 0, 'errors' => []];

            foreach ($reminders as $reminder) {
                try {
                    switch ($validated['action']) {
                        case 'send':
                            if ($this->reminderService && method_exists($this->reminderService, 'sendReminder')) {
                                $result = $this->reminderService->sendReminder($reminder);
                                if ($result['success']) {
                                    $results['success']++;
                                } else {
                                    $results['failed']++;
                                    $results['errors'][] = "Reminder {$reminder->id}: {$result['error']}";
                                }
                            } else {
                                $results['failed']++;
                                $results['errors'][] = 'Reminder service not available';
                            }
                            break;

                        case 'cancel':
                            $reminder->update(['status' => 'cancelled']);
                            $results['success']++;
                            break;

                        case 'delete':
                            $reminder->delete();
                            $results['success']++;
                            break;

                        case 'reschedule':
                            $reminder->update([
                                'status' => 'scheduled',
                                'scheduled_date' => now()->addHours(24),
                            ]);
                            $results['success']++;
                            break;
                    }
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "Reminder {$reminder->id}: {$e->getMessage()}";
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Bulk action completed. Success: {$results['success']}, Failed: {$results['failed']}",
                'results' => $results,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk action failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update defaulters
     */
    public function updateDefaulters(Request $request)
    {
        $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
            'action' => 'required|in:send_reminder,mark_contacted,extend_deadline',
            'reminder_type' => 'required_if:action,send_reminder|in:email,sms,whatsapp',
            'message' => 'required_if:action,send_reminder|string',
            'new_deadline' => 'required_if:action,extend_deadline|date|after:today',
        ]);

        try {
            $result = $this->reminderService->updateDefaulters($request->all());

            return back()->with('success', $result['message']);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update defaulters: '.$e->getMessage());
        }
    }

    /**
     * Health check
     */
    public function healthCheck()
    {
        try {
            $healthData = $this->reminderService->performHealthCheck();

            return response()->json([
                'success' => true,
                'data' => $healthData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Health check failed: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Export reminders
     */
    public function export(Request $request)
    {
        try {
            $query = PaymentReminder::with(['student.batch.course', 'feeCategory']);

            // Apply same filters as index
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('channel')) {
                $query->where('channel', $request->channel);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('scheduled_date', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('scheduled_date', '<=', $request->date_to);
            }

            $reminders = $query->get();

            // Create CSV export
            $filename = 'payment_reminders_'.now()->format('Y_m_d_His').'.csv';
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $callback = function () use ($reminders) {
                $file = fopen('php://output', 'w');

                // CSV headers
                fputcsv($file, [
                    'ID',
                    'Student Name',
                    'Enrollment Number',
                    'Fee Category',
                    'Reminder Type',
                    'Channel',
                    'Status',
                    'Scheduled Date',
                    'Sent Date',
                    'Overdue Amount',
                    'Message Content',
                ]);

                // CSV data
                foreach ($reminders as $reminder) {
                    fputcsv($file, [
                        $reminder->id,
                        $reminder->student->name ?? 'N/A',
                        $reminder->student->enrollment_number ?? 'N/A',
                        $reminder->feeCategory->name ?? 'All Categories',
                        $reminder->reminder_type,
                        $reminder->channel,
                        $reminder->status,
                        $reminder->scheduled_date,
                        $reminder->sent_at,
                        $reminder->overdue_amount,
                        $reminder->message_content,
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            return back()->with('error', 'Export failed: '.$e->getMessage());
        }
    }
    /**
     * Send reminders for all pending students in a specific fee category
     */
    public function sendCategoryReminders(Request $request, FeeCategory $feeCategory): JsonResponse
    {
        $validated = $request->validate([
            'reminder_type' => 'nullable|string|in:gentle,firm,urgent,final_notice,overdue',
            'include_overdue_only' => 'nullable|boolean',
            'minimum_amount' => 'nullable|numeric|min:0',
            'message_content' => 'nullable|string',
            'channel' => 'nullable|string|in:email,sms,whatsapp',
        ]);

        if (!$this->reminderService) {
            return response()->json([
                'success' => false,
                'message' => 'Reminder service not available',
            ], 500);
        }

        try {
            // 1. Find all students with pending/unpaid fees in this category (excluding dropouts)
            $query = Student::where('status', '!=', 'dropout')
                ->whereHas('studentFees', function ($q) use ($feeCategory, $validated) {
                    $q->where('fee_category_id', $feeCategory->id)
                        ->whereIn('status', ['unpaid', 'partial', 'pending'])
                        ->whereRaw('amount - concession_amount - paid_amount > 0');
                    
                    if (!empty($validated['include_overdue_only'])) {
                        $q->where('due_date', '<', now());
                    }

                    if (!empty($validated['minimum_amount'])) {
                        $q->whereRaw('amount - concession_amount - paid_amount >= ?', [$validated['minimum_amount']]);
                    }
                });

            $students = $query->get();

            if ($students->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No pending students found matching the criteria.',
                    'count' => 0
                ]);
            }

            $count = 0;
            $errors = [];

            // 2. Queue reminders for each student
            foreach ($students as $student) {
                try {
                    // Check if already has a pending reminder for this category to avoid duplicates
                    $existing = PaymentReminder::where('student_id', $student->id)
                        ->where('fee_category_id', $feeCategory->id)
                        ->where('status', 'pending')
                        ->exists();

                    if (!$existing) {
                        PaymentReminder::create([
                            'student_id' => $student->id,
                            'fee_category_id' => $feeCategory->id,
                            'reminder_type' => $validated['reminder_type'] ?? 'overdue',
                            'channel' => $validated['channel'] ?? 'email',
                            'scheduled_date' => now(),
                            'status' => 'pending',
                            'message_content' => $validated['message_content'] ?? null,
                            'recipient_details' => [
                                'email' => $student->email,
                                'phone' => $student->student_mobile ?? $student->father_mobile,
                                'student_name' => $student->name,
                                'enrollment_number' => $student->enrollment_number,
                            ],
                            'overdue_amount' => $this->paymentService->getStudentOverdueAmount($student, $feeCategory->id),
                        ]);
                        $count++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Student #{$student->id}: " . $e->getMessage();
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully queued {$count} reminders for {$feeCategory->name}.",
                'count' => $count,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            \Log::error('Error sending category reminders: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send category reminders: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send individual student reminder
     */
    public function sendStudentReminder(Request $request, Student $student): JsonResponse
    {
        $validated = $request->validate([
            'reminder_type' => 'required|string',
            'channel' => 'required|string|in:email,sms,whatsapp',
            'message_content' => 'nullable|string',
            'fee_category_id' => 'nullable|exists:fee_categories,id',
        ]);

        try {
            // Safeguard: Do not send reminders to dropout students
            if ($student->status === 'dropout') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot send reminders to dropout students.',
                ], 400);
            }

            $overdueAmount = 0;
            if ($validated['fee_category_id']) {
                $overdueAmount = $this->paymentService->getStudentOverdueAmount($student, $validated['fee_category_id']);
            } else {
                $overdueAmount = method_exists($student, 'getTotalOverdueAmount') ? $student->getTotalOverdueAmount() : 0;
            }

            $reminder = PaymentReminder::create([
                'student_id' => $student->id,
                'fee_category_id' => $validated['fee_category_id'] ?? null,
                'reminder_type' => $validated['reminder_type'],
                'channel' => $validated['channel'],
                'scheduled_date' => now(),
                'status' => 'pending',
                'message_content' => $validated['message_content'] ?? null,
                'recipient_details' => [
                    'email' => $student->email,
                    'phone' => $student->student_mobile ?? $student->father_mobile,
                    'student_name' => $student->name,
                    'enrollment_number' => $student->enrollment_number,
                ],
                'overdue_amount' => $overdueAmount,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reminder queued successfully!',
                'reminder_id' => $reminder->id
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to queue reminder: ' . $e->getMessage(),
            ], 500);
        }
    }
}
