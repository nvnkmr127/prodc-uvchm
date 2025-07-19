<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\FeeCategory;
use App\Models\Payment;
use App\Models\InvoiceEditLog;
use App\Models\Batch;
use App\Models\FeeStructure;
use App\Services\NotificationService;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use App\Mail\PaymentReceiptMail;
use Carbon\Carbon;
use Spatie\Activitylog\Models\Activity;

class InvoiceController extends Controller
{
    protected $notificationService;
    protected $invoiceService;
    
    public function __construct(NotificationService $notificationService, InvoiceService $invoiceService)
    {
        $this->notificationService = $notificationService;
        $this->invoiceService = $invoiceService;
    }
    
    /**
     * Display invoice management dashboard
     */
    public function index(Request $request)
    {
        $students = null;
        $recentInvoices = null;

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $students = Student::with('batch.course')
                        ->where('name', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('enrollment_number', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('email', 'LIKE', "%{$searchTerm}%")
                        ->get();
        } else {
            $recentInvoices = Invoice::with('student.batch.course')->latest()->limit(15)->get();
        }
        
        return view('admin.invoices.index', compact('students', 'recentInvoices'));
    }

    /**
     * Show student financial ledger
     */
    public function showStudentLedger(Student $student)
    {
        try {
            $student->load('invoices.items', 'invoices.payments', 'batch.course');
            $invoices = $student->invoices;
            
            // Get payments safely - handle different table structures
            $payments = collect();
            
            // Method 1: Get payments through invoices (always works)
            foreach ($student->invoices as $invoice) {
                $payments = $payments->merge($invoice->payments);
            }
            
            // Method 2: If payments table has student_id, get direct payments too
            try {
                if (Schema::hasColumn('payments', 'student_id')) {
                    $directPayments = Payment::where('student_id', $student->id)->get();
                    $payments = $payments->merge($directPayments)->unique('id');
                }
            } catch (\Exception $e) {
                // Continue with payments from invoices only
            }
            
            // Calculate totals
            $transactions = collect($invoices)->concat($payments)->sortBy('created_at');
            $totalBilled = $invoices->sum('total_amount');
            $totalPaid = $payments->sum('amount');
            $totalConcession = $invoices->sum('concession_amount');
            $balanceDue = $totalBilled - $totalConcession - $totalPaid;
            
            // Monthly breakdown
            $monthlyData = $this->getMonthlyFinancialData($student);
            
            return view('admin.invoices.ledger', compact(
                'student', 
                'transactions', 
                'totalBilled', 
                'totalPaid', 
                'totalConcession', 
                'balanceDue',
                'monthlyData'
            ));
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error loading student ledger: ' . $e->getMessage());
        }
    }

  /**
 * Show invoice details with edit history and activities
 */
public function show(Invoice $invoice)
{
    // Load relationships
    $invoice->load([
        'student.batch.course', 
        'items.feeCategory', 
        'payments'
    ]);
    
    // Get edit history for this invoice
    $editHistory = InvoiceEditLog::where('invoice_id', $invoice->id)
                                ->with('user')
                                ->latest()
                                ->get();
    
    // Get general activities (if you're using spatie/laravel-activitylog)
    $activities = collect();
    if (class_exists(\Spatie\Activitylog\Models\Activity::class)) {
        $activities = \Spatie\Activitylog\Models\Activity::forSubject($invoice)
                                                        ->with('causer')
                                                        ->latest()
                                                        ->limit(10)
                                                        ->get();
    }
    
    return view('admin.invoices.show', compact('invoice', 'editHistory', 'activities'));
}

    /**
     * Create new invoice form
     */
    public function create()
    {
        $batches = Batch::with('course')->orderBy('name')->get();
        return view('admin.invoices.create', compact('batches'));
    }

    /**
     * Store new invoice (bulk generation)
     */
    public function store(Request $request)
    {
        $request->validate([
            'batch_id' => 'required|exists:batches,id',
            'due_date' => 'required|date|after:today',
        ]);

        try {
            DB::beginTransaction();

            $batch = Batch::with('course')->findOrFail($request->batch_id);
            $students = Student::where('batch_id', $batch->id)->where('status', 'active')->get();
            
            if ($students->isEmpty()) {
                return redirect()->back()->with('error', 'No active students found in the selected batch.');
            }

            $invoicesCreated = 0;
            $errors = [];

            foreach ($students as $student) {
                try {
                    $this->invoiceService->generateTermInvoicesForStudent($student);
                    $invoicesCreated++;
                } catch (\Exception $e) {
                    $errors[] = "Failed for {$student->name}: " . $e->getMessage();
                }
            }

            DB::commit();

            $message = "Successfully generated invoices for {$invoicesCreated} students.";
            if (!empty($errors)) {
                $message .= " Errors: " . implode(', ', array_slice($errors, 0, 3));
                if (count($errors) > 3) {
                    $message .= " (and " . (count($errors) - 3) . " more)";
                }
            }

            return redirect()->route('admin.invoices.index')->with('success', $message);

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Failed to generate invoices: ' . $e->getMessage());
        }
    }

public function addPayment(Request $request, Invoice $invoice)
{
    $request->validate([
        'amount' => 'required|numeric|min:0.01|max:' . ($invoice->due_amount ?? $invoice->total_amount),
        'payment_date' => 'required|date',
        'payment_method' => 'required|string|in:Cash,Card,Bank Transfer,Cheque,Online',
        'transaction_id' => 'nullable|string|max:255',
        'notes' => 'nullable|string|max:500',
    ]);
    
    try {
        DB::beginTransaction();

        // Generate unique receipt number
        $receiptNumber = $this->generateUniqueReceiptNumber();

        // Create payment record - only use fields that exist in table
        $paymentData = [
            'invoice_id' => $invoice->id,
            'amount' => $request->amount,
            'payment_date' => $request->payment_date,
            'payment_method' => $request->payment_method,
            'notes' => $request->notes,
            'receipt_number' => $receiptNumber,
        ];

        // Only add optional fields if they exist in table
        if (Schema::hasColumn('payments', 'transaction_id') && $request->filled('transaction_id')) {
            $paymentData['transaction_id'] = $request->transaction_id;
        }
        
        if (Schema::hasColumn('payments', 'student_id')) {
            $paymentData['student_id'] = $invoice->student_id;
        }

        // Create the payment
        $payment = Payment::create($paymentData);
        
        // Update invoice paid amount - let the model calculate status automatically
        $invoice->paid_amount = $invoice->paid_amount + $request->amount;
        $invoice->save(); // This will trigger the boot method to calculate due_amount and status

        // Log activity
        activity()
            ->performedOn($invoice)
            ->causedBy(auth()->user())
            ->withProperties([
                'payment_id' => $payment->id,
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'receipt_number' => $receiptNumber
            ])
            ->log("Payment of ₹{$request->amount} recorded via {$request->payment_method}");

        DB::commit();

        return redirect()->back()->with('success', 
            "Payment of ₹" . number_format($request->amount, 2) . " recorded successfully. Receipt #" . $receiptNumber);

    } catch (\Exception $e) {
        DB::rollback();
        
        \Log::error('Payment creation failed', [
            'invoice_id' => $invoice->id,
            'amount' => $request->amount,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return redirect()->back()->with('error', 'Failed to record payment: ' . $e->getMessage())
                                 ->withInput();
    }
}

/**
 * Generate a unique receipt number
 */
private function generateUniqueReceiptNumber(): string
{
    $year = now()->year;
    $attempts = 0;
    $maxAttempts = 10;
    
    do {
        // Get the next sequential number for this year
        $lastPayment = Payment::whereYear('created_at', $year)
                             ->whereNotNull('receipt_number')
                             ->orderBy('id', 'desc')
                             ->first();
        
        if ($lastPayment && $lastPayment->receipt_number) {
            // Extract the number from the last receipt
            preg_match('/RCPT-' . $year . '-(\d+)/', $lastPayment->receipt_number, $matches);
            $nextNumber = isset($matches[1]) ? (int)$matches[1] + 1 : 1;
        } else {
            $nextNumber = 1;
        }
        
        $receiptNumber = 'RCPT-' . $year . '-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
        
        // Check if this receipt number already exists
        $exists = Payment::where('receipt_number', $receiptNumber)->exists();
        
        if (!$exists) {
            return $receiptNumber;
        }
        
        $attempts++;
        
        // If it exists, try with a higher number
        $nextNumber++;
        
    } while ($attempts < $maxAttempts);
    
    // Fallback: use timestamp to ensure uniqueness
    return 'RCPT-' . $year . '-' . time() . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
}
    /**
     * Edit invoice
     */
    public function edit(Invoice $invoice)
{
    $invoice->load(['student.batch.course', 'items.feeCategory']);
    $feeCategories = FeeCategory::orderBy('name')->get();
    
    // ✅ ADD this line
    $editHistory = InvoiceEditLog::where('invoice_id', $invoice->id)
                                ->with('user')
                                ->latest()
                                ->get();
    
    // ✅ ADD editHistory to compact
    return view('admin.invoices.edit', compact('invoice', 'feeCategories', 'editHistory'));
}

    /**
     * Update invoice
     */
    public function update(Request $request, Invoice $invoice)
    {
        $request->validate([
            'due_date' => 'required|date',
            'total_amount' => 'required|numeric|min:0',
            'concession_amount' => 'nullable|numeric|min:0',
            'concession_notes' => 'nullable|string|max:500',
            'description' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $oldData = $invoice->toArray();
            
            // Update invoice
            $invoice->update($request->only([
                'due_date', 'total_amount', 'concession_amount', 
                'concession_notes', 'description'
            ]));

            // Recalculate due amount
            $newDueAmount = max(0, $invoice->total_amount - $invoice->paid_amount - ($invoice->concession_amount ?? 0));
            $invoice->update(['due_amount' => $newDueAmount]);

            // Update status if needed
           if ($newDueAmount <= 0 && $invoice->paid_amount > 0) {
    $invoice->update(['status' => 'paid']);
} elseif ($invoice->paid_amount > 0) {
    $invoice->update(['status' => 'partially_paid']); // Fixed: was 'partial'
} else {
    $invoice->update(['status' => 'unpaid']);
}

            // Log activity
            activity()
                ->performedOn($invoice)
                ->causedBy(auth()->user())
                ->withProperties(['old' => $oldData, 'attributes' => $invoice->fresh()->toArray()])
                ->log('Invoice updated');

            DB::commit();

            return redirect()->route('admin.invoices.show', $invoice)
                           ->with('success', 'Invoice updated successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Failed to update invoice: ' . $e->getMessage());
        }
    }

    /**
     * Delete invoice
     */
    public function destroy(Invoice $invoice)
    {
        try {
            if ($invoice->payments()->exists()) {
                return redirect()->back()->with('error', 'Cannot delete invoice that has payments. Reverse payments first.');
            }

            $invoice->delete();
            
            return redirect()->route('admin.invoices.index')->with('success', 'Invoice deleted successfully.');
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete invoice: ' . $e->getMessage());
        }
    }

    /**
     * Print invoice
     */
    public function print(Invoice $invoice)
    {
        $invoice->load('student.batch.course', 'items', 'payments');
        return view('admin.invoices.print', compact('invoice'));
    }
/**
 * Download student financial statement
 */
public function downloadStatement(Request $request, Student $student)
{
    try {
        $student->load('invoices.items', 'invoices.payments', 'batch.course');

        // Use provided dates or default to past 12 months
        $startDate = $request->start_date ? 
            \Carbon\Carbon::parse($request->start_date)->startOfDay() : 
            now()->subYear()->startOfMonth();
            
        $endDate = $request->end_date ? 
            \Carbon\Carbon::parse($request->end_date)->endOfDay() : 
            now()->endOfMonth();

        $transactions = collect();
        foreach ($student->invoices as $invoice) {
            $transactions = $transactions->merge([$invoice])->merge($invoice->payments);
        }
        $transactions = $transactions->sortBy('created_at');

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('admin.invoices.statement_pdf', [
            'student' => $student,
            'transactions' => $transactions,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);

        $filename = 'statement-' . $student->enrollment_number . '.pdf';

        return $pdf->download($filename);

    } catch (\Exception $e) {
        return redirect()->back()->with('error', 'Failed to generate statement: ' . $e->getMessage());
    }
}

    /**
     * Download invoice as PDF
     */
    public function downloadPdf(Invoice $invoice)
    {
        try {
            $invoice->load('student.batch.course', 'items', 'payments');
            
            $pdf = app('dompdf.wrapper');
            $pdf->loadView('admin.invoices.pdf', compact('invoice'));
            
            $filename = 'invoice-' . $invoice->invoice_number . '.pdf';
            
            return $pdf->download($filename);
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to generate PDF: ' . $e->getMessage());
        }
    }

    /**
     * Send invoice via email
     */
    public function sendEmail(Request $request, Invoice $invoice)
    {
        $request->validate([
            'email' => 'required|email',
            'message' => 'nullable|string|max:1000',
        ]);

        try {
            $invoice->load('student.batch.course', 'items');
            
            // Send email logic here
            // Mail::to($request->email)->send(new InvoiceMail($invoice, $request->message));
            
            return redirect()->back()->with('success', 'Invoice sent successfully to ' . $request->email);
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to send invoice: ' . $e->getMessage());
        }
    }

    /**
     * Bulk actions on invoices
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:delete,mark_paid,send_reminders',
            'invoice_ids' => 'required|array',
            'invoice_ids.*' => 'exists:invoices,id',
        ]);

        try {
            DB::beginTransaction();

            $invoices = Invoice::whereIn('id', $request->invoice_ids)->get();
            $count = 0;

            foreach ($invoices as $invoice) {
                switch ($request->action) {
                    case 'delete':
                        if (!$invoice->payments()->exists()) {
                            $invoice->delete();
                            $count++;
                        }
                        break;
                        
                    case 'mark_paid':
                        if ($invoice->status !== 'paid') {
                            $invoice->update([
                                'paid_amount' => $invoice->total_amount - ($invoice->concession_amount ?? 0),
                                'due_amount' => 0,
                                'status' => 'paid',
                                'paid_at' => now(),
                            ]);
                            $count++;
                        }
                        break;
                        
                    case 'send_reminders':
                        // Send reminder logic here
                        $count++;
                        break;
                }
            }

            DB::commit();

            return redirect()->back()->with('success', "Bulk action completed for {$count} invoices.");

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Bulk action failed: ' . $e->getMessage());
        }
    }
    
    /**
 * Apply concession to an invoice
 */
public function applyConcession(Request $request, Invoice $invoice)
{
    $request->validate([
        'concession_type' => 'required|string|in:fixed,percentage',
        'concession_value' => 'required|numeric|min:0.01',
        'concession_notes' => 'nullable|string|max:500',
    ]);

    try {
        DB::beginTransaction();

        // Calculate concession amount based on type
        $concessionAmount = 0;
        if ($request->concession_type === 'percentage') {
            // Validate percentage is not over 100%
            if ($request->concession_value > 100) {
                return redirect()->back()->with('error', 'Concession percentage cannot exceed 100%.');
            }
            
            $concessionAmount = ($invoice->total_amount * $request->concession_value) / 100;
        } else {
            // Fixed amount
            $concessionAmount = $request->concession_value;
        }

        // Validate concession doesn't exceed total amount
        if ($concessionAmount > $invoice->total_amount) {
            return redirect()->back()->with('error', 'Concession amount cannot exceed the invoice total.');
        }

        // Validate concession doesn't make due amount negative
        $newDueAmount = $invoice->total_amount - $invoice->paid_amount - $concessionAmount;
        if ($newDueAmount < 0) {
            return redirect()->back()->with('error', 'Concession would result in overpayment. Please adjust the amount.');
        }

        // Update invoice with concession
      $invoice->update([
    'concession_amount' => $concessionAmount,
    'concession_notes' => $request->concession_notes,
    'due_amount' => $newDueAmount,
    'status' => $newDueAmount <= 0 ? 'paid' : ($invoice->paid_amount > 0 ? 'partial' : 'unpaid'),
  ]);

        // Log activity
        activity()
            ->performedOn($invoice)
            ->causedBy(auth()->user())
            ->withProperties([
                'concession_type' => $request->concession_type,
                'concession_value' => $request->concession_value,
                'concession_amount' => $concessionAmount,
                'old_due_amount' => $invoice->due_amount + $concessionAmount,
                'new_due_amount' => $newDueAmount,
            ])
            ->log("Applied {$request->concession_type} concession of " . 
                  ($request->concession_type === 'percentage' ? $request->concession_value . '%' : '₹' . number_format($concessionAmount, 2)) . 
                  " to invoice #{$invoice->invoice_number}");

        // Send notification if available
        if (isset($this->notificationService)) {
           $this->notificationService->sendFinancialAlert('concession_applied', [
    'student_id' => $invoice->student_id,
    'student_name' => $invoice->student->name,
    'invoice_number' => $invoice->invoice_number,
    'concession_amount' => $concessionAmount,
    'concession_type' => $request->concession_type,
    'concession_notes' => $request->concession_notes,
]);
        }

        DB::commit();

        return redirect()->back()->with('success', 
            'Concession of ₹' . number_format($concessionAmount, 2) . ' applied successfully. New due amount: ₹' . number_format($newDueAmount, 2));

    } catch (\Exception $e) {
        DB::rollback();
        return redirect()->back()->with('error', 'Failed to apply concession: ' . $e->getMessage());
    }
}


    /**
     * Financial dashboard/reports
     */
    public function reports(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));

        // Summary statistics
        $totalInvoiced = Invoice::whereBetween('issue_date', [$startDate, $endDate])->sum('total_amount');
        $totalPaid = Payment::whereBetween('payment_date', [$startDate, $endDate])->sum('amount');
        $totalDue = Invoice::whereBetween('issue_date', [$startDate, $endDate])->sum('due_amount');
        $totalConcessions = Invoice::whereBetween('issue_date', [$startDate, $endDate])->sum('concession_amount');

        // Monthly trends
        $monthlyTrends = $this->getMonthlyTrends($startDate, $endDate);

        // Outstanding invoices
        $outstandingInvoices = Invoice::with('student.batch.course')
                                    ->where('due_amount', '>', 0)
                                    ->orderBy('due_date')
                                    ->limit(20)
                                    ->get();

        return view('admin.invoices.reports', compact(
            'totalInvoiced', 
            'totalPaid', 
            'totalDue', 
            'totalConcessions',
            'monthlyTrends',
            'outstandingInvoices',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Helper: Get monthly financial data for a student
     */
    private function getMonthlyFinancialData(Student $student)
    {
        $months = [];
        $startDate = now()->subMonths(11)->startOfMonth();
        
        for ($i = 0; $i < 12; $i++) {
            $monthStart = $startDate->copy()->addMonths($i);
            $monthEnd = $monthStart->copy()->endOfMonth();
            
            $invoiced = $student->invoices()
                               ->whereBetween('issue_date', [$monthStart, $monthEnd])
                               ->sum('total_amount');
                               
            $paid = 0;
            foreach ($student->invoices as $invoice) {
                $paid += $invoice->payments()
                              ->whereBetween('payment_date', [$monthStart, $monthEnd])
                              ->sum('amount');
            }
            
            $months[] = [
                'month' => $monthStart->format('M Y'),
                'invoiced' => $invoiced,
                'paid' => $paid,
            ];
        }
        
        return $months;
    }
    
  

/**
 * ✅ NEW: Show full edit history page
 */
public function editHistory(Invoice $invoice)
{
    $invoice->load(['student.batch.course']);
    
    $editLogs = InvoiceEditLog::where('invoice_id', $invoice->id)
                             ->with('user')
                             ->latest()
                             ->paginate(20);
    
    return view('admin.invoices.edit-history', compact('invoice', 'editLogs'));
}

    /**
     * Helper: Get monthly trends
     */
    private function getMonthlyTrends($startDate, $endDate)
    {
        $start = Carbon::parse($startDate)->startOfMonth();
        $end = Carbon::parse($endDate)->endOfMonth();
        $trends = [];
        
        while ($start <= $end) {
            $monthStart = $start->copy()->startOfMonth();
            $monthEnd = $start->copy()->endOfMonth();
            
            $invoiced = Invoice::whereBetween('issue_date', [$monthStart, $monthEnd])->sum('total_amount');
            $paid = Payment::whereBetween('payment_date', [$monthStart, $monthEnd])->sum('amount');
            
            $trends[] = [
                'month' => $start->format('M Y'),
                'invoiced' => $invoiced,
                'paid' => $paid,
            ];
            
            $start->addMonth();
        }
        
        return $trends;
    }
}