<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\{DashboardService, DashboardDataService, DashboardPermissionService};
use App\Models\{Widget, Dashboard, DashboardWidget};
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    protected $dashboardService;
    protected $dataService;
    protected $permissionService;

    public function __construct(
        DashboardService $dashboardService,
        DashboardDataService $dataService,
        DashboardPermissionService $permissionService
    ) {
        $this->middleware('auth');
        $this->middleware('throttle:dashboard-api');
        
        $this->dashboardService = $dashboardService;
        $this->dataService = $dataService;
        $this->permissionService = $permissionService;
    }

    public function stats(Request $request)
    {
        return $this->getQuickStats($request);
    }

    public function getWidgetDataForWidget(Request $request, $widgetId)
    {
        $user = auth()->user();

        if (!class_exists('App\\Models\\Widget')) {
            return response()->json([
                'error' => 'Widget system not available'
            ], 501);
        }

        $widgetClass = 'App\\Models\\Widget';
        $widget = $widgetClass::findOrFail($widgetId);

        if (!$this->permissionService->canViewWidget($user, $widget)) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        $config = [];
        $data = $this->dataService->getWidgetData($user, $widget, $config);

        return response()->json([
            'widget_id' => $widget->id,
            'widget_name' => $widget->name,
            'data' => $data,
            'last_updated' => now()->toISOString(),
            'cache_duration' => $widget->cache_duration
        ]);
    }

    public function storeMetrics(Request $request)
    {
        $validated = $request->validate([
            'metrics' => 'required|array',
            'session' => 'nullable|string|max:100'
        ]);

        $metrics = $validated['metrics'];
        $widgetRender = $metrics['widget-render'] ?? [];

        $maxWidgetRenderMs = null;
        if (is_array($widgetRender)) {
            foreach ($widgetRender as $entry) {
                $duration = is_array($entry) ? ($entry['duration'] ?? null) : null;
                if (is_numeric($duration)) {
                    $duration = (float) $duration;
                    $maxWidgetRenderMs = $maxWidgetRenderMs === null ? $duration : max($maxWidgetRenderMs, $duration);
                }
            }
        }

        $nav = $metrics['navigation'][0] ?? null;
        $navSummary = null;
        if (is_array($nav)) {
            $navSummary = [
                'loadTime' => $nav['loadTime'] ?? null,
                'domComplete' => $nav['domComplete'] ?? null,
                'firstPaint' => $nav['firstPaint'] ?? null,
            ];
        }

        Log::channel('dashboard')->info('Client performance metrics', [
            'user_id' => auth()->id(),
            'session' => $validated['session'] ?? null,
            'nav' => $navSummary,
            'max_widget_render_ms' => $maxWidgetRenderMs,
        ]);

        return response()->json(['status' => 'ok']);
    }

    /**
     * Get widget data
     */
    public function getWidgetData(Request $request)
    {
        $request->validate([
            'widget_id' => 'required|exists:widgets,id',
            'instance_id' => 'nullable|string',
            'config' => 'nullable|array'
        ]);

        $user = auth()->user();
        $widget = Widget::findOrFail($request->widget_id);

        if (!$this->permissionService->canViewWidget($user, $widget)) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        $config = $request->config ?? [];
        
        // If instance_id provided, get instance-specific config
        if ($request->instance_id) {
            $dashboardWidget = DashboardWidget::where('instance_id', $request->instance_id)->first();
            if ($dashboardWidget) {
                $config = array_merge($config, $dashboardWidget->getMergedConfig());
            }
        }

        $data = $this->dataService->getWidgetData($user, $widget, $config);

        return response()->json([
            'widget_id' => $widget->id,
            'widget_name' => $widget->name,
            'instance_id' => $request->instance_id,
            'data' => $data,
            'last_updated' => now()->toISOString(),
            'cache_duration' => $widget->cache_duration
        ]);
    }

    /**
     * Refresh dashboard data
     */
    public function refreshDashboard(Request $request)
    {
        $user = auth()->user();
        
        // Clear user cache
        $this->dashboardService->clearUserCache($user);
        
        // Get fresh dashboard data
        $dashboardData = $this->dashboardService->getDashboardData($user);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Dashboard refreshed successfully',
            'dashboard_data' => $dashboardData,
            'refreshed_at' => now()->toISOString()
        ]);
    }

    /**
     * Update dashboard layout
     */
    public function updateLayout(Request $request)
    {
        $request->validate([
            'dashboard_id' => 'required|exists:dashboards,id',
            'widgets' => 'required|array',
            'widgets.*.instance_id' => 'required|string',
            'widgets.*.x' => 'required|integer|min:0',
            'widgets.*.y' => 'required|integer|min:0',
            'widgets.*.w' => 'required|integer|min:1',
            'widgets.*.h' => 'required|integer|min:1',
            'widgets.*.order' => 'nullable|integer'
        ]);

        $user = auth()->user();
        $dashboard = Dashboard::findOrFail($request->dashboard_id);

        if (!$this->permissionService->canEditDashboard($user, $dashboard)) {
            return response()->json(['error' => 'Cannot edit this dashboard'], 403);
        }

        $success = $this->dashboardService->updateUserDashboardLayout(
            $user,
            $dashboard,
            $request->widgets
        );

        return response()->json([
            'status' => $success ? 'success' : 'error',
            'message' => $success ? 'Layout updated successfully' : 'Failed to update layout',
            'updated_at' => now()->toISOString()
        ]);
    }

    /**
     * Get dashboard notifications
     */
    public function getNotifications(Request $request)
    {
        $user = auth()->user();
        $limit = $request->get('limit', 10);

        // Sample notifications - integrate with your notification system
        $notifications = [
            [
                'id' => 1,
                'title' => 'System Update',
                'message' => 'Dashboard system has been updated',
                'type' => 'info',
                'read' => false,
                'created_at' => now()->subHours(1)->toISOString()
            ],
            [
                'id' => 2,
                'title' => 'Data Refresh',
                'message' => 'Widget data has been refreshed',
                'type' => 'success',
                'read' => true,
                'created_at' => now()->subHours(3)->toISOString()
            ]
        ];

        return response()->json([
            'notifications' => array_slice($notifications, 0, $limit),
            'unread_count' => collect($notifications)->where('read', false)->count(),
            'total_count' => count($notifications)
        ]);
    }

    /**
     * Get quick stats for dashboard
     */
    public function getQuickStats(Request $request)
    {
        $user = auth()->user();
        $roleName = $user->getRoleNames()->first();

        $stats = $this->getStatsForRole($roleName);

        return response()->json([
            'role' => $roleName,
            'stats' => $stats,
            'generated_at' => now()->toISOString()
        ]);
    }

    /**
     * Export widget data
     */
    public function exportWidgetData(Request $request)
    {
        $request->validate([
            'widget_id' => 'required|exists:widgets,id',
            'format' => 'required|in:json,csv,xlsx'
        ]);

        $user = auth()->user();
        $widget = Widget::findOrFail($request->widget_id);

        if (!$this->permissionService->canViewWidget($user, $widget)) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        $data = $this->dataService->getWidgetData($user, $widget);
        
        switch ($request->format) {
            case 'json':
                return response()->json($data);
                
            case 'csv':
                return $this->exportToCsv($data, $widget->name);
                
            case 'xlsx':
                return $this->exportToExcel($data, $widget->name);
                
            default:
                return response()->json(['error' => 'Invalid format'], 400);
        }
    }

    // Helper Methods
    protected function getStatsForRole($roleName): array
    {
        switch ($roleName) {
            case 'super-admin':
                return [
                    'total_users' => \App\Models\User::count(),
                    'total_students' => \App\Models\Student::count(),
                    'monthly_revenue' => \App\Models\Payment::where('status', 'completed')
                        ->whereMonth('payment_date', now()->month)
                        ->sum('amount'),
                    'system_health' => 'good'
                ];

            case 'college-admin':
                return [
                    'total_students' => \App\Models\Student::count(),
                    'active_courses' => \App\Models\Course::where('is_active', true)->count(),
                    'today_classes' => \App\Models\Timetable::whereDate('schedule_date', today())->count(),
                    'pending_enquiries' => \App\Models\Enquiry::where('status', 'pending')->count()
                ];

            case 'accountant':
                $pendingAmount = (float) \App\Models\StudentFee::whereIn('status', ['unpaid', 'partial'])
                    ->selectRaw('COALESCE(SUM(GREATEST(0, amount - COALESCE(concession_amount, 0) - COALESCE(paid_amount, 0))), 0) as due')
                    ->value('due');

                $overdueAmount = (float) \App\Models\StudentFee::whereIn('status', ['unpaid', 'partial'])
                    ->whereNotNull('due_date')
                    ->where('due_date', '<', now())
                    ->selectRaw('COALESCE(SUM(GREATEST(0, amount - COALESCE(concession_amount, 0) - COALESCE(paid_amount, 0))), 0) as due')
                    ->value('due');

                return [
                    'monthly_revenue' => \App\Models\Payment::where('status', 'completed')
                        ->whereMonth('payment_date', now()->month)
                        ->sum('amount'),
                    'pending_amount' => $pendingAmount,
                    'overdue_amount' => $overdueAmount,
                    'collection_rate' => $this->calculateCollectionRate()
                ];

            case 'staff':
                $user = auth()->user();
                return [
                    'today_classes' => \App\Models\Timetable::where('user_id', $user->id)
                        ->whereDate('schedule_date', today())->count(),
                    'pending_attendance' => \App\Models\Timetable::where('user_id', $user->id)
                        ->whereDate('schedule_date', today())
                        ->whereDoesntHave('attendances')->count(),
                    'my_students' => $this->getMyStudentsCount($user),
                    'attendance_rate' => $this->getMyAttendanceRate($user)
                ];

            case 'student':
                $student = auth()->user()->student;
                if (!$student) return [];
                
                $pendingFees = (float) \App\Models\StudentFee::where('student_id', $student->id)
                    ->whereIn('status', ['unpaid', 'partial'])
                    ->selectRaw('COALESCE(SUM(GREATEST(0, amount - COALESCE(concession_amount, 0) - COALESCE(paid_amount, 0))), 0) as due')
                    ->value('due');

                return [
                    'attendance_percentage' => $this->getStudentAttendancePercentage($student),
                    'pending_fees' => $pendingFees,
                    'today_classes' => $this->getStudentTodayClassesCount($student),
                    'upcoming_exams' => 2 // Sample count
                ];

            default:
                return [];
        }
    }

    protected function exportToCsv($data, $filename): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"',
        ];

        return response()->stream(function () use ($data) {
            $file = fopen('php://output', 'w');
            
            if (is_array($data) && !empty($data)) {
                // Write headers
                if (isset($data[0])) {
                    fputcsv($file, array_keys($data[0]));
                }
                
                // Write data
                foreach ($data as $row) {
                    fputcsv($file, $row);
                }
            }
            
            fclose($file);
        }, 200, $headers);
    }

    protected function exportToExcel($data, $filename)
    {
        // This would require a package like PhpSpreadsheet
        // For now, return JSON with a note
        return response()->json([
            'message' => 'Excel export not implemented yet',
            'data' => $data
        ]);
    }

    private function calculateCollectionRate(): float
    {
        $collected = (float) \App\Models\Payment::where('status', 'completed')->sum('amount');
        $due = (float) \App\Models\StudentFee::whereIn('status', ['unpaid', 'partial'])
            ->selectRaw('COALESCE(SUM(GREATEST(0, amount - COALESCE(concession_amount, 0) - COALESCE(paid_amount, 0))), 0) as due')
            ->value('due');

        $total = $collected + $due;

        return $total > 0 ? round(($collected / $total) * 100, 1) : 0;
    }

    private function getMyStudentsCount($user): int
    {
        return \App\Models\Timetable::where('user_id', $user->id)
            ->with('batch.students')
            ->get()
            ->pluck('batch.students')
            ->flatten()
            ->unique('id')
            ->count();
    }

    private function getMyAttendanceRate($user): float
    {
        $totalClasses = \App\Models\Timetable::where('user_id', $user->id)->count();
        $attendanceTaken = \App\Models\Timetable::where('user_id', $user->id)
            ->whereHas('attendances')->count();
        
        return $totalClasses > 0 ? round(($attendanceTaken / $totalClasses) * 100, 1) : 0;
    }

    private function getStudentAttendancePercentage($student): float
    {
        $attendances = \App\Models\Attendance::where('student_id', $student->id)->get();
        $total = $attendances->count();
        $present = $attendances->where('status', 'present')->count();
        
        return $total > 0 ? round(($present / $total) * 100, 1) : 0;
    }

    private function getStudentTodayClassesCount($student): int
    {
        $batch = $student->batch;
        if (!$batch) return 0;
        
        return \App\Models\Timetable::where('batch_id', $batch->id)
            ->whereDate('schedule_date', today())
            ->count();
    }
}
