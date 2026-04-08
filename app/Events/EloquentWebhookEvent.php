<?php

// App/Events/EloquentWebhookEvent.php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Synthetic event for Eloquent model events to trigger webhooks
 * * This event is automatically created when Eloquent models fire their
 * built-in events (creating, created, updating, updated, etc.)
 */
class EloquentWebhookEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The model instance that triggered this event
     */
    public Model $model;

    /**
     * The type of Eloquent event (created, updated, deleted, etc.)
     */
    public string $eventType;

    /**
     * The model name (Student, Payment, etc.)
     */
    public string $modelName;

    /**
     * The webhook event name (student.created, payment.updated, etc.)
     */
    public string $webhookEventName;

    /**
     * Additional data that can be attached to the event
     */
    public array $additionalData = [];

    /**
     * Timestamp when the event occurred
     */
    public \Carbon\Carbon $occurredAt;

    /**
     * The user who triggered this event (if available)
     */
    public ?Model $triggeredBy = null;

    /**
     * Context about how this event was triggered
     */
    public array $context = [];

    /**
     * Create a new Eloquent webhook event
     */
    public function __construct(Model $model, string $eventType, string $modelName, array $additionalData = [])
    {
        $this->model = $model;
        $this->eventType = $eventType;
        $this->modelName = $modelName;
        $this->webhookEventName = str_starts_with($eventType, strtolower($modelName).'.')
            ? $eventType
            : strtolower($modelName).'.'.$eventType;
        $this->additionalData = $additionalData;
        $this->occurredAt = now();

        // Try to capture the authenticated user
        $this->captureAuthenticatedUser();

        // Capture context about the event
        $this->captureContext();
    }

    /**
     * Capture the currently authenticated user if available
     */
    protected function captureAuthenticatedUser(): void
    {
        try {
            if (auth()->check()) {
                $this->triggeredBy = auth()->user();
            }
        } catch (\Exception $e) {
            // Silently fail if auth is not available
            $this->triggeredBy = null;
        }
    }

    /**
     * Capture context about how this event was triggered
     */
    protected function captureContext(): void
    {
        $this->context = [
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'url' => request()?->fullUrl(),
            'method' => request()?->method(),
            'session_id' => session()?->getId(),
            'timestamp' => $this->occurredAt->toIso8601String(),
        ];

        // Add model-specific context
        $this->addModelSpecificContext();
    }

    /**
     * Add context specific to the model type
     */
    protected function addModelSpecificContext(): void
    {
        // Add primary key value
        if ($this->model->getKey()) {
            $this->context['model_id'] = $this->model->getKey();
        }

        // Add model table name
        if (method_exists($this->model, 'getTable')) {
            $this->context['table_name'] = $this->model->getTable();
        }

        // Add whether this is a new model or existing
        if (method_exists($this->model, 'wasRecentlyCreated')) {
            $this->context['was_recently_created'] = $this->model->wasRecentlyCreated;
        }

        // Add dirty attributes for update events
        if ($this->eventType === 'updated' && method_exists($this->model, 'getDirty')) {
            $this->context['changed_attributes'] = array_keys($this->model->getDirty());
        }

        // Add original attributes for update/delete events
        if (in_array($this->eventType, ['updated', 'deleted']) && method_exists($this->model, 'getOriginal')) {
            $this->context['original_attributes'] = $this->model->getOriginal();
        }
    }

    /**
     * Get the model data as an array for webhook payload
     */
    public function getModelData(): array
    {
        $data = $this->model->toArray();

        // Add model metadata
        $data['_model_info'] = [
            'class' => get_class($this->model),
            'table' => method_exists($this->model, 'getTable') ? $this->model->getTable() : null,
            'primary_key' => method_exists($this->model, 'getKeyName') ? $this->model->getKeyName() : 'id',
            'exists' => method_exists($this->model, 'exists') ? $this->model->exists : true,
            'timestamps' => $this->model->timestamps ?? false,
        ];

        return $data;
    }

    /**
     * Get the user data who triggered this event
     */
    public function getTriggeredByData(): ?array
    {
        if (! $this->triggeredBy) {
            return null;
        }

        $userData = $this->triggeredBy->toArray();

        // Remove sensitive information
        unset($userData['password'], $userData['remember_token'], $userData['email_verified_at']);

        return [
            'id' => $this->triggeredBy->getKey(),
            'name' => $userData['name'] ?? 'Unknown User',
            'email' => $userData['email'] ?? null,
            'type' => get_class($this->triggeredBy),
        ];
    }

    /**
     * Set additional data for this event
     */
    public function setAdditionalData(array $data): self
    {
        $this->additionalData = array_merge($this->additionalData, $data);

        return $this;
    }

    /**
     * Add a single piece of additional data
     */
    public function addData(string $key, $value): self
    {
        $this->additionalData[$key] = $value;

        return $this;
    }

    /**
     * Get the priority level of this event
     */
    public function getPriority(): string
    {
        // ✅ CHANGED: Added component models to high priority list
        $highPriorityModels = ['Payment', 'StudentFee', 'StudentConcession', 'User'];
        $highPriorityEvents = ['created', 'deleted'];

        if (in_array($this->modelName, $highPriorityModels)) {
            return 'high';
        }

        if (in_array($this->eventType, $highPriorityEvents)) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Determine if this event should trigger immediate notifications
     */
    public function shouldNotifyImmediately(): bool
    {
        // ✅ CHANGED: Added component models to immediate notification list
        $immediateModels = ['Payment', 'StudentFee'];
        $immediateEvents = ['created'];

        return in_array($this->modelName, $immediateModels) &&
            in_array($this->eventType, $immediateEvents);
    }

    /**
     * Get a human-readable description of this event
     */
    public function getDescription(): string
    {
        $modelName = \Illuminate\Support\Str::title(\Illuminate\Support\Str::snake($this->modelName, ' '));
        $eventName = ucfirst($this->eventType);

        $descriptions = [
            'created' => "{$modelName} was created",
            'updated' => "{$modelName} was updated",
            'deleted' => "{$modelName} was deleted",
            'saving' => "{$modelName} is being saved",
            'saved' => "{$modelName} was saved",
            'creating' => "{$modelName} is being created",
            'updating' => "{$modelName} is being updated",
            'deleting' => "{$modelName} is being deleted",
        ];

        return $descriptions[$this->eventType] ?? "{$modelName} {$eventName} event occurred";
    }

    /**
     * Convert the event to an array for serialization
     */
    public function toArray(): array
    {
        return [
            'webhook_event_name' => $this->webhookEventName,
            'model_name' => $this->modelName,
            'event_type' => $this->eventType,
            'model_data' => $this->getModelData(),
            'triggered_by' => $this->getTriggeredByData(),
            'additional_data' => $this->additionalData,
            'context' => $this->context,
            'occurred_at' => $this->occurredAt->toIso8601String(),
            'priority' => $this->getPriority(),
            'should_notify_immediately' => $this->shouldNotifyImmediately(),
            'description' => $this->getDescription(),
        ];
    }

    /**
     * Get clean model data without Laravel internals
     */
    public function getCleanModelData(): array
    {
        if (! $this->model) {
            return [];
        }

        $data = $this->model->toArray();

        // Remove Laravel internal fields that aren't needed in webhooks
        $fieldsToRemove = [
            'pivot',
            'laravel_through_key',
            'created_at',
            'updated_at',
        ];

        foreach ($fieldsToRemove as $field) {
            unset($data[$field]);
        }

        // Keep only essential timestamp info
        if (isset($this->model->created_at)) {
            $data['created_at'] = $this->model->created_at->toISOString();
        }
        if (isset($this->model->updated_at)) {
            $data['updated_at'] = $this->model->updated_at->toISOString();
        }

        return $data;
    }

    /**
     * Get essential triggered by data without full user model
     */
    public function getCleanTriggeredByData(): ?array
    {
        if (! $this->triggeredBy) {
            return null;
        }

        return [
            'id' => $this->triggeredBy->id,
            'name' => $this->triggeredBy->name ?? 'Unknown User',
            'email' => $this->triggeredBy->email ?? null,
            'type' => get_class($this->triggeredBy),
        ];
    }

    /**
     * Get essential additional data without internal Laravel structures
     */
    public function getCleanAdditionalData(): array
    {
        $cleanData = [];

        // ✅ CHANGED: Updated allowed keys for component system
        $allowedKeys = [
            'receipt_urls',
            'formatted_amount',
            'payment_status',
            'fee_status',
            'student_status',
            'notification_sent',
            'metadata',
        ];

        foreach ($this->additionalData as $key => $value) {
            if (in_array($key, $allowedKeys)) {
                $cleanData[$key] = $value;
            }
        }

        return $cleanData;
    }

    /**
     * Convert the event to a clean, optimized array for webhook payloads
     */
    public function toOptimizedArray(): array
    {
        return [
            'id' => $this->model ? $this->model->getKey() : null,
            'action' => $this->eventType,
            'model_type' => $this->modelName,
            'model_data' => $this->getCleanModelData(),
            'triggered_by' => $this->getCleanTriggeredByData(),
            'additional_data' => $this->getCleanAdditionalData(),
            'occurred_at' => $this->occurredAt->toISOString(),
            'webhook_event_name' => $this->webhookEventName,
        ];
    }

    /**
     * ✅ CHANGED: Build payment-specific clean data for the component system
     */
    public function buildPaymentData(): array
    {
        if (! $this->model || ! ($this->model instanceof \App\Models\Payment)) {
            return [];
        }

        $payment = $this->model;

        $data = [
            'id' => $payment->id,
            'amount' => (float) $payment->amount,
            'formatted_amount' => '₹'.number_format($payment->amount, 2),
            'payment_method' => $payment->payment_method,
            'payment_date' => $payment->payment_date,
            'receipt_number' => $payment->receipt_number,
            'status' => 'completed',
            'payment_type' => $payment->payment_type,
        ];

        // Add student info if available
        if ($payment->student) {
            $student = $payment->student;
            $data['student'] = [
                'id' => $student->id,
                'name' => $student->name,
                'enrollment_number' => $student->enrollment_number,
                'email' => $student->email,
                'mobile' => $student->student_mobile,
            ];
        }

        // Add component details if it's a component payment
        if ($payment->isComponentPayment() && $payment->componentItems) {
            $data['components_paid'] = $payment->componentItems->map(function ($item) {
                return [
                    'student_fee_id' => $item->student_fee_id,
                    'category_name' => $item->studentFee->feeCategory->name ?? 'Unknown',
                    'amount_paid' => (float) $item->amount_paid,
                ];
            });
        }

        return $data;
    }

    /**
     * Build student-specific clean data
     */
    public function buildStudentData(): array
    {
        if (! $this->model || ! ($this->model instanceof \App\Models\Student)) {
            return [];
        }

        $student = $this->model;

        $data = [
            'id' => $student->id,
            'name' => $student->name,
            'email' => $student->email,
            'enrollment_number' => $student->enrollment_number,
            'mobile' => $student->student_mobile,
            'status' => $student->status,
            'admission_date' => $student->admission_date,
        ];

        // Add batch info if available
        if ($student->batch) {
            $data['batch'] = [
                'id' => $student->batch->id,
                'name' => $student->batch->name,
            ];
        }

        return $data;
    }

    /**
     * ✅ NEW: Build StudentFee-specific clean data
     */
    public function buildStudentFeeData(): array
    {
        if (! $this->model || ! ($this->model instanceof \App\Models\StudentFee)) {
            return [];
        }

        $studentFee = $this->model;

        $data = [
            'id' => $studentFee->id,
            'amount' => (float) $studentFee->amount,
            'paid_amount' => (float) $studentFee->paid_amount,
            'concession_amount' => (float) $studentFee->concession_amount,
            'remaining_amount' => (float) $studentFee->getRemainingAmount(),
            'status' => $studentFee->status,
            'due_date' => $studentFee->due_date,
            'academic_year' => $studentFee->academic_year,
            'installment' => "{$studentFee->installment_number} of {$studentFee->total_installments}",
        ];

        if ($studentFee->student) {
            $data['student'] = [
                'id' => $studentFee->student->id,
                'name' => $studentFee->student->name,
                'enrollment_number' => $studentFee->student->enrollment_number,
            ];
        }

        if ($studentFee->feeCategory) {
            $data['fee_category'] = [
                'id' => $studentFee->feeCategory->id,
                'name' => $studentFee->feeCategory->name,
            ];
        }

        return $data;
    }

    /**
     * ✅ NEW: Build StudentConcession-specific clean data
     */
    public function buildConcessionData(): array
    {
        if (! $this->model || ! ($this->model instanceof \App\Models\StudentConcession)) {
            return [];
        }

        $concession = $this->model;

        $data = [
            'id' => $concession->id,
            'concession_amount' => (float) $concession->concession_amount,
            'concession_type' => $concession->concession_type,
            'notes' => $concession->notes,
            'applied_at' => $concession->applied_at,
        ];

        if ($concession->student) {
            $data['student'] = [
                'id' => $concession->student->id,
                'name' => $concession->student->name,
            ];
        }

        if ($concession->feeCategory) {
            $data['fee_category'] = [
                'id' => $concession->feeCategory->id,
                'name' => $concession->feeCategory->name,
            ];
        }

        if ($concession->appliedBy) {
            $data['applied_by'] = [
                'id' => $concession->appliedBy->id,
                'name' => $concession->appliedBy->name,
            ];
        }

        return $data;
    }

    /**
     * Get model-specific optimized data based on model type
     */
    public function getModelSpecificData(): array
    {
        if (! $this->model) {
            return [];
        }

        $modelClass = get_class($this->model);

        // ✅ CHANGED: Updated switch statement for component models
        switch (class_basename($modelClass)) {
            case 'Payment':
                return $this->buildPaymentData();
            case 'Student':
                return $this->buildStudentData();
            case 'StudentFee':
                return $this->buildStudentFeeData();
            case 'StudentConcession':
                return $this->buildConcessionData();
            default:
                return $this->getCleanModelData();
        }
    }

    /**
     * Handle dynamic property access
     */
    public function __get(string $name)
    {
        // Allow access to model properties
        if (property_exists($this->model, $name)) {
            return $this->model->$name;
        }

        // Allow access to additional data
        if (isset($this->additionalData[$name])) {
            return $this->additionalData[$name];
        }

        return null;
    }

    /**
     * Handle dynamic property checking
     */
    public function __isset(string $name): bool
    {
        return property_exists($this->model, $name) || isset($this->additionalData[$name]);
    }

    /**
     * Get a string representation of the event
     */
    public function __toString(): string
    {
        return $this->getDescription();
    }
}
