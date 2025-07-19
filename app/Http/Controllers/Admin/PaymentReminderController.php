<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PaymentReminderService;
use App\Models\PaymentReminder;
use App\Models\PaymentReminderTemplate;
use App\Models\PaymentDefaulter;
use App\Models\Student;
use App\Models\Invoice;
use App\Jobs\SendPaymentReminder;
use App\Jobs\ProcessPendingReminders;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\Response;

class PaymentReminderController extends Controller
{
    public function __construct(
        private PaymentReminderService $reminderService
    ) {}

    /**
     * Dashboard with reminder statistics
     */
    public function dashboard(): View
    {
        $stats = $this->reminderService->getReminderStatistics();
        $collectionEfficiency = $this->reminderService->getCollectionEfficiency();
        $recentReminders = PaymentReminder::with(['student', 'feeCategory'])
            ->latest('created_at')
            ->limit(10)
            ->get();

        return view('payment-reminders.dashboard', compact(
            'stats',
            'collectionEfficiency', 
            'recentReminders'
        ));
    }

    /**
     * List all payment reminders with filters
     */
    public function index(Request $request): View
    {
        $query = PaymentReminder::with(['student.batch.course', 'feeCategory', 'invoice']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }

        if ($request->filled('reminder_type')) {
            $query->where('reminder_type', $request->reminder_type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('scheduled_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('scheduled_date', '<=', $request->date_to);
        }

        if ($request->filled('student_search')) {
            $query->whereHas('student', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->student_search . '%')
                  ->orWhere('enrollment_number', 'like', '%' . $request->student_search . '%');
            });
        }

        $reminders = $query->orderBy('scheduled_date', 'desc')->paginate(20);

        return view('payment-reminders.index', compact('reminders'));
    }

    /**
     * Show payment reminder details
     */
    public function show(PaymentReminder $reminder): View
    {
        $reminder->load(['student.batch.course', 'feeCategory', 'invoice', 'logs.performedBy']);
        
        return view('payment-reminders.show', compact('reminder'));
    }

    /**
     * Show payment defaulters list
     */
    public function defaulters(Request $request): View
    {
        $defaulters = $this->reminderService->generateDefaultersList();
        
        // Apply filters
        if ($request->filled('category')) {
            $defaulters = array_filter($defaulters, function($defaulter) use ($request) {
                return $defaulter['defaulter_category'] === $request->category;
            });
        }

        if ($request->filled('min_amount')) {
            $defaulters = array_filter($defaulters, function($defaulter) use ($request) {
                return $defaulter['total_overdue_amount'] >= $request->min_amount;
            });
        }

        if ($request->filled('course')) {
            $defaulters = array_filter($defaulters, function($defaulter) use ($request) {
                return stripos($defaulter['course'], $request->course) !== false;
            });
        }

        return view('payment-reminders.defaulters', compact('defaulters'));
    }

    /**
     * Send test reminder
     */
    public function sendTestReminder(Request $request): JsonResponse
    {
        $request->validate([
            'channel' => 'required|in:email,sms,whatsapp',
            'recipient' => 'required|string',
            'message' => 'required|string|max:1000'
        ]);

        $result = $this->reminderService->sendTestReminder(
            $request->channel,
            $request->recipient,
            $request->message
        );

        return response()->json($result);
    }

    /**
     * Send individual reminder
     */
    public function sendReminder(PaymentReminder $reminder): JsonResponse
    {
        $result = $this->reminderService->sendSingleReminder($reminder);
        
        return response()->json($result);
    }

    /**
     * Send reminder via queue
     */
    public function queueReminder(PaymentReminder $reminder): JsonResponse
    {
        try {
            SendPaymentReminder::dispatch($reminder);
            
            $reminder->update(['status' => 'processing']);
            
            return response()->json([
                'success' => true,
                'message' => 'Reminder queued successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel a reminder
     */
    public function cancelReminder(PaymentReminder $reminder): JsonResponse
    {
        try {
            $reminder->update(['status' => 'cancelled']);
            
            return response()->json([
                'success' => true,
                'message' => 'Reminder cancelled successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a reminder
     */
    public function deleteReminder(PaymentReminder $reminder): JsonResponse
    {
        try {
            $reminder->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Reminder deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process pending reminders
     */
    public function processPendingReminders(): JsonResponse
    {
        try {
            ProcessPendingReminders::dispatch();
            
            return response()->json([
                'success' => true,
                'message' => 'Processing job queued successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk actions on reminders
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:send,cancel,delete',
            'reminder_ids' => 'required|array',
            'reminder_ids.*' => 'exists:payment_reminders,id'
        ]);

        $reminders = PaymentReminder::whereIn('id', $request->reminder_ids)->get();
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($reminders as $reminder) {
            try {
                switch ($request->action) {
                    case 'send':
                        SendPaymentReminder::dispatch($reminder);
                        $reminder->update(['status' => 'processing']);
                        $results['success']++;
                        break;
                        
                    case 'cancel':
                        $reminder->update(['status' => 'cancelled']);
                        $results['success']++;
                        break;
                        
                    case 'delete':
                        $reminder->delete();
                        $results['success']++;
                        break;
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Reminder {$reminder->id}: " . $e->getMessage();
            }
        }

        return response()->json([
            'success' => true,
            'results' => $results
        ]);
    }

    /**
     * Setup reminder schedule for student
     */
    public function setupSchedule(Request $request): JsonResponse
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'invoice_id' => 'required|exists:invoices,id'
        ]);

        try {
            $student = Student::findOrFail($request->student_id);
            $invoice = Invoice::findOrFail($request->invoice_id);
            
            $this->reminderService->setupReminderSchedule($student, $invoice);
            
            return response()->json([
                'success' => true,
                'message' => 'Reminder schedule created successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel reminders for paid invoice
     */
    public function cancelForInvoice(Invoice $invoice): JsonResponse
    {
        try {
            $this->reminderService->cancelRemindersForInvoice($invoice);
            
            return response()->json([
                'success' => true,
                'message' => 'Reminders cancelled successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get reminder statistics (API)
     */
    public function statistics(): JsonResponse
    {
        $stats = $this->reminderService->getReminderStatistics();
        $collectionEfficiency = $this->reminderService->getCollectionEfficiency();
        
        return response()->json([
            'reminder_stats' => $stats,
            'collection_efficiency' => $collectionEfficiency
        ]);
    }

    /**
     * Update defaulter records
     */
    public function updateDefaulters(): JsonResponse
    {
        try {
            $this->reminderService->updateDefaulterRecords();
            
            return response()->json([
                'success' => true,
                'message' => 'Defaulter records updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cleanup old records
     */
    public function cleanup(Request $request): JsonResponse
    {
        $request->validate([
            'days' => 'integer|min:1|max:365'
        ]);

        try {
            $deletedCount = $this->reminderService->cleanupOldRecords(
                $request->input('days', 30)
            );
            
            return response()->json([
                'success' => true,
                'message' => "Cleaned up {$deletedCount} old records"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get templates for reminder type and channel
     */
    public function getTemplates(Request $request): JsonResponse
    {
        $request->validate([
            'reminder_type' => 'required|string',
            'channel' => 'required|string'
        ]);

        $templates = PaymentReminderTemplate::where('is_active', true)
            ->where('reminder_type', $request->reminder_type)
            ->where('channel', $request->channel)
            ->get(['id', 'name', 'message_template', 'subject_template']);

        return response()->json($templates);
    }

    /**
     * Export defaulters list
     */
    public function exportDefaulters(Request $request): Response
    {
        $defaulters = $this->reminderService->generateDefaultersList();
        
        // Filter by selected IDs if provided
        if ($request->filled('selected_defaulters')) {
            $selectedIds = $request->input('selected_defaulters');
            $defaulters = array_filter($defaulters, function($defaulter) use ($selectedIds) {
                return in_array($defaulter['student_id'], $selectedIds);
            });
        }
        
        $filename = 'payment_defaulters_' . now()->format('Y_m_d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($defaulters) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'Student Name',
                'Enrollment Number',
                'Course',
                'Batch',
                'Total Overdue Amount',
                'Overdue Days',
                'Overdue Invoices',
                'Category',
                'Contact Phone',
                'Contact Email',
                'Last Payment Date',
                'Fee Types'
            ]);
            
            // CSV data
            foreach ($defaulters as $defaulter) {
                fputcsv($file, [
                    $defaulter['student_name'],
                    $defaulter['enrollment_number'],
                    $defaulter['course'],
                    $defaulter['batch'],
                    $defaulter['total_overdue_amount'],
                    $defaulter['overdue_days'],
                    $defaulter['overdue_invoice_count'],
                    $defaulter['defaulter_category'],
                    $defaulter['contact_phone'],
                    $defaulter['contact_email'],
                    $defaulter['last_payment_date'],
                    implode(', ', $defaulter['overdue_fee_types'])
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get student's unpaid invoices (for AJAX)
     */
    public function getStudentUnpaidInvoices(Student $student): JsonResponse
    {
        $invoices = $student->invoices()
            ->where('status', '!=', 'paid')
            ->get(['id', 'invoice_number', 'total_amount', 'due_date'])
            ->map(function($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'total_amount' => number_format($invoice->total_amount, 2),
                    'due_date' => $invoice->due_date->format('M d, Y')
                ];
            });

        return response()->json($invoices);
    }

    /**
     * Get student payment details (for modal)
     */
    public function getStudentPaymentDetails(Student $student): View
    {
        $student->load(['batch.course', 'invoices.payments', 'paymentReminders']);
        
        $overdueInvoices = $student->invoices()
            ->where('due_date', '<', now())
            ->where('status', '!=', 'paid')
            ->get();

        $totalOverdue = $overdueInvoices->sum('total_amount');
        $reminderCount = $student->paymentReminders()->count();
        $lastPayment = $student->invoices()
            ->whereHas('payments')
            ->with('payments')
            ->get()
            ->flatMap->payments
            ->sortByDesc('payment_date')
            ->first();

        return view('payment-reminders.partials.student-details', compact(
            'student',
            'overdueInvoices',
            'totalOverdue',
            'reminderCount',
            'lastPayment'
        ));
    }

    /**
     * Send individual reminder with custom options
     */
    public function sendIndividualReminder(Request $request): JsonResponse
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'channel' => 'required|in:email,sms,whatsapp',
            'reminder_type' => 'required|in:upcoming_due,overdue,escalation,final_notice',
            'message' => 'nullable|string|max:1000'
        ]);

        try {
            $student = Student::findOrFail($request->student_id);
            
            // Get the latest unpaid invoice for this student
            $invoice = $student->invoices()
                ->where('status', '!=', 'paid')
                ->orderBy('due_date', 'asc')
                ->first();

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'error' => 'No unpaid invoices found for this student'
                ]);
            }

            // Create a reminder record
            $reminder = PaymentReminder::create([
                'student_id' => $student->id,
                'invoice_id' => $invoice->id,
                'reminder_type' => $request->reminder_type,
                'channel' => $request->channel,
                'scheduled_date' => now(),
                'status' => 'pending',
                'message_content' => $request->message,
                'recipient_details' => [
                    'email' => $student->email,
                    'phone' => $student->student_mobile ?? $student->father_mobile,
                    'student_name' => $student->name,
                    'enrollment_number' => $student->enrollment_number,
                ]
            ]);

            // Send the reminder
            $result = $this->reminderService->sendSingleReminder($reminder);
            
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}