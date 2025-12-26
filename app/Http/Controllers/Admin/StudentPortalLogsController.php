<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StudentPortalActivityLog;
use App\Models\Student;
use App\Services\SuspiciousActivityDetector;
use Illuminate\Http\Request;
use Carbon\Carbon;

class StudentPortalLogsController extends Controller
{
    public function index(Request $request)
    {
        $query = StudentPortalActivityLog::with('student')
            ->orderBy('created_at', 'desc');

        // Filter by student
        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        // Filter by action
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Filter by suspicious activity
        if ($request->filled('suspicious') && $request->suspicious == '1') {
            $query->where('is_suspicious', true);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(50);

        // Get unique actions for filter dropdown
        $actions = StudentPortalActivityLog::distinct()->pluck('action');

        return view('admin.student-portal-logs.index', compact('logs', 'actions'));
    }

    public function export(Request $request)
    {
        $query = StudentPortalActivityLog::with('student')
            ->orderBy('created_at', 'desc');

        // Apply same filters as index
        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('suspicious') && $request->suspicious == '1') {
            $query->where('is_suspicious', true);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->get();

        $filename = 'student_portal_logs_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');

            // CSV Headers
            fputcsv($file, [
                'ID',
                'Student Name',
                'Enrollment Number',
                'Action',
                'IP Address',
                'Location',
                'Mobile Number',
                'Timestamp',
                'Suspicious',
                'Flagged Reason',
                'Metadata'
            ]);

            // Data rows
            foreach ($logs as $log) {
                $location = '';
                if ($log->location_data) {
                    $location = ($log->location_data['city'] ?? '') . ', ' . ($log->location_data['country'] ?? '');
                }

                fputcsv($file, [
                    $log->id,
                    $log->student->name ?? 'N/A',
                    $log->student->enrollment_number ?? 'N/A',
                    $log->action,
                    $log->ip_address,
                    $location,
                    $log->mobile_number,
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->is_suspicious ? 'Yes' : 'No',
                    $log->flagged_reason ?? '',
                    json_encode($log->metadata)
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function dashboard()
    {
        // Get statistics
        $stats = $this->getStats(new Request(['hours' => 24]));

        // Get recent suspicious activities
        $suspiciousActivities = SuspiciousActivityDetector::getRecentSuspicious(10);

        // Get recent activities
        $recentActivities = StudentPortalActivityLog::with('student')
            ->orderBy('created_at', 'desc')
            ->limit(15)
            ->get();

        return view('admin.student-portal-logs.dashboard', compact('stats', 'suspiciousActivities', 'recentActivities'));
    }

    public function getStats(Request $request)
    {
        $hours = $request->get('hours', 24);
        $since = Carbon::now()->subHours($hours);

        $stats = [
            'total_logins_today' => StudentPortalActivityLog::where('action', 'login_success')
                ->whereDate('created_at', today())
                ->count(),

            'failed_logins_today' => StudentPortalActivityLog::where('action', 'login_failed')
                ->whereDate('created_at', today())
                ->count(),

            'active_students' => StudentPortalActivityLog::where('action', 'login_success')
                ->where('created_at', '>=', $since)
                ->distinct('student_id')
                ->count('student_id'),

            'suspicious_count' => SuspiciousActivityDetector::getSuspiciousCount($hours),

            'top_locations' => StudentPortalActivityLog::where('created_at', '>=', $since)
                ->whereNotNull('location_data')
                ->get()
                ->groupBy(function ($log) {
                    return $log->location_data['city'] ?? 'Unknown';
                })
                ->map->count()
                ->sortDesc()
                ->take(5),

            'hourly_activity' => StudentPortalActivityLog::where('created_at', '>=', Carbon::now()->subHours(24))
                ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
                ->groupBy('hour')
                ->pluck('count', 'hour'),
        ];

        return response()->json($stats);
    }

    public function show($id)
    {
        $log = StudentPortalActivityLog::with('student')->findOrFail($id);
        return view('admin.student-portal-logs.show', compact('log'));
    }
}
