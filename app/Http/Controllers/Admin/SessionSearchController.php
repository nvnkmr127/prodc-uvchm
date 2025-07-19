<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Course;
use App\Models\Batch;
use App\Models\User;
use App\Models\Invoice;

class SessionSearchController extends Controller
{
    /**
     * Perform global search across multiple models
     */
    public function search(Request $request)
    {
        $query = $request->input('q');
        
        if (empty($query) || strlen($query) < 2) {
            return response()->json([
                'students' => [],
                'courses' => [],
                'batches' => [],
                'faculty' => [],
                'invoices' => []
            ]);
        }

        $results = [
            'students' => $this->searchStudents($query),
            'courses' => $this->searchCourses($query),
            'batches' => $this->searchBatches($query),
            'faculty' => $this->searchFaculty($query),
            'invoices' => $this->searchInvoices($query),
        ];

        return response()->json($results);
    }

    /**
     * Search students
     */
    private function searchStudents($query)
    {
        return Student::where('name', 'LIKE', "%{$query}%")
                     ->orWhere('enrollment_number', 'LIKE', "%{$query}%")
                     ->orWhere('student_mobile', 'LIKE', "%{$query}%")
                     ->orWhere('email', 'LIKE', "%{$query}%")
                     ->with('batch.course')
                     ->limit(5)
                     ->get()
                     ->map(function ($student) {
                         return [
                             'id' => $student->id,
                             'name' => $student->name,
                             'enrollment_number' => $student->enrollment_number,
                             'course' => $student->batch->course->name ?? 'N/A',
                             'batch' => $student->batch->name ?? 'N/A',
                             'url' => route('admin.students.show', $student),
                             'type' => 'student'
                         ];
                     });
    }

    /**
     * Search courses
     */
    private function searchCourses($query)
    {
        return Course::where('name', 'LIKE', "%{$query}%")
                    ->limit(3)
                    ->get()
                    ->map(function ($course) {
                        return [
                            'id' => $course->id,
                            'name' => $course->name,
                            'duration' => $course->duration_in_years . ' years',
                            'url' => route('admin.courses.show', $course),
                            'type' => 'course'
                        ];
                    });
    }

    /**
     * Search batches
     */
    private function searchBatches($query)
    {
        return Batch::where('name', 'LIKE', "%{$query}%")
                   ->with('course')
                   ->limit(3)
                   ->get()
                   ->map(function ($batch) {
                       return [
                           'id' => $batch->id,
                           'name' => $batch->name,
                           'course' => $batch->course->name,
                           'students_count' => $batch->students()->count(),
                           'url' => route('admin.batches.show', $batch),
                           'type' => 'batch'
                       ];
                   });
    }

    /**
     * Search faculty
     */
    private function searchFaculty($query)
    {
        return User::role(['staff', 'admin', 'college-admin'])
                  ->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('email', 'LIKE', "%{$query}%")
                  ->limit(3)
                  ->get()
                  ->map(function ($faculty) {
                      return [
                          'id' => $faculty->id,
                          'name' => $faculty->name,
                          'email' => $faculty->email,
                          'roles' => $faculty->getRoleNames()->implode(', '),
                          'url' => route('admin.faculty.edit', $faculty),
                          'type' => 'faculty'
                      ];
                  });
    }

    /**
     * Search invoices
     */
    private function searchInvoices($query)
    {
        return Invoice::where('invoice_number', 'LIKE', "%{$query}%")
                     ->with('student')
                     ->limit(3)
                     ->get()
                     ->map(function ($invoice) {
                         return [
                             'id' => $invoice->id,
                             'invoice_number' => $invoice->invoice_number,
                             'student_name' => $invoice->student->name,
                             'total_amount' => $invoice->total_amount,
                             'status' => $invoice->status,
                             'url' => route('admin.invoices.show', $invoice),
                             'type' => 'invoice'
                         ];
                     });
    }
}