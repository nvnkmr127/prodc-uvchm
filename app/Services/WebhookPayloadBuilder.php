<?php

// app/Services/WebhookPayloadBuilder.php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentFee;
use App\Models\StudentConcession;

class WebhookPayloadBuilder
{
    /**
     * Build optimized payload from existing EloquentWebhookEvent
     */
    public static function buildOptimizedPayload($event): array
    {
        try {
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
                'app_name' => config('app.name', 'CollegeManagement'),
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
     * Build event-specific data based on component models
     */
    private static function buildEventData(string $modelType, string $action, Model $model, array $additionalData = []): array
    {
        return match ($modelType) {
            'payment' => self::buildPaymentData($action, $model, $additionalData),
            'student' => self::buildStudentData($action, $model, $additionalData),
            'studentfee' => self::buildStudentFeeData($action, $model, $additionalData),
            'studentconcession' => self::buildConcessionData($action, $model, $additionalData),
            default => self::buildGenericData($action, $model, $additionalData),
        };
    }

    /**
     * ✅ FIXED: Build component-based payment webhook data with proper relationship loading
     */
    private static function buildPaymentData(string $action, Model $payment, array $additionalData = []): array
    {
        // Force load relationships if not already loaded
        if (!$payment->relationLoaded('student')) {
            $payment->load('student');
        }
        
        if (!$payment->relationLoaded('componentItems')) {
            $payment->load('componentItems.studentFee.feeCategory');
        }

        $data = [
            'id' => $payment->id,
            'action' => $action,
            'payment' => [
                'id' => $payment->id,
                'amount' => (float) $payment->amount,
                'formatted_amount' => '₹' . number_format($payment->amount, 2),
                'payment_method' => $payment->payment_method,
                'payment_date' => $payment->payment_date ? $payment->payment_date->toISOString() : null,
                'receipt_number' => $payment->receipt_number,
                'status' => $payment->status ?? 'completed',
                'payment_type' => $payment->payment_type ?? 'component',
            ],
            'receipt_urls' => self::generatePublicReceiptUrls($payment),
            'components_paid' => [] // Initialize empty array
        ];

        // Add student information
        if ($payment->student) {
            $student = $payment->student;
            $data['student'] = [
                'id' => $student->id,
                'name' => $student->name,
                'enrollment_number' => $student->enrollment_number,
                'email' => $student->email ?? null,
                'mobile' => $student->student_mobile ?? null,
                'Father mobile' => $student->father_mobile ?? null,
            ];
        }

        // ✅ FIXED: Add component details with proper null checking
        if ($payment->payment_type === 'component' || $payment->isComponentPayment()) {
            try {
                $componentItems = $payment->componentItems;
                
                if ($componentItems && $componentItems->count() > 0) {
                    $data['components_paid'] = $componentItems->map(function ($item) {
                        $categoryName = 'Unknown Category';
                        
                        // Safely get category name
                        if ($item->studentFee) {
                            if ($item->studentFee->feeCategory) {
                                $categoryName = $item->studentFee->feeCategory->name;
                            } elseif ($item->studentFee->relationLoaded('feeCategory')) {
                                // Try to load the relationship if not loaded
                                $item->studentFee->load('feeCategory');
                                $categoryName = $item->studentFee->feeCategory->name ?? $categoryName;
                            }
                        }

                        return [
                            'student_fee_id' => $item->student_fee_id,
                            'category_name' => $categoryName,
                            'amount_paid' => (float) $item->amount_paid,
                            'fee_status_after_payment' => $item->studentFee ? $item->studentFee->status : null,
                        ];
                    })->toArray();
                } else {
                    // Log warning if no component items found for component payment
                    \Log::warning('Component payment has no component items', [
                        'payment_id' => $payment->id,
                        'payment_type' => $payment->payment_type
                    ]);
                }
                
            } catch (\Exception $e) {
                \Log::warning('Could not load component items for webhook', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return array_merge($data, $additionalData);
    }

    /**
     * Generate public receipt URLs for a payment
     */
    private static function generatePublicReceiptUrls(Model $payment): array
    {
        $baseUrl = rtrim(config('app.url'), '/');
        $receiptNumber = $payment->receipt_number;
        
        return [
            'view' => "{$baseUrl}/receipts/{$receiptNumber}",
            'download_pdf' => "{$baseUrl}/receipts/{$receiptNumber}/pdf",
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
                'status' => $student->status,
                'admission_date' => $student->admission_date,
                'mobile' => $student->student_mobile,
            ]
        ];

        try {
            if ($student->batch) {
                $data['batch'] = [
                    'id' => $student->batch->id, 
                    'name' => $student->batch->name,
                    'course_id' => $student->batch->course_id ?? null
                ];
            }
        } catch (\Exception $e) {
            // Skip if relation fails
        }

        return array_merge($data, $additionalData);
    }

    /**
     * Build StudentFee webhook data
     */
    private static function buildStudentFeeData(string $action, Model $studentFee, array $additionalData = []): array
    {
        $data = [
            'id' => $studentFee->id,
            'action' => $action,
            'student_fee' => [
                'id' => $studentFee->id,
                'amount' => (float) $studentFee->amount,
                'paid_amount' => (float) $studentFee->paid_amount,
                'concession_amount' => (float) ($studentFee->concession_amount ?? 0),
                'remaining_amount' => (float) $studentFee->getRemainingAmount(),
                'status' => $studentFee->status,
                'due_date' => $studentFee->due_date,
                'academic_year' => $studentFee->academic_year,
            ]
        ];

        try {
            if ($studentFee->student) {
                $data['student'] = [
                    'id' => $studentFee->student->id, 
                    'name' => $studentFee->student->name,
                    'enrollment_number' => $studentFee->student->enrollment_number
                ];
            }
            
            if ($studentFee->feeCategory) {
                $data['fee_category'] = [
                    'id' => $studentFee->feeCategory->id, 
                    'name' => $studentFee->feeCategory->name
                ];
            }
        } catch (\Exception $e) {
            // Skip if relations fail
        }

        return array_merge($data, $additionalData);
    }
    
    /**
     * Build StudentConcession webhook data
     */
    private static function buildConcessionData(string $action, Model $concession, array $additionalData = []): array
    {
        $data = [
            'id' => $concession->id,
            'action' => $action,
            'concession' => [
                'id' => $concession->id,
                'amount' => (float) $concession->amount,
                'reason' => $concession->reason,
                'approved_by' => $concession->approved_by,
                'status' => $concession->status,
            ]
        ];

        try {
            if ($concession->studentFee) {
                $data['student_fee'] = [
                    'id' => $concession->studentFee->id,
                    'category_name' => $concession->studentFee->feeCategory->name ?? 'Unknown'
                ];
            }
            
            if (isset($concession->studentFee->student)) {
                $data['student'] = [
                    'id' => $concession->studentFee->student->id,
                    'name' => $concession->studentFee->student->name
                ];
            }
        } catch (\Exception $e) {
            // Skip if relations fail
        }

        return array_merge($data, $additionalData);
    }

    /**
     * Build generic webhook data for unknown models
     */
    private static function buildGenericData(string $action, Model $model, array $additionalData = []): array
    {
        $data = [
            'id' => $model->getKey(),
            'action' => $action,
            'model_type' => class_basename($model),
            'model_data' => $model->toArray(),
        ];

        return array_merge($data, $additionalData);
    }

    /**
     * Build fallback payload when something goes wrong
     */
    private static function buildFallbackPayload($event): array
    {
        return [
            'event' => 'system.error',
            'event_id' => 'evt_' . uniqid(),
            'timestamp' => now()->toISOString(),
            'app_name' => config('app.name', 'CollegeManagement'),
            'environment' => app()->environment(),
            'data' => [
                'error' => 'Could not build webhook payload',
                'event_class' => get_class($event),
                'debug_info' => [
                    'has_model' => property_exists($event, 'model') && $event->model !== null,
                    'model_class' => property_exists($event, 'model') && $event->model ? get_class($event->model) : null,
                ]
            ]
        ];
    }
}