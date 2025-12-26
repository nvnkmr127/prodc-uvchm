<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance\NotificationLog;

class AttendanceNotificationController extends Controller
{
    /**
     * Display attendance notifications
     */
    public function index(Request $request)
    {
        try {
            // ✅ FIXED: Use paginate() instead of get() to make total() method available
            $notifications = NotificationLog::latest()->paginate(20);
            
            // Preserve query parameters for pagination links
            $notifications->appends($request->query());

            return view('attendance.notifications.index', compact('notifications'));
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load notifications: ' . $e->getMessage());
        }
    }

    /**
     * Get unread notification count for AJAX requests
     */
    public function getUnreadCount()
    {
        try {
            $count = NotificationLog::whereJsonDoesntContain('read_by', auth()->id())
                ->orWhereNull('read_by')
                ->count();

            return response()->json(['count' => $count]);
        } catch (\Exception $e) {
            return response()->json(['count' => 0]);
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(NotificationLog $notification)
    {
        try {
            $readBy = json_decode($notification->read_by ?? '[]', true);
            
            if (!in_array(auth()->id(), $readBy)) {
                $readBy[] = auth()->id();
                $notification->update(['read_by' => json_encode($readBy)]);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Mark all notifications as read for current user
     */
    public function markAllAsRead()
    {
        try {
            $notifications = NotificationLog::whereJsonDoesntContain('read_by', auth()->id())
                ->orWhereNull('read_by')
                ->get();

            foreach ($notifications as $notification) {
                $readBy = json_decode($notification->read_by ?? '[]', true);
                if (!in_array(auth()->id(), $readBy)) {
                    $readBy[] = auth()->id();
                    $notification->update(['read_by' => json_encode($readBy)]);
                }
            }

            return response()->json([
                'success' => true, 
                'message' => 'All notifications marked as read',
                'count' => $notifications->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Delete notification
     */
    public function destroy(NotificationLog $notification)
    {
        try {
            $notification->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Notification deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to delete notification: ' . $e->getMessage()
            ]);
        }
    }
}