<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
// MODIFIED: Replaced Invoice with StudentFee
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentFee;

class StudentDashboardController extends Controller
{
    public function academicProgress()
    {
        $user = auth()->user();
        $student = $user->student;

        if (! $student) {
            return response()->json(['error' => 'Student profile not found'], 404);
        }

        $progress = [
            'course' => $student->batch->course->name ?? 'N/A',
            'batch' => $student->batch->name ?? 'N/A',
            'enrollment_date' => $student->admission_date,
            'current_status' => $student->status,
            'monthly_attendance' => $this->getMonthlyAttendance($student),
            'recent_payments' => $this->getRecentPayments($student),
        ];

        return response()->json($progress);
    }

    private function getAttendancePercentage($student)
    {
        $totalClasses = $student->attendances()->count();
        if ($totalClasses === 0) {
            return 0;
        }

        $presentClasses = $student->attendances()->where('status', 'present')->count();

        return round(($presentClasses / $totalClasses) * 100, 2);
    }

    private function getRecentPayments($student)
    {
        // MODIFIED: Fetches recent component-based payments directly for the student
        return Payment::where('student_id', $student->id)
            ->where('payment_type', 'component') // Ensure we only get component payments
            ->latest('payment_date')
            ->limit(5)
            ->get()
            ->map(function ($payment) {
                return [
                    'amount' => $payment->amount,
                    'date' => $payment->payment_date,
                    'method' => $payment->payment_method,
                    'receipt_number' => $payment->receipt_number,
                ];
            });
    }

    private function getMonthlyAttendance($student)
    {
        return $student->attendances()
            ->selectRaw('DATE(date) as date, status')
            ->whereMonth('date', now()->month)
            ->orderBy('date')
            ->get()
            ->groupBy('date')
            ->map(function ($dayAttendance) {
                return [
                    'date' => $dayAttendance->first()->date,
                    'status' => $dayAttendance->first()->status,
                ];
            });
    }
}
