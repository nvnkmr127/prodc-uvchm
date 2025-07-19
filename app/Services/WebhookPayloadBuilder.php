<?php

// STEP 1: Create this file: app/Services/WebhookPayloadBuilder.php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class WebhookPayloadBuilder
{
    /**
     * Build optimized payload from existing EloquentWebhookEvent
     * This is the method your UniversalWebhookListener is looking for
     */
    public static function buildOptimizedPayload($event): array
    {
        try {
            // Extract key information from the existing event structure
            $eventName = property_exists($event, 'webhookEventName') ? $event->webhookEventName : 'unknown';
            $model = property_exists($event, 'model') ? $event->model : null;
            $eventType = property_exists($event, 'eventType') ? $event->eventType : 'unknown';
            $additionalData = property_exists($event, 'additionalData') ? $event->additionalData : [];

            if (!$model) {
                return self::buildFallbackPayload($event);
            }

            $modelType = strtolower(class_basename($model));
            
            return [
                'event' => $eventName,
                'event_id' => 'evt_' . uniqid(),
                'timestamp' => now()->toISOString(),
                'app_name' => config('app.name', 'UVCHM'),
                'environment' => app()->environment(),
                'data' => self::buildEventData($modelType, $eventType, $model, $additionalData)
            ];
            
        } catch (\Exception $e) {
            \Log::error('Error building optimized payload', [
                'error' => $e->getMessage(),
                'event_class' => get_class($event)
            ]);
            
            return self::buildFallbackPayload($event);
        }
    }

    /**
     * Build event-specific data based on model type
     */
    private static function buildEventData(string $modelType, string $action, Model $model, array $additionalData = []): array
    {
        switch ($modelType) {
            case 'payment':
                return self::buildPaymentData($action, $model, $additionalData);
            case 'student':
                return self::buildStudentData($action, $model, $additionalData);
            case 'invoice':
                return self::buildInvoiceData($action, $model, $additionalData);
            default:
                return self::buildGenericData($action, $model, $additionalData);
        }
    }

    /**
     * Build payment webhook data - clean and minimal
     */
    private static function buildPaymentData(string $action, Model $payment, array $additionalData = []): array
    {
        $data = [
            'id' => $payment->id,
            'action' => $action,
            'payment' => [
                'id' => $payment->id,
                'amount' => (float) $payment->amount,
                'formatted_amount' => '₹' . number_format($payment->amount, 2),
                'payment_method' => $payment->payment_method,
                'payment_date' => $payment->payment_date,
                'receipt_number' => $payment->receipt_number,
                'status' => 'completed'
            ]
        ];

        // Add invoice info if payment has invoice
        try {
            if ($payment->invoice) {
                // Calculate updated due amount after this payment
                $totalPaid = $payment->invoice->paid_amount + $payment->amount;
                $remainingDue = $payment->invoice->total_amount - $totalPaid;
                
                // Determine payment status
                $paymentStatus = 'partial';
                if ($remainingDue <= 0) {
                    $paymentStatus = 'fully_paid';
                } elseif ($totalPaid == $payment->amount) {
                    $paymentStatus = 'first_payment';
                }
                
                $data['invoice'] = [
                    'id' => $payment->invoice->id,
                    'invoice_number' => $payment->invoice->invoice_number,
                    'total_amount' => (float) $payment->invoice->total_amount,
                    'paid_before_this' => (float) $payment->invoice->paid_amount,
                    'paid_now' => (float) $payment->amount,
                    'total_paid' => (float) $totalPaid,
                    'due_amount' => (float) max(0, $remainingDue),
                    'status' => $payment->invoice->status,
                    'payment_status' => $paymentStatus
                ];

                // Add student info if available
                if ($payment->invoice->student) {
                    $student = $payment->invoice->student;
                    $data['student'] = [
                        'id' => $student->id,
                        'name' => $student->name,
                        'enrollment_number' => $student->enrollment_number,
                        'email' => $student->email,
                        'mobile' => $student->student_mobile,
                        'father_name' => $student->father_name,
                        'father_mobile' => $student->father_mobile
                    ];
                }
            }
        } catch (\Exception $e) {
            // If there's an error loading relations, just skip them
            \Log::warning('Could not load payment relations for webhook', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
        }

        // Always generate public receipt URLs (override any admin URLs)
        $data['receipt_urls'] = self::generatePublicReceiptUrls($payment);

        return $data;
    }

    /**
     * Generate public receipt URLs for a payment
     */
    private static function generatePublicReceiptUrls(Model $payment): array
    {
        $baseUrl = config('app.url');
        $receiptNumber = $payment->receipt_number;
        
        return [
            'view' => "{$baseUrl}/receipts/{$receiptNumber}",
            'download_pdf' => "{$baseUrl}/receipts/{$receiptNumber}/pdf",
            'public' => "{$baseUrl}/receipts/{$receiptNumber}"
        ];
    }

    /**
     * Build student webhook data
     */
    private static function buildStudentData(string $action, Model $student, array $additionalData = []): array
    {
        $data = [
            'id' => $student->id,
            'action' => $action,
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
                'enrollment_number' => $student->enrollment_number,
                'mobile' => $student->student_mobile,
                'father_name' => $student->father_name,
                'father_mobile' => $student->father_mobile,
                'status' => $student->status,
                'admission_date' => $student->admission_date
            ]
        ];

        // Add batch info if available
        try {
            if ($student->batch) {
                $data['batch'] = [
                    'id' => $student->batch->id,
                    'name' => $student->batch->name
                ];
            }
        } catch (\Exception $e) {
            // Skip if batch relation fails
        }

        return array_merge($data, $additionalData);
    }

    /**
     * Build invoice webhook data
     */
    private static function buildInvoiceData(string $action, Model $invoice, array $additionalData = []): array
    {
        $data = [
            'id' => $invoice->id,
            'action' => $action,
            'invoice' => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'total_amount' => (float) $invoice->total_amount,
                'due_amount' => (float) $invoice->due_amount,
                'status' => $invoice->status,
                'due_date' => $invoice->due_date,
                'issue_date' => $invoice->issue_date
            ]
        ];

        // Add student info if available
        try {
            if ($invoice->student) {
                $data['student'] = [
                    'id' => $invoice->student->id,
                    'name' => $invoice->student->name,
                    'enrollment_number' => $invoice->student->enrollment_number
                ];
            }
        } catch (\Exception $e) {
            // Skip if student relation fails
        }

        return array_merge($data, $additionalData);
    }

    /**
     * Build generic model data for unknown types
     */
    private static function buildGenericData(string $action, Model $model, array $additionalData = []): array
    {
        $data = [
            'id' => $model->id,
            'action' => $action,
            'model_type' => get_class($model),
            'model_data' => $model->only(['id', 'name', 'title', 'status', 'created_at', 'updated_at'])
        ];

        return array_merge($data, $additionalData);
    }

    /**
     * Build fallback payload for events without proper model data
     */
    private static function buildFallbackPayload($event): array
    {
        return [
            'event' => property_exists($event, 'webhookEventName') ? $event->webhookEventName : 'unknown',
            'event_id' => 'evt_' . uniqid(),
            'timestamp' => now()->toISOString(),
            'app_name' => config('app.name', 'UVCHM'),
            'environment' => app()->environment(),
            'data' => [
                'event_class' => get_class($event),
                'message' => 'Optimized payload could not be built, using fallback'
            ]
        ];
    }
}