<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
// ✅ CORRECT IMPORTS - Add these at the top of your controller
// ✅ CORRECT: Model namespace
use App\Models\Student;               // ✅ CORRECT: Model namespace
use App\Services\ComponentPaymentService;               // ✅ CORRECT: Model namespace
// ✅ CORRECT: Model namespace
use Illuminate\Http\Request; // ✅ CORRECT: Service namespace

// ❌ REMOVE any incorrect imports like:
// use App\Http\Controllers\Admin\ComponentPaymentItem; // ❌ WRONG NAMESPACE

class PaymentController extends Controller
{
    protected $componentPaymentService;

    public function __construct(ComponentPaymentService $componentPaymentService)
    {
        $this->componentPaymentService = $componentPaymentService;
    }

    /**
     * Store a new payment
     */
    public function store(Request $request)
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'student_id' => 'required|exists:students,id',
                'total_amount' => 'required|numeric|min:0.01',
                'payment_method' => 'required|string',
                'payment_date' => 'required|date',
                'transaction_id' => 'nullable|string',
                'notes' => 'nullable|string',
                'components' => 'required|array',
                'components.*.selected' => 'required|boolean',
                'components.*.amount' => 'required_if:components.*.selected,1|numeric|min:0.01',
            ]);

            $student = Student::findOrFail($validated['student_id']);

            // Filter selected components
            $selectedComponents = collect($validated['components'])
                ->filter(fn ($component) => $component['selected'] == '1')
                ->map(fn ($component, $studentFeeId) => [
                    'student_fee_id' => $studentFeeId,
                    'amount' => (float) $component['amount'],
                ])
                ->values()
                ->toArray();

            if (empty($selectedComponents)) {
                return back()->withErrors(['components' => 'Please select at least one component to pay.']);
            }

            // Process the payment using the service
            $result = $this->componentPaymentService->processPayment(
                $student,
                $selectedComponents,
                [
                    'payment_method' => $validated['payment_method'],
                    'payment_date' => $validated['payment_date'],
                    'transaction_id' => $validated['transaction_id'],
                    'notes' => $validated['notes'],
                ]
            );

            if ($result['success']) {
                return redirect()
                    ->route('admin.students.show', $student->id)
                    ->with('success', $result['message']);
            } else {
                return back()->withErrors(['payment' => $result['message']]);
            }

        } catch (\Exception $e) {
            \Log::error('Payment creation failed', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'student_id' => $request->student_id,
                'request_data' => $request->all(),
            ]);

            return back()->withErrors(['payment' => 'Payment processing failed. Please try again.']);
        }
    }

    /**
     * Show payment details
     */
    public function show($paymentId)
    {
        $payment = Payment::with([
            'student',
            'componentItems.studentFee.feeCategory',
        ])->findOrFail($paymentId);

        return view('admin.payments.show', compact('payment'));
    }

    public function receipt($paymentId)
    {
        $payment = Payment::with([
            'student.batch.course',
            'componentItems.studentFee.feeCategory',
        ])->findOrFail($paymentId);

        if (! $payment->isComponentPayment()) {
            abort(404, 'Receipt not available for this payment type.');
        }

        // ✅ Use component-compatible view
        return view('admin.receipts.component-show', compact('payment'));
    }

    /**
     * Manually trigger the payment webhook
     */
    public function resendWebhook($paymentId)
    {
        try {
            $payment = Payment::with('student')->findOrFail($paymentId);

            // Trigger the webhook event manually
            if (context()->has('webhook_enabled')) {
                // If there's a specific logic, but usually the trait handles it.
            }

            // If the model uses WebhookEnabled trait
            if (method_exists($payment, 'fireWebhookEvent')) {
                // 1. Send Student Webhook (Standard)
                $payment->fireWebhookEvent('created');

                // 2. Send Father Webhook (if father mobile exists)
                if ($payment->student && $payment->student->father_mobile) {
                    // Temporarily swap mobile number to father's mobile
                    // This creates a second webhook event where 'mobile' field contains father's number
                    $originalMobile = $payment->student->student_mobile;
                    $payment->student->student_mobile = $payment->student->father_mobile;

                    // Fire event again for father
                    $payment->fireWebhookEvent('created');

                    // Restore original mobile number
                    $payment->student->student_mobile = $originalMobile;
                }
            } else {
                // Fallback: fire the event manually if the trait isn't used but the event exists
                if (class_exists('\App\Events\EloquentWebhookEvent')) {
                    event(new \App\Events\EloquentWebhookEvent($payment, 'created', 'Payment'));
                }
            }

            // Additionally, to ensure the PaymentObserver logic (WhatsApp) runs if that was the intent:
            // Observers don't run on manual event firing unless we explicitly call them.
            // But "Payment Webhook" usually refers to the external integration.
            // I will strictly implement "Send Payment Webhook" as requested.

            return back()->with('success', 'Payment webhook triggered successfully.');

        } catch (\Exception $e) {
            \Log::error('Manual webhook trigger failed', [
                'error' => $e->getMessage(),
                'payment_id' => $paymentId,
            ]);

            return back()->with('error', 'Failed to trigger webhook: '.$e->getMessage());
        }
    }
}
