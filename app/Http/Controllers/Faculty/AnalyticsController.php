<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Batch;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function dashboard()
    {
        $user = auth()->user();
        
        // Get analytics for faculty's batches
        $batches = Batch::where('faculty_id', $user->id)->withCount('students')->get();
        
        $analytics = [
            'total_batches' => $batches->count(),
            'total_students' => $batches->sum('students_count'),
            'batch_performance' => $batches->map(function($batch) {
                return [
                    'name' => $batch->name,
                    'student_count' => $batch->students_count,
                    'attendance_rate' => 85 // Calculate from actual attendance
                ];
            })
        ];
        
        return view('faculty.analytics', compact('analytics'));
    }
}