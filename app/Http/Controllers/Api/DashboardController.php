<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Dashboard;
use App\Models\DashboardWidget;
use App\Models\Payment;
use App\Models\StudentFee;
use App\Models\Timetable;
use App\Models\Widget;
use App\Services\DashboardDataService;
use App\Services\DashboardPermissionService;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    public function storeMetrics(Request $request)
    {
        $validated = $request->validate([
            'metrics' => 'required|array',
            'session' => 'nullable|string|max:100',
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
            'config' => 'nullable|array',
        ]);

        $user = auth()->user();
        $widget = Widget::findOrFail($request->widget_id);

        if (! $this->permissionService->canViewWidget($user, $widget)) {
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
            'cache_duration' => $widget->cache_duration,
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
            'refreshed_at' => now()->toISOString(),
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
                'created_at' => now()->subHours(1)->toISOString(),
            ],
            [
                'id' => 2,
                'title' => 'Data Refresh',
                'message' => 'Widget data has been refreshed',
                'type' => 'success',
                'read' => true,
                'created_at' => now()->subHours(3)->toISOString(),
            ],
        ];

        return response()->json([
            'notifications' => array_slice($notifications, 0, $limit),
            'unread_count' => collect($notifications)->where('read', false)->count(),
            'total_count' => count($notifications),
        ]);
    }

    // Helper Methods

    protected function exportToCsv($data, $filename): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'.csv"',
        ];

        return response()->stream(function () use ($data) {
            $file = fopen('php://output', 'w');

            if (is_array($data) && ! empty($data)) {
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
            'data' => $data,
        ]);
    }

    private function calculateCollectionRate(): float
    {
        $collected = (float) Payment::where('status', 'completed')->sum('amount');
        $due = (float) StudentFee::whereIn('status', ['unpaid', 'partial'])
            ->selectRaw('COALESCE(SUM(GREATEST(0, amount - COALESCE(concession_amount, 0) - COALESCE(paid_amount, 0))), 0) as due')
            ->value('due');

        $total = $collected + $due;

        return $total > 0 ? round(($collected / $total) * 100, 1) : 0;
    }

    private function getMyStudentsCount($user): int
    {
        return Timetable::where('user_id', $user->id)
            ->with('batch.students')
            ->get()
            ->pluck('batch.students')
            ->flatten()
            ->unique('id')
            ->count();
    }

    private function getStudentAttendancePercentage($student): float
    {
        $attendances = Attendance::where('student_id', $student->id)->get();
        $total = $attendances->count();
        $present = $attendances->where('status', 'present')->count();

        return $total > 0 ? round(($present / $total) * 100, 1) : 0;
    }
}
