<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentFee;
use App\Models\Payment;
use App\Models\Invoice;
use App\Models\FeeCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ComponentPaymentController extends Controller
{
    /**
     * Show component-wise payment dashboard for a student
     */
    public function studentComponentDashboard(Student $student)
    {
        try {
            // Check if studentFees relationship exists, if not use alternative approach
            if (method_exists($student, 'studentFees')) {
                $student->load([
                    'studentFees.feeCategory',
                    'studentFees.invoice',
                    'invoices.payments'
                ]);
                
                // Group student fees by category
                $feeComponents = $student->studentFees->groupBy('fee_category_id')->map(function ($fees, $categoryId) {
                    $category = $fees->first()->feeCategory;
                    $totalAmount = $fees->sum('amount');
                    $paidAmount = $fees->where('status', 'paid')->sum('amount');
                    $pendingAmount = $totalAmount - $paidAmount;
                    
                    return [
                        'category' => $category,
                        'total_amount' => $totalAmount,
                        'paid_amount' => $paidAmount,
                        'pending_amount' => $pendingAmount,
                        'payment_percentage' => $totalAmount > 0 ? round(($paidAmount / $totalAmount) * 100, 2) : 0,
                        'fees' => $fees,
                        'status' => $this->getComponentStatus($fees)
                    ];
                });
            } else {
                // Alternative approach: Get student fees directly using StudentFee model
                $studentFees = StudentFee::where('student_id', $student->id)
                    ->with(['feeCategory', 'invoice'])
                    ->get();
                
                $feeComponents = $studentFees->groupBy('fee_category_id')->map(function ($fees, $categoryId) {
                    $category = $fees->first()->feeCategory;
                    $totalAmount = $fees->sum('amount');
                    $paidAmount = $fees->where('status', 'paid')->sum('amount');
                    $pendingAmount = $totalAmount - $paidAmount;
                    
                    return [
                        'category' => $category,
                        'total_amount' => $totalAmount,
                        'paid_amount' => $paidAmount,
                        'pending_amount' => $pendingAmount,
                        'payment_percentage' => $totalAmount > 0 ? round(($paidAmount / $totalAmount) * 100, 2) : 0,
                        'fees' => $fees,
                        'status' => $this->getComponentStatus($fees)
                    ];
                });
            }

            // If no fee components found, create empty collection
            if ($feeComponents->isEmpty()) {
                $feeComponents = collect();
            }

            // Recent payments grouped by component
            $recentPayments = $this->getRecentComponentPayments($student);

            return view('admin.payments.component-dashboard', compact('student', 'feeComponents', 'recentPayments'));

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error loading component dashboard: ' . $e->getMessage());
        }
    }

    /**
     * Record partial payment for specific components
     */
    public function recordComponentPayment(Request $request, Student $student)
    {
        $request->validate([
            'components' => 'required|array|min:1',
            'components.*.fee_category_id' => 'required|exists:fee_categories,id',
            'components.*.amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'payment_date' => 'required|date',
            'transaction_id' => 'nullable|string',
            'notes' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            $totalPaymentAmount = collect($request->components)->sum('amount');
            
            // Create main payment record
            $payment = Payment::create([
                'student_id' => $student->id,
                'amount' => $totalPaymentAmount,
                'payment_method' => $request->payment_method,
                'payment_date' => $request->payment_date,
                'transaction_id' => $request->transaction_id,
                'notes' => $request->notes,
                'receipt_number' => $this->generateReceiptNumber(),
            ]);

            foreach ($request->components as $component) {
                $this->processComponentPayment($student, $component, $payment);
            }

            DB::commit();

            return redirect()->back()->with('success', 
                "Payment of ₹{$totalPaymentAmount} recorded successfully for " . count($request->components) . " components."
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Payment recording failed: ' . $e->getMessage());
        }
    }

    /**
     * Get student components (AJAX)
     */
    public function getStudentComponents(Student $student)
    {
        $components = $student->studentFees()->with('feeCategory')->get()->groupBy('fee_category_id');
        
        return response()->json($components->map(function ($fees, $categoryId) {
            $category = $fees->first()->feeCategory;
            $totalAmount = $fees->sum('amount');
            $paidAmount = $fees->where('status', 'paid')->sum('amount');
            
            return [
                'category_id' => $categoryId,
                'category_name' => $category->name,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'pending_amount' => $totalAmount - $paidAmount,
                'fees_count' => $fees->count(),
                'paid_count' => $fees->where('status', 'paid')->count()
            ];
        }));
    }

    /**
     * Component payment report
     */
    public function componentPaymentReport(Request $request)
    {
        $query = StudentFee::with(['student', 'feeCategory', 'invoice'])
            ->when($request->fee_category_id, function ($q) use ($request) {
                return $q->where('fee_category_id', $request->fee_category_id);
            })
            ->when($request->status, function ($q) use ($request) {
                return $q->where('status', $request->status);
            });

        $componentData = $query->get()->groupBy('fee_category_id')->map(function ($fees, $categoryId) {
            $category = $fees->first()->feeCategory;
            return [
                'category' => $category,
                'total_students' => $fees->unique('student_id')->count(),
                'total_amount' => $fees->sum('amount'),
                'paid_amount' => $fees->where('status', 'paid')->sum('amount'),
                'unpaid_amount' => $fees->where('status', 'unpaid')->sum('amount'),
                'collection_rate' => $fees->sum('amount') > 0 ? 
                    round(($fees->where('status', 'paid')->sum('amount') / $fees->sum('amount')) * 100, 2) : 0
            ];
        });

        $feeCategories = FeeCategory::all();
        
        return view('admin.reports.component-payment-report', compact('componentData', 'feeCategories'));
    }

    /**
     * Process payment for a specific fee component
     */
    private function processComponentPayment(Student $student, array $componentData, Payment $payment)
    {
        $feeCategory = FeeCategory::find($componentData['fee_category_id']);
        $paymentAmount = $componentData['amount'];

        // Get unpaid student fees for this category
        $unpaidFees = StudentFee::where('student_id', $student->id)
            ->where('fee_category_id', $feeCategory->id)
            ->where('status', 'unpaid')
            ->orderBy('due_date', 'asc')
            ->get();

        $remainingPayment = $paymentAmount;

        foreach ($unpaidFees as $fee) {
            if ($remainingPayment <= 0) break;

            if ($remainingPayment >= $fee->amount) {
                // Full payment for this fee
                $fee->markAsPaid($payment->payment_method, $payment->transaction_id);
                $remainingPayment -= $fee->amount;
            } else {
                // Partial payment - create new fee record for remaining amount
                $remainingAmount = $fee->amount - $remainingPayment;
                
                // Update current fee to paid amount
                $fee->update([
                    'amount' => $remainingPayment,
                    'status' => 'paid',
                    'paid_date' => $payment->payment_date,
                    'payment_method' => $payment->payment_method,
                    'transaction_id' => $payment->transaction_id
                ]);

                // Create new fee record for remaining amount
                StudentFee::create([
                    'student_id' => $student->id,
                    'fee_structure_id' => $fee->fee_structure_id,
                    'fee_category_id' => $fee->fee_category_id,
                    'invoice_id' => $fee->invoice_id,
                    'amount' => $remainingAmount,
                    'due_date' => $fee->due_date,
                    'status' => 'unpaid'
                ]);

                $remainingPayment = 0;
            }
        }

        // Update related invoice if exists
        if ($unpaidFees->first() && $unpaidFees->first()->invoice) {
            $this->updateInvoiceFromComponentPayments($unpaidFees->first()->invoice);
        }
    }

    /**
     * Update invoice amounts based on component payments
     */
    private function updateInvoiceFromComponentPayments(Invoice $invoice)
    {
        $studentFees = StudentFee::where('invoice_id', $invoice->id)->get();
        
        $totalAmount = $studentFees->sum('amount');
        $paidAmount = $studentFees->where('status', 'paid')->sum('amount');
        
        $invoice->update([
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'due_amount' => $totalAmount - $paidAmount,
            'status' => $this->calculateInvoiceStatus($totalAmount, $paidAmount)
        ]);
    }

    /**
     * Get component payment status
     */
    private function getComponentStatus($fees)
    {
        $totalFees = $fees->count();
        $paidFees = $fees->where('status', 'paid')->count();
        $partialFees = $fees->where('status', 'partial')->count();

        if ($paidFees === $totalFees) {
            return 'fully_paid';
        } elseif ($paidFees > 0 || $partialFees > 0) {
            return 'partially_paid';
        } else {
            return 'unpaid';
        }
    }

    /**
     * Get recent component-wise payments
     */
    private function getRecentComponentPayments(Student $student, int $limit = 10)
    {
        return StudentFee::where('student_id', $student->id)
            ->where('status', 'paid')
            ->with(['feeCategory', 'invoice'])
            ->orderBy('paid_date', 'desc')
            ->limit($limit)
            ->get()
            ->groupBy('fee_category_id')
            ->map(function ($payments, $categoryId) {
                $category = $payments->first()->feeCategory;
                return [
                    'category' => $category,
                    'total_paid' => $payments->sum('amount'),
                    'payment_count' => $payments->count(),
                    'last_payment' => $payments->first()->paid_date,
                    'payments' => $payments
                ];
            });
    }

    /**
     * Calculate invoice status
     */
    private function calculateInvoiceStatus(float $totalAmount, float $paidAmount): string
    {
        if ($paidAmount >= $totalAmount) {
            return 'paid';
        } elseif ($paidAmount > 0) {
            return 'partial';
        } else {
            return 'unpaid';
        }
    }

    /**
     * Generate unique receipt number
     */
    private function generateReceiptNumber(): string
    {
        $prefix = 'RCP';
        $latest = Payment::where('receipt_number', 'LIKE', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = 1;
        if ($latest) {
            $lastNumber = (int) substr($latest->receipt_number, strlen($prefix));
            $nextNumber = $lastNumber + 1;
        }

        return $prefix . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }
}