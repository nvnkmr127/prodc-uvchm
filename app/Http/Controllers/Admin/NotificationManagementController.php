<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemNotification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationManagementController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Show notification management dashboard
     */
    public function dashboard()
    {
        $stats = [
            'total_notifications' => SystemNotification::count(),
            'unread_notifications' => SystemNotification::whereNull('read_by')->count(),
            'notifications_today' => SystemNotification::whereDate('created_at', today())->count(),
            'notifications_this_week' => SystemNotification::where('created_at', '>=', now()->startOfWeek())->count(),
            'critical_notifications' => SystemNotification::where('priority', 'urgent')->where('created_at', '>=', now()->subDays(7))->count(),
        ];

        $recentNotifications = SystemNotification::orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $categoryBreakdown = SystemNotification::select('category', DB::raw('count(*) as count'))
            ->groupBy('category')
            ->get();

        $priorityBreakdown = SystemNotification::select('priority', DB::raw('count(*) as count'))
            ->groupBy('priority')
            ->get();

        return view('admin.notifications.dashboard', compact(
            'stats',
            'recentNotifications',
            'categoryBreakdown',
            'priorityBreakdown'
        ));
    }

    /**
     * Show all notifications with filters
     */
    public function index(Request $request)
    {
        $query = SystemNotification::query();

        // Apply filters
        if ($request->category) {
            $query->where('category', $request->category);
        }

        if ($request->priority) {
            $query->where('priority', $request->priority);
        }

        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->unread_only) {
            $query->whereNull('read_by');
        }

        $notifications = $query->orderBy('created_at', 'desc')->paginate(20);

        $categories = SystemNotification::distinct()->pluck('category');
        $priorities = SystemNotification::distinct()->pluck('priority');
        $types = SystemNotification::distinct()->pluck('type');

        return view('admin.notifications.index', compact(
            'notifications',
            'categories',
            'priorities',
            'types'
        ));
    }

    /**
     * Show notification details
     */
    public function show(SystemNotification $notification)
    {
        return view('admin.notifications.show', compact('notification'));
    }

    /**
     * Test notification system
     */
    public function testNotifications(Request $request)
    {
        $request->validate([
            'test_type' => 'required|string|in:all,financial,academic,system,attendance',
        ]);

        $results = [];

        try {
            switch ($request->test_type) {
                case 'all':
                    $results = $this->runAllTests();
                    break;
                case 'financial':
                    $results = $this->testFinancialNotifications();
                    break;
                case 'academic':
                    $results = $this->testAcademicNotifications();
                    break;
                case 'system':
                    $results = $this->testSystemNotifications();
                    break;
                case 'attendance':
                    $results = $this->testAttendanceNotifications();
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => 'Test notifications sent successfully',
                'results' => $results,
            ]);

        } catch (\Exception $e) {
            Log::error('Notification test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Test failed due to system error',
            ], 500);
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(SystemNotification $notification)
    {
        try {
            $success = $notification->markAsReadBy(auth()->id());

            return response()->json([
                'success' => $success,
                'message' => $success ? 'Notification marked as read' : 'Failed to mark as read',
            ]);
        } catch (\Exception $e) {
            Log::error('Mark as read failed', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notification as read',
            ], 500);
        }
    }

    /**
     * Bulk mark notifications as read
     */
    public function markAllAsRead(Request $request)
    {
        try {
            $query = SystemNotification::whereNull('read_by');

            if ($request->category) {
                $query->where('category', $request->category);
            }

            $notifications = $query->get();
            $count = 0;

            foreach ($notifications as $notification) {
                if ($notification->markAsReadBy(auth()->id())) {
                    $count++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Marked {$count} notifications as read",
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk mark as read failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notifications as read',
            ], 500);
        }
    }

    /**
     * Export notifications
     */
    public function export(Request $request)
    {
        try {
            $query = SystemNotification::query();

            // Apply same filters as index
            if ($request->category) {
                $query->where('category', $request->category);
            }
            if ($request->priority) {
                $query->where('priority', $request->priority);
            }
            if ($request->type) {
                $query->where('type', $request->type);
            }
            if ($request->date_from) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->date_to) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $notifications = $query->orderBy('created_at', 'desc')->get();

            $csvData = [];
            $csvData[] = ['ID', 'Title', 'Message', 'Type', 'Category', 'Priority', 'Created At', 'Read By Count'];

            foreach ($notifications as $notification) {
                $csvData[] = [
                    $notification->id,
                    $notification->title,
                    $notification->message,
                    $notification->type,
                    $notification->category,
                    $notification->priority,
                    $notification->created_at->format('Y-m-d H:i:s'),
                    $notification->read_by ? count($notification->read_by) : 0,
                ];
            }

            $filename = 'notifications_export_'.now()->format('Y-m-d_H-i-s').'.csv';

            return response()->stream(function () use ($csvData) {
                $handle = fopen('php://output', 'w');
                foreach ($csvData as $row) {
                    fputcsv($handle, $row);
                }
                fclose($handle);
            }, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]);
        } catch (\Exception $e) {
            Log::error('Notification export failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Export operation failed',
            ], 500);
        }
    }

    // Test methods with proper data handling

}
