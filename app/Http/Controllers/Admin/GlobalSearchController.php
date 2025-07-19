<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Course;
use App\Models\User;

class GlobalSearchController extends Controller
{
    public function search(Request $request)
    {
        $searchTerm = $request->input('q');

        // Validate search term
        if (empty($searchTerm) || strlen($searchTerm) < 2) {
            return response()->json(['results' => []]);
        }

        $results = [];

        // Search for students
        $students = Student::where('name', 'LIKE', "%{$searchTerm}%")
                           ->orWhere('enrollment_number', 'LIKE', "%{$searchTerm}%")
                           ->orWhere('student_mobile', 'LIKE', "%{$searchTerm}%")
                           ->orWhere('father_mobile', 'LIKE', "%{$searchTerm}%")
                           ->with('batch.course')
                           ->limit(5)
                           ->get();

        foreach ($students as $student) {
            $results[] = [
                'type' => 'Student',
                'title' => $student->name . ' (' . $student->enrollment_number . ')',
                'url' => route('admin.students.show', $student),
                'icon' => 'fa-user-graduate'
            ];
        }

        // Search for courses
        $courses = Course::where('name', 'LIKE', "%{$searchTerm}%")
                         ->limit(3)
                         ->get();

        foreach ($courses as $course) {
            $results[] = [
                'type' => 'Course',
                'title' => $course->name,
                'url' => route('admin.courses.show', $course),
                'icon' => 'fa-book'
            ];
        }

        // Search for faculty (if user has permission)
        if (auth()->user()->can('view faculty')) {
            $faculty = User::role('staff')
                          ->where('name', 'LIKE', "%{$searchTerm}%")
                          ->limit(3)
                          ->get();

            foreach ($faculty as $member) {
                $results[] = [
                    'type' => 'Faculty',
                    'title' => $member->name,
                    'url' => route('admin.faculty.show', $member),
                    'icon' => 'fa-chalkboard-teacher'
                ];
            }
        }

        return response()->json(['results' => $results]);
    }
}