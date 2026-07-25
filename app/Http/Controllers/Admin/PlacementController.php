<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Course;
use App\Models\Batch;
use Illuminate\Http\Request;

class PlacementController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if (!$user->hasRole('super-admin') && !$user->hasRole('Placement Officer') && !$user->can('view students')) {
                abort(403, 'Unauthorized access. Only Placement Officers and Admins can view this portal.');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        // 1. Build base query for filtered student scope (Course, Batch, Search)
        $baseQuery = Student::withoutGlobalScope('academic_year')
            ->where('status', '!=', 'dropout');

        if ($request->filled('course_id')) {
            $baseQuery->whereHas('batch', function($q) use ($request) {
                $q->withoutGlobalScope('academic_year')->where('course_id', $request->course_id);
            });
        }

        if ($request->filled('batch_id')) {
            $baseQuery->where('batch_id', $request->batch_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $baseQuery->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('enrollment_number', 'like', "%{$search}%")
                  ->orWhere('student_mobile', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('placed_at', 'like', "%{$search}%")
                  ->orWhere('placement_designation', 'like', "%{$search}%");
            });
        }

        // 2. Compute accurate stats for current filter scope
        $totalStudents = (clone $baseQuery)->count();
        $jobCount = (clone $baseQuery)->where('placement_status', 'Job')->count();
        $internshipCount = (clone $baseQuery)->where('placement_status', 'Internship')->count();
        $trainingCount = (clone $baseQuery)->where('placement_status', 'Training')->count();
        $notPlacedCount = (clone $baseQuery)->where(function($q) {
            $q->whereNull('placement_status')->orWhere('placement_status', 'Not Placed');
        })->count();

        $placementRate = $totalStudents > 0 ? round((($jobCount + $internshipCount) / $totalStudents) * 100, 1) : 0;

        $stats = [
            'total' => $totalStudents,
            'job' => $jobCount,
            'internship' => $internshipCount,
            'training' => $trainingCount,
            'not_placed' => $notPlacedCount,
            'placement_rate' => $placementRate,
        ];

        // 3. Build table pagination query (adding placement_status filter & relationships)
        $query = (clone $baseQuery)->with(['batch' => function($q) {
            $q->withoutGlobalScope('academic_year')->with(['course' => function($cq) {
                $cq->withoutGlobalScope('academic_year');
            }]);
        }]);

        if ($request->filled('placement_status')) {
            if ($request->placement_status === 'Not Placed') {
                $query->where(function($q) {
                    $q->whereNull('placement_status')
                      ->orWhere('placement_status', 'Not Placed');
                });
            } else {
                $query->where('placement_status', $request->placement_status);
            }
        }

        $students = $query->orderBy('name')->paginate(15)->withQueryString();

        $courses = Course::withoutGlobalScope('academic_year')
            ->with(['batches' => function($q) {
                $q->withoutGlobalScope('academic_year')->orderBy('name');
            }])
            ->orderBy('name')
            ->get();

        // 4. Compute per-course statistics (filtered by selected course if specified)
        $courseQuery = Course::withoutGlobalScope('academic_year');
        if ($request->filled('course_id')) {
            $courseQuery->where('id', $request->course_id);
        }

        $courseStats = $courseQuery
            ->withCount(['students as total_students' => function($q) {
                $q->withoutGlobalScope('academic_year')->where('students.status', '!=', 'dropout');
            }])
            ->withCount(['students as placed_students' => function($q) {
                $q->withoutGlobalScope('academic_year')
                  ->where('students.status', '!=', 'dropout')
                  ->whereIn('students.placement_status', ['Job', 'Internship']);
            }])
            ->orderBy('name')
            ->get()
            ->map(function($course) {
                $course->placement_rate = $course->total_students > 0 
                    ? round(($course->placed_students / $course->total_students) * 100, 1) 
                    : 0;
                return $course;
            });

        return view('admin.placements.index', compact('students', 'courses', 'stats', 'courseStats'));
    }

    public function update(Request $request, Student $student)
    {
        $validated = $request->validate([
            'placement_status' => 'required|string',
            'placed_at' => 'nullable|string|max:255',
            'placement_designation' => 'nullable|string|max:255',
        ]);

        $student->update([
            'placement_status' => $validated['placement_status'],
            'placed_at' => $validated['placed_at'] ?? null,
            'placement_designation' => $validated['placement_designation'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Placement details updated successfully.');
    }

    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
            'placement_status' => 'required|string',
            'placed_at' => 'nullable|string|max:255',
            'placement_designation' => 'nullable|string|max:255',
        ]);

        Student::withoutGlobalScope('academic_year')
            ->whereIn('id', $validated['student_ids'])
            ->update([
                'placement_status' => $validated['placement_status'],
                'placed_at' => $validated['placed_at'] ?? null,
                'placement_designation' => $validated['placement_designation'] ?? null,
            ]);

        return redirect()->back()->with('success', count($validated['student_ids']) . ' students updated successfully.');
    }

    public function export(Request $request)
    {
        $query = Student::withoutGlobalScope('academic_year')
            ->with(['batch' => function($q) {
                $q->withoutGlobalScope('academic_year')->with(['course' => function($cq) {
                    $cq->withoutGlobalScope('academic_year');
                }]);
            }])
            ->where('status', '!=', 'dropout');

        if ($request->filled('course_id')) {
            $query->whereHas('batch', function($q) use ($request) {
                $q->withoutGlobalScope('academic_year')->where('course_id', $request->course_id);
            });
        }

        if ($request->filled('batch_id')) {
            $query->where('batch_id', $request->batch_id);
        }

        if ($request->filled('placement_status')) {
            if ($request->placement_status === 'Not Placed') {
                $query->where(function($q) {
                    $q->whereNull('placement_status')
                      ->orWhere('placement_status', 'Not Placed');
                });
            } else {
                $query->where('placement_status', $request->placement_status);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('enrollment_number', 'like', "%{$search}%")
                  ->orWhere('student_mobile', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('placed_at', 'like', "%{$search}%")
                  ->orWhere('placement_designation', 'like', "%{$search}%");
            });
        }

        $students = $query->orderBy('name')->get();

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\StudentPlacementExport($students), 
            'Placements_Report_'.now()->format('Ymd_His').'.xlsx'
        );
    }
}
