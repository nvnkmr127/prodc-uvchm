<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
// MODIFIED: Replaced Invoice with StudentFee
use App\Models\{Student, Attendance, StudentFee, Payment};
use Illuminate\Http\Request;

class StudentDashboardController extends Controller
{
    public function studentMetrics()
    {
        $user = auth()->user();
        $student = $user->student;
        
        if (!$student) {
            return response()->json(['error' => 'Student profile not found'], 404);
        }
        
        // MODIFIED: Calculations are now based on the StudentFee (component) model
        $studentFees = $student->studentFees;
        
        $metrics = [
            'attendance_percentage' => $this->getAttendancePercentage($student),
            'total_classes' => $student->attendances()->count(),
            'present_classes' => $student->attendances()->where('status', 'present')->count(),
            'absent_classes' => $student->attendances()->where('status', 'absent')->count(),
            // Calculates total outstanding amount from all fee components
            'unpaid_fees' => $studentFees->sum(fn($fee) => $fee->getRemainingAmount()),
            // Calculates total paid amount across all fee components
            'total_paid' => $studentFees->sum('paid_amount'),
        ];
        
        return response()->json($metrics);
    }
    
    public function academicProgress()
    {
        $user = auth()->user();
        $student = $user->student;
        
        if (!$student) {
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
    
    public function mySchedule()
    {
        $user = auth()->user();
        $student = $user->student;
        
        if (!$student) {
            return response()->json(['error' => 'Student profile not found'], 404);
        }
        
        // This would depend on your timetable implementation
        $schedule = [
            'today_classes' => [],
            'week_schedule' => [],
            'upcoming_exams' => [],
        ];
        
        return response()->json($schedule);
    }
    
    private function getAttendancePercentage($student)
    {
        $totalClasses = $student->attendances()->count();
        if ($totalClasses === 0) return 0;
        
        $presentClasses = $student->attendances()->where('status', 'present')->count();
        return round(($presentClasses / $totalClasses) * 100, 2);
    }
    
    private function getMonthlyAttendance($student)
    {
        return $student->attendances()
                      ->selectRaw('DATE(date) as date, status')
                      ->whereMonth('date', now()->month)
                      ->orderBy('date')
                      ->get()
                      ->groupBy('date')
                      ->map(function($dayAttendance) {
                          return [
                              'date' => $dayAttendance->first()->date,
                              'status' => $dayAttendance->first()->status,
                          ];
                      });
    }
    
    private function getRecentPayments($student)
    {
        // MODIFIED: Fetches recent component-based payments directly for the student
        return Payment::where('student_id', $student->id)
                 ->where('payment_type', 'component') // Ensure we only get component payments
                 ->latest('payment_date')
                 ->limit(5)
                 ->get()
                 ->map(function($payment) {
                     return [
                         'amount' => $payment->amount,
                         'date' => $payment->payment_date,
                         'method' => $payment->payment_method,
                         'receipt_number' => $payment->receipt_number,
                     ];
                 });
    }
}