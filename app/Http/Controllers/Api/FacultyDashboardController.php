<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Student, Batch, Attendance};
use Illuminate\Http\Request;

class FacultyDashboardController extends Controller
{
    public function facultyMetrics()
    {
        $user = auth()->user();
        
        $metrics = [
            'my_batches' => Batch::where('faculty_id', $user->id)->count(),
            'total_students' => Student::whereHas('batch', function($query) use ($user) {
                $query->where('faculty_id', $user->id);
            })->count(),
            'today_attendance' => $this->getTodayAttendance($user),
            'monthly_classes' => $this->getMonthlyClasses($user),
        ];
        
        return response()->json($metrics);
    }
    
    public function classAnalytics()
    {
        $user = auth()->user();
        
        $analytics = [
            'batch_performance' => $this->getBatchPerformance($user),
            'attendance_trends' => $this->getAttendanceTrends($user),
            'student_progress' => $this->getStudentProgress($user),
        ];
        
        return response()->json($analytics);
    }
    
    public function myStudents()
    {
        $user = auth()->user();
        
        $students = Student::whereHas('batch', function($query) use ($user) {
                         $query->where('faculty_id', $user->id);
                     })
                     ->with('batch.course')
                     ->get()
                     ->map(function($student) {
                         return [
                             'id' => $student->id,
                             'name' => $student->name,
                             'enrollment_number' => $student->enrollment_number,
                             'batch' => $student->batch->name,
                             'course' => $student->batch->course->name,
                             'status' => $student->status,
                             'attendance_percentage' => $this->getStudentAttendancePercentage($student),
                         ];
                     });
        
        return response()->json($students);
    }
    
    private function getTodayAttendance($user)
    {
        return Attendance::whereHas('student.batch', function($query) use ($user) {
            $query->where('faculty_id', $user->id);
        })->whereDate('date', today())->where('status', 'present')->count();
    }
    
    private function getMonthlyClasses($user)
    {
        return Attendance::whereHas('student.batch', function($query) use ($user) {
            $query->where('faculty_id', $user->id);
        })->whereMonth('date', now()->month)->count();
    }
    
    private function getBatchPerformance($user)
    {
        return Batch::where('faculty_id', $user->id)
                   ->withCount('students')
                   ->get()
                   ->map(function($batch) {
                       return [
                           'name' => $batch->name,
                           'student_count' => $batch->students_count,
                           'average_attendance' => $this->getBatchAttendanceRate($batch),
                       ];
                   });
    }
    
    private function getAttendanceTrends($user)
    {
        return Attendance::whereHas('student.batch', function($query) use ($user) {
                         $query->where('faculty_id', $user->id);
                     })
                     ->selectRaw('DATE(date) as date, 
                                COUNT(*) as total_classes,
                                SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present_classes')
                     ->whereBetween('date', [now()->subDays(30), now()])
                     ->groupBy('date')
                     ->orderBy('date')
                     ->get();
    }
    
    private function getStudentProgress($user)
    {
        return Student::whereHas('batch', function($query) use ($user) {
                     $query->where('faculty_id', $user->id);
                 })
                 ->get()
                 ->map(function($student) {
                     return [
                         'name' => $student->name,
                         'attendance_percentage' => $this->getStudentAttendancePercentage($student),
                         'status' => $student->status,
                     ];
                 });
    }
    
    private function getBatchAttendanceRate($batch)
    {
        $totalClasses = Attendance::whereHas('student', function($query) use ($batch) {
            $query->where('batch_id', $batch->id);
        })->count();
        
        if ($totalClasses === 0) return 0;
        
        $presentClasses = Attendance::whereHas('student', function($query) use ($batch) {
            $query->where('batch_id', $batch->id);
        })->where('status', 'present')->count();
        
        return round(($presentClasses / $totalClasses) * 100, 2);
    }
    
    private function getStudentAttendancePercentage($student)
    {
        $totalClasses = $student->attendances()->count();
        if ($totalClasses === 0) return 0;
        
        $presentClasses = $student->attendances()->where('status', 'present')->count();
        return round(($presentClasses / $totalClasses) * 100, 2);
    }
}
