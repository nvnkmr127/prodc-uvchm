<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentEditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PaymentEditController extends Controller
{
    /**
     * Show payment edit form
     */
    public function edit(Payment $payment)
    {
        $payment->load(['invoice.student.batch.course']);
        
        // Get edit history for this payment
        $editHistory = PaymentEditLog::where('payment_id', $payment->id)
                                   ->with('user')
                                   ->latest()
                                   ->get();
        
        return view('admin.payments.edit', compact('payment', 'editHistory'));
    }

    /**
     * Update payment with audit trail
     */
    public function update(Request $request, Payment $payment)
    {
        // Check if payment can be edited
        if (!$payment->canBeEdited()) {
            return redirect()->back()
                           ->with('error', 'This payment cannot be edited due to business rules (age, finalized invoice, etc.).');
        }

        $request->validate([
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
                function ($attribute, $value, $fail) use ($payment) {
                    // Check if new amount doesn't exceed invoice total
                    $otherPayments = Payment::where('invoice_id', $payment->invoice_id)
                                          ->where('id', '!=', $payment->id)
                                          ->sum('amount');
                    
                    $maxAllowed = $payment->invoice->total_amount - $otherPayments;
                    
                    if ($value > $maxAllowed) {
                        $fail("Amount cannot exceed ₹" . number_format($maxAllowed, 2));
                    }
                },
            ],
            'payment_date' => 'required|date|before_or_equal:today',
            'payment_method' => 'required|string|in:Cash,Card,Bank Transfer,Cheque,Online,UPI',
            'transaction_id' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
            'edit_reason' => 'required|string|max:1000', // Required edit reason
        ]);

        try {
            DB::beginTransaction();

            // Capture original state for audit trail
            $originalState = $this->capturePaymentState($payment);
            $oldAmount = $payment->amount;
            $newAmount = $request->amount;

            // Update payment record
            $updateData = [
                'amount' => $newAmount,
                'payment_date' => $request->payment_date,
                'payment_method' => $request->payment_method,
                'notes' => $request->notes,
            ];

            // Only add transaction_id if the column exists
            if (Schema::hasColumn('payments', 'transaction_id') && $request->filled('transaction_id')) {
                $updateData['transaction_id'] = $request->transaction_id;
            }

            // Update the payment (this will trigger model events and webhooks)
            $payment->update($updateData);

            // Note: Invoice recalculation is handled in the Payment model's boot method
            // This ensures webhooks get the correct updated data

            // Capture new state for audit trail
            $newState = $this->capturePaymentState($payment->fresh());

            // Log the edit with detailed changes
            $this->logPaymentEdit($payment, $originalState, $newState, $request->edit_reason);

            // Additional activity logging for important changes
            activity()
                ->performedOn($payment)
                ->causedBy(auth()->user())
                ->withProperties([
                    'old_amount' => $oldAmount,
                    'new_amount' => $newAmount,
                    'amount_difference' => $newAmount - $oldAmount,
                    'edit_reason' => $request->edit_reason,
                    'invoice_id' => $payment->invoice_id,
                    'invoice_number' => $payment->invoice->invoice_number
                ])
                ->log("Payment #{$payment->receipt_number} updated: Amount changed from ₹{$oldAmount} to ₹{$newAmount}");

            DB::commit();

            Log::info('Payment updated successfully with webhook integration:', [
                'payment_id' => $payment->id,
                'receipt_number' => $payment->receipt_number,
                'user_id' => auth()->id(),
                'old_amount' => $oldAmount,
                'new_amount' => $newAmount,
                'edit_reason' => $request->edit_reason,
                'webhook_enabled' => $payment->areWebhooksEnabled(),
            ]);

            return redirect()->route('admin.payments.show', $payment)
                           ->with('success', "Payment #{$payment->receipt_number} updated successfully. Webhooks have been triggered for external integrations.");

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update payment:', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                           ->withInput()
                           ->with('error', 'Failed to update payment: ' . $e->getMessage());
        }
    }

    /**
     * Show payment edit history
     */
    public function editHistory(Payment $payment)
    {
        $editHistory = PaymentEditLog::where('payment_id', $payment->id)
                                   ->with('user')
                                   ->latest()
                                   ->paginate(20);

        return view('admin.payments.edit-history', compact('payment', 'editHistory'));
    }

    /**
     * Revert payment to a previous state
     */
    public function revert(Payment $payment, PaymentEditLog $editLog, Request $request)
    {
        $request->validate([
            'revert_reason' => 'required|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            // Capture current state
            $currentState = $this->capturePaymentState($payment);

            // Revert to previous state
            $previousState = json_decode($editLog->previous_state, true);
            
            $revertData = [
                'amount' => $previousState['amount'],
                'payment_date' => $previousState['payment_date'],
                'payment_method' => $previousState['payment_method'],
                'notes' => $previousState['notes'] ?? null,
            ];

            if (isset($previousState['transaction_id']) && Schema::hasColumn('payments', 'transaction_id')) {
                $revertData['transaction_id'] = $previousState['transaction_id'];
            }

            $payment->update($revertData);

            // Recalculate invoice totals
            $this->recalculateInvoiceTotals($payment->invoice);

            // Log the revert action
            $this->logPaymentEdit(
                $payment,
                $currentState,
                $this->capturePaymentState($payment->fresh()),
                "Reverted to state from " . $editLog->created_at->format('Y-m-d H:i:s') . ". Reason: " . $request->revert_reason,
                'revert'
            );

            DB::commit();

            return redirect()->route('admin.payments.show', $payment)
                           ->with('success', 'Payment reverted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to revert payment: ' . $e->getMessage());
        }
    }

    /**
     * Capture current payment state for audit trail
     */
    private function capturePaymentState(Payment $payment): array
    {
        return [
            'amount' => $payment->amount,
            'payment_date' => $payment->payment_date,
            'payment_method' => $payment->payment_method,
            'transaction_id' => $payment->transaction_id ?? null,
            'notes' => $payment->notes,
            'receipt_number' => $payment->receipt_number,
            'invoice_id' => $payment->invoice_id,
            'student_id' => $payment->student_id ?? null,
        ];
    }

    /**
     * Log payment edit with detailed changes
     */
    private function logPaymentEdit(Payment $payment, array $originalState, array $newState, string $editReason, string $action = 'update')
    {
        $changes = [];
        
        foreach ($newState as $field => $newValue) {
            $oldValue = $originalState[$field] ?? null;
            
            if ($oldValue != $newValue) {
                $changes[$field] = [
                    'old' => $oldValue,
                    'new' => $newValue
                ];
            }
        }

        PaymentEditLog::create([
            'payment_id' => $payment->id,
            'user_id' => auth()->id(),
            'action' => $action,
            'previous_state' => json_encode($originalState),
            'new_state' => json_encode($newState),
            'changes' => json_encode($changes),
            'edit_reason' => $editReason,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Recalculate invoice totals after payment changes
     */
    private function recalculateInvoiceTotals($invoice)
    {
        $totalPaid = $invoice->payments()->sum('amount');
        
        $invoice->update([
            'paid_amount' => $totalPaid,
            'due_amount' => max(0, $invoice->total_amount - $totalPaid - ($invoice->concession_amount ?? 0)),
            'status' => $this->calculateInvoiceStatus($invoice->total_amount, $totalPaid, $invoice->concession_amount ?? 0)
        ]);
    }

    /**
     * Calculate invoice status based on amounts
     */
    private function calculateInvoiceStatus($totalAmount, $paidAmount, $concessionAmount = 0): string
    {
        $dueAmount = max(0, $totalAmount - $paidAmount - $concessionAmount);
        
        if ($dueAmount <= 0 && $paidAmount > 0) {
            return 'paid';
        } elseif ($paidAmount > 0) {
            return 'partially_paid';
        } else {
            return 'unpaid';
        }
    }
}