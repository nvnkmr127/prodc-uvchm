<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    /**
     * Search for students by name, enrollment number, or mobile number.
     */
    public function search(Request $request)
    {
        $searchTerm = $request->input('q');

        if (empty($searchTerm)) {
            return response()->json(['data' => []]);
        }

        $students = Student::with('batch.course')
                           ->where('name', 'LIKE', "%{$searchTerm}%")
                           ->orWhere('enrollment_number', 'LIKE', "%{$searchTerm}%")
                           ->orWhere('student_mobile', 'LIKE', "%{$searchTerm}%")
                           ->limit(10)
                           ->get();

        // We can re-format the data for a cleaner API response if needed
        $formattedStudents = $students->map(function ($student) {
            return [
                'id' => $student->id,
                'name' => $student->name,
                'enrollment_number' => $student->enrollment_number,
                'course' => $student->batch->course->name ?? 'N/A',
                'batch' => $student->batch->name ?? 'N/A',
            ];
        });

        return response()->json(['data' => $formattedStudents]);
    }

    /**
     * Get the full details for a single student.
     */
    public function show(Student $student)
    {
        // Load all the relationships you want to show
        $student->load('batch.course', 'invoices', 'attendances');

        return response()->json(['data' => $student]);
    }
}