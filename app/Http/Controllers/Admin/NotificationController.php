<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use App\Models\SystemNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class NotificationController extends Controller
{

/**
     * Get all notifications for index
     */
    public function index(Request $request)
    {
        $query = SystemNotification::forUser(Auth::id());

        // Apply filters
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        $notifications = $query->orderBy('created_at', 'desc')
            ->paginate(20);

        // [FIX] Define ALL variables required by the view filters
        $categories = [
            'financial' => 'Financial',
            'academic' => 'Academic',
            'attendance' => 'Attendance',
            'system' => 'System',
            'general' => 'General'
        ];

        $types = [
            'info' => 'Info',
            'success' => 'Success',
            'warning' => 'Warning',
            'error' => 'Error'
        ];

        $priorities = [
            'low' => 'Low',
            'normal' => 'Normal',
            'high' => 'High',
            'urgent' => 'Urgent'
        ];

        // Pass everything to the view
        return view('admin.notifications.index', compact('notifications', 'categories', 'types', 'priorities'));
    }

    /**
     * Get recent notifications (for header dropdown)
     */
    public function recent(Request $request)
    {
        try {
            $userId = Auth::id();
            
            // Get unread notifications first
            $notifications = SystemNotification::forUser($userId)
                ->unread($userId) // Use the scope from your model
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
                
            // Get total unread count
            $unreadCount = SystemNotification::getUnreadCountForUser($userId);

            return response()->json([
                'success' => true,
                'notifications' => $notifications,
                'unread_count' => $unreadCount
            ]);
        } catch (\Exception $e) {
            Log::error('Recent notifications error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'notifications' => [],
                'unread_count' => 0
            ]);
        }
    }

    /**
     * Get unread notification count
     */
    public function getUnreadCount()
    {
        try {
            $count = SystemNotification::getUnreadCountForUser(Auth::id());

            return response()->json([
                'success' => true,
                'count' => $count
            ]);
        } catch (\Exception $e) {
            Log::error('Notification count error: ' . $e->getMessage());
            return response()->json(['success' => false, 'count' => 0]);
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($id)
    {
        try {
            $notification = SystemNotification::findOrFail($id);

            // Check if user is authorized to read this
            if ($notification->canBeViewedBy(Auth::id())) {
                $notification->markAsReadBy(Auth::id());
                
                return response()->json([
                    'success' => true,
                    'message' => 'Notification marked as read'
                ]);
            }

            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);

        } catch (\Exception $e) {
            Log::error('Mark as read error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error marking as read']);
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        try {
            $userId = Auth::id();
            
            // Get all unread notifications for this user
            $unreadNotifications = SystemNotification::forUser($userId)
                ->unread($userId)
                ->get();

            foreach ($unreadNotifications as $notification) {
                $notification->markAsReadBy($userId);
            }

            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as read'
            ]);
        } catch (\Exception $e) {
            Log::error('Mark all as read error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error processing request']);
        }
    }
    
    /**
     * Display the specified notification
     */
    public function show($id)
    {
        try {
            $notification = SystemNotification::findOrFail($id);

            // Check permission
            if (!$notification->canBeViewedBy(Auth::id())) {
                abort(403);
            }

            // Mark as read when viewing
            $notification->markAsReadBy(Auth::id());

            // Use the correct view path
            return view('admin.notifications.show', compact('notification'));

        } catch (\Exception $e) {
            Log::error('Show notification error: ' . $e->getMessage());
            return redirect()->route('admin.notifications.index')->with('error', 'Notification not found');
        }
    }

    /**
     * Get notification preferences
     */
    public function preferences()
    {
        return view('admin.notifications.settings');
    }

    /**
     * Update notification preferences
     */
    public function updatePreferences(Request $request)
    {
        // Logic to update preferences would go here
        return back()->with('success', 'Preferences updated successfully');
    }
    
    /**
     * Dashboard view
     */
    public function dashboard()
    {
        return view('admin.notifications.dashboard');
    }
    
    /**
     * Settings view
     */
    public function settings()
    {
        return view('admin.notifications.settings');
    }
}