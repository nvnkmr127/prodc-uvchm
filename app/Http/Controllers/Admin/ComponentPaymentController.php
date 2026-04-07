<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentFee;
use App\Models\Payment;
use App\Models\PaymentEditLog;
use Illuminate\Support\Facades\Log;
use App\Models\FeeCategory;
use App\Models\StudentConcession;
use App\Services\ComponentPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\ComponentPaymentItem;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;



class ComponentPaymentController extends Controller
{
    protected $paymentService;

    public function __construct(ComponentPaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

/**
 * Enhanced student component dashboard with modern design
 */
public function studentComponentDashboard(Student $student)
{
    // Load student with relationships
    $student->load([
        'batch.course',
        'studentFees.feeCategory'
    ]);

    // Get payment history (paginated)
    $payments = Payment::where('student_id', $student->id)
        ->with(['createdBy', 'componentItems.studentFee.feeCategory'])
        ->orderBy('payment_date', 'desc')
        ->paginate(15);

    // Get recent payments (for activity timeline)
    $recentPayments = Payment::where('student_id', $student->id)
        ->with(['createdBy', 'componentItems.studentFee.feeCategory'])
        ->orderBy('payment_date', 'desc')
        ->limit(5)
        ->get();

    // Enhanced payment activities with better formatting
    $paymentActivities = Payment::where('student_id', $student->id)
        ->with([
            'createdBy:id,name',
            'updatedBy:id,name',
            'componentItems.studentFee.feeCategory:id,name'
        ])
        ->orderBy('created_at', 'desc')
        ->limit(10) // Limit for dashboard performance
        ->get()
        ->map(function($payment) {
            return [
                'id' => $payment->id,
                'type' => 'payment_created',
                'description' => "Payment of ₹" . number_format($payment->amount, 0) . " recorded",
                'amount' => $payment->amount,
                'payment_method' => $payment->payment_method,
                'receipt_number' => $payment->receipt_number,
                'user' => $payment->createdBy->name ?? 'System',
                'timestamp' => $payment->created_at,
                'details' => $payment->componentItems->map(function($item) {
                    return $item->studentFee->feeCategory->name . ': ₹' . number_format($item->amount_paid, 0);
                })->implode(', ')
            ];
        });

    // Get student fees with enhanced calculations
    $studentFees = $student->studentFees()->with('feeCategory')->get();

    // Enhanced financial calculations
    $totalFees = $student->studentFees->sum('amount');
    $totalPaid = $student->studentFees->sum('paid_amount');
    $totalConcession = $student->studentFees->sum('concession_amount');
    $totalDue = $totalFees - $totalPaid - $totalConcession;
    $totalBilled = $totalFees - $totalConcession;
    $balanceDue = $totalDue;

    // Enhanced payment statistics
    $paymentStats = [
        'total_transactions' => $payments->total(),
        'last_payment_date' => $recentPayments->first()?->payment_date,
        'average_payment' => $payments->count() > 0 ? $totalPaid / $payments->count() : 0,
        'payment_frequency' => $this->calculatePaymentFrequency($student->id),
        'completion_rate' => $totalBilled > 0 ? round(($totalPaid / $totalBilled) * 100, 1) : 0,
        'pending_components' => $studentFees->filter(function($fee) {
            return ($fee->amount - $fee->paid_amount - $fee->concession_amount) > 0;
        })->count()
    ];

    // Get pending fee components
    $pendingFees = $student->studentFees()
        ->where(function($query) {
            $query->whereRaw('amount > (paid_amount + concession_amount)');
        })
        ->with('feeCategory')
        ->get();

    return view('admin.payments.component-dashboard', compact(
        'student', 'payments', 'recentPayments', 'paymentActivities', 'paymentStats',
        'studentFees', 'totalFees', 'totalPaid', 'totalConcession', 
        'totalDue', 'totalBilled', 'balanceDue', 'pendingFees'
    ));
}

/**
 * Get dashboard analytics data via AJAX
 */
public function getDashboardAnalytics(Student $student)
{
    $analytics = [
        'payment_trend' => $this->getPaymentTrend($student->id),
        'category_breakdown' => $this->getCategoryBreakdown($student->id),
        'monthly_collections' => $this->getMonthlyCollections($student->id),
        'overdue_analysis' => $this->getOverdueAnalysis($student->id)
    ];

    return response()->json($analytics);
}

/**
 * Get payment trend data for charts
 */
private function getPaymentTrend($studentId)
{
    return Payment::where('student_id', $studentId)
        ->selectRaw('DATE(payment_date) as date, SUM(amount) as amount')
        ->where('payment_date', '>=', now()->subMonths(6))
        ->groupBy('date')
        ->orderBy('date')
        ->get()
        ->map(function($payment) {
            return [
                'date' => Carbon::parse($payment->date)->format('M d'),
                'amount' => $payment->amount
            ];
        });
}

/**
 * Get category-wise payment breakdown
 */
private function getCategoryBreakdown($studentId)
{
    return StudentFee::where('student_id', $studentId)
        ->with('feeCategory')
        ->get()
        ->groupBy('feeCategory.name')
        ->map(function($fees, $categoryName) {
            $totalAmount = $fees->sum('amount');
            $paidAmount = $fees->sum('paid_amount');
            $concessionAmount = $fees->sum('concession_amount');
            
            return [
                'category' => $categoryName,
                'total' => $totalAmount,
                'paid' => $paidAmount,
                'concession' => $concessionAmount,
                'remaining' => $totalAmount - $paidAmount - $concessionAmount,
                'percentage' => $totalAmount > 0 ? round(($paidAmount / $totalAmount) * 100, 1) : 0
            ];
        })->values();
}

/**
 * Get monthly collection data
 */
private function getMonthlyCollections($studentId)
{
    return Payment::where('student_id', $studentId)
        ->selectRaw('YEAR(payment_date) as year, MONTH(payment_date) as month, SUM(amount) as total')
        ->where('payment_date', '>=', now()->subYear())
        ->groupBy('year', 'month')
        ->orderBy('year')
        ->orderBy('month')
        ->get()
        ->map(function($payment) {
            return [
                'month' => Carbon::createFromDate($payment->year, $payment->month, 1)->format('M Y'),
                'amount' => $payment->total
            ];
        });
}

/**
 * Get overdue analysis
 */
private function getOverdueAnalysis($studentId)
{
    $overdueFees = StudentFee::where('student_id', $studentId)
        ->where('due_date', '<', now())
        ->whereRaw('amount > (paid_amount + concession_amount)')
        ->with('feeCategory')
        ->get();

    return [
        'count' => $overdueFees->count(),
        'total_amount' => $overdueFees->sum(function($fee) {
            return $fee->amount - $fee->paid_amount - $fee->concession_amount;
        }),
        'categories' => $overdueFees->groupBy('feeCategory.name')->map(function($fees, $category) {
            return [
                'category' => $category,
                'count' => $fees->count(),
                'amount' => $fees->sum(function($fee) {
                    return $fee->amount - $fee->paid_amount - $fee->concession_amount;
                })
            ];
        })->values()
    ];
}


/**
 * Enhanced quick payment processing
 */
public function storeQuickPayment(Request $request)
{
    $validated = $request->validate([
        'student_id' => 'required|exists:students,id',
        'student_fee_id' => 'required|exists:student_fees,id',
        'amount' => 'required|numeric|min:0.01',
        'payment_method' => 'required|string',
        'payment_date' => 'required|date',
        'transaction_id' => 'nullable|string|max:255',
        'notes' => 'nullable|string|max:500'
    ]);

    try {
        DB::beginTransaction();

        $student = Student::findOrFail($validated['student_id']);
        $studentFee = StudentFee::findOrFail($validated['student_fee_id']);
        
        // Validate the student fee belongs to the student
        if ((int)$studentFee->student_id !== (int)$student->id) {
            throw new \Exception('Invalid fee component for this student.');
        }

        // Calculate remaining amount
        $remainingAmount = $studentFee->amount - $studentFee->paid_amount - $studentFee->concession_amount;
        
        if ($validated['amount'] > $remainingAmount) {
            throw new \Exception('Payment amount exceeds remaining balance.');
        }

        // Generate receipt number - BYPASS GLOBAL SCOPES
        $receiptNumber = 'RCP-' . date('Ymd') . '-' . str_pad(Payment::withoutGlobalScope('academic_year')->whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);

        // Create payment record
        $payment = Payment::create([
            'student_id' => $student->id,
            'receipt_number' => $receiptNumber,
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'payment_date' => $validated['payment_date'],
            'transaction_id' => $validated['transaction_id'],
            'notes' => $validated['notes'],
            'payment_type' => 'component',
            'status' => 'completed',
            'created_by' => auth()->id()
        ]);

        // Create component payment item
        ComponentPaymentItem::create([
            'payment_id' => $payment->id,
            'student_fee_id' => $studentFee->id,
            'amount_paid' => $validated['amount']
        ]);

        // Update student fee paid amount
        $studentFee->increment('paid_amount', $validated['amount']);
        $studentFee->save();

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Payment recorded successfully!',
            'receipt_number' => $receiptNumber,
            'payment_id' => $payment->id
        ]);

    } catch (\Exception $e) {
        DB::rollback();
        
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 422);
    }
}

/**
 * Calculate payment frequency (enhanced version)
 */
private function calculatePaymentFrequency($studentId)
{
    $payments = Payment::where('student_id', $studentId)
        ->orderBy('payment_date')
        ->pluck('payment_date')
        ->toArray();
    
    if (count($payments) < 2) {
        return 'Insufficient data';
    }
    
    $intervals = [];
    for ($i = 1; $i < count($payments); $i++) {
        $interval = Carbon::parse($payments[$i])->diffInDays(Carbon::parse($payments[$i-1]));
        $intervals[] = $interval;
    }
    
    $averageInterval = array_sum($intervals) / count($intervals);
    
    if ($averageInterval <= 7) return 'Weekly';
    if ($averageInterval <= 30) return 'Monthly';
    if ($averageInterval <= 90) return 'Quarterly';
    return 'Irregular';
}

 /**
     * Get component data for AJAX - NEW
     */
    public function getComponentData(Request $request)
    {
        $studentFeeId = $request->student_fee_id;
        $studentFee = StudentFee::with('feeCategory')->findOrFail($studentFeeId);
        
        $remainingAmount = $studentFee->amount - $studentFee->paid_amount - $studentFee->concession_amount;
        
        return response()->json([
            'component_name' => $studentFee->feeCategory->name,
            'total_amount' => $studentFee->amount,
            'paid_amount' => $studentFee->paid_amount,
            'concession_amount' => $studentFee->concession_amount,
            'remaining_amount' => $remainingAmount,
            'due_date' => $studentFee->due_date,
            'status' => $studentFee->status
        ]);
    }

 /**
     * Generate a unique receipt number
     */
    private function generateReceiptNumber()
    {
        $prefix = 'RCP';
        $year = date('Y');
        $month = date('m');
        
        // Get the next sequence number for this month - BYPASS GLOBAL SCOPE
        $lastPayment = Payment::withoutGlobalScope('academic_year')
                              ->where('receipt_number', 'like', "{$prefix}{$year}{$month}%")
                              ->orderBy('receipt_number', 'desc')
                              ->first();
        
        if ($lastPayment) {
            $lastSequence = (int) substr($lastPayment->receipt_number, -4);
            $nextSequence = $lastSequence + 1;
        } else {
            $nextSequence = 1;
        }
        
        return $prefix . $year . $month . str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
    }

   /**
    * Show component payment form - FIXED SYNTAX ERROR
    */
   public function componentPaymentForm(Student $student)
   {
       try {
           $student->load(['batch.course', 'studentFees.feeCategory']);

           $unpaidFees = collect();
           if ($student->studentFees) {
               $unpaidFees = $student->studentFees()
                   ->whereIn('status', ['unpaid', 'partial'])
                   ->with('feeCategory')
                   ->orderBy('due_date')
                   ->get();
           }

           $feeCategories = \App\Models\FeeCategory::orderBy('name')->get();

           return view('admin.payments.component-payment-form', compact(
               'student', 'unpaidFees', 'feeCategories'
           ));
       } catch (\Exception $e) {
           return redirect()->back()->with('error', 'Unable to load payment form: ' . $e->getMessage());
       }
   }

  /**
     * Record a component payment for a student
     */
    public function recordComponentPayment(Request $request, Student $student)
    {
        $validated = $request->validate([
            'components' => 'required|array|min:1',
            'components.*.fee_category_id' => 'required|exists:fee_categories,id',
            'components.*.amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'payment_date' => 'required|date',
            'transaction_id' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            $totalAmount = collect($validated['components'])->sum('amount');

            // Create payment with explicit user tracking
            $payment = Payment::create([
                'student_id' => $student->id,
                'payment_type' => 'component',
                'amount' => $totalAmount,
                'payment_method' => $validated['payment_method'],
                'payment_date' => $validated['payment_date'],
                'transaction_id' => $validated['transaction_id'],
                'notes' => $validated['notes'],
                'status' => 'completed',
                'academic_year' => now()->format('Y'), // Set current academic year
                'created_by' => auth()->id(), // Explicitly set created_by
                'updated_by' => auth()->id()  // Set updated_by as well
            ]);

            // Process components
            foreach ($validated['components'] as $component) {
                $studentFee = StudentFee::where('student_id', $student->id)
                    ->where('fee_category_id', $component['fee_category_id'])
                    ->first();

                if (!$studentFee) {
                    throw new \Exception("Fee category not found for student: " . $component['fee_category_id']);
                }

                // Create component item
                $componentItem = $payment->componentItems()->create([
                    'student_fee_id' => $studentFee->id,
                    'amount_paid' => $component['amount']
                ]);

                // Update student fee
                $studentFee->increment('paid_amount', $component['amount']);
                $this->updateStudentFeeStatus($studentFee);

                // Log the component creation
                Log::info('Component item created', [
                    'payment_id' => $payment->id,
                    'student_fee_id' => $studentFee->id,
                    'amount_paid' => $component['amount'],
                    'created_by' => auth()->id()
                ]);
            }

            // Log payment creation in PaymentEditLog
            if (class_exists(PaymentEditLog::class)) {
                PaymentEditLog::logPaymentChange(
                    $payment,
                    'created',
                    [],
                    [
                        'amount' => $payment->amount,
                        'payment_method' => $payment->payment_method,
                        'payment_date' => $payment->payment_date->format('Y-m-d'),
                        'components' => $validated['components'],
                        'student_id' => $payment->student_id
                    ],
                    'Payment created via component payment system'
                );
            }

            DB::commit();

            // Log successful payment creation
            Log::info('Payment recorded successfully', [
                'payment_id' => $payment->id,
                'student_id' => $student->id,
                'amount' => $totalAmount,
                'created_by' => auth()->id(),
                'receipt_number' => $payment->receipt_number
            ]);

            return redirect()->route('admin.students.show', $student)
                ->with('success', 'Payment recorded successfully! Receipt: ' . $payment->receipt_number);

        } catch (\Exception $e) {
            DB::rollback();
            
            // Log the error
            Log::error('Payment recording failed', [
                'student_id' => $student->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);
            
            return back()->withInput()
                ->with('error', 'Failed to record payment: ' . $e->getMessage());
        }
    }


/**
 * Export payment activity log to Excel
 */
public function exportActivityLog(Student $student)
{
    $activities = Payment::where('student_id', $student->id)
        ->with(['createdBy', 'componentItems.studentFee.feeCategory'])
        ->orderBy('created_at', 'desc')
        ->get();

    return Excel::download(new PaymentActivityExport($activities, $student), 
        "payment_activity_{$student->enrollment_number}.xlsx");
}

/**
 * Get activity log data via AJAX
 */
public function getActivityLogData(Student $student, Request $request)
{
    $query = Payment::where('student_id', $student->id)
        ->with(['createdBy', 'componentItems.studentFee.feeCategory']);

    // Filter by date range
    if ($request->has('start_date')) {
        $query->whereDate('created_at', '>=', $request->start_date);
    }
    if ($request->has('end_date')) {
        $query->whereDate('created_at', '<=', $request->end_date);
    }

    // Filter by type
    if ($request->has('type') && $request->type !== 'all') {
        // Add filters based on payment type, status, etc.
    }

    $activities = $query->orderBy('created_at', 'desc')
        ->paginate(20);

    return response()->json([
        'success' => true,
        'activities' => $activities,
        'total' => $activities->total()
    ]);
}

/**
 * Add activity log entry (for custom events)
 */
public function addActivityLogEntry(Student $student, $type, $description, $details = null)
{
    // You might want to create a separate ActivityLog model for non-payment activities
    ActivityLog::create([
        'student_id' => $student->id,
        'user_id' => auth()->id(),
        'activity_type' => $type,
        'description' => $description,
        'details' => $details,
        'ip_address' => request()->ip(),
        'user_agent' => request()->userAgent(),
    ]);
}

/**
 * Get payment timeline for charts/graphs
 */
public function getPaymentTimeline(Student $student)
{
    $timeline = Payment::where('student_id', $student->id)
        ->selectRaw('DATE(payment_date) as date, SUM(amount) as daily_total, COUNT(*) as payment_count')
        ->groupBy('date')
        ->orderBy('date')
        ->get();

    return response()->json([
        'timeline' => $timeline,
        'chart_data' => [
            'labels' => $timeline->pluck('date'),
            'amounts' => $timeline->pluck('daily_total'),
            'counts' => $timeline->pluck('payment_count')
        ]
    ]);
}


  /**
     * Store bulk payments
     */
    public function storeBulkPayments(Request $request)
    {
        $validated = $request->validate([
            'payments' => 'required|array|min:1',
            'payments.*.student_id' => 'required|exists:students,id',
            'payments.*.components' => 'required|array|min:1',
            'payments.*.components.*.student_fee_id' => 'required|exists:student_fees,id',
            'payments.*.components.*.amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'payment_date' => 'required|date',
        ]);

        try {
            DB::beginTransaction();

            $createdPayments = [];

            foreach ($validated['payments'] as $paymentData) {
                $student = Student::findOrFail($paymentData['student_id']);
                $totalAmount = collect($paymentData['components'])->sum('amount');

                $payment = Payment::create([
                    'student_id' => $student->id,
                    'payment_type' => 'component',
                    'amount' => $totalAmount,
                    'payment_method' => $validated['payment_method'],
                    'payment_date' => $validated['payment_date'],
                    'status' => 'completed',
                    'academic_year' => now()->format('Y'),
                    'created_by' => auth()->id(), // Explicitly set created_by
                    'updated_by' => auth()->id()  // Set updated_by as well
                ]);

                foreach ($paymentData['components'] as $component) {
                    $studentFee = StudentFee::findOrFail($component['student_fee_id']);
                    
                    $payment->componentItems()->create([
                        'student_fee_id' => $studentFee->id,
                        'amount_paid' => $component['amount'],
                    ]);

                    $studentFee->increment('paid_amount', $component['amount']);
                    
                    $remainingAmount = $studentFee->amount - $studentFee->concession_amount - $studentFee->paid_amount;
                    if ($remainingAmount <= 0) {
                        $studentFee->update(['status' => 'paid']);
                    } elseif ($studentFee->paid_amount > 0) {
                        $studentFee->update(['status' => 'partial']);
                    }
                }

                // Log bulk payment creation
                if (class_exists(PaymentEditLog::class)) {
                    PaymentEditLog::logPaymentChange(
                        $payment,
                        'created',
                        [],
                        [
                            'amount' => $payment->amount,
                            'payment_method' => $payment->payment_method,
                            'payment_date' => $payment->payment_date->format('Y-m-d'),
                            'components' => $paymentData['components']
                        ],
                        'Bulk payment created'
                    );
                }

                $createdPayments[] = $payment;
            }

            DB::commit();

            return redirect()->route('admin.component-payments.index')
                ->with('success', count($createdPayments) . ' payments recorded successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Bulk payment recording failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            return back()->withInput()
                ->with('error', 'Failed to record bulk payments: ' . $e->getMessage());
        }
    }


    /**
     * Quick component payment (for single category)
     */
    public function quickComponentPayment(Request $request, Student $student)
    {
        $request->validate([
            'fee_category_id' => 'required|exists:fee_categories,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash, card, bank_transfer, cheque, Phonepe, Gpay, Paytm, UPI, online',
            'notes' => 'nullable|string|max:500'
        ]);

        $components = [[
            'fee_category_id' => $request->fee_category_id,
            'amount' => $request->amount
        ]];

        try {
            $result = $this->paymentService->processPayment(
                $student,
                $components,
                [
                    'payment_method' => $request->payment_method,
                    'payment_date' => now(),
                    'notes' => $request->notes
                ]
            );

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Payment failed: ' . $e->getMessage()
            ], 422);
        }
    }

 /**
     * Display the specified payment
     */
    public function show(Payment $componentPayment)
    {
        $componentPayment->load([
            'student.batch.course',
            'componentItems.studentFee.feeCategory',
            'creator'
        ]);
        
        
        // Get edit history
        $editHistory = PaymentEditLog::forPayment($componentPayment->id)
            ->with('user')
            ->latest()
            ->get();

        return view('admin.component-payments.index', compact('componentPayment'));
    }
    
/**
     * Show the form for editing the specified payment
     */
    public function edit(Payment $componentPayment)
    {
        if ($componentPayment->payment_type !== 'component') {
            return redirect()->back()->with('error', 'This payment cannot be edited.');
        }

        // Check if payment can be edited
        if (!$componentPayment->canBeEdited()) {
            return redirect()->back()->with('error', 'This payment is no longer editable due to age or policy restrictions.');
        }

        $componentPayment->load([
            'student.batch.course',
            'componentItems.studentFee.feeCategory',
            'student.studentFees.feeCategory',
            'creator'
        ]);

        $paymentMethods = ['cash', 'card', 'bank_transfer', 'cheque', 'Phonepe', 'Gpay', 'Paytm', 'UPI', 'online'];
        
        // Get edit history
        $editHistory = PaymentEditLog::forPayment($componentPayment->id)
            ->with('user')
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.component-payments.edit', compact('componentPayment', 'paymentMethods', 'editHistory'));
    }


/**
 * Get student components summary (robust version)
 */
private function getStudentComponents(Student $student)
{
    try {
        // Check if student has any fees
        if (!$student->studentFees || $student->studentFees->isEmpty()) {
            return collect(); // Return empty collection
        }

        return $student->studentFees()->with('feeCategory')->get()->map(function ($fee) {
            // Ensure all amounts are numeric
            $amount = (float) ($fee->amount ?? 0);
            $paidAmount = (float) ($fee->paid_amount ?? 0);
            $concessionAmount = (float) ($fee->concession_amount ?? 0);
            
            $balance = $amount - $concessionAmount - $paidAmount;
            $balance = max(0, $balance); // Ensure balance is not negative
            
            // Check if overdue (with proper null checking)
            $isOverdue = false;
            if ($fee->due_date && $balance > 0) {
                try {
                    $isOverdue = \Carbon\Carbon::parse($fee->due_date)->isPast();
                } catch (\Exception $e) {
                    $isOverdue = false; // Default to false if date parsing fails
                }
            }
            
            // Determine status
            $status = 'unpaid';
            if ($balance <= 0) {
                $status = 'paid';
            } elseif ($paidAmount > 0) {
                $status = 'partial';
            }
            
            return [
                'id' => $fee->id ?? 0,
                'category' => optional($fee->feeCategory)->name ?? 'N/A',
                'total_amount' => $amount,
                'paid_amount' => $paidAmount,
                'concession_amount' => $concessionAmount,
                'balance' => $balance,
                'due_date' => $fee->due_date,
                'status' => $status,
                'is_overdue' => $isOverdue
            ];
        });
    } catch (\Exception $e) {
        // Log error and return empty collection
        \Log::error('Error getting student components for student ' . $student->id . ': ' . $e->getMessage());
        return collect();
    }
}

    /**
     * Get payable components for student (AJAX)
     */
    public function getPayableComponents(Student $student)
    {
        $unpaidFees = $student->studentFees()
            ->whereIn('status', ['unpaid', 'partial'])
            ->with('feeCategory')
            ->orderBy('due_date')
            ->get();

        return response()->json([
            'components' => $unpaidFees->groupBy('fee_category_id')->map(function($fees, $categoryId) {
                $category = $fees->first()->feeCategory;
                $totalRemaining = $fees->sum(fn($fee) => $fee->getRemainingAmount());
                
                return [
                    'category_id' => $categoryId,
                    'category_name' => $category->name,
                    'category_type' => $category->category_type ?? 'other',
                    'remaining_amount' => $totalRemaining,
                    'fees_count' => $fees->count(),
                    'min_amount' => 0.01,
                    'max_amount' => $totalRemaining
                ];
            })->values()
        ]);
    }

    /**
     * Get student payment summary (AJAX)
     */
    public function studentPaymentSummary(Student $student)
    {
        $summary = $this->getFinancialSummary($student);
        $components = $this->getStudentComponents($student);

        return response()->json([
            'summary' => $summary,
            'components' => $components
        ]);
    }



    /**
     * Component payment report
     */
    public function componentPaymentReport(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth());
        $endDate = $request->get('end_date', now()->endOfMonth());
        $feeCategory = $request->get('fee_category_id');

        // Category-wise summary
        $categorySummary = FeeCategory::with(['studentFees' => function($query) use ($startDate, $endDate) {
            $query->whereHas('componentPaymentItems.payment', function($q) use ($startDate, $endDate) {
                $q->whereBetween('payment_date', [$startDate, $endDate]);
            });
        }])
        ->get()
        ->map(function($category) {
            $fees = $category->studentFees;
            $totalAmount = $fees->sum('amount');
            $concessionAmount = $fees->sum('concession_amount');
            $paidAmount = $fees->sum('paid_amount');
            $netAmount = $totalAmount - $concessionAmount;

            return [
                'category_id' => $category->id,
                'category_name' => $category->name,
                'total_amount' => $totalAmount,
                'concession_amount' => $concessionAmount,
                'net_amount' => $netAmount,
                'paid_amount' => $paidAmount,
                'remaining_amount' => $netAmount - $paidAmount,
                'payment_percentage' => $netAmount > 0 ? round(($paidAmount / $netAmount) * 100, 2) : 100,
                'students_count' => $fees->unique('student_id')->count()
            ];
        });

        // Recent payments
        $recentPayments = Payment::where('payment_type', 'component')
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->with(['student', 'componentItems.studentFee.feeCategory'])
            ->orderBy('payment_date', 'desc')
            ->limit(50)
            ->get();

        // Outstanding amounts
        $outstandingAmounts = StudentFee::with(['student', 'feeCategory'])
            ->whereRaw('amount - concession_amount - paid_amount > 0')
            ->orderBy('due_date')
            ->limit(50)
            ->get()
            ->map(function ($fee) {
                return [
                    'student' => $fee->student->name,
                    'enrollment_number' => $fee->student->enrollment_number,
                    'category' => $fee->feeCategory->name,
                    'outstanding_amount' => $fee->getRemainingAmount(),
                    'due_date' => $fee->due_date?->format('Y-m-d'),
                    'is_overdue' => $fee->isOverdue()
                ];
            });

        return view('admin.reports.component-payments', compact(
            'categorySummary', 'recentPayments', 'outstandingAmounts', 
            'startDate', 'endDate', 'feeCategory'
        ));
    }
    
     /**
     * Show the form for creating a new payment
     */
    public function create(Request $request)
    {
        $student = null;
        if ($request->filled('student_id')) {
            $student = Student::with(['batch.course', 'studentFees.feeCategory'])
                ->findOrFail($request->student_id);
        }

        $students = Student::with('batch.course')->orderBy('name')->get();
        $feeCategories = FeeCategory::orderBy('name')->get();

        return view('admin.payments.create', compact(
            'student', 'students', 'feeCategories'
        ));
    }

/**
 * Store a newly created payment in storage. - DEBUG VERSION
 */
public function store(Request $request)
{
    // Add debugging
    \Log::info('Payment store method called', [
        'request_data' => $request->all(),
        'user_id' => auth()->id()
    ]);

    try {
        // Validate the request
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'total_amount' => 'required|numeric|min:0.01|max:999999.99',
            'payment_method' => 'required|string|in:cash,online,upi,cheque,card',
            'payment_date' => 'required|date|before_or_equal:today',
            'transaction_id' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'components' => 'required|array|min:1',
            'components.*.selected' => 'required|boolean',
            'components.*.amount' => 'required_if:components.*.selected,1|numeric|min:0.01'
        ], [
            'student_id.required' => 'Student is required.',
            'student_id.exists' => 'Selected student does not exist.',
            'total_amount.required' => 'Payment amount is required.',
            'total_amount.numeric' => 'Payment amount must be a valid number.',
            'total_amount.min' => 'Payment amount must be at least ₹0.01.',
            'payment_method.required' => 'Payment method is required.',
            'payment_method.in' => 'Invalid payment method selected.',
            'payment_date.required' => 'Payment date is required.',
            'payment_date.before_or_equal' => 'Payment date cannot be in the future.',
            'components.required' => 'At least one component must be selected.',
            'components.*.amount.required_if' => 'Amount is required for selected components.',
            'components.*.amount.numeric' => 'Component amount must be a valid number.'
        ]);

        \Log::info('Validation passed', ['validated_data' => $validated]);

        $student = Student::findOrFail($validated['student_id']);
        \Log::info('Student loaded', ['student_id' => $student->id, 'student_name' => $student->name]);
        
        // Filter selected components
        $selectedComponents = [];
        foreach ($validated['components'] as $studentFeeId => $component) {
            if ($component['selected'] == '1' || $component['selected'] === true) {
                $selectedComponents[] = [
                    'student_fee_id' => $studentFeeId,
                    'amount' => (float) $component['amount']
                ];
            }
        }

        \Log::info('Selected components', ['components' => $selectedComponents]);

        if (empty($selectedComponents)) {
            return back()->withErrors(['components' => 'Please select at least one component to pay.'])
                       ->withInput();
        }

        // Validate component amounts - THIS IS WHERE IT'S LIKELY FAILING
        $componentsTotal = 0;
        foreach ($selectedComponents as $component) {
            \Log::info('Processing component', ['student_fee_id' => $component['student_fee_id']]);
            
            try {
                $studentFee = StudentFee::findOrFail($component['student_fee_id']);
                \Log::info('StudentFee loaded', [
                    'id' => $studentFee->id,
                    'amount' => $studentFee->amount,
                    'paid_amount' => $studentFee->paid_amount ?? 0,
                    'concession_amount' => $studentFee->concession_amount ?? 0,
                    'student_id' => $studentFee->student_id
                ]);
                
                // Check if student fee belongs to the selected student (FIXED - use loose comparison)
                if ((int)$studentFee->student_id !== (int)$student->id) {
                    \Log::error('StudentFee does not belong to student', [
                        'student_fee_student_id' => $studentFee->student_id,
                        'student_fee_student_id_type' => gettype($studentFee->student_id),
                        'selected_student_id' => $student->id,
                        'selected_student_id_type' => gettype($student->id)
                    ]);
                    return back()->withErrors(['components' => 'Invalid component selected.'])
                               ->withInput();
                }
                
                // Calculate remaining amount (FIXED - don't use amount_due column)
                $totalAmount = (float) $studentFee->amount;
                $paidAmount = (float) ($studentFee->paid_amount ?? 0);
                $concessionAmount = (float) ($studentFee->concession_amount ?? 0);
                $remainingAmount = $totalAmount - $paidAmount - $concessionAmount;
                
                \Log::info('Amount calculation', [
                    'total_amount' => $totalAmount,
                    'paid_amount' => $paidAmount,
                    'concession_amount' => $concessionAmount,
                    'remaining_amount' => $remainingAmount,
                    'payment_amount' => $component['amount']
                ]);
                
                // Check if amount doesn't exceed remaining amount
                if ($component['amount'] > $remainingAmount) {
                    \Log::error('Payment amount exceeds remaining amount', [
                        'payment_amount' => $component['amount'],
                        'remaining_amount' => $remainingAmount
                    ]);
                    return back()->withErrors([
                        'components' => "Amount for {$studentFee->feeCategory->name} exceeds the remaining amount of ₹" . number_format($remainingAmount, 2)
                    ])->withInput();
                }
                
                $componentsTotal += $component['amount'];
                \Log::info('Component validation passed', ['component_total_so_far' => $componentsTotal]);
                
            } catch (\Exception $e) {
                \Log::error('Error processing component', [
                    'student_fee_id' => $component['student_fee_id'],
                    'error' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'file' => $e->getFile()
                ]);
                throw $e;
            }
        }

        \Log::info('All components validated', ['total_components_amount' => $componentsTotal]);

        // Validate total amount matches components
        if (abs($validated['total_amount'] - $componentsTotal) > 0.01) {
            \Log::error('Total amount mismatch', [
                'form_total' => $validated['total_amount'],
                'components_total' => $componentsTotal,
                'difference' => abs($validated['total_amount'] - $componentsTotal)
            ]);
            return back()->withErrors([
                'total_amount' => 'Payment amount must equal the sum of component amounts.'
            ])->withInput();
        }

        \Log::info('Starting database transaction');
        // Start database transaction
        DB::beginTransaction();

        try {
            // Generate receipt number
            $receiptNumber = $this->generateReceiptNumber();
            \Log::info('Receipt number generated', ['receipt_number' => $receiptNumber]);

            // Create main payment record
            $paymentData = [
                'student_id' => $student->id,
                'amount' => $validated['total_amount'],
                'payment_type' => 'component',
                'payment_method' => $validated['payment_method'],
                'payment_date' => $validated['payment_date'],
                'transaction_id' => $validated['transaction_id'],
                'receipt_number' => $receiptNumber,
                'notes' => $validated['notes'],
                'academic_year' => $this->getCurrentAcademicYear($student),
                'status' => 'completed',
                'created_by' => auth()->id(),
            ];
            
            \Log::info('Creating payment with data', ['payment_data' => $paymentData]);
            
            $payment = Payment::create($paymentData);
            \Log::info('Payment record created', ['payment_id' => $payment->id]);

            // Create component payment items and update student fees
            foreach ($selectedComponents as $component) {
                \Log::info('Processing payment item', ['component' => $component]);
                
                $studentFee = StudentFee::findOrFail($component['student_fee_id']);
                
                // Create component payment item
                $itemData = [
                    'payment_id' => $payment->id,
                    'student_fee_id' => $studentFee->id,
                    'amount_paid' => $component['amount'],
                    'notes' => "Payment for {$studentFee->feeCategory->name}",
                ];
                
                \Log::info('Creating component payment item', ['item_data' => $itemData]);
                
                ComponentPaymentItem::create($itemData);
                \Log::info('Component payment item created');

                // Update student fee - BE CAREFUL WITH COLUMN NAMES
                $oldPaidAmount = $studentFee->paid_amount ?? 0;
                $newPaidAmount = $oldPaidAmount + $component['amount'];
                
                \Log::info('Updating student fee', [
                    'student_fee_id' => $studentFee->id,
                    'old_paid_amount' => $oldPaidAmount,
                    'payment_amount' => $component['amount'],
                    'new_paid_amount' => $newPaidAmount
                ]);
                
                $studentFee->paid_amount = $newPaidAmount;
                
                // Calculate new remaining amount
                $totalAmount = (float) $studentFee->amount;
                $concessionAmount = (float) ($studentFee->concession_amount ?? 0);
                $newRemainingAmount = $totalAmount - $concessionAmount - $newPaidAmount;
                
                // Update payment status
                if ($newRemainingAmount <= 0.01) {
                    $studentFee->status = 'paid';
                    \Log::info('StudentFee marked as paid');
                } else {
                    $studentFee->status = 'partial';
                    \Log::info('StudentFee marked as partial', ['remaining_amount' => $newRemainingAmount]);
                }
                
                $studentFee->save();
                \Log::info('StudentFee updated successfully');
            }

            // Commit transaction
            DB::commit();
            \Log::info('Payment transaction completed successfully', ['payment_id' => $payment->id]);

            return redirect()
                ->route('admin.students.show', $student->id)
                ->with('success', "Payment of ₹{$validated['total_amount']} recorded successfully! Receipt: {$receiptNumber}");

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Database transaction failed', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }

    } catch (\Illuminate\Validation\ValidationException $e) {
        \Log::warning('Validation failed', ['errors' => $e->errors()]);
        return back()->withErrors($e->errors())->withInput();
        
    } catch (\Exception $e) {
        \Log::error('Payment creation failed', [
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
            'student_id' => $request->student_id ?? 'not_set',
            'request_data' => $request->all(),
            'trace' => $e->getTraceAsString()
        ]);

        return back()->withErrors([
            'payment' => 'Payment processing failed. Please try again. Error: ' . $e->getMessage()
        ])->withInput();
    }
}


/**
 * Get current academic year dynamically
 */
private function getCurrentAcademicYear($student = null): string
{
    // Method 1: Try to get from student's existing fees (most accurate)
    if ($student && $student->studentFees()->exists()) {
        $existingAcademicYear = $student->studentFees()
            ->whereNotNull('academic_year')
            ->latest()
            ->value('academic_year');
        
        if ($existingAcademicYear) {
            \Log::info('Academic year from student fees', ['academic_year' => $existingAcademicYear]);
            return $existingAcademicYear;
        }
    }

    // Method 2: Try to get from AcademicYear model if it exists
    if (class_exists('\App\Models\AcademicYear')) {
        try {
            $currentAcademicYear = \App\Models\AcademicYear::where('is_current', true)->first();
            if ($currentAcademicYear) {
                \Log::info('Academic year from AcademicYear model', ['academic_year' => $currentAcademicYear->name]);
                return $currentAcademicYear->name;
            }
        } catch (\Exception $e) {
            \Log::warning('Could not fetch from AcademicYear model', ['error' => $e->getMessage()]);
        }
    }

    // Method 3: Try to get from session (if academic year switching is implemented)
    if (session('selected_academic_year_id')) {
        try {
            $selectedYear = \App\Models\AcademicYear::find(session('selected_academic_year_id'));
            if ($selectedYear) {
                \Log::info('Academic year from session', ['academic_year' => $selectedYear->name]);
                return $selectedYear->name;
            }
        } catch (\Exception $e) {
            \Log::warning('Could not fetch academic year from session', ['error' => $e->getMessage()]);
        }
    }

    // Method 4: Try to get from settings
    if (function_exists('setting')) {
        $settingYear = setting('current_academic_year');
        if ($settingYear) {
            \Log::info('Academic year from settings', ['academic_year' => $settingYear]);
            return $settingYear;
        }
    }

    // Method 5: Calculate based on current date (fallback)
    $currentYear = date('Y');
    $currentMonth = date('n');
    
    // Academic year typically starts in April (month 4) or July (month 7)
    // You can adjust this logic based on your institution's academic calendar
    if ($currentMonth >= 7) { // July to December = current year to next year
        $academicYear = $currentYear . '-' . ($currentYear + 1);
    } elseif ($currentMonth >= 4) { // April to June = current year to next year  
        $academicYear = $currentYear . '-' . ($currentYear + 1);
    } else { // January to March = previous year to current year
        $academicYear = ($currentYear - 1) . '-' . $currentYear;
    }
    
    \Log::info('Academic year calculated from date', [
        'academic_year' => $academicYear,
        'current_month' => $currentMonth,
        'current_year' => $currentYear
    ]);
    
    return $academicYear;
}

/**
 * Alternative method if you want to get academic year from course duration
 */
private function getAcademicYearFromCourse($student): string
{
    if (!$student->batch || !$student->batch->course) {
        return $this->getCurrentAcademicYear();
    }

    $course = $student->batch->course;
    $admissionDate = $student->admission_date ?? $student->created_at;
    
    // If course has duration, calculate based on admission date
    if (isset($course->duration_in_years)) {
        $admissionYear = date('Y', strtotime($admissionDate));
        $currentYear = date('Y');
        
        // Calculate which year of study the student is in
        $yearOfStudy = $currentYear - $admissionYear + 1;
        
        // Generate academic year based on year of study
        if ($yearOfStudy <= $course->duration_in_years) {
            return $currentYear . '-' . ($currentYear + 1);
        }
    }
    
    // Fallback to standard calculation
    return $this->getCurrentAcademicYear();
}
    
     /**
     * Remove the specified payment
     */
    public function destroy(Payment $componentPayment)
    {
        if ($componentPayment->payment_type !== 'component') {
            return redirect()->back()->with('error', 'This payment cannot be deleted.');
        }

        try {
            DB::beginTransaction();

            // Reverse the payment effects on student fees
            foreach ($componentPayment->componentItems as $item) {
                $studentFee = $item->studentFee;
                $studentFee->decrement('paid_amount', $item->amount);
                
                // Update status
                $remainingAmount = $studentFee->amount - $studentFee->concession_amount - $studentFee->paid_amount;
                if ($remainingAmount >= $studentFee->amount - $studentFee->concession_amount) {
                    $studentFee->update(['status' => 'unpaid']);
                } elseif ($studentFee->paid_amount > 0) {
                    $studentFee->update(['status' => 'partial']);
                }
            }

            // Delete the payment
            $componentPayment->delete();

            DB::commit();

            return redirect()->route('admin.component-payments.index')
                ->with('success', 'Payment deleted successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            
            return redirect()->back()
                ->with('error', 'Failed to delete payment: ' . $e->getMessage());
        }
    }

    /**
     * Show bulk payment creation form
     */
    public function bulkCreate()
    {
        $students = Student::with('batch.course')->orderBy('name')->get();
        $feeCategories = FeeCategory::orderBy('name')->get();

        return view('admin.component-payments.bulk-create', compact('students', 'feeCategories'));
    }

    /**
     * Store bulk payments
     */
    public function bulkStore(Request $request)
    {
        $request->validate([
            'payments' => 'required|array|min:1',
            'payments.*.student_id' => 'required|exists:students,id',
            'payments.*.components' => 'required|array|min:1',
            'payments.*.components.*.student_fee_id' => 'required|exists:student_fees,id',
            'payments.*.components.*.amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'payment_date' => 'required|date',
        ]);

        try {
            DB::beginTransaction();

            $createdPayments = [];

            foreach ($request->payments as $paymentData) {
                $student = Student::findOrFail($paymentData['student_id']);
                $totalAmount = collect($paymentData['components'])->sum('amount');

                $payment = Payment::create([
                    'student_id' => $student->id,
                    'payment_type' => 'component',
                    'amount' => $totalAmount,
                    'payment_method' => $request->payment_method,
                    'payment_date' => $request->payment_date,
                    'status' => 'completed',
                    'created_by' => auth()->id(),
                ]);

                foreach ($paymentData['components'] as $component) {
                    $studentFee = StudentFee::findOrFail($component['student_fee_id']);
                    
                    $payment->componentItems()->create([
                        'student_fee_id' => $studentFee->id,
                        'amount_paid' => $component['amount'],
                    ]);

                    $studentFee->increment('paid_amount', $component['amount']);
                    
                    $remainingAmount = $studentFee->amount - $studentFee->concession_amount - $studentFee->paid_amount;
                    if ($remainingAmount <= 0) {
                        $studentFee->update(['status' => 'paid']);
                    } elseif ($studentFee->paid_amount > 0) {
                        $studentFee->update(['status' => 'partial']);
                    }
                }

                $createdPayments[] = $payment;
            }

            DB::commit();

            return redirect()->route('admin.component-payments.index')
                ->with('success', count($createdPayments) . ' payments recorded successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            
            return back()->withInput()
                ->with('error', 'Failed to record bulk payments: ' . $e->getMessage());
        }
    }

    
/**
     * Update the specified payment with full audit trail
     */
    public function update(Request $request, Payment $componentPayment)
    {
        if ($componentPayment->payment_type !== 'component') {
            return redirect()->back()->with('error', 'This payment cannot be updated.');
        }

        if (!$componentPayment->canBeEdited()) {
            return redirect()->back()->with('error', 'This payment is no longer editable.');
        }

        $request->validate([
            'components' => 'required|array|min:1',
            'components.*.fee_category_id' => 'required|exists:fee_categories,id',
            'components.*.amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'payment_date' => 'required|date',
            'transaction_id' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
            'edit_reason' => 'required|string|min:10|max:1000'
        ]);

        try {
            DB::beginTransaction();

            // Capture original state for audit trail
            $originalValues = [
                'amount' => $componentPayment->amount,
                'payment_method' => $componentPayment->payment_method,
                'payment_date' => $componentPayment->payment_date->format('Y-m-d'),
                'transaction_id' => $componentPayment->transaction_id,
                'notes' => $componentPayment->notes,
                'components' => $componentPayment->componentItems->map(function($item) {
                    return [
                        'fee_category_id' => $item->studentFee->fee_category_id,
                        'amount' => $item->amount_paid
                    ];
                })->toArray()
            ];

            // Calculate new total amount
            $newTotalAmount = collect($request->components)->sum('amount');

            // Update payment basic details
            $componentPayment->update([
                'amount' => $newTotalAmount,
                'payment_method' => $request->payment_method,
                'payment_date' => $request->payment_date,
                'transaction_id' => $request->transaction_id,
                'notes' => $request->notes,
                'updated_by' => auth()->id()
            ]);

            // Process component changes
            $this->updatePaymentComponents($componentPayment, $request->components);

            // Capture new state
            $newValues = [
                'amount' => $componentPayment->fresh()->amount,
                'payment_method' => $request->payment_method,
                'payment_date' => $request->payment_date,
                'transaction_id' => $request->transaction_id,
                'notes' => $request->notes,
                'components' => $request->components
            ];

            // Log the change
            PaymentEditLog::logPaymentChange(
                $componentPayment,
                'updated',
                $originalValues,
                $newValues,
                $request->edit_reason
            );

            DB::commit();

            return redirect()->route('admin.component-payments.show', $componentPayment)
                ->with('success', 'Component payment updated successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            
            return back()->withInput()
                ->with('error', 'Failed to update payment: ' . $e->getMessage());
        }
    }

 /**
     * Update payment components with proper fee tracking
     */
    private function updatePaymentComponents(Payment $payment, array $components)
    {
        // First, reverse the original payment effects
        foreach ($payment->componentItems as $item) {
            $studentFee = $item->studentFee;
            $studentFee->decrement('paid_amount', $item->amount_paid);
            
            // Update status based on new paid amount
            $this->updateStudentFeeStatus($studentFee);
        }

        // Delete old component items
        $payment->componentItems()->delete();

        // Create new component items
        foreach ($components as $component) {
            $studentFee = StudentFee::findOrFail($component['student_fee_id']);
            
            // Create new component item
            $payment->componentItems()->create([
                'student_fee_id' => $studentFee->id,
                'amount_paid' => $component['amount'],
                'notes' => null
            ]);

            // Update student fee paid amount
            $studentFee->increment('paid_amount', $component['amount']);
            
            // Update status
            $this->updateStudentFeeStatus($studentFee);
        }
    }
    
    
    /**
     * Update student fee status based on paid amount
     */
    private function updateStudentFeeStatus(StudentFee $studentFee)
    {
        $totalAmount = $studentFee->amount - $studentFee->concession_amount;
        $paidAmount = $studentFee->paid_amount;

        if ($paidAmount >= $totalAmount) {
            $studentFee->update(['status' => 'paid']);
        } elseif ($paidAmount > 0) {
            $studentFee->update(['status' => 'partial']);
        } else {
            $studentFee->update(['status' => 'unpaid']);
        }
    }

    /**
     * Show edit history for a payment
     */
    public function editHistory(Payment $componentPayment)
    {
        $editHistory = PaymentEditLog::forPayment($componentPayment->id)
            ->with(['user', 'payment.student'])
            ->latest()
            ->paginate(20);

        return view('admin.component-payments.edit-history', compact('componentPayment', 'editHistory'));
    }


    /**
     * Bulk create fees for students
     */
    public function bulkCreateFees(Request $request)
    {
        $request->validate([
            'batch_id' => 'required|exists:batches,id',
            'fee_structure_id' => 'required|exists:fee_structures,id',
            'academic_year' => 'required|string'
        ]);

        DB::beginTransaction();
        try {
            $batch = \App\Models\Batch::with(['students', 'feeStructure.feeCategories'])->find($request->batch_id);
            $feeStructure = \App\Models\FeeStructure::with('feeCategories')->find($request->fee_structure_id);
            
            $createdCount = 0;
            
            foreach ($batch->students as $student) {
                foreach ($feeStructure->feeCategories as $category) {
                    // Check if fee already exists
                    $existingFee = StudentFee::where([
                        'student_id' => $student->id,
                        'fee_category_id' => $category->id,
                        'academic_year' => $request->academic_year
                    ])->first();

                    if (!$existingFee) {
                        StudentFee::create([
                            'student_id' => $student->id,
                            'fee_structure_id' => $feeStructure->id,
                            'fee_category_id' => $category->id,
                            'academic_year' => $request->academic_year,
                            'amount' => $category->pivot->amount,
                            'due_date' => now()->addDays(30),
                            'status' => 'unpaid',
                            'installment_number' => 1,
                            'total_installments' => 1
                        ]);
                        $createdCount++;
                    }
                }
            }

            DB::commit();

            return redirect()->back()->with('success', 
                "Created {$createdCount} fee components for {$batch->students->count()} students");

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Bulk creation failed: ' . $e->getMessage());
        }
    }

    /**
     * Reverse payment
     */
    public function reversePayment(Payment $payment)
    {
        try {
            $result = $this->paymentService->reversePayment($payment);
            
            if ($result['success']) {
                return back()->with('success', 'Payment reversed successfully');
            } else {
                return back()->with('error', $result['error']);
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to reverse payment: ' . $e->getMessage());
        }
    }


/**
 * Enhanced concession application with activity logging
 */
public function applyConcession(Request $request, Student $student)
{
    $request->validate([
        'student_fee_id' => 'required|exists:student_fees,id',
        'concession_amount' => 'required|numeric|min:0.01',
        'reason' => 'nullable|string|max:500'
    ]);

    try {
        DB::beginTransaction();

        // First, check if student has any fee components at all
        $studentFeesCount = $student->studentFees()->count();
        if ($studentFeesCount == 0) {
            // Auto-generate missing fee components
            $this->generateMissingFeeComponents($student);
            
            // Refresh the student relationship
            $student->load('studentFees.feeCategory');
            
            return redirect()->back()->with('warning', 
                'Fee components were missing and have been auto-generated. Please try applying the concession again.'
            );
        }

        $studentFee = StudentFee::findOrFail($request->student_fee_id);
        
        // FIXED: Use type casting for comparison
        if ((int)$studentFee->student_id !== (int)$student->id) {
            throw new \Exception(
                "Invalid fee component for this student. Please refresh the page and try again."
            );
        }

        $remainingAmount = $studentFee->amount - $studentFee->paid_amount - $studentFee->concession_amount;
        
        if ($request->concession_amount > $remainingAmount) {
            throw new \Exception(
                "Concession amount ₹" . number_format($request->concession_amount, 2) . 
                " exceeds remaining balance of ₹" . number_format($remainingAmount, 2) . 
                " for {$studentFee->feeCategory->name}."
            );
        }

        // Store old values for activity logging
        $oldConcessionAmount = $studentFee->concession_amount;
        $oldStatus = $studentFee->status;

        // Apply concession
        $studentFee->increment('concession_amount', $request->concession_amount);
        
        // Update status if needed
        $newRemaining = $studentFee->amount - $studentFee->paid_amount - $studentFee->concession_amount;
        if ($newRemaining <= 0) {
            $studentFee->update(['status' => 'paid']);
        } elseif ($studentFee->concession_amount > 0 || $studentFee->paid_amount > 0) {
            $studentFee->update(['status' => 'partial']);
        }
        
        // ✨ Enhanced Activity Logging
        $this->logConcessionActivity($student, $studentFee, $request->concession_amount, $request->reason, $oldConcessionAmount, $oldStatus);

        DB::commit();

        return redirect()->back()->with('success', 
            'Concession of ₹' . number_format($request->concession_amount, 2) . 
            ' applied to ' . $studentFee->feeCategory->name . ' successfully!'
        );

    } catch (\Exception $e) {
        DB::rollback();
        
        // Log the error as an activity
        activity()
            ->causedBy(auth()->user())
            ->performedOn($student)
            ->withProperties([
                'error' => $e->getMessage(),
                'request_data' => $request->except(['_token']),
                'type' => 'concession_error'
            ])
            ->log('Concession application failed');
        
        return redirect()->back()->with('error', $e->getMessage());
    }
}



/**
 * Auto-generate missing fee components for a student
 */
private function generateMissingFeeComponents(Student $student)
{
    if (!$student->batch || !$student->batch->feeStructure) {
        $batchName = $student->batch ? $student->batch->name : 'Unknown';
        throw new \Exception(
            "Cannot generate fee components: Student's batch ({$batchName}) " .
            "does not have a fee structure assigned."
        );
    }

    $batch = $student->batch;
    $feeStructure = $batch->feeStructure;
    $academicYear = date('Y') . '-' . (date('Y') + 1);
    
    $generatedCount = 0;
    
    foreach ($feeStructure->feeCategories as $category) {
        // Check if component already exists
        $existingFee = StudentFee::where([
            'student_id' => $student->id,
            'fee_category_id' => $category->id,
            'academic_year' => $academicYear
        ])->first();

        if (!$existingFee) {
            StudentFee::create([
                'student_id' => $student->id,
                'fee_structure_id' => $feeStructure->id,
                'fee_category_id' => $category->id,
                'academic_year' => $academicYear,
                'amount' => $category->pivot->amount ? $category->pivot->amount : 0,
                'due_date' => now()->addDays(30),
                'status' => 'unpaid',
                'installment_number' => 1,
                'total_installments' => 1,
            ]);
            $generatedCount++;
        }
    }
    
    \Log::info("Auto-generated {$generatedCount} fee components for student {$student->name} (ID: {$student->id})");
    
    return $generatedCount;
}

/**
 * Enhanced concession activity logging
 */
private function logConcessionActivity(Student $student, StudentFee $studentFee, $amount, $reason, $oldConcessionAmount, $oldStatus)
{
    // Create Spatie Activity Log entry
    activity()
        ->causedBy(auth()->user())
        ->performedOn($student)
        ->withProperties([
            'fee_category' => $studentFee->feeCategory->name,
            'concession_amount' => $amount,
            'total_concession_before' => $oldConcessionAmount,
            'total_concession_after' => $studentFee->concession_amount,
            'reason' => $reason,
            'status_before' => $oldStatus,
            'status_after' => $studentFee->status,
            'remaining_amount' => $studentFee->amount - $studentFee->paid_amount - $studentFee->concession_amount,
            'type' => 'concession'
        ])
        ->log("Concession of ₹{$amount} applied to {$studentFee->feeCategory->name}");
    
    // Also log to Laravel logs for debugging
    \Log::info('Concession Applied Successfully', [
        'student_id' => $student->id,
        'student_name' => $student->name,
        'fee_category' => $studentFee->feeCategory->name,
        'amount' => $amount,
        'reason' => $reason,
        'applied_by' => auth()->user()->name,
        'timestamp' => now()
    ]);
}


public function applyGenderBasedConcession(Student $student)
{
    $genderConcessionPercentage = (float) setting('womens_discount_percentage', 0);
    
    if ($student->gender !== 'Female' || $genderConcessionPercentage <= 0) {
        return response()->json([
            'success' => false,
            'message' => 'Student not eligible for gender-based concession'
        ]);
    }

    DB::beginTransaction();
    try {
        $studentFees = $student->studentFees()
            ->whereIn('status', ['unpaid', 'partial'])
            ->whereRaw('amount - concession_amount - paid_amount > 0')
            ->get();

        $totalConcessionApplied = 0;
        $feesUpdated = 0;

        foreach ($studentFees as $fee) {
            $remainingAmount = $fee->amount - $fee->paid_amount - $fee->concession_amount;
            $concessionAmount = ($fee->amount * $genderConcessionPercentage) / 100;
            $finalConcessionAmount = min($concessionAmount, $remainingAmount);

            if ($finalConcessionAmount > 0) {
                // Create concession record
                $concession = StudentConcession::create([
                    'student_id' => $student->id,
                    'student_fee_id' => $fee->id,
                    'fee_category_id' => $fee->fee_category_id,
                    'concession_type' => 'discount',
                    'concession_amount' => $finalConcessionAmount,
                    'percentage' => $genderConcessionPercentage,
                    'reason' => "Automatic {$genderConcessionPercentage}% gender-based discount for female students",
                    'status' => 'applied',
                    'requested_by' => auth()->id(),
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                    'applied_by' => auth()->id(),
                    'applied_at' => now()
                ]);

                // Apply to student fee
                $fee->update([
                    'concession_amount' => $fee->concession_amount + $finalConcessionAmount,
                    'concession_reason' => $concession->reason,
                    'concession_approved_by' => auth()->id(),
                    'concession_approved_at' => now()
                ]);

                // Update status
                $newRemaining = $fee->amount - $fee->paid_amount - $fee->concession_amount;
                if ($newRemaining <= 0) {
                    $fee->status = 'paid';
                } elseif ($fee->paid_amount > 0 || $fee->concession_amount > 0) {
                    $fee->status = 'partial';
                }
                $fee->save();

                $totalConcessionApplied += $finalConcessionAmount;
                $feesUpdated++;
            }
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'total_amount' => $totalConcessionApplied,
            'fees_updated' => $feesUpdated,
            'message' => "Automatic gender-based concession of ₹" . number_format($totalConcessionApplied, 2) . " applied to {$feesUpdated} fee components"
        ]);

    } catch (\Exception $e) {
        DB::rollback();
        return response()->json([
            'success' => false,
            'message' => 'Failed to apply automatic concession: ' . $e->getMessage()
        ]);
    }
}

/**
 * Show payment receipt
 */
public function showReceipt(Student $student, Payment $payment)
{
    // Verify payment belongs to student
    if ((int)$payment->student_id !== (int)$student->id) {
        abort(404, 'Payment not found for this student');
    }

    // Load payment with all necessary relationships
    $payment->load([
        'student.batch.course',
        'createdBy',
        'componentItems.studentFee.feeCategory'
    ]);

    return view('admin.payments.receipt', compact('payment', 'student'));
}

/**
 * Show receipt by payment ID only (backward compatibility)
 */
public function showReceiptById(Payment $payment)
{
    // Load payment with all necessary relationships
    $payment->load([
        'student.batch.course',
        'createdBy',
        'componentItems.studentFee.feeCategory'
    ]);

    $student = $payment->student;
    
    return view('admin.payments.receipt', compact('payment', 'student'));
}

/**
 * Download payment receipt as PDF
 */
public function downloadReceipt(Student $student, Payment $payment)
{
    // Verify payment belongs to student
    if ((int)$payment->student_id !== (int)$student->id) {
        abort(404, 'Payment not found for this student');
    }

    // Load payment with all necessary relationships
    $payment->load([
        'student.batch.course',
        'createdBy',
        'componentItems.studentFee.feeCategory'
    ]);

    // Use the PDF-specific view
    $html = view('admin.payments.receipt-pdf', compact('payment', 'student'))->render();
    
    // Create PDF using DomPDF (if you have it installed)
    if (class_exists('\Barryvdh\DomPDF\Facade\Pdf')) {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
        $pdf->setPaper('A5', 'portrait');
        
        $filename = 'receipt-' . $payment->receipt_number . '.pdf';
        return $pdf->download($filename);
    }
    
    // Fallback: Return the HTML view directly for printing
    return response($html)
        ->header('Content-Type', 'text/html')
        ->header('Content-Disposition', 'inline; filename="receipt-' . $payment->receipt_number . '.html"');
}

/**
 * Show PDF receipt in browser (for preview)
 */
public function showPdfReceipt(Student $student, Payment $payment)
{
    // Verify payment belongs to student
    if ((int)$payment->student_id !== (int)$student->id) {
        abort(404, 'Payment not found for this student');
    }

    // Load payment with all necessary relationships
    $payment->load([
        'student.batch.course',
        'createdBy',
        'componentItems.studentFee.feeCategory'
    ]);

    // Return the PDF view directly for browser display
    return view('admin.payments.receipt-pdf', compact('payment', 'student'));
}

/**
 * Show public receipt (no authentication required)
 */
public function showPublicReceipt($receiptNumber)
{
    try {
        // Find payment by receipt number
        $payment = Payment::withoutAcademicYearFilter()
            ->where('receipt_number', $receiptNumber)
            ->with([
                'student' => function ($q) {
                    $q->withoutGlobalScope('academic_year')
                        ->with('batch.course');
                },
                'createdBy',
                'componentItems.studentFee.feeCategory'
            ])
            ->first();

        if (!$payment) {
            abort(404, 'Receipt not found');
        }

        // Check if it's a component payment
        if ($payment->payment_type !== 'component') {
            abort(404, 'Receipt not available for this payment type');
        }

        $student = $payment->student;

        // Return public receipt view (no auth required)
        return view('public.receipt', compact('payment', 'student'));
        
    } catch (\Exception $e) {
        \Log::error('Public receipt error: ' . $e->getMessage());
        abort(404, 'Receipt not found');
    }
}

/**
 * Download public receipt as PDF (no authentication required)
 */
public function downloadPublicReceipt($receiptNumber)
{
    try {
        // Find payment by receipt number
        $payment = Payment::withoutAcademicYearFilter()
            ->where('receipt_number', $receiptNumber)
            ->with([
                'student' => function ($q) {
                    $q->withoutGlobalScope('academic_year')
                        ->with('batch.course');
                },
                'createdBy',
                'componentItems.studentFee.feeCategory'
            ])
            ->first();

        if (!$payment) {
            abort(404, 'Receipt not found');
        }

        // Check if it's a component payment
        if ($payment->payment_type !== 'component') {
            abort(404, 'Receipt not available for this payment type');
        }

        $student = $payment->student;

        // Generate PDF
        $html = view('public.receipt-pdf', compact('payment', 'student'))->render();
        
        // Try to create PDF using DomPDF
        if (class_exists('\Barryvdh\DomPDF\Facade\Pdf')) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
            $pdf->setPaper('A5', 'portrait');
            
            $filename = 'receipt-' . $payment->receipt_number . '.pdf';
            return $pdf->download($filename);
        }
        
        // Fallback: Return HTML view for printing
        return response($html)
            ->header('Content-Type', 'text/html')
            ->header('Content-Disposition', 'inline; filename="receipt-' . $payment->receipt_number . '.html"');
            
    } catch (\Exception $e) {
        \Log::error('Public receipt PDF error: ' . $e->getMessage());
        abort(404, 'Receipt not found');
    }
}


/**
 * Alternative method using TCPDF (if you prefer TCPDF over DomPDF)
 */
public function downloadReceiptTCPDF(Student $student, Payment $payment)
{
    // Verify payment belongs to student
    if ((int)$payment->student_id !== (int)$student->id) {
        abort(404, 'Payment not found for this student');
    }

    // Load payment with all necessary relationships
    $payment->load([
        'student.batch.course',
        'createdBy',
        'componentItems.studentFee.feeCategory'
    ]);

    if (class_exists('\Elibyy\TCPDF\Facades\TCPDF')) {
        $pdf = new \TCPDF();
        
        // Set document information
        $pdf->SetCreator('College Management System');
        $pdf->SetAuthor(setting('college_name', 'Institution'));
        $pdf->SetTitle('Payment Receipt - ' . $payment->receipt_number);
        
        // Set margins and page format
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(TRUE, 10);
        $pdf->AddPage('P', 'A5'); // A5 Portrait
        
        // Get HTML content
        $html = view('admin.payments.receipt-pdf', compact('payment', 'student'))->render();
        
        // Write HTML to PDF
        $pdf->writeHTML($html, true, false, true, false, '');
        
        $filename = 'receipt-' . $payment->receipt_number . '.pdf';
        return $pdf->Output($filename, 'D'); // D = Download
    }
    
    // Fallback to DomPDF method
    return $this->downloadReceipt($student, $payment);
}

/**
 * Create fee components for a single student
 */
public function createFeeComponentsForStudent(Student $student, $academicYear = null, $feeStructureId = null)
{
    DB::beginTransaction();
    try {
        $academicYear = $academicYear ?? date('Y') . '-' . (date('Y') + 1);
        
        // Get fee structure from batch or use provided one
        $feeStructure = $feeStructureId ? 
            \App\Models\FeeStructure::with('feeCategories')->find($feeStructureId) : 
            $student->batch->feeStructure;
        
        if (!$feeStructure) {
            throw new \Exception('Fee Structure not found for student batch');
        }

        $createdCount = 0;
        
        foreach ($feeStructure->feeCategories as $category) {
            // Check if component already exists
            $existingFee = \App\Models\StudentFee::where([
                'student_id' => $student->id,
                'fee_category_id' => $category->id,
                'academic_year' => $academicYear
            ])->first();

            if (!$existingFee) {
                \App\Models\StudentFee::create([
                    'student_id' => $student->id,
                    'fee_structure_id' => $feeStructure->id,
                    'fee_category_id' => $category->id,
                    'academic_year' => $academicYear,
                    'amount' => $category->pivot->amount ?? 0,
                    'due_date' => now()->addDays(30),
                    'status' => 'unpaid',
                    'installment_number' => 1,
                    'total_installments' => $feeStructure->payment_terms ?? 1
                ]);
                $createdCount++;
            }
        }

        DB::commit();
        
        return [
            'success' => true,
            'created_count' => $createdCount,
            'message' => "Created {$createdCount} fee components for student {$student->name}"
        ];

    } catch (\Exception $e) {
        DB::rollback();
        throw $e;
    }
}


/**
     * 1. INDEX: Display list of all payments with Filters
     */
    public function index(Request $request)
    {
        // [UPDATED] Eager load component items and fee categories for the view
        $query = Payment::with(['student', 'createdBy', 'componentItems.studentFee.feeCategory']);

        // 1. Student Search
        if ($request->filled('student_search')) {
            $search = $request->student_search;
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('enrollment_number', 'like', "%{$search}%");
            });
        }

        // 2. Payment Method Filter
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // 3. Date Filters (Default to today if not provided to satisfy user request)
        if (!$request->has('date_from')) {
            $request->merge(['date_from' => Carbon::today()->format('Y-m-d')]);
        }
        if (!$request->has('date_to')) {
            $request->merge(['date_to' => Carbon::today()->format('Y-m-d')]);
        }

        $query->whereDate('payment_date', '>=', $request->date_from);
        $query->whereDate('payment_date', '<=', $request->date_to);

        // 4. [NEW] Fee Component Filter
        if ($request->filled('fee_category_id')) {
            $query->whereHas('componentItems.studentFee', function($q) use ($request) {
                $q->where('fee_category_id', $request->fee_category_id);
            });
        }

        $payments = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();
        
        // [NEW] Get Fee Categories for the dropdown
        $feeCategories = \App\Models\FeeCategory::orderBy('name')->get();

        return view('admin.payments.index', compact('payments', 'feeCategories'));
    }

    /**
     * Helper methods
     */
    private function getComponentStatus($remainingAmount)
    {
        if ($remainingAmount <= 0) {
            return 'paid';
        } elseif ($remainingAmount > 0) {
            return 'unpaid';
        }
        return 'partial';
    }

 /**
 * Get financial summary for student (robust version)
 */
private function getFinancialSummary(Student $student)
{
    try {
        // Check if student has fees relationship
        if (!$student->studentFees) {
            return [
                'total_amount' => 0,
                'paid_amount' => 0,
                'concession_amount' => 0,
                'remaining_amount' => 0,
                'payment_percentage' => 0
            ];
        }

        $fees = $student->studentFees;
        
        // Ensure all calculations use numeric values
        $totalAmount = (float) $fees->sum('amount');
        $paidAmount = (float) $fees->sum('paid_amount');
        $concessionAmount = (float) $fees->sum('concession_amount');
        
        $remainingAmount = $fees->sum(function($fee) {
            $amount = (float) ($fee->amount ?? 0);
            $paid = (float) ($fee->paid_amount ?? 0);
            $concession = (float) ($fee->concession_amount ?? 0);
            return max(0, $amount - $concession - $paid);
        });

        // Calculate payment percentage
        $paymentPercentage = 0;
        if ($totalAmount > 0) {
            $paymentPercentage = round(($paidAmount / $totalAmount) * 100, 2);
        }

        return [
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'concession_amount' => $concessionAmount,
            'remaining_amount' => $remainingAmount,
            'payment_percentage' => $paymentPercentage
        ];
    } catch (\Exception $e) {
        // Log error and return default values
        \Log::error('Error getting financial summary for student ' . $student->id . ': ' . $e->getMessage());
        return [
            'total_amount' => 0,
            'paid_amount' => 0,
            'concession_amount' => 0,
            'remaining_amount' => 0,
            'payment_percentage' => 0
        ];
    }
}

}
