<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function studentReport(Request $request, Student $student = null)
    {
        if (!$student) {
            $student = auth()->user()->student;
        }
        
        if (!$student) {
            abort(404, 'Student profile not found');
        }
        
        $reportData = [
            'student' => $student,
            'attendance_summary' => $this->getAttendanceSummary($student),
            'financial_summary' => $this->getFinancialSummary($student),
            'academic_summary' => $this->getAcademicSummary($student),
            'generated_at' => now(),
        ];
        
        return view('student.report', $reportData);
    }
    
    private function getAttendanceSummary(Student $student)
    {
        $totalClasses = $student->attendances()->count();
        $presentClasses = $student->attendances()->where('status', 'present')->count();
        
        return [
            'total_classes' => $totalClasses,
            'present_classes' => $presentClasses,
            'absent_classes' => $totalClasses - $presentClasses,
            'attendance_percentage' => $totalClasses > 0 ? round(($presentClasses / $totalClasses) * 100, 2) : 0
        ];
    }
    
    private function getFinancialSummary(Student $student)
    {
        // MODIFIED: This method now uses the component-based StudentFee model instead of Invoices.
        $studentFees = $student->studentFees;
        
        return [
            // Calculates the total amount billed from all fee components.
            'total_invoiced' => $studentFees->sum('amount'),
            // Calculates the total amount paid across all fee components.
            'total_paid' => $studentFees->sum('paid_amount'),
            // Calculates the total outstanding amount using the helper method from the StudentFee model.
            'total_due' => $studentFees->sum(fn($fee) => $fee->getRemainingAmount()),
        ];
    }
    
    private function getAcademicSummary(Student $student)
    {
        return [
            'course' => $student->batch->course->name ?? 'N/A',
            'batch' => $student->batch->name ?? 'N/A',
            'enrollment_date' => $student->admission_date,
            'status' => $student->status,
        ];
    }
}