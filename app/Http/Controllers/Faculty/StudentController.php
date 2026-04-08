<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function lookup(Request $request)
    {
        if ($request->ajax()) {
            $searchTerm = $request->get('q');

            if (empty($searchTerm) || strlen($searchTerm) < 2) {
                return response()->json(['students' => []]);
            }

            // Faculty should only see students from their batches
            $user = auth()->user();
            $students = Student::with('batch.course')
                ->whereHas('batch', function ($query) use ($user) {
                    $query->where('faculty_id', $user->id);
                })
                ->where(function ($query) use ($searchTerm) {
                    $query->where('name', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('enrollment_number', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('student_mobile', 'LIKE', "%{$searchTerm}%");
                })
                ->limit(10)
                ->get()
                ->map(function ($student) {
                    return [
                        'id' => $student->id,
                        'name' => $student->name,
                        'enrollment_number' => $student->enrollment_number,
                        'course' => $student->batch->course->name ?? 'N/A',
                        'batch' => $student->batch->name ?? 'N/A',
                        'mobile' => $student->student_mobile,
                        'status' => $student->status,
                        'url' => route('admin.students.show', $student),
                    ];
                });

            return response()->json(['students' => $students]);
        }

        return view('faculty.student_lookup');
    }
}
