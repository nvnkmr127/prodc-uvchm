<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceEditLog;
use App\Models\FeeCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class InvoiceEditController extends Controller
{
    /**
     * Show invoice edit form
     */
    public function edit(Invoice $invoice)
    {
        $invoice->load(['student.batch.course', 'items.feeCategory', 'payments']);
        $feeCategories = FeeCategory::orderBy('name')->get();
        $editHistory = InvoiceEditLog::where('invoice_id', $invoice->id)
                                   ->with('user')
                                   ->latest()
                                   ->get();
        
        return view('admin.invoices.edit', compact('invoice', 'feeCategories', 'editHistory'));
    }

    /**
     * Update invoice with audit trail
     */
    public function update(Request $request, Invoice $invoice)
    {
        $request->validate([
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'items' => 'required|array|min:1',
            'items.*.fee_category_id' => 'required|exists:fee_categories,id',
            'items.*.amount' => 'required|numeric|min:0',
            'items.*.description' => 'nullable|string|max:255',
            'concession_amount' => 'nullable|numeric|min:0',
            'concession_notes' => 'nullable|string|max:500',
            'edit_notes' => 'required|string|max:1000', // ✅ Required edit reason
        ]);

        try {
            DB::beginTransaction();

            // ✅ Capture original state for audit trail
            $originalState = $this->captureInvoiceState($invoice);

            // Calculate new total amount
            $newTotalAmount = collect($request->items)->sum('amount');
            $concessionAmount = $request->concession_amount ?? 0;
            $finalAmount = $newTotalAmount - $concessionAmount;

            // ✅ Update invoice
            $invoice->update([
                'issue_date' => $request->issue_date,
                'due_date' => $request->due_date,
                'total_amount' => $finalAmount,
                'due_amount' => $finalAmount - $invoice->paid_amount,
                'concession_amount' => $concessionAmount,
                'concession_notes' => $request->concession_notes,
                'status' => $this->calculateInvoiceStatus($finalAmount, $invoice->paid_amount),
            ]);

            // ✅ Update invoice items
            $this->updateInvoiceItems($invoice, $request->items);

            // ✅ Capture new state for audit trail
            $newState = $this->captureInvoiceState($invoice->fresh());

            // ✅ Log the edit with detailed changes
            $this->logInvoiceEdit($invoice, $originalState, $newState, $request->edit_notes);

            DB::commit();

            Log::info('Invoice updated successfully:', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'user_id' => auth()->id(),
                'original_amount' => $originalState['total_amount'],
                'new_amount' => $finalAmount,
                'edit_notes' => $request->edit_notes
            ]);

            return redirect()->route('admin.invoices.show', $invoice)
                           ->with('success', "Invoice #{$invoice->invoice_number} updated successfully.");

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update invoice:', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                           ->withInput()
                           ->with('error', 'Failed to update invoice: ' . $e->getMessage());
        }
    }

    /**
     * Show invoice edit history
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
     * Revert invoice to previous state
     */
    public function revert(Invoice $invoice, InvoiceEditLog $editLog)
    {
        try {
            DB::beginTransaction();

            // ✅ Capture current state before revert
            $currentState = $this->captureInvoiceState($invoice);

            // ✅ Restore invoice to previous state
            $previousState = $editLog->previous_state;
            
            $invoice->update([
                'issue_date' => $previousState['issue_date'],
                'due_date' => $previousState['due_date'],
                'total_amount' => $previousState['total_amount'],
                'due_amount' => $previousState['total_amount'] - $invoice->paid_amount,
                'concession_amount' => $previousState['concession_amount'] ?? 0,
                'concession_notes' => $previousState['concession_notes'] ?? null,
                'status' => $this->calculateInvoiceStatus($previousState['total_amount'], $invoice->paid_amount),
            ]);

            // ✅ Restore invoice items
            $this->restoreInvoiceItems($invoice, $previousState['items']);

            // ✅ Log the revert action
            $this->logInvoiceEdit(
                $invoice, 
                $currentState, 
                $previousState, 
                "Reverted to state from " . $editLog->created_at->format('Y-m-d H:i:s'),
                'revert'
            );

            DB::commit();

            return redirect()->route('admin.invoices.show', $invoice)
                           ->with('success', "Invoice reverted to previous state successfully.");

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to revert invoice:', [
                'invoice_id' => $invoice->id,
                'edit_log_id' => $editLog->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                           ->with('error', 'Failed to revert invoice: ' . $e->getMessage());
        }
    }

    /**
     * ✅ Capture complete invoice state for audit trail
     */
    private function captureInvoiceState(Invoice $invoice): array
    {
        $invoice->load(['items.feeCategory']);

        return [
            'invoice_number' => $invoice->invoice_number,
            'issue_date' => $invoice->issue_date,
            'due_date' => $invoice->due_date,
            'total_amount' => $invoice->total_amount,
            'paid_amount' => $invoice->paid_amount,
            'due_amount' => $invoice->due_amount,
            'concession_amount' => $invoice->concession_amount,
            'concession_notes' => $invoice->concession_notes,
            'status' => $invoice->status,
            'items' => $invoice->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'fee_category_id' => $item->fee_category_id,
                    'fee_category_name' => $item->feeCategory->name ?? 'Unknown',
                    'amount' => $item->amount,
                    'description' => $item->description,
                ];
            })->toArray()
        ];
    }

    /**
     * ✅ Update invoice items
     */
    private function updateInvoiceItems(Invoice $invoice, array $items)
    {
        // Delete existing items
        $invoice->items()->delete();

        // Create new items
        foreach ($items as $item) {
            $invoice->items()->create([
                'fee_category_id' => $item['fee_category_id'],
                'amount' => $item['amount'],
                'description' => $item['description'] ?? null,
            ]);
        }
    }

    /**
     * ✅ Restore invoice items from previous state
     */
    private function restoreInvoiceItems(Invoice $invoice, array $previousItems)
    {
        // Delete current items
        $invoice->items()->delete();

        // Restore previous items
        foreach ($previousItems as $item) {
            $invoice->items()->create([
                'fee_category_id' => $item['fee_category_id'],
                'amount' => $item['amount'],
                'description' => $item['description'],
            ]);
        }
    }

    /**
     * ✅ Log invoice edit with detailed audit trail
     */
    private function logInvoiceEdit(Invoice $invoice, array $previousState, array $newState, string $notes, string $action = 'edit')
    {
        // Calculate changes
        $changes = $this->calculateChanges($previousState, $newState);

        InvoiceEditLog::create([
            'invoice_id' => $invoice->id,
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name ?? 'System',
            'action' => $action,
            'previous_state' => $previousState,
            'new_state' => $newState,
            'changes' => $changes,
            'notes' => $notes,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * ✅ Calculate detailed changes between states
     */
    private function calculateChanges(array $previous, array $new): array
    {
        $changes = [];

        // Check main invoice fields
        $fieldsToCheck = ['issue_date', 'due_date', 'total_amount', 'concession_amount', 'concession_notes', 'status'];
        
        foreach ($fieldsToCheck as $field) {
            if (($previous[$field] ?? null) !== ($new[$field] ?? null)) {
                $changes[$field] = [
                    'from' => $previous[$field] ?? null,
                    'to' => $new[$field] ?? null
                ];
            }
        }

        // Check items changes
        $itemChanges = $this->calculateItemChanges($previous['items'] ?? [], $new['items'] ?? []);
        if (!empty($itemChanges)) {
            $changes['items'] = $itemChanges;
        }

        return $changes;
    }

    /**
     * ✅ Calculate changes in invoice items
     */
    private function calculateItemChanges(array $previousItems, array $newItems): array
    {
        $changes = [
            'added' => [],
            'removed' => [],
            'modified' => []
        ];

        // Find removed items
        foreach ($previousItems as $prevItem) {
            $found = false;
            foreach ($newItems as $newItem) {
                if ($prevItem['fee_category_id'] === $newItem['fee_category_id']) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $changes['removed'][] = $prevItem;
            }
        }

        // Find added and modified items
        foreach ($newItems as $newItem) {
            $previousItem = null;
            foreach ($previousItems as $prevItem) {
                if ($prevItem['fee_category_id'] === $newItem['fee_category_id']) {
                    $previousItem = $prevItem;
                    break;
                }
            }

            if ($previousItem === null) {
                // New item added
                $changes['added'][] = $newItem;
            } elseif ($previousItem['amount'] !== $newItem['amount'] || $previousItem['description'] !== $newItem['description']) {
                // Item modified
                $changes['modified'][] = [
                    'fee_category_id' => $newItem['fee_category_id'],
                    'fee_category_name' => $newItem['fee_category_name'] ?? 'Unknown',
                    'previous' => $previousItem,
                    'new' => $newItem
                ];
            }
        }

        return $changes;
    }

    /**
     * ✅ Calculate invoice status based on amounts
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
}