<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentEditLog;
use App\Models\ComponentPaymentItem;
use App\Models\StudentFee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentEditController extends Controller
{
    /**
     * Show payment edit form
     */
    public function edit(Payment $payment)
    {
        // Check permissions
        $this->authorize('edit payments');

        // Verify payment can be edited
        if (!$payment->canBeEdited()) {
            return redirect()->back()->with('error', 'This payment cannot be edited.');
        }

        // Load payment with relationships
        $payment->load([
            'student.batch.course',
            'createdBy',
            'updatedBy',
            'componentItems.studentFee.feeCategory'
        ]);

        // Get available fee categories for this student
        $availableFees = $payment->student->studentFees()
            ->with('feeCategory')
            ->get();

        return view('admin.payment-edit.edit', compact('payment', 'availableFees'));
    }

    /**
     * Update payment
     */
    public function update(Request $request, Payment $payment)
    {
        // Check permissions
        $this->authorize('edit payments');

        // Verify payment can be edited
        if (!$payment->canBeEdited()) {
            return redirect()->back()->with('error', 'This payment cannot be edited.');
        }

        // Validate request
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'payment_date' => 'required|date',
            'transaction_id' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'edit_reason' => 'required|string|max:500',
            'components' => 'required|array|min:1',
            'components.*.student_fee_id' => 'required|exists:student_fees,id',
            'components.*.amount' => 'required|numeric|min:0.01'
        ]);

        try {
            DB::beginTransaction();

            // Capture original state
            $originalState = $this->capturePaymentState($payment);

            // Update payment
            $payment->update([
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'payment_date' => $validated['payment_date'],
                'transaction_id' => $validated['transaction_id'],
                'notes' => $validated['notes'],
                'updated_by' => auth()->id()
            ]);

            // Update component items
            $this->updateComponentItems($payment, $validated['components'], $originalState['components']);

            // Capture new state
            $newState = $this->capturePaymentState($payment->fresh());

            // Log the edit
            PaymentEditLog::logPaymentChange(
                $payment,
                'update',
                $originalState,
                $newState,
                $validated['edit_reason']
            );

            DB::commit();

            return redirect()->route('admin.students.show', $payment->student)
                ->with('success', 'Payment updated successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Payment edit failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return back()->withInput()
                ->with('error', 'Failed to update payment: ' . $e->getMessage());
        }
    }

    /**
     * Show payment edit history
     */
    public function history(Payment $payment)
    {
        // Check permissions
        $this->authorize('view payment history');

        // Load payment with relationships
        $payment->load([
            'student',
            'createdBy',
            'componentItems.studentFee.feeCategory'
        ]);

        // Get edit history
        $editHistory = PaymentEditLog::where('payment_id', $payment->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('admin.payment-edit.history', compact('payment', 'editHistory'));
    }


    

    /**
     * Revert payment to a previous state
     */
    public function revert(Request $request, Payment $payment)
    {
        // Check permissions
        $this->authorize('revert payments');

        $validated = $request->validate([
            'log_id' => 'required|exists:payment_edit_logs,id',
            'revert_reason' => 'required|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            // Get the log entry to revert to
            $logEntry = PaymentEditLog::findOrFail($validated['log_id']);
            
            if ($logEntry->payment_id !== $payment->id) {
                throw new \Exception('Invalid log entry for this payment');
            }

            // Capture current state
            $currentState = $this->capturePaymentState($payment);

            // Revert payment to old values
            $oldValues = $logEntry->old_values;
            
            $payment->update([
                'amount' => $oldValues['amount'] ?? $payment->amount,
                'payment_method' => $oldValues['payment_method'] ?? $payment->payment_method,
                'payment_date' => $oldValues['payment_date'] ?? $payment->payment_date,
                'transaction_id' => $oldValues['transaction_id'] ?? $payment->transaction_id,
                'notes' => $oldValues['notes'] ?? $payment->notes,
                'updated_by' => auth()->id()
            ]);

            // Revert component items if available
            if (isset($oldValues['components'])) {
                $this->revertComponentItems($payment, $oldValues['components'], $currentState['components']);
            }

            // Log the reversion
            PaymentEditLog::logPaymentChange(
                $payment,
                'revert',
                $currentState,
                $oldValues,
                $validated['revert_reason'] . ' (Reverted to state from ' . $logEntry->created_at->format('Y-m-d H:i:s') . ')'
            );

            DB::commit();

            return redirect()->route('admin.payment-edit.history', $payment)
                ->with('success', 'Payment reverted successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Payment revert failed', [
                'payment_id' => $payment->id,
                'log_id' => $validated['log_id'],
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return back()->with('error', 'Failed to revert payment: ' . $e->getMessage());
        }
    }

    /**
     * Capture current payment state
     */
    private function capturePaymentState(Payment $payment): array
    {
        return [
            'id' => $payment->id,
            'amount' => $payment->amount,
            'payment_method' => $payment->payment_method,
            'payment_date' => $payment->payment_date,
            'transaction_id' => $payment->transaction_id,
            'notes' => $payment->notes,
            'status' => $payment->status,
            'components' => $payment->componentItems->map(function($item) {
                return [
                    'student_fee_id' => $item->student_fee_id,
                    'amount_paid' => $item->amount_paid,
                    'fee_category_name' => $item->studentFee->feeCategory->name ?? 'Unknown'
                ];
            })->toArray()
        ];
    }

/**
 * Update component items for the payment
 */
private function updateComponentItems(Payment $payment, array $components, array $originalComponents)
{
    // Delete existing component items
    $payment->componentItems()->delete();
    
    // Create new component items
    foreach ($components as $component) {
        if (isset($component['student_fee_id']) && isset($component['amount']) && $component['amount'] > 0) {
            ComponentPaymentItem::create([
                'payment_id' => $payment->id,
                'student_fee_id' => $component['student_fee_id'],
                'amount_paid' => $component['amount']
            ]);
            
            // Update the student fee paid amount
            $this->updateStudentFeePaidAmount($component['student_fee_id']);
        }
    }
}

/**
 * Update student fee paid amount
 */
private function updateStudentFeePaidAmount($studentFeeId)
{
    $studentFee = StudentFee::find($studentFeeId);
    if ($studentFee) {
        $totalPaid = ComponentPaymentItem::where('student_fee_id', $studentFeeId)
            ->sum('amount_paid');
        
        $studentFee->update(['paid_amount' => $totalPaid]);
        
        // Update status based on payment
        $totalAmount = $studentFee->amount - ($studentFee->concession_amount ?? 0);
        $paidAmount = $studentFee->paid_amount;

        if ($paidAmount >= $totalAmount) {
            $studentFee->update(['status' => 'paid']);
        } elseif ($paidAmount > 0) {
            $studentFee->update(['status' => 'partial']);
        } else {
            $studentFee->update(['status' => 'unpaid']);
        }
    }
}

/**
 * Revert component items to previous state
 */
private function revertComponentItems(Payment $payment, array $oldComponents, array $currentComponents)
{
    // Delete current component items
    $payment->componentItems()->delete();
    
    // Recreate old component items
    foreach ($oldComponents as $component) {
        if (isset($component['student_fee_id']) && isset($component['amount_paid'])) {
            ComponentPaymentItem::create([
                'payment_id' => $payment->id,
                'student_fee_id' => $component['student_fee_id'],
                'amount_paid' => $component['amount_paid']
            ]);
            
            // Update the student fee paid amount
            $this->updateStudentFeePaidAmount($component['student_fee_id']);
        }
    }
}

    /**
     * Update student fee status
     */
    private function updateStudentFeeStatus(StudentFee $studentFee): void
    {
        $remainingAmount = $studentFee->amount - $studentFee->concession_amount - $studentFee->paid_amount;
        
        if ($remainingAmount <= 0) {
            $studentFee->update(['status' => 'paid']);
        } elseif ($studentFee->paid_amount > 0) {
            $studentFee->update(['status' => 'partial']);
        } else {
            $studentFee->update(['status' => 'pending']);
        }
    }
}