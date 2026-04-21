<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\Attendance;
use App\Models\Enquiry;
use App\Models\FollowUp;
use App\Models\Student;
use App\Models\StudentFee;
use App\Models\StudentPortalActivityLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentAnalyticsController extends Controller
{
    /**
     * Main Analytics Dashboard
     */
    public function index(Request $request)
    {
        $lifecycleStats = $this->getLifecycleOverview();
        $engagementStats = $this->getEngagementOverview();
        
        return view('admin.analytics.student.index', compact('lifecycleStats', 'engagementStats'));
    }

    /**
     * Student Lifecycle & Retention Analysis
     */
    public function lifecycle(Request $request)
    {
        $startDate = $request->input('start_date', now()->subMonths(12)->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        $courseId = $request->input('course_id');
        $batchId = $request->input('batch_id');

        // 1. Dropout Risk Indicators (Attendance < 75% + Pending Fees)
        // Optimized: Get attendance percentages for all active students in one query
        $attendanceStart = now()->subMonths(3);
        $attendanceData = Attendance::where('attendance_date', '>=', $attendanceStart)
            ->select('student_id', 
                DB::raw('COUNT(*) as total_days'),
                DB::raw('SUM(CASE WHEN status IN ("present", "late", "excused", "internship") THEN 1 ELSE 0 END) as present_days')
            )
            ->groupBy('student_id')
            ->get()
            ->keyBy('student_id');

        $dropoutRiskStudents = Student::where('status', 'active')
            ->when($courseId, function($q) use ($courseId) {
                return $q->whereHas('batch', function($bq) use ($courseId) {
                    $bq->where('course_id', $courseId);
                });
            })
            ->when($batchId, function($q) use ($batchId) {
                return $q->where('batch_id', $batchId);
            })
            ->whereHas('studentFees', function($q) {
                $q->whereRaw('amount - concession_amount - paid_amount > 0');
            })
            ->with(['batch.course'])
            ->get()
            ->filter(function($student) use ($attendanceData) {
                $atData = $attendanceData->get($student->id);
                if (!$atData || $atData->total_days == 0) return false;
                
                $percentage = ($atData->present_days / $atData->total_days) * 100;
                return $percentage < 75;
            })->values();

        // 2. Enrollment Funnel Leakage
        $funnelData = [
            'enquiries' => Enquiry::whereBetween('created_at', [$startDate, $endDate])
                ->when($courseId, fn($q) => $q->where('course_id', $courseId))
                ->count(),
            'admissions' => Admission::whereBetween('created_at', [$startDate, $endDate])
                ->when($courseId, fn($q) => $q->where('course_id', $courseId))
                ->count(),
            'approved' => Admission::whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'approved')
                ->when($courseId, fn($q) => $q->where('course_id', $courseId))
                ->count(),
        ];

        // 3. Cohort Retention (by Admission Year)
        $cohortRetention = Admission::selectRaw('YEAR(created_at) as year, count(*) as total, sum(case when status = "approved" then 1 else 0 end) as approved')
            ->when($courseId, fn($q) => $q->where('course_id', $courseId))
            ->groupBy('year')
            ->orderBy('year', 'desc')
            ->get();

        $courses = Course::all();
        $batches = $courseId ? Batch::where('course_id', $courseId)->get() : Batch::all();

        return view('admin.analytics.student.lifecycle', compact('dropoutRiskStudents', 'funnelData', 'cohortRetention', 'startDate', 'endDate', 'courses', 'batches', 'courseId', 'batchId'));
    }

    /**
     * Behavioral & Engagement Analytics
     */
    public function engagement(Request $request)
    {
        $days = (int) $request->input('days', 30);
        $startDate = now()->subDays($days);
        $courseId = $request->input('course_id');
        $batchId = $request->input('batch_id');

        // 1. Portal Engagement Score (Active users vs Total users)
        $totalStudents = Student::where('status', 'active')
            ->when($courseId, function($q) use ($courseId) {
                return $q->whereHas('batch', function($bq) use ($courseId) { $bq->where('course_id', $courseId); });
            })
            ->when($batchId, fn($q) => $q->where('batch_id', $batchId))
            ->count();

        $activeInPortal = StudentPortalActivityLog::where('created_at', '>=', $startDate)
            ->whereHas('student', function($q) use ($courseId, $batchId) {
                $q->where('status', 'active')
                ->when($courseId, function($sq) use ($courseId) {
                    $sq->whereHas('batch', fn($bq) => $bq->where('course_id', $courseId));
                })
                ->when($batchId, fn($sq) => $sq->where('batch_id', $batchId));
            })
            ->distinct('student_id')
            ->count('student_id');
        
        $engagementRate = $totalStudents > 0 ? round(($activeInPortal / $totalStudents) * 100, 1) : 0;

        // 2. Most Active Students
        $topActiveStudents = StudentPortalActivityLog::select('student_id', DB::raw('count(*) as activity_count'))
            ->where('created_at', '>=', $startDate)
            ->whereHas('student', function($q) use ($courseId, $batchId) {
                $q->when($courseId, function($sq) use ($courseId) {
                    $sq->whereHas('batch', fn($bq) => $bq->where('course_id', $courseId));
                })
                ->when($batchId, fn($sq) => $sq->where('batch_id', $batchId));
            })
            ->groupBy('student_id')
            ->orderBy('activity_count', 'desc')
            ->with('student')
            ->limit(10)
            ->get();

        // 3. Counselor Performance
        $counselors = User::whereHas('roles', function($q) {
                $q->whereIn('name', ['college-admin', 'super-admin']);
            })
            ->withCount([
                'assignedEnquiries as total_enquiries' => function($q) use ($courseId) {
                    $q->when($courseId, fn($eq) => $eq->where('course_id', $courseId));
                },
                'assignedEnquiries as converted_enquiries' => function($q) use ($courseId) {
                    $q->whereIn('status', ['converted', 'Admitted'])
                      ->when($courseId, fn($eq) => $eq->where('course_id', $courseId));
                }
            ])
            ->get();

        $counselorPerformance = $counselors->map(function($user) {
            $user->conversion_rate = $user->total_enquiries > 0 
                ? round(($user->converted_enquiries / $user->total_enquiries) * 100, 1) 
                : 0;
            return $user;
        })->sortByDesc('conversion_rate');

        $courses = Course::all();
        $batches = $courseId ? Batch::where('course_id', $courseId)->get() : Batch::all();

        return view('admin.analytics.student.engagement', compact('engagementRate', 'topActiveStudents', 'counselorPerformance', 'days', 'courses', 'batches', 'courseId', 'batchId'));
    }

    /**
     * PRIVATE: Get lifecycle overview for main dashboard
     */
    private function getLifecycleOverview()
    {
        return [
            'total_active' => Student::where('status', 'active')->count(),
            'dropout_count' => Student::where('status', 'dropout')->count(),
            'recent_conversions' => Enquiry::where('status', 'converted')->where('updated_at', '>=', now()->subDays(30))->count(),
        ];
    }

    /**
     * PRIVATE: Get engagement overview for main dashboard
     */
    private function getEngagementOverview()
    {
        return [
            'daily_active_portal' => StudentPortalActivityLog::where('created_at', '>=', now()->startOfDay())->distinct('student_id')->count(),
            'weekly_active_portal' => StudentPortalActivityLog::where('created_at', '>=', now()->subDays(7))->distinct('student_id')->count(),
        ];
    }
}
