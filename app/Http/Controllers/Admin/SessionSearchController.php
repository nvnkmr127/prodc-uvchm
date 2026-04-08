<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Course;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request; // ✅ CHANGED: Replaced Invoice with Payment model

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
                'payments' => [], // ✅ CHANGED: 'invoices' key is now 'payments'
            ]);
        }

        $results = [
            'students' => $this->searchStudents($query),
            'courses' => $this->searchCourses($query),
            'batches' => $this->searchBatches($query),
            'faculty' => $this->searchFaculty($query),
            'payments' => $this->searchPayments($query), // ✅ CHANGED: Called the new searchPayments method
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
                    'type' => 'student',
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
                    'duration' => $course->duration_in_years.' years',
                    'url' => route('admin.courses.show', $course),
                    'type' => 'course',
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
                    'type' => 'batch',
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
                    'type' => 'faculty',
                ];
            });
    }

    /**
     * ✅ REPLACED: searchInvoices method is now searchPayments.
     * Searches for component-based payments by their receipt number.
     */
    private function searchPayments($query)
    {
        return Payment::where('payment_type', 'component')
            ->where('receipt_number', 'LIKE', "%{$query}%")
            ->with('student')
            ->limit(3)
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'name' => 'Receipt: '.$payment->receipt_number,
                    'student_name' => $payment->student->name,
                    'amount' => $payment->amount,
                    'date' => $payment->payment_date->format('d M, Y'),
                    'url' => route('admin.payments.receipt.show', $payment),
                    'type' => 'payment',
                ];
            });
    }
}
