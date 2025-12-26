<?php
// app/Http/Controllers/Attendance/AnalyticsController.php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\Batch;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view attendance');
    }

    public function index()
    {
        try {
            // Get today's attendance data with proper error handling
            $todayAttendance = $this->getTodayAttendanceData();
            
            // Get weekly trends
            $weeklyTrends = $this->getWeeklyTrends();
            
            // Get batch-wise statistics
            $batchStats = $this->getBatchStatistics();
            
            return view('attendance.analytics.index', compact(
                'todayAttendance',
                'weeklyTrends', 
                'batchStats'
            ));
            
        } catch (\Exception $e) {
            \Log::error('Analytics index error: ' . $e->getMessage());
            
            // Return view with safe default data
            return view('attendance.analytics.index', [
                'todayAttendance' => $this->getDefaultTodayData(),
                'weeklyTrends' => $this->getDefaultWeeklyData(),
                'batchStats' => $this->getDefaultBatchStats()
            ]);
        }
    }
    
    private function getTodayAttendanceData()
    {
        $today = Carbon::today();
        
        // Get total active students
        $totalStudents = Student::where('status', 'active')->count();
        
        if ($totalStudents == 0) {
            return $this->getDefaultTodayData();
        }
        
        // Get attendance records for today
        $attendances = Attendance::whereDate('attendance_date', $today)->get();
        
        // Count by status
        $presentCount = $attendances->whereIn('status', ['present', 'late'])->count();
        $absentCount = $attendances->where('status', 'absent')->count();
        $lateCount = $attendances->where('status', 'late')->count();
        $excusedCount = $attendances->where('status', 'excused')->count();
        
        // Calculate percentages
        $presentPercentage = $totalStudents > 0 ? round(($presentCount / $totalStudents) * 100, 2) : 0;
        $absentPercentage = $totalStudents > 0 ? round(($absentCount / $totalStudents) * 100, 2) : 0;
        
        return [
            'present_count' => $presentCount,
            'absent_count' => $absentCount,
            'late_count' => $lateCount,
            'excused_count' => $excusedCount,
            'total_students' => $totalStudents,
            'present_percentage' => $presentPercentage,
            'absent_percentage' => $absentPercentage,
            'attendance_rate' => $presentPercentage
        ];
    }
    
    private function getWeeklyTrends()
    {
        $weekStart = Carbon::now()->startOfWeek();
        $trends = [];
        
        for ($i = 0; $i < 7; $i++) {
            $date = $weekStart->copy()->addDays($i);
            $dayData = Attendance::whereDate('attendance_date', $date)
                ->selectRaw('
                    DATE(attendance_date) as date,
                    COUNT(CASE WHEN status IN ("present", "late") THEN 1 END) as present,
                    COUNT(CASE WHEN status = "absent" THEN 1 END) as absent,
                    COUNT(*) as total
                ')
                ->groupBy('date')
                ->first();
            
            $trends[] = [
                'date' => $date->format('Y-m-d'),
                'day' => $date->format('D'),
                'present' => $dayData->present ?? 0,
                'absent' => $dayData->absent ?? 0,
                'total' => $dayData->total ?? 0,
                'percentage' => $dayData && $dayData->total > 0 ? 
                    round(($dayData->present / $dayData->total) * 100, 2) : 0
            ];
        }
        
        return $trends;
    }
    
    private function getBatchStatistics()
    {
        try {
            $batches = Batch::with(['students', 'course'])->get();
            $stats = [];
            
            foreach ($batches as $batch) {
                $studentIds = $batch->students->pluck('id');
                $totalStudents = $studentIds->count();
                
                if ($totalStudents == 0) {
                    continue;
                }
                
                // Get today's attendance for this batch
                $todayAttendance = Attendance::whereDate('attendance_date', Carbon::today())
                    ->whereIn('student_id', $studentIds)
                    ->selectRaw('
                        COUNT(CASE WHEN status IN ("present", "late") THEN 1 END) as present,
                        COUNT(CASE WHEN status = "absent" THEN 1 END) as absent,
                        COUNT(*) as total
                    ')
                    ->first();
                
                $presentCount = $todayAttendance->present ?? 0;
                $percentage = $totalStudents > 0 ? round(($presentCount / $totalStudents) * 100, 2) : 0;
                
                $stats[] = [
                    'batch_id' => $batch->id,
                    'batch_name' => $batch->name,
                    'course_name' => $batch->course->name ?? 'N/A',
                    'total_students' => $totalStudents,
                    'present_count' => $presentCount,
                    'absent_count' => $todayAttendance->absent ?? 0,
                    'attendance_percentage' => $percentage
                ];
            }
            
            return $stats;
            
        } catch (\Exception $e) {
            \Log::error('Error getting batch statistics: ' . $e->getMessage());
            return $this->getDefaultBatchStats();
        }
    }
    
    // Default data methods
    private function getDefaultTodayData()
    {
        return [
            'present_count' => 0,
            'absent_count' => 0,
            'late_count' => 0,
            'excused_count' => 0,
            'total_students' => 0,
            'present_percentage' => 0,
            'absent_percentage' => 0,
            'attendance_rate' => 0
        ];
    }
    
    private function getDefaultWeeklyData()
    {
        $weekStart = Carbon::now()->startOfWeek();
        $trends = [];
        
        for ($i = 0; $i < 7; $i++) {
            $date = $weekStart->copy()->addDays($i);
            $trends[] = [
                'date' => $date->format('Y-m-d'),
                'day' => $date->format('D'),
                'present' => 0,
                'absent' => 0,
                'total' => 0,
                'percentage' => 0
            ];
        }
        
        return $trends;
    }
    
    private function getDefaultBatchStats()
    {
        return [];
    }
    
    public function dashboard()
    {
        return $this->index();
    }
}