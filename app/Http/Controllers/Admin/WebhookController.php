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

            // Use paginate() to get LengthAwarePaginator which has total() method
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
                // Fallback to basic event types (COMPONENT-BASED) with daily.summary
                $eventTypes = [
                    'payment.created' => ['name' => 'Payment Created', 'description' => 'When a component payment is made', 'category' => 'Financial'],
                    'student_fee.created' => ['name' => 'Student Fee Created', 'description' => 'When a new fee component is assigned to a student', 'category' => 'Financial'],
                    'concession.applied' => ['name' => 'Concession Applied', 'description' => 'When a concession is applied to a student fee', 'category' => 'Financial'],
                    'student.created' => ['name' => 'Student Created', 'description' => 'When a new student is added', 'category' => 'Student Management'],
                    'student.birthday' => ['name' => 'Student Birthday Today', 'description' => 'Triggers daily for each active student celebrating their birthday today.', 'category' => 'Student Management'],
                    'enquiry.created' => ['name' => 'Enquiry Created', 'description' => 'When a new enquiry is submitted', 'category' => 'Lead Management'],
                    'daily.summary' => ['name' => 'Daily Summary Report', 'description' => 'Automated daily report with payment totals and attendance summary. Sent at 5:00 PM on working days (Monday-Saturday)', 'category' => 'Automation'],

                ];

                $categories = [
                    'Financial' => '💰',
                    'Student Management' => '👨‍🎓',
                    'Lead Management' => '📞',
                    'Automation' => '🤖',
                ];
            }

            // Get statistics safely with date filter
            $date = $request->get('date', now()->format('Y-m-d'));
            $stats = $this->getWebhookStats($date);

            return view('admin.webhooks.index', compact('webhooks', 'eventTypes', 'categories', 'stats', 'date'));

        } catch (\Exception $e) {
            // Log the error
            \Log::error('Webhook index error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            // Return a safe fallback view with empty paginated collection
            $webhooks = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20);
            $eventTypes = [];
            $categories = [];
            $stats = [
                'total' => 0,
                'active' => 0,
                'failing' => 0,
                'calls_count' => 0,
                'success_rate' => 0,
            ];

            return view('admin.webhooks.index', compact('webhooks', 'eventTypes', 'categories', 'stats'))
                ->with('error', 'There was an issue loading webhooks. Please check the logs.');
        }
    }

    protected function getWebhookStats($date = null): array
    {
        try {
            $date = $date ?? now()->format('Y-m-d');

            $callsQuery = \App\Models\WebhookCall::whereDate('created_at', $date);
            $totalCalls = (clone $callsQuery)->count();
            $successfulCalls = (clone $callsQuery)->where('success', true)->count();
            $successRate = $totalCalls > 0 ? round(($successfulCalls / $totalCalls) * 100, 1) : 100;

            return [
                'total' => \App\Models\Webhook::count(),
                'active' => \App\Models\Webhook::where('is_active', true)->count(),
                'failing' => \App\Models\Webhook::where('consecutive_failures', '>=', 3)->count(),
                'calls_count' => $totalCalls,
                'success_rate' => $successRate,
            ];
        } catch (\Exception $e) {
            return [
                'total' => 0,
                'active' => 0,
                'failing' => 0,
                'calls_count' => 0,
                'success_rate' => 0,
            ];
        }
    }

    /**
     * Show the form for creating a new webhook
     */
    public function create()
    {
        try {
            $eventTypes = Webhook::getAvailableEvents();
            $categories = Webhook::getEventCategories();
        } catch (\Exception $e) {
            // Fallback to basic event types including daily.summary
            $eventTypes = [
                'payment.created' => ['name' => 'Payment Created', 'description' => 'When a new payment is recorded', 'category' => 'Financial'],
                'payment.updated' => ['name' => 'Payment Updated', 'description' => 'When a payment record is modified', 'category' => 'Financial'],
                'student_fee.created' => ['name' => 'Student Fee Created', 'description' => 'When a new fee is assigned', 'category' => 'Financial'],
                'student.created' => ['name' => 'Student Created', 'description' => 'When a new student is added', 'category' => 'Student Management'],
                'student.updated' => ['name' => 'Student Updated', 'description' => 'When student profile/details are changed', 'category' => 'Student Management'],
                'student.birthday' => ['name' => 'Student Birthday Today', 'description' => 'Daily triggers for student birthdays', 'category' => 'Student Management'],
                'attendance.daily_absent' => ['name' => 'Daily Absent Report', 'description' => 'Daily list of absent students', 'category' => 'Student Management'],
                'enquiry.created' => ['name' => 'Enquiry Created', 'description' => 'When a new enquiry is submitted', 'category' => 'Lead Management'],
                'attendance.daily_absent' => [
                    'name' => 'Daily Absent Report',
                    'description' => 'Triggers once daily after the "Present Cutoff Time". Sends a list of all students who have not marked attendance.',
                    'category' => 'Student Management'
                ],
                'daily.summary' => ['name' => 'Daily Summary Report', 'description' => 'Automated daily report with payment totals and attendance summary. Sent at 5:00 PM on working days (Monday-Saturday)', 'category' => 'Automation'],
            ];

            $categories = [
                'Financial' => [
                    'icon' => '💰',
                    'events' => [
                        'payment.created' => $eventTypes['payment.created'],
                        'payment.updated' => $eventTypes['payment.updated'],
                        'student_fee.created' => $eventTypes['student_fee.created'],
                    ]
                ],
                'Student Management' => [
                    'icon' => '👨‍🎓',
                    'events' => [
                        'student.created' => $eventTypes['student.created'],
                        'student.updated' => $eventTypes['student.updated'],
                        'student.birthday' => $eventTypes['student.birthday'],
                        'attendance.daily_absent' => $eventTypes['attendance.daily_absent'],
                    ]
                ],
                'Lead Management' => [
                    'icon' => '📞',
                    'events' => [
                        'enquiry.created' => $eventTypes['enquiry.created'],
                    ]
                ],
                'Automation' => [
                    'icon' => '🤖',
                    'events' => [
                        'daily.summary' => $eventTypes['daily.summary'],
                    ]
                ],
            ];
        }

        return view('admin.webhooks.create', [
            'eventTypes' => $eventTypes ?? [],
            'eventCategories' => $categories ?? []
        ]);
    }

    public function store(Request $request)
    {
        // Get available events with fallback consistently
        $availableEvents = [];
        try {
            $availableEvents = Webhook::getAvailableEvents();
        } catch (\Exception $e) {}

        // Ensure critical fallbacks are always in the validation array if discovery fails
        $validationKeys = array_keys($availableEvents);
        if (empty($validationKeys)) {
            $validationKeys = [
                'payment.created', 'student_fee.created', 'concession.applied',
                'student.created', 'student.birthday', 'enquiry.created',
                'daily.summary', 'attendance.daily_absent'
            ];
        }

        $request->validate([
            'url' => 'required|url|unique:webhooks,url',
            'event_name' => [
                'required',
                'string',
                Rule::in($validationKeys)
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
        $webhook->load([
            'calls' => function ($query) {
                $query->latest()->limit(50);
            }
        ]);

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

            // Fallback data - completely safe (COMPONENT-BASED) with daily.summary
            $eventTypes = [
                'payment.created' => ['name' => 'Payment Created', 'category' => 'Financial'],
                'payment.updated' => ['name' => 'Payment Updated', 'category' => 'Financial'],
                'student_fee.created' => ['name' => 'Student Fee Created', 'category' => 'Financial'],
                'student.created' => ['name' => 'Student Created', 'category' => 'Student Management'],
                'student.updated' => ['name' => 'Student Updated', 'category' => 'Student Management'],
                'student.birthday' => ['name' => 'Student Birthday Today', 'category' => 'Student Management'],
                'attendance.daily_absent' => ['name' => 'Daily Absent Report', 'category' => 'Student Management'],
                'enquiry.created' => ['name' => 'Enquiry Created', 'category' => 'Lead Management'],
                'daily.summary' => ['name' => 'Daily Summary Report', 'category' => 'Automation'],
            ];

            $eventCategories = [
                'Financial' => [
                    'icon' => '💰',
                    'events' => [
                        'payment.created' => $eventTypes['payment.created'],
                        'payment.updated' => $eventTypes['payment.updated'],
                        'student_fee.created' => $eventTypes['student_fee.created'],
                    ]
                ],
                'Student Management' => [
                    'icon' => '👨‍🎓',
                    'events' => [
                        'student.created' => $eventTypes['student.created'],
                        'student.updated' => $eventTypes['student.updated'],
                        'student.birthday' => $eventTypes['student.birthday'],
                        'attendance.daily_absent' => $eventTypes['attendance.daily_absent'],
                    ]
                ],
                'Lead Management' => [
                    'icon' => '📞',
                    'events' => [
                        'enquiry.created' => $eventTypes['enquiry.created'],
                    ]
                ],
                'Automation' => [
                    'icon' => '🤖',
                    'events' => [
                        'daily.summary' => $eventTypes['daily.summary'],
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
        // Get available events with fallback including daily.summary
        $availableEvents = [];
        try {
            $availableEvents = Webhook::getAvailableEvents();
        } catch (\Exception $e) {
            // Fallback events including daily.summary
            $availableEvents = [
                'payment.created' => [],
                'student_fee.created' => [],
                'concession.applied' => [],
                'student.created' => [],
                'student.birthday' => [],
                'enquiry.created' => [],
                'daily.summary' => [], // Include daily.summary in validation
            ];
        }
        $availableEvents['attendance.daily_absent'] = [];

        $request->validate([
            'url' => [
                'required',
                'url',
                Rule::unique('webhooks', 'url')->ignore($webhook->id)
            ],
            'event_name' => [
                'required',
                'string',
                Rule::in(array_keys($availableEvents))
            ],
            'description' => 'nullable|string|max:500',
            'timeout_seconds' => 'nullable|integer|min:5|max:120',
            'is_active' => 'boolean',
            'max_failures_before_disable' => 'nullable|integer|min:1|max:50',
            'auto_disable_after_failures' => 'boolean',
        ]);

        $webhook->update($request->only([
            'url',
            'event_name',
            'description',
            'timeout_seconds',
            'is_active',
            'max_failures_before_disable',
            'auto_disable_after_failures'
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
     * Toggle webhook active status
     */
    public function toggle(Webhook $webhook)
    {
        try {
            $webhook->update(['is_active' => !$webhook->is_active]);

            // Reset failure count when reactivating
            if ($webhook->is_active && method_exists($webhook, 'resetFailures')) {
                $webhook->resetFailures();
            }

            $status = $webhook->is_active ? 'activated' : 'deactivated';

            if (request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Webhook has been {$status}.",
                    'is_active' => $webhook->is_active
                ]);
            }

            return redirect()->route('admin.webhooks.index')
                ->with('success', "Webhook has been {$status}.");
        } catch (\Exception $e) {
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to toggle webhook status'
                ], 500);
            }

            return redirect()->route('admin.webhooks.index')
                ->with('error', 'Failed to toggle webhook status.');
        }
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

        // Generate dynamic payload based on event name
        $payload = $this->generateTestPayload($webhook);

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
     * Test the daily summary webhook manually
     */
    public function testDailySummary(Request $request)
    {
        try {
            $testDate = $request->get('date', now()->format('Y-m-d'));

            // Run the artisan command in test mode
            \Artisan::call('webhook:daily-summary', [
                '--test' => true,
                '--date' => $testDate
            ]);

            $output = \Artisan::output();

            return response()->json([
                'success' => true,
                'message' => 'Daily summary webhook test completed successfully',
                'output' => $output,
                'date' => $testDate
            ]);

        } catch (\Exception $e) {
            \Log::error('Daily summary test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send daily summary webhook manually
     */
    public function sendDailySummary(Request $request)
    {
        try {
            $date = $request->get('date', now()->format('Y-m-d'));

            // Run the artisan command with force flag for manual trigger
            \Artisan::call('webhook:daily-summary', [
                '--date' => $date,
                '--force' => true // Force run even on non-working days when manually triggered
            ]);

            $output = \Artisan::output();

            return response()->json([
                'success' => true,
                'message' => 'Daily summary webhook sent successfully',
                'output' => $output,
                'date' => $date
            ]);

        } catch (\Exception $e) {
            \Log::error('Daily summary send failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
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
                        dispatch(function () use ($webhook) {
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

        $exportData = $webhooks->map(function ($webhook) {
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
     * Regenerate webhook secret key
     */
    public function regenerateSecret(Webhook $webhook)
    {
        try {
            $newSecret = 'whsec_' . bin2hex(random_bytes(32));
            $webhook->update(['secret_key' => $newSecret]);

            if (request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'secret' => $newSecret,
                    'message' => 'Secret regenerated successfully'
                ]);
            }

            return redirect()->route('admin.webhooks.index')
                ->with('success', 'Webhook secret regenerated successfully!');

        } catch (\Exception $e) {
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to regenerate secret'
                ], 500);
            }

            return redirect()->route('admin.webhooks.index')
                ->with('error', 'Failed to regenerate webhook secret.');
        }
    }

    /**
     * Health check for all webhooks
     */
    /**
     * Generate a realistic test payload based on the event name
     */
    private function generateTestPayload(Webhook $webhook): array
    {
        $eventName = $webhook->event_name;
        $basePayload = [
            'event' => $eventName,
            'event_id' => 'evt_' . Str::random(12),
            'webhook_id' => $webhook->id,
            'created_at' => now()->toISOString(),
            'app_name' => config('app.name'),
        ];

        $data = [];

        // Determine specific data based on event type
        if (Str::startsWith($eventName, 'payment.')) {
            $data = [
                'payment' => [
                    'id' => rand(1000, 9999),
                    'amount' => 5000.00,
                    'formatted_amount' => '₹5,000.00',
                    'payment_method' => 'online',
                    'payment_date' => now()->toDateString(),
                    'receipt_number' => 'RCP-2025-' . rand(100, 999),
                    'status' => 'completed',
                ],
                'student' => [
                    'id' => rand(1, 100),
                    'name' => 'John Doe',
                    'enrollment_number' => 'STD-2024-001'
                ]
            ];
        } elseif (Str::startsWith($eventName, 'student.')) {
            if ($eventName === 'student.birthday') {
                $data = [
                    'student' => [
                        'id' => rand(1, 100),
                        'name' => 'Jane Smith',
                        'birthday' => now()->toDateString(),
                        'age' => 15,
                        'enrollment_number' => 'STD-2024-045'
                    ]
                ];
            } else {
                $data = [
                    'student' => [
                        'id' => rand(1, 100),
                        'name' => 'John Doe',
                        'email' => 'john.doe@example.com',
                        'phone' => '9876543210',
                        'enrollment_number' => 'STD-2024-001',
                        'status' => 'active'
                    ]
                ];
            }
        } elseif (Str::startsWith($eventName, 'enquiry.')) {
            $data = [
                'enquiry' => [
                    'id' => rand(100, 999),
                    'name' => 'Prospective Student',
                    'email' => 'prospect@example.com',
                    'phone' => '9123456780',
                    'course' => 'Full Stack Development',
                    'message' => 'I am interested in learning more about your web development program.'
                ]
            ];
        } elseif ($eventName === 'daily.summary') {
            return array_merge($basePayload, [
                'date' => now()->format('Y-m-d'),
                'report_day' => now()->format('l'),
                'report_generated_at' => now()->toISOString(),
                'summary' => [
                    'payments' => [
                        'total_amount' => 125000.75,
                        'payment_count' => 12,
                        'method_distribution' => [
                            'Cash' => 45000.00,
                            'Online' => 80000.75
                        ]
                    ],
                    'attendance' => [
                        'present' => 142,
                        'absent' => 8,
                        'total_students' => 150,
                        'percentage' => 94.6
                    ],
                    'enquiries' => [
                        'new_count' => 5,
                        'follow_ups' => 12
                    ]
                ]
            ]);
        } elseif ($eventName === 'attendance.daily_absent') {
            return array_merge($basePayload, [
                'date' => now()->toDateString(),
                'absent_count' => 2,
                'students' => [
                    [
                        'id' => 101,
                        'name' => 'Alice Brown',
                        'batch' => 'Evening Batch A',
                        'parent_phone' => '9887766554'
                    ],
                    [
                        'id' => 108,
                        'name' => 'Bob White',
                        'batch' => 'Evening Batch A',
                        'parent_phone' => '9776655443'
                    ]
                ]
            ]);
        } else {
            // Generic sample data for other eloquent events (created/updated/deleted)
            $modelName = ucfirst(explode('.', $eventName)[0] ?? 'Resource');
            $data = [
                'model' => $modelName,
                'id' => rand(1, 1000),
                'action' => explode('.', $eventName)[1] ?? 'updated',
                'attributes' => [
                    'status' => 'active',
                    'updated_at' => now()->toISOString()
                ]
            ];
        }

        return array_merge($basePayload, ['data' => $data]);
    }

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