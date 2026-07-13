<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ComponentPaymentItem;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
use App\Models\Payment;
use App\Models\PaymentEditLog;
use App\Models\Student;
use App\Models\StudentConcession;
use App\Models\StudentFee;
use App\Services\AcademicYearService;
use App\Services\ComponentPaymentService;
use App\Services\UnifiedIdentifierService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
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
            'studentFees.feeCategory',
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
                'componentItems.studentFee.feeCategory:id,name',
            ])
            ->orderBy('created_at', 'desc')
            ->limit(10) // Limit for dashboard performance
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'type' => 'payment_created',
                    'description' => 'Payment of ₹'.number_format($payment->amount, 0).' recorded',
                    'amount' => $payment->amount,
                    'payment_method' => $payment->payment_method,
                    'receipt_number' => $payment->receipt_number,
                    'user' => $payment->createdBy->name ?? 'System',
                    'timestamp' => $payment->created_at,
                    'details' => $payment->componentItems->map(function ($item) {
                        return $item->studentFee->feeCategory->name.': ₹'.number_format($item->amount_paid, 0);
                    })->implode(', '),
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
            'pending_components' => $studentFees->filter(function ($fee) {
                return ($fee->amount - $fee->paid_amount - $fee->concession_amount) > 0;
            })->count(),
        ];

        // Get pending fee components
        $pendingFees = $student->studentFees()
            ->where(function ($query) {
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
            'overdue_analysis' => $this->getOverdueAnalysis($student->id),
        ];

        return response()->json($analytics);
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
            'total_amount' => $overdueFees->sum(function ($fee) {
                return $fee->amount - $fee->paid_amount - $fee->concession_amount;
            }),
            'categories' => $overdueFees->groupBy('feeCategory.name')->map(function ($fees, $category) {
                return [
                    'category' => $category,
                    'count' => $fees->count(),
                    'amount' => $fees->sum(function ($fee) {
                        return $fee->amount - $fee->paid_amount - $fee->concession_amount;
                    }),
                ];
            })->values(),
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
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $student = Student::findOrFail($validated['student_id']);
            $studentFee = StudentFee::findOrFail($validated['student_fee_id']);

            // Validate the student fee belongs to the student
            if ((int) $studentFee->student_id !== (int) $student->id) {
                throw new \Exception('Invalid fee component for this student.');
            }

            // Calculate remaining amount
            $remainingAmount = $studentFee->amount - $studentFee->paid_amount - $studentFee->concession_amount;

            if ($validated['amount'] > $remainingAmount) {
                throw new \Exception('Payment amount exceeds remaining balance.');
            }

            // Generate receipt number - BYPASS GLOBAL SCOPES
            $receiptNumber = 'RCP-'.date('Ymd').'-'.str_pad(Payment::withoutGlobalScope('academic_year')->whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);

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
                // [FIX] Use withoutGlobalScope to ensure we get the batch info even if it's from a different year
                'academic_year' => $student->batch()->withoutGlobalScope('academic_year')->first()?->academicYear?->name ?? null,
                'academic_year_id' => $student->batch()->withoutGlobalScope('academic_year')->first()?->academic_year_id ?? null,
                'created_by' => auth()->id(),
            ]);

            // Create component payment item
            ComponentPaymentItem::create([
                'payment_id' => $payment->id,
                'student_fee_id' => $studentFee->id,
                'amount_paid' => $validated['amount'],
            ]);

            // Update student fee paid amount
            $studentFee->increment('paid_amount', $validated['amount']);
            $studentFee->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment recorded successfully!',
                'receipt_number' => $receiptNumber,
                'payment_id' => $payment->id,
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
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
            'status' => $studentFee->status,
        ]);
    }

    /**
     * Generate a unique receipt number
     */
    private function generateReceiptNumber()
    {
        return app(UnifiedIdentifierService::class)->generateReceiptNumber();
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

            $feeCategories = FeeCategory::orderBy('name')->get();

            return view('admin.payments.component-payment-form', compact(
                'student', 'unpaidFees', 'feeCategories'
            ));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Unable to load payment form: '.$e->getMessage());
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
            'notes' => 'nullable|string|max:500',
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
                // [FIX] Use withoutGlobalScope to ensure we get the batch info even if it's from a different year
                'academic_year' => $student->batch()->withoutGlobalScope('academic_year')->first()?->academicYear?->name ?? null,
                'academic_year_id' => $student->batch()->withoutGlobalScope('academic_year')->first()?->academic_year_id ?? null,
                'created_by' => auth()->id(), // Explicitly set created_by
                'updated_by' => auth()->id(),  // Set updated_by as well
            ]);

            // Process components
            foreach ($validated['components'] as $component) {
                $studentFee = StudentFee::where('student_id', $student->id)
                    ->where('fee_category_id', $component['fee_category_id'])
                    ->first();

                if (! $studentFee) {
                    throw new \Exception('Fee category not found for student: '.$component['fee_category_id']);
                }

                // Create component item
                $componentItem = $payment->componentItems()->create([
                    'student_fee_id' => $studentFee->id,
                    'amount_paid' => $component['amount'],
                ]);

                // Update student fee
                $studentFee->increment('paid_amount', $component['amount']);
                $this->updateStudentFeeStatus($studentFee);

                // Log the component creation
                Log::info('Component item created', [
                    'payment_id' => $payment->id,
                    'student_fee_id' => $studentFee->id,
                    'amount_paid' => $component['amount'],
                    'created_by' => auth()->id(),
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
                        'payment_date' => Carbon::parse($payment->payment_date)->format('Y-m-d'),
                        'components' => $validated['components'],
                        'student_id' => $payment->student_id,
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
                'receipt_number' => $payment->receipt_number,
            ]);

            return redirect()->route('admin.students.show', $student)
                ->with('success', 'Payment recorded successfully! Receipt: '.$payment->receipt_number);

        } catch (\Exception $e) {
            DB::rollback();

            // Log the error
            Log::error('Payment recording failed', [
                'student_id' => $student->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
            ]);

            return back()->withInput()
                ->with('error', 'Failed to record payment: '.$e->getMessage());
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
            'creator',
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
        if (! $componentPayment->canBeEdited()) {
            return redirect()->back()->with('error', 'This payment is no longer editable due to age or policy restrictions.');
        }

        $componentPayment->load([
            'student.batch.course',
            'componentItems.studentFee.feeCategory',
            'student.studentFees.feeCategory',
            'creator',
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
            'user_id' => auth()->id(),
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
                'components.*.amount' => 'required_if:components.*.selected,1|numeric|min:0.01',
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
                'components.*.amount.numeric' => 'Component amount must be a valid number.',
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
                        'amount' => (float) $component['amount'],
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
                        'student_id' => $studentFee->student_id,
                    ]);

                    // Check if student fee belongs to the selected student (FIXED - use loose comparison)
                    if ((int) $studentFee->student_id !== (int) $student->id) {
                        \Log::error('StudentFee does not belong to student', [
                            'student_fee_student_id' => $studentFee->student_id,
                            'student_fee_student_id_type' => gettype($studentFee->student_id),
                            'selected_student_id' => $student->id,
                            'selected_student_id_type' => gettype($student->id),
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
                        'payment_amount' => $component['amount'],
                    ]);

                    // Check if amount doesn't exceed remaining amount
                    if ($component['amount'] > $remainingAmount) {
                        \Log::error('Payment amount exceeds remaining amount', [
                            'payment_amount' => $component['amount'],
                            'remaining_amount' => $remainingAmount,
                        ]);

                        return back()->withErrors([
                            'components' => "Amount for {$studentFee->feeCategory->name} exceeds the remaining amount of ₹".number_format($remainingAmount, 2),
                        ])->withInput();
                    }

                    $componentsTotal += $component['amount'];
                    \Log::info('Component validation passed', ['component_total_so_far' => $componentsTotal]);

                } catch (\Exception $e) {
                    \Log::error('Error processing component', [
                        'student_fee_id' => $component['student_fee_id'],
                        'error' => $e->getMessage(),
                        'line' => $e->getLine(),
                        'file' => $e->getFile(),
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
                    'difference' => abs($validated['total_amount'] - $componentsTotal),
                ]);

                return back()->withErrors([
                    'total_amount' => 'Payment amount must equal the sum of component amounts.',
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
                        'new_paid_amount' => $newPaidAmount,
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
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }

        } catch (ValidationException $e) {
            \Log::warning('Validation failed', ['errors' => $e->errors()]);

            return back()->withErrors($e->errors())->withInput();

        } catch (\Exception $e) {
            \Log::error('Payment creation failed', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'student_id' => $request->student_id ?? 'not_set',
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors([
                'payment' => 'Payment processing failed. Please try again. Error: '.$e->getMessage(),
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
                $currentAcademicYear = app(AcademicYearService::class)->getCurrentAcademicYear();
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
                $selectedYear = AcademicYear::find(session('selected_academic_year_id'));
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
            $academicYear = $currentYear.'-'.($currentYear + 1);
        } elseif ($currentMonth >= 4) { // April to June = current year to next year
            $academicYear = $currentYear.'-'.($currentYear + 1);
        } else { // January to March = previous year to current year
            $academicYear = ($currentYear - 1).'-'.$currentYear;
        }

        \Log::info('Academic year calculated from date', [
            'academic_year' => $academicYear,
            'current_month' => $currentMonth,
            'current_year' => $currentYear,
        ]);

        return $academicYear;
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
                ->with('error', 'Failed to delete payment: '.$e->getMessage());
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
                ->with('success', count($createdPayments).' payments recorded successfully.');

        } catch (\Exception $e) {
            DB::rollback();

            return back()->withInput()
                ->with('error', 'Failed to record bulk payments: '.$e->getMessage());
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

        if (! $componentPayment->canBeEdited()) {
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
            'edit_reason' => 'required|string|min:10|max:1000',
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
                'components' => $componentPayment->componentItems->map(function ($item) {
                    return [
                        'fee_category_id' => $item->studentFee->fee_category_id,
                        'amount' => $item->amount_paid,
                    ];
                })->toArray(),
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
                'updated_by' => auth()->id(),
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
                'components' => $request->components,
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
                ->with('error', 'Failed to update payment: '.$e->getMessage());
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
            return back()->with('error', 'Failed to reverse payment: '.$e->getMessage());
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
            'reason' => 'nullable|string|max:500',
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
            if ((int) $studentFee->student_id !== (int) $student->id) {
                throw new \Exception(
                    'Invalid fee component for this student. Please refresh the page and try again.'
                );
            }

            $remainingAmount = $studentFee->amount - $studentFee->paid_amount - $studentFee->concession_amount;

            if ($request->concession_amount > $remainingAmount) {
                throw new \Exception(
                    'Concession amount ₹'.number_format($request->concession_amount, 2).
                    ' exceeds remaining balance of ₹'.number_format($remainingAmount, 2).
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
                'Concession of ₹'.number_format($request->concession_amount, 2).
                ' applied to '.$studentFee->feeCategory->name.' successfully!'
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
                    'type' => 'concession_error',
                ])
                ->log('Concession application failed');

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function applyGenderBasedConcession(Student $student)
    {
        $genderConcessionPercentage = (float) setting('womens_discount_percentage', 0);

        if ($student->gender !== 'Female' || $genderConcessionPercentage <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Student not eligible for gender-based concession',
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
                        'applied_at' => now(),
                    ]);

                    // Apply to student fee
                    $fee->update([
                        'concession_amount' => $fee->concession_amount + $finalConcessionAmount,
                        'concession_reason' => $concession->reason,
                        'concession_approved_by' => auth()->id(),
                        'concession_approved_at' => now(),
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
                'message' => 'Automatic gender-based concession of ₹'.number_format($totalConcessionApplied, 2)." applied to {$feesUpdated} fee components",
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Failed to apply automatic concession: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Show payment receipt
     */
    public function showReceipt(Student $student, Payment $payment)
    {
        // Verify payment belongs to student
        if ((int) $payment->student_id !== (int) $student->id) {
            abort(404, 'Payment not found for this student');
        }

        // Load payment with all necessary relationships
        $payment->load([
            'student.batch.course',
            'createdBy',
            'componentItems.studentFee.feeCategory',
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
            'componentItems.studentFee.feeCategory',
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
        if ((int) $payment->student_id !== (int) $student->id) {
            abort(404, 'Payment not found for this student');
        }

        // Load payment with all necessary relationships
        $payment->load([
            'student.batch.course',
            'createdBy',
            'componentItems.studentFee.feeCategory',
        ]);

        // Use the PDF-specific view
        $html = view('admin.payments.receipt-pdf', compact('payment', 'student'))->render();

        // Create PDF using DomPDF (if you have it installed)
        if (class_exists('\Barryvdh\DomPDF\Facade\Pdf')) {
            $pdf = Pdf::loadHTML($html);
            $pdf->setPaper('A5', 'portrait');

            $filename = 'receipt-'.$payment->receipt_number.'.pdf';

            return $pdf->download($filename);
        }

        // Fallback: Return the HTML view directly for printing
        return response($html)
            ->header('Content-Type', 'text/html')
            ->header('Content-Disposition', 'inline; filename="receipt-'.$payment->receipt_number.'.html"');
    }

    /**
     * Show PDF receipt in browser (for preview)
     */
    public function showPdfReceipt(Student $student, Payment $payment)
    {
        // Verify payment belongs to student
        if ((int) $payment->student_id !== (int) $student->id) {
            abort(404, 'Payment not found for this student');
        }

        // Load payment with all necessary relationships
        $payment->load([
            'student.batch.course',
            'createdBy',
            'componentItems.studentFee.feeCategory',
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
                    'componentItems.studentFee.feeCategory',
                ])
                ->first();

            if (! $payment) {
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
            \Log::error('Public receipt error: '.$e->getMessage());
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
                    'componentItems.studentFee.feeCategory',
                ])
                ->first();

            if (! $payment) {
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
                $pdf = Pdf::loadHTML($html);
                $pdf->setPaper('A5', 'portrait');

                $filename = 'receipt-'.$payment->receipt_number.'.pdf';

                return $pdf->download($filename);
            }

            // Fallback: Return HTML view for printing
            return response($html)
                ->header('Content-Type', 'text/html')
                ->header('Content-Disposition', 'inline; filename="receipt-'.$payment->receipt_number.'.html"');

        } catch (\Exception $e) {
            \Log::error('Public receipt PDF error: '.$e->getMessage());
            abort(404, 'Receipt not found');
        }
    }

    /**
     * Create fee components for a single student
     */
    public function createFeeComponentsForStudent(Student $student, $academicYear = null, $feeStructureId = null)
    {
        DB::beginTransaction();
        try {
            $academicYear = $academicYear ?? date('Y').'-'.(date('Y') + 1);

            // Get fee structure from batch or use provided one
            $feeStructure = $feeStructureId ?
                FeeStructure::with('feeCategories')->find($feeStructureId) :
                $student->batch->feeStructure;

            if (! $feeStructure) {
                throw new \Exception('Fee Structure not found for student batch');
            }

            $createdCount = 0;

            foreach ($feeStructure->feeCategories as $category) {
                // Check if component already exists
                $existingFee = StudentFee::where([
                    'student_id' => $student->id,
                    'fee_category_id' => $category->id,
                    'academic_year' => $academicYear,
                ])->first();

                if (! $existingFee) {
                    StudentFee::create([
                        'student_id' => $student->id,
                        'fee_structure_id' => $feeStructure->id,
                        'fee_category_id' => $category->id,
                        'academic_year' => $academicYear,
                        'amount' => $category->pivot->amount ?? 0,
                        'due_date' => now()->addDays(30),
                        'status' => 'unpaid',
                        'installment_number' => 1,
                        'total_installments' => $feeStructure->payment_terms ?? 1,
                    ]);
                    $createdCount++;
                }
            }

            DB::commit();

            return [
                'success' => true,
                'created_count' => $createdCount,
                'message' => "Created {$createdCount} fee components for student {$student->name}",
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
        $query = Payment::withoutGlobalScope('academic_year')->with(['student', 'createdBy', 'componentItems.studentFee.feeCategory']);

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
        if (! $request->has('date_from')) {
            $request->merge(['date_from' => Carbon::today()->format('Y-m-d')]);
        }
        if (! $request->has('date_to')) {
            $request->merge(['date_to' => Carbon::today()->format('Y-m-d')]);
        }

        $query->whereDate('payment_date', '>=', $request->date_from);
        $query->whereDate('payment_date', '<=', $request->date_to);

        // 4. [NEW] Fee Component Filter
        if ($request->filled('fee_category_id')) {
            $query->whereHas('componentItems.studentFee', function ($q) use ($request) {
                $q->where('fee_category_id', $request->fee_category_id);
            });
        }

        $payments = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        // [NEW] Get Fee Categories for the dropdown
        $feeCategories = FeeCategory::orderBy('name')->get();

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
            if (! $student->studentFees) {
                return [
                    'total_amount' => 0,
                    'paid_amount' => 0,
                    'concession_amount' => 0,
                    'remaining_amount' => 0,
                    'payment_percentage' => 0,
                ];
            }

            $fees = $student->studentFees;

            // Ensure all calculations use numeric values
            $totalAmount = (float) $fees->sum('amount');
            $paidAmount = (float) $fees->sum('paid_amount');
            $concessionAmount = (float) $fees->sum('concession_amount');

            $remainingAmount = $fees->sum(function ($fee) {
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
                'payment_percentage' => $paymentPercentage,
            ];
        } catch (\Exception $e) {
            // Log error and return default values
            \Log::error('Error getting financial summary for student '.$student->id.': '.$e->getMessage());

            return [
                'total_amount' => 0,
                'paid_amount' => 0,
                'concession_amount' => 0,
                'remaining_amount' => 0,
                'payment_percentage' => 0,
            ];
        }
    }
}
