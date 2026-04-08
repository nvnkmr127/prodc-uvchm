<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Student;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function search(Request $request)
    {
        $searchTerm = $request->input('q');

        // If the search query is too short, return empty results
        if (empty($searchTerm) || strlen($searchTerm) < 2) {
            return response()->json(['students' => [], 'courses' => []]);
        }

        // Search for students by name, enrollment number, or mobile number
        $students = Student::where('name', 'LIKE', "%{$searchTerm}%")
            ->orWhere('enrollment_number', 'LIKE', "%{$searchTerm}%")
            ->orWhere('student_mobile', 'LIKE', "%{$searchTerm}%")
            ->limit(5)->get(['id', 'name', 'enrollment_number']);

        // Also search for courses by name
        $courses = Course::where('name', 'LIKE', "%{$searchTerm}%")
            ->limit(3)->get(['id', 'name']);

        return response()->json([
            'students' => $students,
            'courses' => $courses,
        ]);
    }
}
