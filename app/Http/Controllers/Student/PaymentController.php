<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\ComponentPaymentService;

// ✅ IMPORTED: The new service for component-based finances.

class PaymentController extends Controller
{
    /**
     * @var ComponentPaymentService
     */
    protected $componentPaymentService;

    /**
     * ✅ UPDATED: Constructor now injects the ComponentPaymentService.
     */
    public function __construct(ComponentPaymentService $componentPaymentService)
    {
        $this->componentPaymentService = $componentPaymentService;
    }

    /**
     * ✅ UPDATED: index()
     * This method is now powered by the component-based system. It fetches a detailed
     * breakdown of all outstanding fee components instead of monolithic invoices.
     */
    public function index()
    {
        $user = auth()->user();
        $student = $user->student;

        if (! $student) {
            abort(404, 'Student profile not found.');
        }

        // Get all financial data using the new service layer.
        $financialSummary = $this->componentPaymentService->getStudentFinancialSummary($student);

        // Fetch the specific fee components that are unpaid or partially paid.
        $payableComponents = $student->studentFees()
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->whereRaw('amount - concession_amount - paid_amount > 0')
            ->with('feeCategory')
            ->orderBy('due_date', 'asc')
            ->get();

        // Fetch recent component-based payments.
        $recentPayments = Payment::where('student_id', $student->id)
            ->where('payment_type', 'component')
            ->with('componentItems.studentFee.feeCategory')
            ->latest()
            ->limit(10)
            ->get();

        $paymentData = [
            'student' => $student,
            'payable_components' => $payableComponents, // Replaces 'unpaid_invoices'
            'recent_payments' => $recentPayments,
            'total_due' => $financialSummary['due_amount'],
            'total_paid' => $financialSummary['paid_amount'],
            'total_concession' => $financialSummary['concession_amount'],
            'total_fees' => $financialSummary['total_amount'],
        ];

        return view('student.fee_payment', $paymentData);
    }
}
