<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Http\Request;

class NotificationSettingsController extends Controller
{
    /**
     * Show notification settings
     */
    public function index()
    {
        $globalSettings = [
            'email_notifications' => setting('email_notifications', true),
            'sms_notifications' => setting('sms_notifications', false),
            'push_notifications' => setting('push_notifications', true),
            'sound_notifications' => setting('sound_notifications', true),
            'fee_reminder_days' => setting('fee_reminder_days', 7),
            'minimum_attendance_percentage' => setting('minimum_attendance_percentage', 75),
        ];

        $userPreferences = NotificationPreference::where('user_id', auth()->id())->get();

        return view('admin.notifications.settings', compact('globalSettings', 'userPreferences'));
    }

    /**
     * Update notification settings
     */
    public function update(Request $request)
    {
        $request->validate([
            'email_notifications' => 'nullable|boolean',
            'sms_notifications' => 'nullable|boolean',
            'push_notifications' => 'nullable|boolean',
            'sound_notifications' => 'nullable|boolean',
            'fee_reminder_days' => 'nullable|integer|min:1|max:30',
            'minimum_attendance_percentage' => 'nullable|integer|min:50|max:100',
        ]);

        try {
            // Update global settings using the helper function
            foreach ($request->only([
                'email_notifications',
                'sms_notifications',
                'push_notifications',
                'sound_notifications',
                'fee_reminder_days',
                'minimum_attendance_percentage',
            ]) as $key => $value) {
                if ($value !== null) {
                    update_setting($key, $value);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Notification settings updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update user preferences
     */
    public function updateUserPreferences(Request $request)
    {
        $request->validate([
            'preferences' => 'required|array',
            'preferences.*.notification_type' => 'required|string|in:email,push,sound,desktop',
            'preferences.*.category' => 'required|string|in:financial,academic,system,attendance,general',
            'preferences.*.enabled' => 'required|boolean',
        ]);

        try {
            foreach ($request->preferences as $pref) {
                NotificationPreference::updateOrCreate([
                    'user_id' => auth()->id(),
                    'notification_type' => $pref['notification_type'],
                    'category' => $pref['category'],
                ], [
                    'enabled' => $pref['enabled'],
                    'settings' => $pref['settings'] ?? [],
                ]);
            }

            return response()->json(['success' => true, 'message' => 'Preferences updated successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update preferences: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reset preferences to default
     */
    public function resetPreferences()
    {
        try {
            NotificationPreference::where('user_id', auth()->id())->delete();

            return response()->json([
                'success' => true,
                'message' => 'Preferences reset to default successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset preferences: '.$e->getMessage(),
            ], 500);
        }
    }
}
