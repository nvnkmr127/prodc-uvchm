<?php

namespace App\Observers;

use App\Models\Payment;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;

class PaymentObserver
{
    protected $whatsAppService;

    public function __construct(WhatsAppService $whatsAppService)
    {
        $this->whatsAppService = $whatsAppService;
    }

    /**
     * Handle the Payment "created" event.
     */
    public function created(Payment $payment): void
    {
        try {
            // Ensure student relation is loaded
            if (! $payment->relationLoaded('student')) {
                $payment->load('student');
            }

            $student = $payment->student;
            if (! $student) {
                return;
            }

            $amount = '₹'.number_format((float) $payment->amount, 2);
            $date = ($payment->payment_date instanceof \Carbon\Carbon) ? $payment->payment_date->format('d-m-Y') : now()->format('d-m-Y');

            // Prepare template data for WhatsAppService
            // The service uses this data to map to template parameters
            $templateData = [
                'student_name' => $student->name,
                'amount' => $amount,
                'due_date' => $date, // Using payment date as transaction date
                'fee_type' => 'Fee Payment',
            ];

            $message = "Payment Received: {$amount} for {$student->name}";

            // Send to Student
            if ($student->student_mobile) {
                $this->whatsAppService->sendPaymentReminder($student->student_mobile, $message, $templateData);
                Log::info("Payment notification sent to student: {$student->student_mobile}", ['payment_id' => $payment->id]);
            }

            // Send to Father
            if ($student->father_mobile) {
                $this->whatsAppService->sendPaymentReminder($student->father_mobile, $message, $templateData);
                Log::info("Payment notification sent to father: {$student->father_mobile}", ['payment_id' => $payment->id]);
            }

        } catch (\Exception $e) {
            Log::error('Error sending payment notification: '.$e->getMessage(), ['payment_id' => $payment->id]);
        }
    }
}
