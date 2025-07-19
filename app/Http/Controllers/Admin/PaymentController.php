<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use PDF;

class PaymentController extends Controller
{
    /**
     * Display the payment receipt in the browser (Online View).
     *
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function showReceipt(Payment $payment)
    {
        $payment->load('invoice.student.batch');

        // Get settings
        $settings = [
            'college_name' => setting('college_name', 'My College'),
            'college_address' => setting('college_address', ''),
            'college_logo' => setting('college_logo', ''),
            'currency_symbol' => setting('currency_symbol', '₹'),
            'invoice_footer_text' => setting('invoice_footer_text', 'This is a computer-generated receipt.')
        ];

        // Use the online view template
        return view('admin.receipts.online_view', compact('payment', 'settings'));
    }

    /**
     * Download the payment receipt as a PDF file (PDF View).
     *
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function downloadReceipt(Payment $payment)
    {
        $payment->load('invoice.student.batch');

        // Get settings
        $settings = [
            'college_name' => setting('college_name', 'My College'),
            'college_address' => setting('college_address', ''),
            'college_logo' => setting('college_logo', ''),
            'currency_symbol' => setting('currency_symbol', '₹'),
            'invoice_footer_text' => setting('invoice_footer_text', 'This is a computer-generated receipt.')
        ];

        // Use the PDF view template
        $pdf = PDF::loadView('admin.receipts.pdf_view', compact('payment', 'settings'));
        
        // Set options for better PDF rendering
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isPhpEnabled' => true,
            'defaultFont' => 'DejaVu Sans',
            'encoding' => 'UTF-8'
        ]);
        
        $fileName = 'Receipt-' . $payment->receipt_number . '.pdf';

        return $pdf->download($fileName);
    }

    /**
     * Display a public receipt by receipt number (Online View).
     *
     * @param string $receipt_number
     * @return \Illuminate\Http\Response
     */
    public function showPublicReceipt($receipt_number)
    {
        $payment = Payment::where('receipt_number', $receipt_number)
                         ->with('invoice.student.batch')
                         ->firstOrFail();

        // Get settings
        $settings = [
            'college_name' => setting('college_name', 'My College'),
            'college_address' => setting('college_address', ''),
            'college_logo' => setting('college_logo', ''),
            'currency_symbol' => setting('currency_symbol', '₹'),
            'invoice_footer_text' => setting('invoice_footer_text', 'This is a computer-generated receipt.')
        ];

        // Use the online view template
        return view('admin.receipts.online_view', compact('payment', 'settings'));
    }

    /**
     * Download public receipt as PDF by receipt number (PDF View).
     *
     * @param string $receipt_number
     * @return \Illuminate\Http\Response
     */
    public function downloadPublicReceipt($receipt_number)
    {
        $payment = Payment::where('receipt_number', $receipt_number)
                         ->with('invoice.student.batch')
                         ->firstOrFail();

        // Get settings
        $settings = [
            'college_name' => setting('college_name', 'My College'),
            'college_address' => setting('college_address', ''),
            'college_logo' => setting('college_logo', ''),
            'currency_symbol' => setting('currency_symbol', '₹'),
            'invoice_footer_text' => setting('invoice_footer_text', 'This is a computer-generated receipt.')
        ];

        // Use the PDF view template
        $pdf = PDF::loadView('admin.receipts.pdf_view', compact('payment', 'settings'));
        
        // Set options for better PDF rendering
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isPhpEnabled' => true,
            'defaultFont' => 'DejaVu Sans',
            'encoding' => 'UTF-8'
        ]);
        
        $fileName = 'Receipt-' . $payment->receipt_number . '.pdf';

        return $pdf->download($fileName);
    }
    /**
 * Display the specified payment
 * Add this method to your existing PaymentController class
 */
public function show(Payment $payment)
{
    $payment->load([
        'invoice.student.batch.course',
        'invoice.items.feeCategory'
    ]);

    // Get edit history
    $editHistory = PaymentEditLog::where('payment_id', $payment->id)
                                ->with('user')
                                ->latest()
                                ->limit(10)
                                ->get();

    // Calculate payment impact on invoice
    $otherPayments = $payment->invoice->payments()
                            ->where('id', '!=', $payment->id)
                            ->get();

    return view('admin.payments.show', compact('payment', 'editHistory', 'otherPayments'));
}
}