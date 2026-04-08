<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;

class PublicReceiptController extends Controller
{
    /**
     * Show public receipt details based on the new component payment system.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $receiptNumber)
    {
        try {
            // MODIFIED: Eager load relationships for the component-based system
            $payment = Payment::with([
                'student:id,name,enrollment_number',
                'componentItems.studentFee.feeCategory:id,name',
            ])
                ->where('receipt_number', $receiptNumber)
                ->where('payment_type', 'component') // Ensure it's a component payment
                ->firstOrFail();

            // MODIFIED: Prepare response data from the new component relationships
            return response()->json([
                'status' => 'success',
                'receipt_number' => $payment->receipt_number,
                'payment_date' => $payment->payment_date->format('Y-m-d'),
                'amount' => $payment->amount,
                'student_name' => $payment->student->name ?? 'N/A',
                'enrollment_number' => $payment->student->enrollment_number ?? 'N/A',
                'payment_method' => $payment->payment_method,
                // Add details of the fee components that were paid
                'components_paid' => $payment->componentItems->map(function ($item) {
                    return [
                        'category' => $item->studentFee->feeCategory->name ?? 'Unknown Fee',
                        'amount' => $item->amount_paid,
                    ];
                }),
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Receipt not found or is not a valid component payment receipt.'], 404);
        }
    }

    /**
     * Download a PDF receipt for a component-based payment.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function downloadPdf(string $receiptNumber)
    {
        try {
            // MODIFIED: Eager load relationships for the component-based system
            $payment = Payment::with([
                'student.batch.course',
                'componentItems.studentFee.feeCategory',
            ])
                ->where('receipt_number', $receiptNumber)
                ->where('payment_type', 'component') // Ensure it's a component payment
                ->firstOrFail();

            // NOTE: The actual PDF generation logic would go here.
            // This placeholder response is updated to reflect the new data structure.
            return response()->json([
                'message' => 'PDF download would start here for component payment.',
                'receipt_number' => $receiptNumber,
                'student_name' => $payment->student->name,
                'amount' => $payment->amount,
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Receipt not found or is not a valid component payment receipt.'], 404);
        }
    }
}
