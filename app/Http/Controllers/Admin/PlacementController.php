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
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $students = $query->orderBy('name')->paginate(15)->withQueryString();
        $courses = Course::withoutGlobalScope('academic_year')->orderBy('name')->get();
        $batches = Batch::withoutGlobalScope('academic_year')->orderBy('name')->get();

        return view('admin.placements.index', compact('students', 'courses', 'batches'));
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
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $students = $query->orderBy('name')->get();

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\StudentPlacementExport($students), 
            'Placements_Report_'.now()->format('Ymd_His').'.xlsx'
        );
    }
}
