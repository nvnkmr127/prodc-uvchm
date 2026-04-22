<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Course;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentReportController extends Controller
{
    private function getBaseQuery(Request $request)
    {
        $batchId = $request->input('batch_id');
        $courseId = $request->input('course_id');
        $search = $request->input('search');
        $isInternship = $request->has('is_internship') && $request->input('is_internship') === '1';

        $query = Student::query()
            ->with(['batch.course'])
            ->where('status', '!=', 'dropout');

        if ($batchId) {
            $query->where('batch_id', $batchId);
        }

        if ($courseId) {
            $query->whereHas('batch', function($q) use ($courseId) {
                $q->where('course_id', $courseId);
            });
        }

        if ($isInternship) {
            $query->whereHas('batch', function($q) {
                $q->where('is_on_internship', true);
            });
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('enrollment_number', 'LIKE', "%{$search}%")
                  ->orWhere('student_mobile', 'LIKE', "%{$search}%")
                  ->orWhere('father_mobile', 'LIKE', "%{$search}%")
                  ->orWhere('village', 'LIKE', "%{$search}%")
                  ->orWhere('father_name', 'LIKE', "%{$search}%");
            });
        }

        return $query;
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 25);
        $query = $this->getBaseQuery($request);
        
        $paginatedResults = $query->latest()->paginate($perPage)->appends($request->all());
        
        // Dropdown Data
        $courses = Course::orderBy('name')->get();
        $batches = Batch::when($request->course_id, function($q) use ($request) {
            return $q->where('course_id', $request->course_id);
        })->where('status', 'active')->orderBy('name')->get();

        return view('admin.reports.students.index', compact(
            'paginatedResults', 'courses', 'batches'
        ));
    }

    public function export(Request $request)
    {
        $results = $this->getBaseQuery($request)->latest()->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=Student_Report_".now()->format('Ymd_His').".csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($results) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                '#', 
                'Enrollment No', 
                'Name', 
                'Father Name', 
                'Student Mobile', 
                'Father Mobile', 
                'Village/Address', 
                'Course', 
                'Batch', 
                'Internship',
                'Total Fee', 
                'Paid Fee', 
                'Outstanding Fee',
                'Admission Date',
                'Status'
            ]);

            foreach ($results as $index => $student) {
                $financials = $student->getFinancialSummary();
                
                fputcsv($file, [
                    $index + 1,
                    $student->enrollment_number,
                    $student->name,
                    $student->father_name,
                    $student->student_mobile,
                    $student->father_mobile,
                    $student->village,
                    $student->course_name,
                    $student->batch_name,
                    ($student->batch && $student->batch->is_on_internship) ? 'Yes' : 'No',
                    $financials['total_fees'],
                    $financials['total_paid'],
                    $financials['total_outstanding'],
                    $student->admission_date ? $student->admission_date->format('Y-m-d') : 'N/A',
                    ucfirst($student->status)
                ]);
            }
            fclose($file);
        };

        return new StreamedResponse($callback, 200, $headers);
    }
}
