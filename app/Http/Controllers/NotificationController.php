<?php

namespace App\Http\Controllers;

use App\Models\SystemNotification;
use App\Models\NotificationPreference;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get notifications for authenticated user
     */
    public function index(Request $request)
    {
        $userId = auth()->id();
        
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }
        
        $query = SystemNotification::forUser($userId)->active();
        
        // Apply filters
        if ($request->category) {
            $query->byCategory($request->category);
        }
        
        if ($request->type) {
            $query->where('type', $request->type);
        }
        
        if ($request->unread_only) {
            $query->unread($userId);
        }
        
        // Apply limit for API calls
        $limit = $request->limit ?? $request->per_page ?? 20;
        $limit = min($limit, 100); // Max 100 notifications
        
        // Order by priority and date
        $notifications = $query
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'normal' THEN 3 ELSE 4 END")
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'notifications' => $notifications,
                'unread_count' => SystemNotification::getUnreadCountForUser($userId),
                'total_count' => $notifications->count()
            ]);
        }

        // For web requests, paginate
        $paginatedNotifications = $query->paginate($limit);
        return view('notifications.index', compact('paginatedNotifications'));
    }

    /**
     * Show a specific notification
     */
    public function show(Request $request, SystemNotification $notification)
    {
        // Check if user can view this notification
        if (!$notification->canBeViewedBy(auth()->id())) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to view this notification.'
                ], 403);
            }
            abort(403, 'You do not have permission to view this notification.');
        }

        // Mark as read when viewed
        $notification->markAsReadBy(auth()->id());

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'notification' => $notification
            ]);
        }

        return view('notifications.show', compact('notification'));
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request, SystemNotification $notification)
    {
        try {
            $userId = auth()->id();
            
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }
            
            $success = $notification->markAsReadBy($userId);
            
            return response()->json([
                'success' => $success,
                'message' => $success ? 'Notification marked as read' : 'Failed to mark as read',
                'unread_count' => SystemNotification::getUnreadCountForUser($userId)
            ]);
        } catch (\Exception $e) {
            \Log::error('Error marking notification as read: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request)
    {
        try {
            $userId = auth()->id();
            
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }
            
            $notifications = SystemNotification::forUser($userId)->unread($userId)->get();
            
            $count = 0;
            foreach ($notifications as $notification) {
                if ($notification->markAsReadBy($userId)) {
                    $count++;
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => "Marked {$count} notifications as read",
                'count' => $count,
                'unread_count' => SystemNotification::getUnreadCountForUser($userId)
            ]);
        } catch (\Exception $e) {
            \Log::error('Error marking all notifications as read: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get unread count - THIS IS THE KEY METHOD YOUR JAVASCRIPT IS CALLING
     * This method handles both web and API requests
     */
    public function getUnreadCount(Request $request)
    {
        try {
            $userId = auth()->id();
            
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                    'count' => 0
                ], 401);
            }
            
            $count = SystemNotification::getUnreadCountForUser($userId);
            
            return response()->json([
                'success' => true,
                'count' => $count
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting unread notification count: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to get unread count',
                'count' => 0
            ], 500);
        }
    }

    /**
     * Get notification preferences
     */
    public function preferences(Request $request)
    {
        try {
            $userId = auth()->id();
            
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }
            
            $preferences = NotificationPreference::where('user_id', $userId)->get();
            
            return response()->json([
                'success' => true,
                'preferences' => $preferences,
                'categories' => ['financial', 'academic', 'system', 'attendance', 'general'],
                'types' => ['push', 'email', 'sound', 'desktop']
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting notification preferences: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update notification preferences
     */
    public function updatePreferences(Request $request)
    {
        try {
            $userId = auth()->id();
            
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }
            
            $request->validate([
                'preferences' => 'required|array',
                'preferences.*.notification_type' => 'required|string',
                'preferences.*.category' => 'required|string',
                'preferences.*.enabled' => 'required|boolean',
            ]);

            $preferences = $request->preferences;
            
            foreach ($preferences as $pref) {
                NotificationPreference::updateOrCreate(
                    [
                        'user_id' => $userId,
                        'notification_type' => $pref['notification_type'],
                        'category' => $pref['category']
                    ],
                    [
                        'enabled' => $pref['enabled'],
                        'updated_at' => now()
                    ]
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Notification preferences updated successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error updating notification preferences: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}