<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\PaymentReminder;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PaymentReminderSettingsController extends Controller
{
    protected $reminderService;

    public function __construct()
    {
        // ✅ FIXED: Use ComponentPaymentReminderService instead of PaymentReminderService
        if (class_exists('\App\Services\ComponentPaymentReminderService')) {
            $this->reminderService = app(\App\Services\ComponentPaymentReminderService::class);
        } else {
            $this->reminderService = null;
        }
    }

    /**
     * Display payment reminder settings page
     * ✅ FIXED: Now returns the correct view in the payments folder
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
                // ✅ CHANGED: The relationship is updated from 'invoice' to 'studentFee.feeCategory'
                // to align with the new component-based system.
                $recentActivity = PaymentReminder::with(['student', 'studentFee.feeCategory'])
                    ->latest()
                    ->limit(10)
                    ->get();
            }
        } catch (\Exception $e) {
            \Log::info('PaymentReminder table not yet available: ' . $e->getMessage());
        }

        // ✅ FIXED: Return the correct view path in the payments folder
        return view('admin.payments.reminder-settings', compact(
            'settings',
            'stats',
            'recentActivity'
        ));
    }

    /**
     * Update payment reminder settings
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'nullable|string'
        ]);

        try {
            foreach ($validated['settings'] as $settingData) {
                Setting::updateOrCreate(
                    ['key' => $settingData['key']],
                    ['value' => $settingData['value']]
                );
            }

            // Clear config cache to apply new settings
            if (function_exists('artisan')) {
                \Artisan::call('config:clear');
            }

            return redirect()->back()->with('success', 'Payment reminder settings updated successfully!');

        } catch (\Exception $e) {
            \Log::error('Failed to update payment reminder settings: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update settings. Please try again.');
        }
    }

    /**
     * Get reminder statistics for the settings page
     */
    private function getReminderStatistics(): array
    {
        try {
            if (!class_exists('\App\Models\PaymentReminder')) {
                return [
                    'total_reminders' => 0,
                    'sent_today' => 0,
                    'pending' => 0,
                    'failed' => 0,
                    'success_rate' => 0
                ];
            }

            $total = PaymentReminder::count();
            $sentToday = PaymentReminder::whereDate('sent_at', today())->count();
            $pending = PaymentReminder::where('status', 'pending')->count();
            $failed = PaymentReminder::where('status', 'failed')->count();
            $sent = PaymentReminder::where('status', 'sent')->count();

            $successRate = $total > 0 ? round(($sent / $total) * 100, 1) : 0;

            return [
                'total_reminders' => $total,
                'sent_today' => $sentToday,
                'pending' => $pending,
                'failed' => $failed,
                'success_rate' => $successRate
            ];

        } catch (\Exception $e) {
            \Log::error('Error getting reminder statistics: ' . $e->getMessage());
            return [
                'total_reminders' => 0,
                'sent_today' => 0,
                'pending' => 0,
                'failed' => 0,
                'success_rate' => 0
            ];
        }
    }

    /**
     * Test reminder functionality
     */
    public function testReminder(Request $request)
    {
        $validated = $request->validate([
            'channel' => 'required|in:email,sms,whatsapp',
            'recipient' => 'required|string',
            'test_message' => 'required|string'
        ]);

        try {
            if ($this->reminderService) {
                $result = $this->reminderService->sendTestReminder(
                    $validated['channel'],
                    $validated['recipient'],
                    $validated['test_message']
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
            }

            return response()->json([
                'success' => false,
                'message' => 'Reminder service not available'
            ], 503);

        } catch (\Exception $e) {
            \Log::error('Test reminder failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Test failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate reminder configuration
     */
    public function validateConfiguration(Request $request)
    {
        try {
            $issues = [];

            // Check email configuration
            if (!config('mail.default')) {
                $issues[] = 'Email driver not configured';
            }

            // Check SMS configuration (if applicable)
            if (!config('services.sms.api_key')) {
                $issues[] = 'SMS service not configured';
            }

            // Check WhatsApp configuration (if applicable) 
            if (!config('services.whatsapp.api_key')) {
                $issues[] = 'WhatsApp service not configured';
            }

            // Check database tables
            try {
                PaymentReminder::count();
            } catch (\Exception $e) {
                $issues[] = 'PaymentReminder table not found or accessible';
            }

            return response()->json([
                'success' => count($issues) === 0,
                'issues' => $issues,
                'message' => count($issues) === 0 ? 'Configuration is valid' : 'Configuration issues found'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset reminder settings to defaults
     */
    public function resetToDefaults(Request $request)
    {
        try {
            $defaultSettings = [
                'reminder_days_before_due' => '3',
                'overdue_reminder_frequency' => '7',
                'max_reminder_attempts' => '5',
                'auto_send_reminders' => '1',
                'default_reminder_channel' => 'email',
                'sender_email' => config('mail.from.address', 'noreply@college.edu'),
                'reminder_email_template' => 'Dear [STUDENT_NAME], Your fee payment of ₹[AMOUNT] is due on [DUE_DATE]. Please make the payment at your earliest convenience.',
                'reminder_sms_template' => 'Dear [STUDENT_NAME], Fee payment of ₹[AMOUNT] due on [DUE_DATE]. Pay now to avoid late fees.',
                'defaulter_grace_period' => '15',
                'escalation_threshold' => '5000',
                'auto_block_defaulters' => '0'
            ];

            foreach ($defaultSettings as $key => $value) {
                Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value]
                );
            }

            // Clear config cache
            if (function_exists('artisan')) {
                \Artisan::call('config:clear');
            }

            return redirect()->back()->with('success', 'Settings reset to defaults successfully!');

        } catch (\Exception $e) {
            \Log::error('Failed to reset settings: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to reset settings. Please try again.');
        }
    }
}