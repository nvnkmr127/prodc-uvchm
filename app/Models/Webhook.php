<?php

// App/Models/Webhook.php - Updated with universal event discovery
namespace App\Models;

use App\Services\EventDiscoveryService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\WebhookEnabled;

class Webhook extends Model
{
    use WebhookEnabled;
    use HasFactory;

    protected $fillable = [
        'event_name',
        'url', 
        'is_active',
        'description',
     
        'retry_count',
        'timeout_seconds',
        'headers',
        'last_called_at',
        'last_success_at',
        'last_failure_at',
        'consecutive_failures',
        'auto_disable_after_failures',
        'signing_secret',
        'max_failures_before_disable'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_called_at' => 'datetime',
        'last_success_at' => 'datetime',
        'last_failure_at' => 'datetime',
        'headers' => 'array',
        'auto_disable_after_failures' => 'boolean',
    ];

    protected $attributes = [
        'timeout_seconds' => 30,
        'retry_count' => 0,
        'consecutive_failures' => 0,
        'auto_disable_after_failures' => true,
        'max_failures_before_disable' => 10,
    ];

    /**
     * Get all available events using the discovery service
     */
    public static function getAvailableEvents(): array
    {
        return EventDiscoveryService::getAvailableEvents();
    }

    /**
     * Get events grouped by category
     */
    public static function getEventCategories(): array
    {
        return EventDiscoveryService::getEventsByCategory();
    }

    /**
     * Get events for dropdown with wildcard option
     */
    public static function getEventsForDropdown(): array
    {
        $events = self::getAvailableEvents();
        $categories = self::getEventCategories();
        
        $dropdown = [];
        
        // Add wildcard option
        $dropdown['*'] = [
            'name' => 'All Events (Wildcard)',
            'description' => 'Listen to all events in the application',
            'category' => ['name' => 'Universal', 'icon' => 'fas fa-globe', 'emoji' => '🌐'],
            'icon' => 'fas fa-globe'
        ];
        
        // Add categorized events
        foreach ($categories as $categoryName => $categoryData) {
            foreach ($categoryData['events'] as $eventKey => $eventData) {
                $dropdown[$eventKey] = $eventData;
            }
        }
        
        return $dropdown;
    }

    /**
     * Sync available events from discovery service
     */
    public static function syncAvailableEvents(): array
    {
        $service = app(EventDiscoveryService::class);
        return $service->syncWithWebhookSystem();
    }

    /**
     * Scope for active webhooks only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific event types (including wildcard)
     */
    public function scopeForEvent($query, string $eventName)
    {
        return $query->where(function($q) use ($eventName) {
            $q->where('event_name', $eventName)
              ->orWhere('event_name', '*'); // Include wildcard webhooks
        });
    }

    /**
     * Scope for healthy webhooks (not disabled due to failures)
     */
    public function scopeHealthy($query)
    {
        return $query->where(function($q) {
            $q->where('auto_disable_after_failures', false)
              ->orWhere('consecutive_failures', '<', 'max_failures_before_disable');
        });
    }

    /**
     * Mark webhook as called successfully
     */
    public function markAsSuccessful()
    {
        $this->update([
            'last_called_at' => now(),
            'last_success_at' => now(),
            'consecutive_failures' => 0,
        ]);
    }

    /**
     * Mark webhook as failed
     */
    public function markAsFailed()
    {
        $consecutiveFailures = $this->consecutive_failures + 1;
        
        $updateData = [
            'last_called_at' => now(),
            'last_failure_at' => now(),
            'consecutive_failures' => $consecutiveFailures,
        ];

        // Auto-disable if threshold reached
        if ($this->auto_disable_after_failures && 
            $consecutiveFailures >= $this->max_failures_before_disable) {
            $updateData['is_active'] = false;
        }

        $this->update($updateData);
    }

    /**
     * Legacy method for backward compatibility
     */
    public function markAsCalled()
    {
        $this->update(['last_called_at' => now()]);
    }

    /**
     * Increment retry count
     */
    public function incrementRetryCount()
    {
        $this->increment('retry_count');
    }

    /**
     * Reset failure count
     */
    public function resetFailures()
    {
        $this->update([
            'consecutive_failures' => 0,
            'is_active' => true
        ]);
    }

    /**
     * Get webhook health status
     */
    public function getHealthStatus(): array
    {
        $totalCalls = $this->calls()->count();
        $successfulCalls = $this->calls()->where('success', true)->count();
        $failedCalls = $this->calls()->where('success', false)->count();
        
        $successRate = $totalCalls > 0 ? ($successfulCalls / $totalCalls) * 100 : 0;
        
        $status = 'healthy';
        if ($this->consecutive_failures >= 5) {
            $status = 'warning';
        }
        if (!$this->is_active || $this->consecutive_failures >= $this->max_failures_before_disable) {
            $status = 'critical';
        }

        return [
            'status' => $status,
            'total_calls' => $totalCalls,
            'successful_calls' => $successfulCalls,
            'failed_calls' => $failedCalls,
            'success_rate' => round($successRate, 1),
            'consecutive_failures' => $this->consecutive_failures,
            'last_called' => $this->last_called_at?->diffForHumans(),
            'last_success' => $this->last_success_at?->diffForHumans(),
            'last_failure' => $this->last_failure_at?->diffForHumans(),
        ];
    }

    /**
     * Get event information from discovery service
     */
    public function getEventInfo(): array
    {
        $events = self::getAvailableEvents();
        
        // Handle wildcard webhooks
        if ($this->event_name === '*') {
            return [
                'name' => 'All Events (Wildcard)',
                'description' => 'Listens to all events in the application',
                'category' => 'Universal',
                'icon' => 'fas fa-globe'
            ];
        }
        
        return $events[$this->event_name] ?? [
            'name' => $this->formatEventName($this->event_name),
            'description' => 'Auto-discovered event',
            'category' => 'Unknown',
            'icon' => 'fas fa-question'
        ];
    }

    /**
     * Format event name for display
     */
    protected function formatEventName(string $eventName): string
    {
        return \Illuminate\Support\Str::title(str_replace(['.', '_'], ' ', $eventName));
    }

    /**
     * Get formatted URL for display
     */
    public function getDisplayUrlAttribute(): string
    {
        if (strlen($this->url) > 50) {
            return substr($this->url, 0, 47) . '...';
        }
        return $this->url;
    }

    /**
     * Get status badge HTML
     */
    public function getStatusBadgeAttribute(): string
    {
        $health = $this->getHealthStatus();
        
        $badgeClass = match($health['status']) {
            'healthy' => 'badge-success',
            'warning' => 'badge-warning', 
            'critical' => 'badge-danger',
            default => 'badge-secondary'
        };

        $statusText = $this->is_active ? 'Active' : 'Inactive';
        
        return "<span class='badge {$badgeClass}'>{$statusText}</span>";
    }

    /**
     * Relationship to webhook calls
     */
    public function calls(): HasMany
    {
        return $this->hasMany(WebhookCall::class)->latest();
    }

    /**
     * Get recent calls for quick overview
     */
    public function recentCalls()
    {
        return $this->calls()->limit(5);
    }

    /**
     * Get average response time
     */
    public function getAverageResponseTime(): float
    {
        return $this->calls()
                   ->where('success', true)
                   ->avg('execution_time_ms') ?? 0;
    }

    /**
     * Check if webhook URL is reachable
     */
    public function isReachable(): bool
    {
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(5)
                ->get($this->url);
            return $response->status() < 500;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Generate a secure secret key
     */
    public static function generateSecretKey(): string
    {
        return 'whsec_' . bin2hex(random_bytes(32));
    }

    /**
     * Test if an event exists in the discovery system
     */
    public function eventExists(): bool
    {
        if ($this->event_name === '*') {
            return true; // Wildcard always exists
        }
        
        $events = self::getAvailableEvents();
        return isset($events[$this->event_name]);
    }

    /**
     * Get suggested events based on URL or description
     */
    public static function getSuggestedEvents(string $url = '', string $description = ''): array
    {
        $events = self::getAvailableEvents();
        $suggestions = [];
        
        // Simple keyword matching for suggestions
        $keywords = array_filter(array_merge(
            explode(' ', strtolower($description)),
            explode('/', parse_url($url, PHP_URL_PATH) ?? '')
        ));
        
        foreach ($events as $eventKey => $eventData) {
            $score = 0;
            
            foreach ($keywords as $keyword) {
                if (stripos($eventKey, $keyword) !== false) {
                    $score += 3;
                }
                if (stripos($eventData['description'], $keyword) !== false) {
                    $score += 2;
                }
                if (stripos($eventData['name'], $keyword) !== false) {
                    $score += 1;
                }
            }
            
            if ($score > 0) {
                $suggestions[$eventKey] = [
                    'event' => $eventData,
                    'score' => $score,
                    'match_reason' => $this->getMatchReason($keywords, $eventKey, $eventData)
                ];
            }
        }
        
        // Sort by score and return top 5
        uasort($suggestions, fn($a, $b) => $b['score'] <=> $a['score']);
        
        return array_slice($suggestions, 0, 5, true);
    }

    /**
     * Get the reason why an event was suggested
     */
    protected static function getMatchReason(array $keywords, string $eventKey, array $eventData): string
    {
        foreach ($keywords as $keyword) {
            if (stripos($eventKey, $keyword) !== false) {
                return "Event name contains '{$keyword}'";
            }
            if (stripos($eventData['name'], $keyword) !== false) {
                return "Display name contains '{$keyword}'";
            }
            if (stripos($eventData['description'], $keyword) !== false) {
                return "Description mentions '{$keyword}'";
            }
        }
        
        return "Keyword match";
    }

    /**
     * Get webhook statistics
     */
    public static function getSystemStats(): array
    {
        return [
            'total_webhooks' => self::count(),
            'active_webhooks' => self::where('is_active', true)->count(),
            'failing_webhooks' => self::where('consecutive_failures', '>=', 3)->count(),
            'total_events_available' => count(self::getAvailableEvents()),
            'total_categories' => count(self::getEventCategories()),
            'calls_today' => \App\Models\WebhookCall::whereDate('created_at', today())->count(),
            'success_rate_today' => self::getTodaySuccessRate(),
        ];
    }

    /**
     * Get today's success rate
     */
    protected static function getTodaySuccessRate(): float
    {
        $callsToday = \App\Models\WebhookCall::whereDate('created_at', today())->count();
        
        if ($callsToday === 0) {
            return 100.0;
        }
        
        $successfulToday = \App\Models\WebhookCall::whereDate('created_at', today())
                                                  ->where('success', true)
                                                  ->count();
        
        return round(($successfulToday / $callsToday) * 100, 1);
    }

    /**
     * Auto-discover and create webhooks for common integrations
     */
    public static function createCommonIntegrations(): array
    {
        $commonIntegrations = [
            [
                'name' => 'Payment Notifications',
                'events' => ['payment.created', 'invoice.generated'],
                'description' => 'Get notified when payments are made or invoices are generated'
            ],
            [
                'name' => 'Student Management',
                'events' => ['student.created', 'admission.approved'],
                'description' => 'Track new students and approved admissions'
            ],
            [
                'name' => 'Lead Management', 
                'events' => ['enquiry.created'],
                'description' => 'Capture new leads from enquiry forms'
            ],
            [
                'name' => 'Complete Monitoring',
                'events' => ['*'],
                'description' => 'Listen to all events in the system'
            ]
        ];
        
        return $commonIntegrations;
    }

    /**
     * Boot method to set default secret key and register event discovery
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($webhook) {
            if (empty($webhook->signing_secret)) {
                $webhook->signing_secret = self::generateSecretKey();
            }
        });

        // Ensure event discovery is cached when webhooks are accessed
        static::retrieved(function ($webhook) {
            // Trigger event discovery caching if not already cached
            if (!cache()->has('webhook_available_events')) {
                self::getAvailableEvents();
            }
        });
    }
}