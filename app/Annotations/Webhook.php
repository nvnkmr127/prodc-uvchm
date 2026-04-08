<?php

// App/Annotations/Webhook.php

namespace App\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * Webhook annotation for marking methods or classes that should trigger webhook events
 * * Usage examples:
 *
 * * @Webhook("payment.processed", description="Payment was successfully processed", priority="high")
 * public function processPayment() { ... }
 * * @Webhook(event="student.graduated", description="Student completed their course", category="academic", immediate=true)
 * public function graduateStudent() { ... }
 *
 * * @Annotation
 *
 * @Target({"CLASS", "METHOD"})
 */
class Webhook
{
    /**
     * The webhook event name (e.g., "payment.created", "student.updated")
     */
    public string $event;

    /**
     * Human-readable description of the event
     */
    public string $description = '';

    /**
     * Event category (financial, academic, student, etc.)
     */
    public string $category = '';

    /**
     * Priority level: low, medium, high
     */
    public string $priority = 'medium';

    /**
     * Whether this event should trigger immediate notifications
     */
    public bool $immediate = false;

    /**
     * Whether this event is enabled by default
     */
    public bool $enabled = true;

    /**
     * Additional metadata for the event
     */
    public array $metadata = [];

    /**
     * Data that should be included in the webhook payload
     */
    public array $includeData = [];

    /**
     * Data that should be excluded from the webhook payload
     */
    public array $excludeData = [];

    /**
     * Conditional logic for when this webhook should fire
     */
    public string $condition = '';

    /**
     * Rate limiting configuration
     */
    public array $rateLimit = [];

    /**
     * Retry configuration for failed webhooks
     */
    public array $retryConfig = [];

    /**
     * Constructor to handle annotation parameters
     */
    public function __construct(array $values = [])
    {
        // Handle shorthand syntax: @Webhook("event.name")
        if (isset($values['value']) && is_string($values['value'])) {
            $this->event = $values['value'];
            unset($values['value']);
        }

        // Handle named parameters
        foreach ($values as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }

        // Validate required fields
        if (empty($this->event)) {
            throw new \InvalidArgumentException('Webhook annotation requires an event name');
        }

        // Auto-generate description if not provided
        if (empty($this->description)) {
            $this->description = $this->generateDefaultDescription();
        }

        // Auto-determine category if not provided
        if (empty($this->category)) {
            $this->category = $this->determineCategory();
        }
    }

    /**
     * Generate a default description based on the event name
     */
    protected function generateDefaultDescription(): string
    {
        $parts = explode('.', $this->event);

        if (count($parts) >= 2) {
            $subject = ucfirst($parts[0]);
            $action = ucfirst($parts[1]);

            return "Triggered when {$subject} is {$action}";
        }

        return "Webhook event: {$this->event}";
    }

    /**
     * Determine category based on event name
     */
    protected function determineCategory(): string
    {
        $eventLower = strtolower($this->event);

        $categories = [
            // MODIFIED: Removed 'invoice' from the keywords for the financial category.
            'financial' => ['payment', 'receipt', 'fee', 'billing', 'refund'],
            'student' => ['student', 'admission', 'enrollment', 'certificate', 'graduation'],
            'academic' => ['attendance', 'exam', 'grade', 'assignment', 'course', 'subject'],
            'hr' => ['leave', 'payroll', 'staff', 'faculty', 'employee'],
            'communication' => ['notification', 'email', 'sms', 'announcement'],
            'inventory' => ['asset', 'equipment', 'maintenance', 'audit'],
            'system' => ['backup', 'login', 'security', 'error', 'maintenance'],
        ];

        foreach ($categories as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($eventLower, $keyword)) {
                    return $category;
                }
            }
        }

        return 'general';
    }

    /**
     * Get the webhook configuration as an array
     */
    public function toArray(): array
    {
        return [
            'event' => $this->event,
            'description' => $this->description,
            'category' => $this->category,
            'priority' => $this->priority,
            'immediate' => $this->immediate,
            'enabled' => $this->enabled,
            'metadata' => $this->metadata,
            'include_data' => $this->includeData,
            'exclude_data' => $this->excludeData,
            'condition' => $this->condition,
            'rate_limit' => $this->rateLimit,
            'retry_config' => $this->retryConfig,
        ];
    }

    /**
     * Check if this webhook should fire based on conditions
     */
    public function shouldFire(array $context = []): bool
    {
        if (! $this->enabled) {
            return false;
        }

        // Check custom condition if provided
        if (! empty($this->condition)) {
            return $this->evaluateCondition($context);
        }

        return true;
    }

    /**
     * Evaluate the custom condition
     */
    protected function evaluateCondition(array $context): bool
    {
        // Simple condition evaluation
        // In a real implementation, you might use a more sophisticated expression evaluator

        if (empty($this->condition)) {
            return true;
        }

        // Example conditions:
        // "amount > 1000"
        // "status == 'active'"
        // "user.role in ['admin', 'manager']"

        try {
            // This is a simplified example - in production, use a proper expression evaluator
            // like symfony/expression-language

            // Replace context variables in the condition
            $condition = $this->condition;
            foreach ($context as $key => $value) {
                $condition = str_replace($key, var_export($value, true), $condition);
            }

            // Evaluate the condition (DANGER: This is unsafe for production use)
            // In production, use a proper expression evaluator
            return eval("return {$condition};");

        } catch (\Exception $e) {
            // If condition evaluation fails, don't fire the webhook
            return false;
        }
    }

    /**
     * Get filtered data based on include/exclude rules
     */
    public function filterData(array $data): array
    {
        $filtered = $data;

        // Apply include filter if specified
        if (! empty($this->includeData)) {
            $filtered = array_intersect_key($filtered, array_flip($this->includeData));
        }

        // Apply exclude filter
        if (! empty($this->excludeData)) {
            $filtered = array_diff_key($filtered, array_flip($this->excludeData));
        }

        return $filtered;
    }

    /**
     * Check if rate limiting allows this webhook to fire
     */
    public function checkRateLimit(string $identifier): bool
    {
        if (empty($this->rateLimit)) {
            return true;
        }

        $limit = $this->rateLimit['limit'] ?? 10;
        $window = $this->rateLimit['window'] ?? 60; // seconds

        $cacheKey = "webhook_rate_limit:{$this->event}:{$identifier}";
        $current = cache()->get($cacheKey, 0);

        if ($current >= $limit) {
            return false;
        }

        cache()->put($cacheKey, $current + 1, $window);

        return true;
    }

    /**
     * Get retry configuration for this webhook
     */
    public function getRetryConfig(): array
    {
        return array_merge([
            'max_attempts' => 3,
            'backoff_multiplier' => 2,
            'initial_delay' => 1, // seconds
            'max_delay' => 300, // 5 minutes
        ], $this->retryConfig);
    }

    /**
     * Validate the annotation configuration
     */
    public function validate(): array
    {
        $errors = [];

        // Validate event name format
        if (! preg_match('/^[a-z][a-z0-9]*(\.[a-z][a-z0-9]*)*$/', $this->event)) {
            $errors[] = "Event name '{$this->event}' should be lowercase.dot.separated format";
        }

        // Validate priority
        if (! in_array($this->priority, ['low', 'medium', 'high'])) {
            $errors[] = 'Priority must be one of: low, medium, high';
        }

        // Validate rate limit configuration
        if (! empty($this->rateLimit)) {
            if (! isset($this->rateLimit['limit']) || ! is_int($this->rateLimit['limit'])) {
                $errors[] = "Rate limit 'limit' must be an integer";
            }
            if (! isset($this->rateLimit['window']) || ! is_int($this->rateLimit['window'])) {
                $errors[] = "Rate limit 'window' must be an integer (seconds)";
            }
        }

        // Validate retry configuration
        if (! empty($this->retryConfig)) {
            $validKeys = ['max_attempts', 'backoff_multiplier', 'initial_delay', 'max_delay'];
            foreach ($this->retryConfig as $key => $value) {
                if (! in_array($key, $validKeys)) {
                    $errors[] = "Invalid retry config key: {$key}";
                }
                if (! is_numeric($value)) {
                    $errors[] = "Retry config '{$key}' must be numeric";
                }
            }
        }

        return $errors;
    }

    /**
     * Create an annotation from a simple string
     */
    public static function fromString(string $definition): self
    {
        // Parse simple definitions like:
        // "payment.created"
        // "payment.created:Payment was processed"
        // "payment.created:Payment was processed:high"

        $parts = explode(':', $definition);
        $event = trim($parts[0]);
        $description = isset($parts[1]) ? trim($parts[1]) : '';
        $priority = isset($parts[2]) ? trim($parts[2]) : 'medium';

        return new self([
            'event' => $event,
            'description' => $description,
            'priority' => $priority,
        ]);
    }

    /**
     * Create multiple annotations from a configuration array
     */
    public static function fromArray(array $config): array
    {
        $annotations = [];

        foreach ($config as $event => $settings) {
            if (is_string($settings)) {
                $annotations[] = self::fromString("{$event}:{$settings}");
            } elseif (is_array($settings)) {
                $annotations[] = new self(array_merge(['event' => $event], $settings));
            }
        }

        return $annotations;
    }

    /**
     * Get all available categories
     */
    public static function getAvailableCategories(): array
    {
        return [
            'financial' => 'Financial Events',
            'student' => 'Student Management',
            'academic' => 'Academic Events',
            'hr' => 'HR Management',
            'communication' => 'Communication',
            'inventory' => 'Inventory Management',
            'system' => 'System Events',
            'general' => 'General Events',
        ];
    }

    /**
     * Get all available priorities
     */
    public static function getAvailablePriorities(): array
    {
        return [
            'low' => 'Low Priority',
            'medium' => 'Medium Priority',
            'high' => 'High Priority',
        ];
    }

    /**
     * Magic method for string representation
     */
    public function __toString(): string
    {
        return $this->event.($this->description ? ": {$this->description}" : '');
    }

    /**
     * Get icon for the event category
     */
    public function getCategoryIcon(): string
    {
        $icons = [
            'financial' => 'fas fa-money-bill-wave',
            'student' => 'fas fa-user-graduate',
            'academic' => 'fas fa-book',
            'hr' => 'fas fa-users',
            'communication' => 'fas fa-bell',
            'inventory' => 'fas fa-boxes',
            'system' => 'fas fa-cog',
            'general' => 'fas fa-bell',
        ];

        return $icons[$this->category] ?? 'fas fa-bell';
    }

    /**
     * Get emoji for the event category
     */
    public function getCategoryEmoji(): string
    {
        $emojis = [
            'financial' => '💰',
            'student' => '👨‍🎓',
            'academic' => '📚',
            'hr' => '👥',
            'communication' => '📱',
            'inventory' => '📦',
            'system' => '⚙️',
            'general' => '📋',
        ];

        return $emojis[$this->category] ?? '📋';
    }

    /**
     * Check if this is a high-priority event
     */
    public function isHighPriority(): bool
    {
        return $this->priority === 'high' || $this->immediate;
    }

    /**
     * Get the webhook payload template
     */
    public function getPayloadTemplate(): array
    {
        return [
            'event' => $this->event,
            'event_id' => '{{event_id}}',
            'created_at' => '{{timestamp}}',
            'priority' => $this->priority,
            'immediate' => $this->immediate,
            'category' => $this->category,
            'description' => $this->description,
            'data' => '{{filtered_data}}',
            'metadata' => $this->metadata,
        ];
    }
}
