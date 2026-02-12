<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Course;
use App\Models\Batch;
use Illuminate\Http\Request;

class CertificateReportController extends Controller
{
    public function index(Request $request)
    {
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $courseId = $request->get('course_id');
        $batchId = $request->get('batch_id');
        $status = $request->get('status');
        $certificateType = $request->get('certificate_type');

        $query = Student::with(['batch.course'])
            ->where('status', 'active'); // Only active students

        // Filters
        if ($status) {
            if ($status === 'received') {
                $query->where('is_certificate_received', true);
            } elseif ($status === 'pending') {
                $query->where('is_certificate_received', false);
            }
        }

        if ($certificateType) {
            $query->where('certificate_type', $certificateType);
        }

        if ($courseId) {
            $query->whereHas('batch', function ($q) use ($courseId) {
                $q->where('course_id', $courseId);
            });
        }

        if ($batchId) {
            $query->where('batch_id', $batchId);
        }

        // Sorting
        if ($sortBy === 'course') {
            $query->join('batches', 'students.batch_id', '=', 'batches.id')
                ->join('courses', 'batches.course_id', '=', 'courses.id')
                ->orderBy('courses.name', $sortOrder)
                ->select('students.*'); // Avoid column collisions
        } elseif ($sortBy === 'batch') {
            $query->join('batches', 'students.batch_id', '=', 'batches.id')
                ->orderBy('batches.name', $sortOrder)
                ->select('students.*');
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $students = $query->paginate(15)->withQueryString();

        // Stats Calculation (respecting current filters)
        $statsQuery = Student::where('status', 'active');
        if ($courseId) {
            $statsQuery->whereHas('batch', function ($q) use ($courseId) {
                $q->where('course_id', $courseId); });
        }
        if ($batchId) {
            $statsQuery->where('batch_id', $batchId);
        }

        $totalStudents = $statsQuery->count();
        $receivedCount = $statsQuery->clone()->where('is_certificate_received', true)->count();
        $pendingCount = $statsQuery->clone()->where('is_certificate_received', false)->count();

        // Breakdown for charts
        $certTypeStats = $statsQuery->clone()
            ->where('is_certificate_received', true)
            ->select('certificate_type', \DB::raw('count(*) as count'))
            ->groupBy('certificate_type')
            ->pluck('count', 'certificate_type')
            ->toArray();

        // Course-wise Pending Stats
        $coursePendingStats = \DB::table('students')
            ->join('batches', 'students.batch_id', '=', 'batches.id')
            ->join('courses', 'batches.course_id', '=', 'courses.id')
            ->where('students.status', 'active')
            ->where('students.is_certificate_received', false)
            ->select('courses.name', \DB::raw('count(*) as count'))
            ->groupBy('courses.name')
            ->get();

        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.reports.certificates._table', compact('students', 'sortBy', 'sortOrder'))->render(),
                'stats' => [
                    'total' => $totalStudents,
                    'received' => $receivedCount,
                    'pending' => $pendingCount,
                    'typeStats' => $certTypeStats,
                    'coursePendingStats' => $coursePendingStats
                ]
            ]);
        }

        $courses = Course::orderBy('name')->get();
        $batches = $courseId ? Batch::where('course_id', $courseId)->orderBy('name')->get() : [];

        return view('admin.reports.certificates.index', compact(
            'students',
            'courses',
            'batches',
            'sortBy',
            'sortOrder',
            'courseId',
            'batchId',
            'status',
            'certificateType',
            'totalStudents',
            'receivedCount',
            'pendingCount',
            'certTypeStats',
            'coursePendingStats'
        ));
    }
}
