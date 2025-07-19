<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\PaymentReminder;
use App\Services\PaymentReminderService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PaymentReminderSettingsController extends Controller
{
    protected $reminderService;

    public function __construct()
    {
        // Initialize service in method instead to avoid dependency issues
    }

    /**
     * Display payment reminder settings page
     */
    public function index()
    {
        $settings = Setting::whereIn('group', ['payment_reminders', 'communication', 'defaulter_management'])
                          ->get()
                          ->groupBy('group');

        // Get reminder statistics
        $stats = $this->getReminderStatistics();

        // Get recent reminder activity
        $recentActivity = collect(); // Initialize as empty collection
        
        // Check if PaymentReminder table exists before querying
        try {
            if (class_exists('\App\Models\PaymentReminder')) {
                $recentActivity = PaymentReminder::with(['student', 'invoice'])
                                                ->latest()
                                                ->limit(10)
                                                ->get();
            }
        } catch (\Exception $e) {
            Log::info('PaymentReminder table not yet available: ' . $e->getMessage());
        }

        return view('admin.settings.payment_reminders', compact('settings', 'stats', 'recentActivity'));
    }

    /**
     * Update payment reminder settings
     */
    public function update(Request $request)
    {
        $request->validate([
            'payment_reminders_enabled' => 'nullable|boolean',
            'reminder_days_before' => 'nullable|integer|min:1|max:30',
            'reminder_days_urgent' => 'nullable|integer|min:1|max:7',
            'overdue_reminder_frequency' => 'nullable|integer|min:1|max:30',
            'escalation_days' => 'nullable|integer|min:1|max:90',
            'final_notice_days' => 'nullable|integer|min:1|max:180',
            'reminder_time' => 'nullable|date_format:H:i',
            'email_reminders_enabled' => 'nullable|boolean',
            'sms_reminders_enabled' => 'nullable|boolean',
            'whatsapp_reminders_enabled' => 'nullable|boolean',
            'defaulter_tracking_enabled' => 'nullable|boolean',
            'mild_defaulter_days' => 'nullable|integer|min:1|max:30',
            'moderate_defaulter_days' => 'nullable|integer|min:1|max:60',
            'severe_defaulter_days' => 'nullable|integer|min:1|max:90',
            'chronic_defaulter_days' => 'nullable|integer|min:1|max:365',
        ]);

        try {
            // Define setting groups for proper organization
            $settingGroups = [
                'payment_reminders_enabled' => 'payment_reminders',
                'reminder_days_before' => 'payment_reminders',
                'reminder_days_urgent' => 'payment_reminders',
                'overdue_reminder_frequency' => 'payment_reminders',
                'escalation_days' => 'payment_reminders',
                'final_notice_days' => 'payment_reminders',
                'reminder_time' => 'payment_reminders',
                'email_reminders_enabled' => 'communication',
                'sms_reminders_enabled' => 'communication',
                'whatsapp_reminders_enabled' => 'communication',
                'defaulter_tracking_enabled' => 'defaulter_management',
                'mild_defaulter_days' => 'defaulter_management',
                'moderate_defaulter_days' => 'defaulter_management',
                'severe_defaulter_days' => 'defaulter_management',
                'chronic_defaulter_days' => 'defaulter_management',
            ];

            foreach ($request->all() as $key => $value) {
                if ($key !== '_token' && $key !== '_method' && isset($settingGroups[$key])) {
                    Setting::updateOrCreate(
                        ['key' => $key],
                        [
                            'value' => $value ?? '0',
                            'group' => $settingGroups[$key]
                        ]
                    );
                }
            }

            return redirect()->back()->with('success', 'Payment reminder settings updated successfully!');
        } catch (\Exception $e) {
            Log::error('Failed to update payment reminder settings: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update settings: ' . $e->getMessage());
        }
    }

    /**
     * Get reminder dashboard data
     */
    public function dashboard()
    {
        try {
            $dashboardData = [
                'pending_reminders' => 0,
                'sent_today' => 0,
                'failed_reminders' => 0,
                'overdue_reminders' => 0,
                'total_defaulters' => 0,
                'collection_rate' => 0,
            ];

            // Only query if PaymentReminder model exists and table is available
            if (class_exists('\App\Models\PaymentReminder')) {
                $dashboardData = [
                    'pending_reminders' => PaymentReminder::where('status', 'pending')->count(),
                    'sent_today' => PaymentReminder::whereDate('sent_at', today())->count(),
                    'failed_reminders' => PaymentReminder::where('status', 'failed')->count(),
                    'overdue_reminders' => PaymentReminder::where('scheduled_date', '<', now())
                                                          ->where('status', 'pending')->count(),
                    'total_defaulters' => 0, // Will be updated when defaulter service is available
                    'collection_rate' => $this->getCollectionRate(),
                ];
            }

            // Get chart data for the last 30 days
            $chartData = $this->getChartData();

            return response()->json([
                'dashboard' => $dashboardData,
                'charts' => $chartData
            ]);
        } catch (\Exception $e) {
            Log::error('Dashboard data error: ' . $e->getMessage());
            return response()->json([
                'dashboard' => [
                    'pending_reminders' => 0,
                    'sent_today' => 0,
                    'failed_reminders' => 0,
                    'overdue_reminders' => 0,
                    'total_defaulters' => 0,
                    'collection_rate' => 0,
                ],
                'charts' => []
            ]);
        }
    }

    /**
     * Test reminder sending functionality
     */
    public function testReminder(Request $request)
    {
        $request->validate([
            'channel' => 'required|in:email,sms,whatsapp',
            'recipient' => 'required|string',
            'message' => 'required|string|max:500'
        ]);

        try {
            $result = $this->sendTestReminder(
                $request->channel,
                $request->recipient,
                $request->message
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Test reminder sent successfully!'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send test reminder: ' . $result['error']
                ], 422);
            }
        } catch (\Exception $e) {
            Log::error('Test reminder error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error sending test reminder: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send test reminder
     */
    private function sendTestReminder(string $channel, string $recipient, string $message): array
    {
        try {
            switch ($channel) {
                case 'email':
                    return $this->sendTestEmailReminder($recipient, $message);
                case 'sms':
                    return $this->sendTestSMSReminder($recipient, $message);
                case 'whatsapp':
                    return $this->sendTestWhatsAppReminder($recipient, $message);
                default:
                    return ['success' => false, 'error' => 'Invalid channel specified'];
            }
        } catch (\Exception $e) {
            Log::error('Test reminder failed', [
                'channel' => $channel,
                'recipient' => $recipient,
                'error' => $e->getMessage()
            ]);
            
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send test email reminder
     */
    private function sendTestEmailReminder(string $email, string $message): array
    {
        try {
            Mail::raw($message, function ($mail) use ($email) {
                $mail->to($email)
                     ->subject('Test Payment Reminder - ' . config('app.name'))
                     ->from(config('mail.from.address'), config('mail.from.name'));
            });

            return ['success' => true, 'message' => 'Test email sent successfully'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Email sending failed: ' . $e->getMessage()];
        }
    }

    /**
     * Send test SMS reminder
     */
    private function sendTestSMSReminder(string $phone, string $message): array
    {
        try {
            // Log the SMS for testing purposes
            Log::info('Test SMS sent', [
                'phone' => $phone,
                'message' => $message,
                'timestamp' => now()->toISOString()
            ]);

            return ['success' => true, 'message' => 'Test SMS logged successfully (SMS provider not configured)'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'SMS sending failed: ' . $e->getMessage()];
        }
    }

    /**
     * Send test WhatsApp reminder
     */
    private function sendTestWhatsAppReminder(string $phone, string $message): array
    {
        try {
            // Log the WhatsApp message for testing purposes
            Log::info('Test WhatsApp sent', [
                'phone' => $phone,
                'message' => $message,
                'timestamp' => now()->toISOString()
            ]);

            return ['success' => true, 'message' => 'Test WhatsApp logged successfully (WhatsApp API not configured)'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'WhatsApp sending failed: ' . $e->getMessage()];
        }
    }

    /**
     * Get reminder statistics
     */
    private function getReminderStatistics(): array
    {
        try {
            if (!class_exists('\App\Models\PaymentReminder')) {
                return $this->getDefaultStatistics();
            }

            return [
                'total_reminders' => PaymentReminder::count(),
                'pending_reminders' => PaymentReminder::where('status', 'pending')->count(),
                'sent_today' => PaymentReminder::whereDate('sent_at', today())->count(),
                'sent_this_week' => PaymentReminder::whereBetween('sent_at', [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek()
                ])->count(),
                'sent_this_month' => PaymentReminder::whereBetween('sent_at', [
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth()
                ])->count(),
                'failed_reminders' => PaymentReminder::where('status', 'failed')->count(),
                'success_rate' => $this->calculateSuccessRate(),
                'avg_response_time' => $this->calculateAverageResponseTime(),
            ];
        } catch (\Exception $e) {
            Log::info('Statistics calculation failed, using defaults: ' . $e->getMessage());
            return $this->getDefaultStatistics();
        }
    }

    /**
     * Get default statistics when PaymentReminder model is not available
     */
    private function getDefaultStatistics(): array
    {
        return [
            'total_reminders' => 0,
            'pending_reminders' => 0,
            'sent_today' => 0,
            'sent_this_week' => 0,
            'sent_this_month' => 0,
            'failed_reminders' => 0,
            'success_rate' => 0,
            'avg_response_time' => 'N/A',
        ];
    }

    /**
     * Calculate reminder success rate
     */
    private function calculateSuccessRate(): float
    {
        try {
            $total = PaymentReminder::whereIn('status', ['sent', 'failed'])->count();
            if ($total === 0) return 0;

            $successful = PaymentReminder::where('status', 'sent')->count();
            return round(($successful / $total) * 100, 2);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Calculate average response time for reminders
     */
    private function calculateAverageResponseTime(): string
    {
        try {
            // This would require payment tracking - simplified for now
            return 'N/A';
        } catch (\Exception $e) {
            return 'N/A';
        }
    }

    /**
     * Get collection rate from invoices
     */
    private function getCollectionRate(): float
    {
        try {
            if (!class_exists('\App\Models\Invoice')) {
                return 0;
            }

            $totalInvoices = \App\Models\Invoice::count();
            if ($totalInvoices === 0) return 0;

            $paidInvoices = \App\Models\Invoice::where('status', 'paid')->count();
            return round(($paidInvoices / $totalInvoices) * 100, 2);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get chart data for dashboard
     */
    private function getChartData(): array
    {
        try {
            if (!class_exists('\App\Models\PaymentReminder')) {
                return [
                    'daily_reminders' => [],
                    'by_channel' => [],
                    'by_type' => [],
                ];
            }

            $last30Days = collect(range(0, 29))->map(function ($i) {
                $date = Carbon::now()->subDays($i);
                return [
                    'date' => $date->format('Y-m-d'),
                    'sent' => PaymentReminder::whereDate('sent_at', $date)->count(),
                    'failed' => PaymentReminder::whereDate('created_at', $date)
                                             ->where('status', 'failed')->count(),
                ];
            })->reverse()->values();

            $remindersByChannel = PaymentReminder::selectRaw('channel, COUNT(*) as count')
                                               ->whereDate('created_at', '>=', Carbon::now()->subDays(30))
                                               ->groupBy('channel')
                                               ->pluck('count', 'channel');

            $remindersByType = PaymentReminder::selectRaw('reminder_type, COUNT(*) as count')
                                            ->whereDate('created_at', '>=', Carbon::now()->subDays(30))
                                            ->groupBy('reminder_type')
                                            ->pluck('count', 'reminder_type');

            return [
                'daily_reminders' => $last30Days,
                'by_channel' => $remindersByChannel,
                'by_type' => $remindersByType,
            ];
        } catch (\Exception $e) {
            return [
                'daily_reminders' => [],
                'by_channel' => [],
                'by_type' => [],
            ];
        }
    }
}