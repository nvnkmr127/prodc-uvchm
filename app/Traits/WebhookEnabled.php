<?php

// App/Traits/WebhookEnabled.php - Fixed version to prevent infinite loops
namespace App\Traits;

use App\Events\EloquentWebhookEvent;
use Illuminate\Support\Str;

/**
 * Trait for Eloquent models to enable automatic webhook events
 * 
 * Add this trait to any model to automatically fire webhook events
 * when the model is created, updated, deleted, etc.
 */
trait WebhookEnabled
{
    /**
     * The webhook event name for this model instance
     */
    public ?string $webhookEventName = null;

    /**
     * Additional data to include in webhook payloads
     */
    protected array $webhookData = [];

    /**
     * Whether webhooks are enabled for this model instance
     */
    protected bool $webhooksEnabled = true;

    /**
     * Events that should trigger webhooks
     */
    protected array $webhookEvents = [
        'created',
        'updated',
        'deleted'
    ];

    /**
     * Events that should NOT trigger webhooks
     */
    protected array $webhookExcludedEvents = [];

    /**
     * Track if we're currently firing a webhook to prevent infinite loops
     */
    protected static array $firingWebhooks = [];

    /**
     * Maximum recursion depth allowed
     */
    protected static int $maxRecursionDepth = 3;

    /**
     * Current recursion depth
     */
    protected static array $recursionDepth = [];

    /**
     * Boot the webhook enabled trait
     */
    protected static function bootWebhookEnabled(): void
    {
        // Only listen to the main events to prevent infinite loops
        static::created(function ($model) {
            $model->handleWebhookEventSafely('created');
        });

        static::updated(function ($model) {
            $model->handleWebhookEventSafely('updated');
        });

        static::deleted(function ($model) {
            $model->handleWebhookEventSafely('deleted');
        });
    }

    /**
     * Handle a webhook event safely with loop prevention
     * FIXED VERSION - This method was not firing the actual event
     */
    protected function handleWebhookEventSafely(string $eventType): void
    {
        // Add debug logging
        \Log::channel('webhook-events')->debug('WebhookEnabled trait - handleWebhookEventSafely called', [
            'model' => get_class($this),
            'model_id' => $this->getKey(),
            'event_type' => $eventType,
            'webhooks_enabled' => $this->areWebhooksEnabled(),
        ]);

        // Check if webhooks are enabled for this instance
        if (!$this->areWebhooksEnabled()) {
            \Log::channel('webhook-events')->debug('Webhooks disabled for this model instance');
            return;
        }

        // Check if this event type should trigger webhooks
        if (!$this->shouldTriggerWebhook($eventType)) {
            \Log::channel('webhook-events')->debug('Event type should not trigger webhooks', ['event_type' => $eventType]);
            return;
        }

        // Prevent infinite loops
        $modelClass = get_class($this);
        $modelId = $this->getKey() ?? 'new';
        $eventKey = "{$modelClass}:{$modelId}:{$eventType}";

        if (isset(static::$firingWebhooks[$eventKey])) {
            \Log::channel('webhook-events')->warning('Webhook infinite loop prevented', [
                'model' => $modelClass,
                'id' => $modelId,
                'event' => $eventType,
            ]);
            return;
        }

        try {
            // Mark as firing
            static::$firingWebhooks[$eventKey] = true;

            // Set webhook event name if not already set
            if (!$this->webhookEventName) {
                $this->setWebhookEventName($eventType);
            }

            \Log::channel('webhook-events')->debug('About to fire EloquentWebhookEvent', [
                'webhook_event_name' => $this->webhookEventName,
                'model_class' => get_class($this),
                'model_id' => $this->getKey(),
            ]);

            // Create the synthetic webhook event
            $syntheticEvent = new \App\Events\EloquentWebhookEvent(
                $this,
                $eventType,
                class_basename(static::class),
                $this->getWebhookData()
            );

            // THIS IS THE KEY FIX: Actually fire the Laravel event
            event($syntheticEvent);

            \Log::channel('webhook-events')->debug('EloquentWebhookEvent fired successfully', [
                'event_name' => $syntheticEvent->webhookEventName,
            ]);

        } catch (\Exception $e) {
            \Log::channel('webhook-events')->error('Failed to fire webhook event', [
                'model' => get_class($this),
                'event_type' => $eventType,
                'error' => $e->getMessage(),
            ]);
        } finally {
            // Always clean up
            unset(static::$firingWebhooks[$eventKey]);
        }
    }

    /**
     * Check if this would create an infinite loop
     */
    protected function isInfiniteLoop(string $eventType): bool
    {
        $modelClass = get_class($this);
        $modelId = $this->getKey() ?? 'new';
        $eventKey = "{$modelClass}:{$modelId}:{$eventType}";

        // Check if we're already firing this exact event
        if (isset(static::$firingWebhooks[$eventKey])) {
            return true;
        }

        // Check recursion depth
        $currentDepth = static::$recursionDepth[$modelClass][$eventType] ?? 0;
        if ($currentDepth >= static::$maxRecursionDepth) {
            return true;
        }

        return false;
    }

    /**
     * Increment recursion depth tracking
     */
    protected function incrementRecursionDepth(string $eventType): void
    {
        $modelClass = get_class($this);

        if (!isset(static::$recursionDepth[$modelClass])) {
            static::$recursionDepth[$modelClass] = [];
        }

        if (!isset(static::$recursionDepth[$modelClass][$eventType])) {
            static::$recursionDepth[$modelClass][$eventType] = 0;
        }

        static::$recursionDepth[$modelClass][$eventType]++;
    }

    /**
     * Decrement recursion depth tracking
     */
    protected function decrementRecursionDepth(string $eventType): void
    {
        $modelClass = get_class($this);

        if (isset(static::$recursionDepth[$modelClass][$eventType])) {
            static::$recursionDepth[$modelClass][$eventType]--;

            if (static::$recursionDepth[$modelClass][$eventType] <= 0) {
                unset(static::$recursionDepth[$modelClass][$eventType]);
            }
        }
    }

    /**
     * Set the webhook event name for this model instance
     */
    public function setWebhookEventName(string $action): void
    {
        $modelName = strtolower(class_basename(static::class));

        // If action already starts with model name and a dot, don't prefix it
        if (str_starts_with($action, $modelName . '.')) {
            $this->webhookEventName = $action;
        } else {
            $this->webhookEventName = "{$modelName}.{$action}";
        }
    }

    /**
     * Fire a webhook event safely with loop prevention
     */
    public function fireWebhookEventSafely(string $action, array $additionalData = []): void
    {
        $modelClass = get_class($this);
        $modelId = $this->getKey() ?? 'new';
        $eventKey = "{$modelClass}:{$modelId}:{$action}";

        // Prevent duplicate firing
        if (isset(static::$firingWebhooks[$eventKey])) {
            return;
        }

        try {
            // Mark as firing
            static::$firingWebhooks[$eventKey] = true;

            // Set webhook event name if not already set
            if (!$this->webhookEventName) {
                $this->setWebhookEventName($action);
            }

            // Create the synthetic webhook event
            $syntheticEvent = new EloquentWebhookEvent(
                $this,
                $action,
                class_basename(static::class),
                array_merge($this->webhookData, $additionalData)
            );

            // Add any custom webhook data
            if (!empty($this->webhookData)) {
                $syntheticEvent->setAdditionalData($this->webhookData);
            }

            // Fire the event
            event($syntheticEvent);

        } finally {
            // Always clean up
            unset(static::$firingWebhooks[$eventKey]);
        }
    }

    /**
     * Fire a custom webhook event (public method for manual use)
     */
    public function fireWebhookEvent(string $action, array $additionalData = []): void
    {
        // Check if webhooks are enabled
        if (!$this->areWebhooksEnabled()) {
            return;
        }

        $this->fireWebhookEventSafely($action, $additionalData);
    }

    /**
     * Add data to be included in webhook payloads
     */
    public function addWebhookData(string $key, $value): self
    {
        $this->webhookData[$key] = $value;
        return $this;
    }

    /**
     * Set multiple webhook data items
     */
    public function setWebhookData(array $data): self
    {
        $this->webhookData = array_merge($this->webhookData, $data);
        return $this;
    }

    /**
     * Get webhook data for this model
     */
    public function getWebhookData(): array
    {
        $data = $this->webhookData;

        // Add computed webhook data
        $data = array_merge($data, $this->getComputedWebhookData());

        return $data;
    }

    /**
     * Get computed data that should be included in webhooks.
     * This version filters changes to respect the model's $hidden property.
     */
    protected function getComputedWebhookData(): array
    {
        $data = [];

        // Add model state information
        if (method_exists($this, 'wasRecentlyCreated')) {
            $data['was_recently_created'] = $this->wasRecentlyCreated;
        }

        if (method_exists($this, 'isDirty')) {
            $data['is_dirty'] = $this->isDirty();
        }

        // Only include changes if the model exists and has changes
        if ($this->exists && method_exists($this, 'getChanges')) {
            $changes = $this->getChanges();

            // Get the array representation of the model. Calling toArray()
            // automatically respects the $hidden and $visible properties.
            $visibleAttributes = $this->toArray();

            // Filter the changes to only include attributes that are visible.
            // This prevents leaking sensitive data.
            $safeChanges = array_intersect_key($changes, $visibleAttributes);

            if (!empty($safeChanges)) {
                $data['changes'] = $safeChanges;
            }
        }

        // Add timestamp information
        $data['webhook_triggered_at'] = now()->toIso8601String();

        // Add model-specific computed data
        if (method_exists($this, 'getCustomWebhookData')) {
            $customData = $this->getCustomWebhookData();
            if (is_array($customData)) {
                $data = array_merge($data, $customData);
            }
        }

        return $data;
    }

    /**
     * Check if webhooks are enabled for this model instance
     */
    public function areWebhooksEnabled(): bool
    {
        return $this->webhooksEnabled;
    }

    /**
     * Enable webhooks for this model instance
     */
    public function enableWebhooks(): self
    {
        $this->webhooksEnabled = true;
        return $this;
    }

    /**
     * Disable webhooks for this model instance
     */
    public function disableWebhooks(): self
    {
        $this->webhooksEnabled = false;
        return $this;
    }

    /**
     * Temporarily disable webhooks for a callback
     */
    public function withoutWebhooks(callable $callback)
    {
        $previousState = $this->webhooksEnabled;
        $this->webhooksEnabled = false;

        try {
            return $callback($this);
        } finally {
            $this->webhooksEnabled = $previousState;
        }
    }

    /**
     * Check if a specific event type should trigger webhooks
     */
    protected function shouldTriggerWebhook(string $eventType): bool
    {
        // Check if event is explicitly excluded
        if (in_array($eventType, $this->getWebhookExcludedEvents())) {
            return false;
        }

        // Check if event is in the allowed list
        return in_array($eventType, $this->getWebhookEvents());
    }

    /**
     * Get the list of events that should trigger webhooks
     */
    public function getWebhookEvents(): array
    {
        return property_exists($this, 'webhookEvents')
            ? $this->webhookEvents
            : ['created', 'updated', 'deleted'];
    }

    /**
     * Get the list of events that should NOT trigger webhooks
     */
    public function getWebhookExcludedEvents(): array
    {
        return property_exists($this, 'webhookExcludedEvents')
            ? $this->webhookExcludedEvents
            : [];
    }

    /**
     * Set which events should trigger webhooks
     */
    public function setWebhookEvents(array $events): self
    {
        $this->webhookEvents = $events;
        return $this;
    }

    /**
     * Fire a webhook event for a custom business logic event
     */
    public function fireBusinessEvent(string $eventName, array $data = []): void
    {
        $this->addWebhookData('business_event', $eventName);
        $this->addWebhookData('business_data', $data);

        $this->fireWebhookEvent($eventName, [
            'business_event' => true,
            'custom_event_name' => $eventName,
            'event_data' => $data
        ]);
    }

    /**
     * Get webhook configuration for this model
     */
    public function getWebhookConfig(): array
    {
        return [
            'model_class' => static::class,
            'model_name' => class_basename(static::class),
            'webhooks_enabled' => $this->webhooksEnabled,
            'webhook_events' => $this->getWebhookEvents(),
            'excluded_events' => $this->getWebhookExcludedEvents(),
            'webhook_data_keys' => array_keys($this->webhookData),
        ];
    }

    /**
     * Clear all recursion tracking (useful for testing)
     */
    public static function clearWebhookTracking(): void
    {
        static::$firingWebhooks = [];
        static::$recursionDepth = [];
    }

    /**
     * Get current webhook firing status (useful for debugging)
     */
    public static function getWebhookStatus(): array
    {
        return [
            'firing_webhooks' => static::$firingWebhooks,
            'recursion_depth' => static::$recursionDepth,
            'max_recursion_depth' => static::$maxRecursionDepth,
        ];
    }

    /**
     * Example method that models can override to provide custom webhook data
     */
    protected function getCustomWebhookData(): array
    {
        return [];
    }
}