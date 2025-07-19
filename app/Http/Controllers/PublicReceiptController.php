<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class PublicReceiptController extends Controller
{
    public function show(string $receiptNumber)
    {
        try {
            $payment = Payment::with(['invoice.student.batch'])
                ->where('receipt_number', $receiptNumber)
                ->firstOrFail();

            return response()->json([
                'receipt_number' => $payment->receipt_number,
                'amount' => $payment->amount,
                'student_name' => $payment->invoice->student->name ?? 'N/A',
                'payment_date' => $payment->payment_date,
                'status' => 'success'
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Receipt not found'], 404);
        }
    }

    public function downloadPdf(string $receiptNumber)
    {
        try {
            $payment = Payment::with(['invoice.student.batch'])
                ->where('receiptNumber', $receiptNumber)
                ->firstOrFail();

            return response()->json([
                'message' => 'PDF download would start here',
                'receipt_number' => $receiptNumber
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Receipt not found'], 404);
        }
    }
}