<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Webhook;
use App\Models\WebhookCall;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class WebhookController extends Controller
{
    /**
     * Display a listing of webhooks with health status
     */
    public function index(Request $request)
    {
        try {
            $query = \App\Models\Webhook::query();

            // Filter by event type
            if ($request->filled('event_type')) {
                $query->where('event_name', $request->event_type);
            }

            // Filter by status
            if ($request->filled('status')) {
                if ($request->status === 'active') {
                    $query->where('is_active', true);
                } elseif ($request->status === 'inactive') {
                    $query->where('is_active', false);
                } elseif ($request->status === 'failing') {
                    $query->where('consecutive_failures', '>=', 3);
                }
            }

            $webhooks = $query->orderBy('created_at', 'desc')->paginate(20);

            // Safely get available events
            $eventTypes = [];
            $categories = [];
            
            try {
                // Try to get events from the model, with fallback
                if (method_exists(\App\Models\Webhook::class, 'getAvailableEvents')) {
                    $eventTypes = \App\Models\Webhook::getAvailableEvents();
                }
                
                if (method_exists(\App\Models\Webhook::class, 'getEventCategories')) {
                    $categories = \App\Models\Webhook::getEventCategories();
                }
            } catch (\Exception $e) {
                // Fallback to basic event types
                $eventTypes = [
                    'payment.created' => ['name' => 'Payment Created', 'description' => 'When a payment is made'],
                    'student.created' => ['name' => 'Student Created', 'description' => 'When a student is added'],
                    'enquiry.created' => ['name' => 'Enquiry Created', 'description' => 'When an enquiry is submitted'],
                    'invoice.generated' => ['name' => 'Invoice Generated', 'description' => 'When an invoice is created'],
                ];
                
                $categories = [
                    'Financial' => '💰',
                    'Student Management' => '👨‍🎓',
                    'Lead Management' => '📞',
                ];
            }

            // Get statistics safely
            $stats = $this->getWebhookStats();

            return view('admin.webhooks.index', compact('webhooks', 'eventTypes', 'categories', 'stats'));

        } catch (\Exception $e) {
            // Log the error
            \Log::error('Webhook index error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            // Return a safe fallback view
            $webhooks = collect([]); // Empty collection
            $eventTypes = [];
            $categories = [];
            $stats = [
                'total' => 0,
                'active' => 0,
                'failing' => 0,
                'total_calls_today' => 0,
            ];

            return view('admin.webhooks.index', compact('webhooks', 'eventTypes', 'categories', 'stats'))
                   ->with('error', 'There was an issue loading webhooks. Please check the logs.');
        }
    }

    protected function getWebhookStats(): array
    {
        try {
            return [
                'total' => \App\Models\Webhook::count(),
                'active' => \App\Models\Webhook::where('is_active', true)->count(),
                'failing' => \App\Models\Webhook::where('consecutive_failures', '>=', 3)->count(),
                'total_calls_today' => \App\Models\WebhookCall::whereDate('created_at', today())->count(),
            ];
        } catch (\Exception $e) {
            return [
                'total' => 0,
                'active' => 0,
                'failing' => 0,
                'total_calls_today' => 0,
            ];
        }
    }

    /**
     * Show the form for creating a new webhook
     */
    public function create()
    {
        $eventTypes = Webhook::getAvailableEvents();
        $categories = Webhook::getEventCategories();
        
        return view('admin.webhooks.create', [
            'eventTypes' => $eventTypes ?? [],
            'eventCategories' => $categories ?? []
        ]);
    }

    /**
     * Store a newly created webhook
     */
    public function store(Request $request)
    {
        $request->validate([
            'url' => 'required|url|unique:webhooks,url',
            'event_name' => [
                'required',
                'string',
                Rule::in(array_keys(Webhook::getAvailableEvents()))
            ],
            'description' => 'nullable|string|max:500',
            'timeout_seconds' => 'nullable|integer|min:5|max:120',
            'is_active' => 'boolean',
        ]);

        $webhook = Webhook::create([
            'url' => $request->url,
            'event_name' => $request->event_name,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active', true),
            'signing_secret' => Webhook::generateSecretKey(),
        ]);
        
        return redirect()->route('admin.webhooks.index')
                        ->with('success', 'Webhook created successfully! Secret key has been generated.');
    }

    /**
     * Display the specified webhook with detailed analytics
     */
    public function show(Webhook $webhook)
    {
        $webhook->load(['calls' => function($query) {
            $query->latest()->limit(50);
        }]);

        // Get health status safely
        try {
            $healthStatus = $webhook->getHealthStatus();
        } catch (\Exception $e) {
            $healthStatus = [
                'status' => 'unknown',
                'message' => 'Could not determine health status'
            ];
        }

        // Get event info safely with fallback icon
        try {
            $eventInfo = $webhook->getEventInfo();
            
            // Ensure eventInfo has all required keys with safe defaults
            $eventInfo = array_merge([
                'name' => $webhook->event_name ?? 'Unknown Event',
                'description' => 'No description available',
                'category' => 'General',
                'icon' => 'fas fa-bell', // Default icon
            ], $eventInfo ?? []);
            
        } catch (\Exception $e) {
            // Complete fallback if getEventInfo fails
            $eventInfo = [
                'name' => $webhook->event_name ?? 'Unknown Event',
                'description' => 'Event information not available',
                'category' => 'General',
                'icon' => 'fas fa-bell',
            ];
        }
        
        // Get call statistics safely
        try {
            $callStats = WebhookCall::where('webhook_id', $webhook->id)
                ->where('created_at', '>=', now()->subDays(30))
                ->selectRaw('DATE(created_at) as date, COUNT(*) as total_calls, SUM(success) as successful_calls')
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        } catch (\Exception $e) {
            $callStats = collect([]);
        }

        return view('admin.webhooks.show', compact('webhook', 'healthStatus', 'eventInfo', 'callStats'));
    }

    /**
     * Show the form for editing the webhook
     */
    public function edit(Webhook $webhook)
    {
        // Ensure webhook has string properties
        $webhook->url = is_string($webhook->url) ? $webhook->url : '';
        $webhook->event_name = is_string($webhook->event_name) ? $webhook->event_name : '';
        $webhook->description = is_string($webhook->description) ? $webhook->description : '';
        
        try {
            $eventTypes = Webhook::getAvailableEvents();
            $eventCategories = Webhook::getEventCategories();
        } catch (\Exception $e) {
            \Log::error('Webhook edit error: ' . $e->getMessage());
            
            // Fallback data - completely safe
            $eventTypes = [
                'payment.created' => [
                    'name' => 'Payment Created', 
                    'description' => 'When a payment is made',
                    'category' => 'Financial'
                ],
                'student.created' => [
                    'name' => 'Student Created', 
                    'description' => 'When a student is added',
                    'category' => 'Student Management'
                ],
                'enquiry.created' => [
                    'name' => 'Enquiry Created', 
                    'description' => 'When an enquiry is submitted',
                    'category' => 'Lead Management'
                ],
                'invoice.generated' => [
                    'name' => 'Invoice Generated', 
                    'description' => 'When an invoice is created',
                    'category' => 'Financial'
                ],
            ];
            
            $eventCategories = [
                'Financial' => [
                    'icon' => '💰',
                    'events' => [
                        'payment.created' => $eventTypes['payment.created'],
                        'invoice.generated' => $eventTypes['invoice.generated'],
                    ]
                ],
                'Student Management' => [
                    'icon' => '👨‍🎓',
                    'events' => [
                        'student.created' => $eventTypes['student.created'],
                    ]
                ],
                'Lead Management' => [
                    'icon' => '📞',
                    'events' => [
                        'enquiry.created' => $eventTypes['enquiry.created'],
                    ]
                ],
            ];
        }
        
        // Get current event info safely
        $currentEventInfo = null;
        if ($webhook->event_name && is_array($eventTypes) && isset($eventTypes[$webhook->event_name])) {
            $currentEventInfo = $eventTypes[$webhook->event_name];
        }
        
        // Get recent deliveries safely
        try {
            $recentDeliveries = $webhook->calls()
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        } catch (\Exception $e) {
            $recentDeliveries = collect([]);
        }
        
        // Ensure all data is safe before passing to view
        $viewData = [
            'webhook' => $webhook,
            'eventTypes' => is_array($eventTypes) ? $eventTypes : [],
            'eventCategories' => is_array($eventCategories) ? $eventCategories : [],
            'currentEventInfo' => is_array($currentEventInfo) ? $currentEventInfo : null,
            'recentDeliveries' => $recentDeliveries ?? collect([])
        ];
        
        return view('admin.webhooks.edit', $viewData);
    }

    /**
     * Update the specified webhook
     */
    public function update(Request $request, Webhook $webhook)
    {
        $request->validate([
            'url' => [
                'required',
                'url',
                Rule::unique('webhooks', 'url')->ignore($webhook->id)
            ],
            'event_name' => [
                'required',
                'string',
                Rule::in(array_keys(Webhook::getAvailableEvents()))
            ],
            'description' => 'nullable|string|max:500',
            'timeout_seconds' => 'nullable|integer|min:5|max:120',
            'is_active' => 'boolean',
            'max_failures_before_disable' => 'nullable|integer|min:1|max:50',
            'auto_disable_after_failures' => 'boolean',
        ]);

        $webhook->update($request->only([
            'url', 'event_name', 'description', 'timeout_seconds', 'is_active',
            'max_failures_before_disable', 'auto_disable_after_failures'
        ]));

        return redirect()->route('admin.webhooks.index')
                        ->with('success', 'Webhook updated successfully!');
    }

    /**
     * Remove the specified webhook
     */
    public function destroy(Webhook $webhook)
    {
        // Delete associated calls first
        $webhook->calls()->delete();
        $webhook->delete();

        return redirect()->route('admin.webhooks.index')
                        ->with('success', 'Webhook deleted successfully!');
    }

    /**
     * Toggle the active status of a webhook
     */
    public function toggle(Webhook $webhook)
    {
        $webhook->update(['is_active' => !$webhook->is_active]);
        
        // Reset failure count when reactivating
        if ($webhook->is_active) {
            $webhook->resetFailures();
        }
        
        $status = $webhook->is_active ? 'activated' : 'deactivated';
        
        return redirect()->route('admin.webhooks.index')
                        ->with('success', "Webhook has been {$status}.");
    }

    /**
     * Send a test payload to the webhook endpoint
     */
    public function test(Request $request, Webhook $webhook)
    {
        try {
            $eventInfo = $webhook->getEventInfo();
        } catch (\Exception $e) {
            $eventInfo = ['name' => 'Test Event'];
        }

        $payload = [
            'event' => 'test.webhook',
            'event_id' => uniqid('test_evt_'),
            'created_at' => now()->toIso8601String(),
            'app_name' => config('app.name'),
            'data' => [
                'message' => 'This is a test webhook notification from the admin panel.',
                'webhook_id' => $webhook->id,
                'test_timestamp' => now()->toIso8601String(),
            ]
        ];

        $startTime = microtime(true);

        try {
            $signature = hash_hmac('sha256', json_encode($payload), $webhook->signing_secret ?? '');

            $response = Http::timeout($webhook->timeout_seconds ?? 30)
                ->withHeaders(['X-App-Signature' => $signature])
                ->post($webhook->url, $payload);

            $executionTime = round((microtime(true) - $startTime) * 1000);

            // Log the test call
            $webhook->calls()->create([
                'success' => $response->successful(),
                'status_code' => $response->status(),
                'payload' => $payload,
                'response_body' => $response->body(),
                'execution_time_ms' => $executionTime,
            ]);

            if ($response->successful()) {
                if (method_exists($webhook, 'markAsSuccessful')) {
                    $webhook->markAsSuccessful();
                }
                $message = "Test webhook sent successfully! Response time: {$executionTime}ms";

                if ($request->wantsJson()) {
                    return response()->json(['success' => true, 'message' => $message]);
                }
                return redirect()->route('admin.webhooks.index')->with('success', $message);

            } else {
                if (method_exists($webhook, 'markAsFailed')) {
                    $webhook->markAsFailed();
                }
                $message = "Webhook test failed. HTTP Status: {$response->status()}";

                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $message], 422);
                }
                return redirect()->route('admin.webhooks.index')->with('error', $message);
            }

        } catch (\Exception $e) {
            if (method_exists($webhook, 'markAsFailed')) {
                $webhook->markAsFailed();
            }
            $message = 'Webhook test failed with an exception: ' . $e->getMessage();

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }
            return redirect()->route('admin.webhooks.index')->with('error', $message);
        }
    }

    /**
     * Show webhook call logs with filtering
     */
    public function showLogs(Request $request, Webhook $webhook)
    {
        $query = $webhook->calls();

        // Filter by success/failure
        if ($request->filled('status')) {
            if ($request->status === 'success') {
                $query->where('success', true);
            } elseif ($request->status === 'failed') {
                $query->where('success', false);
            }
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(20);
        
        // Get summary statistics
        $stats = [
            'total_calls' => $webhook->calls()->count(),
            'successful_calls' => $webhook->calls()->where('success', true)->count(),
            'failed_calls' => $webhook->calls()->where('success', false)->count(),
            'avg_response_time' => method_exists($webhook, 'getAverageResponseTime') ? 
                round($webhook->getAverageResponseTime(), 2) : 0,
        ];

        return view('admin.webhooks.logs', compact('webhook', 'logs', 'stats'));
    }

    /**
     * Bulk actions for webhooks
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,delete,test',
            'webhook_ids' => 'required|array',
            'webhook_ids.*' => 'exists:webhooks,id'
        ]);

        $webhooks = Webhook::whereIn('id', $request->webhook_ids);
        $count = $webhooks->count();

        switch ($request->action) {
            case 'activate':
                $webhooks->update(['is_active' => true, 'consecutive_failures' => 0]);
                $message = "{$count} webhook(s) activated successfully.";
                break;

            case 'deactivate':
                $webhooks->update(['is_active' => false]);
                $message = "{$count} webhook(s) deactivated successfully.";
                break;

            case 'delete':
                // Delete associated calls first
                WebhookCall::whereIn('webhook_id', $request->webhook_ids)->delete();
                $webhooks->delete();
                $message = "{$count} webhook(s) deleted successfully.";
                break;

            case 'test':
                $message = "Test requests sent to {$count} webhook(s). Check individual logs for results.";
                // Queue test jobs for each webhook to avoid timeout
                foreach ($request->webhook_ids as $webhookId) {
                    $webhook = Webhook::find($webhookId);
                    if ($webhook) {
                        dispatch(function() use ($webhook) {
                            $this->test($webhook);
                        })->afterResponse();
                    }
                }
                break;
        }

        return redirect()->route('admin.webhooks.index')->with('success', $message);
    }

    /**
     * Export webhook configuration
     */
    public function export()
    {
        $webhooks = Webhook::with('calls')->get();
        
        $exportData = $webhooks->map(function($webhook) {
            try {
                $health = $webhook->getHealthStatus();
            } catch (\Exception $e) {
                $health = ['status' => 'unknown', 'success_rate' => 0, 'total_calls' => 0];
            }
            
            return [
                'url' => $webhook->url,
                'event_name' => $webhook->event_name,
                'description' => $webhook->description,
                'is_active' => $webhook->is_active,
                'timeout_seconds' => $webhook->timeout_seconds,
                'health_status' => $health['status'],
                'success_rate' => $health['success_rate'],
                'total_calls' => $health['total_calls'],
                'created_at' => $webhook->created_at->toISOString(),
            ];
        });

        $filename = 'webhooks-export-' . now()->format('Y-m-d-H-i-s') . '.json';
        
        return response()->json($exportData, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => "attachment; filename={$filename}"
        ]);
    }

    /**
     * Get webhook analytics data for dashboard
     */
    public function analytics()
    {
        $totalWebhooks = Webhook::count();
        $activeWebhooks = Webhook::where('is_active', true)->count();
        $failingWebhooks = Webhook::where('consecutive_failures', '>=', 3)->count();
        
        $callsToday = WebhookCall::whereDate('created_at', today())->count();
        $successfulCallsToday = WebhookCall::whereDate('created_at', today())
                                          ->where('success', true)->count();
        
        $successRateToday = $callsToday > 0 ? 
            round(($successfulCallsToday / $callsToday) * 100, 1) : 0;

        // Get calls by day for the last 30 days
        $dailyCallStats = WebhookCall::where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total, SUM(success) as successful')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'summary' => [
                'total_webhooks' => $totalWebhooks,
                'active_webhooks' => $activeWebhooks,
                'failing_webhooks' => $failingWebhooks,
                'calls_today' => $callsToday,
                'success_rate_today' => $successRateToday,
            ],
            'daily_stats' => $dailyCallStats,
        ]);
    }

    /**
     * Regenerate the signing secret for a webhook
     */
    public function regenerateSecret(Webhook $webhook): JsonResponse
    {
        // Generate a new, secure random string
        $newSecret = 'whsec_' . Str::random(32);

        $webhook->signing_secret = $newSecret;
        $webhook->save();

        return response()->json([
            'success' => true,
            'secret' => $newSecret,
        ]);
    }

    /**
     * Health check for all webhooks
     */
    public function healthCheck()
    {
        $webhooks = Webhook::where('is_active', true)->get();
        $results = [];
        
        foreach ($webhooks as $webhook) {
            try {
                $isReachable = method_exists($webhook, 'isReachable') ? $webhook->isReachable() : false;
                $health = method_exists($webhook, 'getHealthStatus') ? $webhook->getHealthStatus() : ['status' => 'unknown'];
            } catch (\Exception $e) {
                $isReachable = false;
                $health = ['status' => 'error'];
            }
            
            $results[] = [
                'id' => $webhook->id,
                'url' => $webhook->url,
                'event_name' => $webhook->event_name,
                'is_reachable' => $isReachable,
                'health_status' => $health['status'],
                'consecutive_failures' => $webhook->consecutive_failures ?? 0,
                'last_success' => $webhook->last_success_at?->diffForHumans(),
            ];
        }

        if (request()->expectsJson()) {
            return response()->json(['webhooks' => $results]);
        }

        return view('admin.webhooks.health', compact('results'));
    }
}