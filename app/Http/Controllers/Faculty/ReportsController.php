<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Batch;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function facultyReport()
    {
        $user = auth()->user();
        $batches = Batch::where('faculty_id', $user->id)->with('students')->get();
        
        $reportData = [
            'faculty_name' => $user->name,
            'total_batches' => $batches->count(),
            'total_students' => $batches->sum(function($batch) {
                return $batch->students->count();
            }),
            'batch_details' => $batches->map(function($batch) {
                return [
                    'name' => $batch->name,
                    'student_count' => $batch->students->count(),
                    'active_students' => $batch->students->where('status', 'active')->count()
                ];
            })
        ];
        
        return view('faculty.reports', compact('reportData'));
    }
    
    public function generate(Request $request)
    {
        // Generate faculty-specific reports
        return response()->json(['message' => 'Report generation started']);
    }
}