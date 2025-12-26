<?php

namespace App.Http.Controllers.Student;

use App.Http.Controllers.Controller;
use App.Models.Student;
use App.Models.Payment;
use Illuminate.Http.Request;
use App.Services.ComponentPaymentService; // ✅ IMPORTED: The new service for component-based finances.

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
        
        if (!$student) {
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
    
    /**
     * ✅ UPDATED: initiate()
     * This method is redesigned to handle payments for multiple fee components at once.
     * The request should now send an array of components to be paid.
     */
    public function initiate(Request $request)
    {
        // The front-end should send an array of `components` to pay.
        // E.g., components[0][student_fee_id] = 123, components[0][amount] = 5000
        $request->validate([
            'components' => 'required|array|min:1',
            'components.*.student_fee_id' => 'required|exists:student_fees,id',
            'components.*.amount' => 'required|numeric|min:1'
        ]);
        
        $user = auth()->user();
        $student = $user->student;
        
        if (!$student) {
            return response()->json(['error' => 'Student profile not found'], 404);
        }

        try {
            // The ComponentPaymentService will handle the complex validation logic.
            // For this controller, we do a basic check.
            $totalPaymentAmount = 0;
            foreach ($request->components as $component) {
                $studentFee = $student->studentFees()->findOrFail($component['student_fee_id']);
                
                if ($component['amount'] > $studentFee->getRemainingAmount()) {
                    return response()->json([
                        'error' => 'Amount for ' . $studentFee->feeCategory->name . ' exceeds the due amount.'
                    ], 400);
                }
                $totalPaymentAmount += $component['amount'];
            }

            // Here you would integrate with a payment gateway.
            // The gateway would be initiated with the `totalPaymentAmount`.
            // After successful payment, the `processPayment` method of the
            // ComponentPaymentService would be called in the webhook/callback.

            return response()->json([
                'success' => true,
                'message' => 'Payment initiation successful. Redirecting to payment gateway...',
                'payment_url' => '#', // The actual payment gateway URL would go here.
                'total_amount' => $totalPaymentAmount,
                'components' => $request->components
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }
}