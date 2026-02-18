<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Batch;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AgeReportController extends Controller
{
    public function index(Request $request)
    {
        $courseId = $request->input('course_id');
        $batchId = $request->input('batch_id');
        $gender = $request->input('gender');
        $ageGroup = $request->input('age_group');
        $sortBy = $request->input('sort_by', 'current_age');
        $sortOrder = $request->input('sort_order', 'desc');

        $query = Student::query();

        // [Filter] Exclude Dropouts and Internship students
        $query->where('status', '!=', 'dropout')
            ->whereHas('batch', function ($q) {
                $q->where('is_on_internship', 0);
            });

        if ($courseId) {
            $query->whereHas('batch', function ($q) use ($courseId) {
                $q->where('course_id', $courseId);
            });
        }

        if ($batchId) {
            $query->where('batch_id', $batchId);
        }

        if ($gender) {
            $query->where('gender', $gender);
        }

        // Apply Age Group Filter
        if ($ageGroup) {
            if ($ageGroup === 'Missing') {
                $query->whereNull('dob');
            } else {
                $query->whereNotNull('dob');
                $range = explode('-', $ageGroup);
                if (count($range) === 2) {
                    $query->whereRaw("TIMESTAMPDIFF(YEAR, dob, CURDATE()) BETWEEN ? AND ?", [$range[0], $range[1]]);
                } elseif ($ageGroup === 'Under 18') {
                    $query->whereRaw("TIMESTAMPDIFF(YEAR, dob, CURDATE()) < 18");
                } elseif ($ageGroup === '36+') {
                    $query->whereRaw("TIMESTAMPDIFF(YEAR, dob, CURDATE()) >= 36");
                }
            }
        }

        // 1. Age Distribution Buckets
        $ageBuckets = $query->clone()
            ->whereNotNull('dob')
            ->select(DB::raw("
                CASE 
                    WHEN TIMESTAMPDIFF(YEAR, dob, CURDATE()) < 18 THEN 'Under 18'
                    WHEN TIMESTAMPDIFF(YEAR, dob, CURDATE()) BETWEEN 18 AND 21 THEN '18-21'
                    WHEN TIMESTAMPDIFF(YEAR, dob, CURDATE()) BETWEEN 22 AND 25 THEN '22-25'
                    WHEN TIMESTAMPDIFF(YEAR, dob, CURDATE()) BETWEEN 26 AND 30 THEN '26-30'
                    WHEN TIMESTAMPDIFF(YEAR, dob, CURDATE()) BETWEEN 31 AND 35 THEN '31-35'
                    ELSE '36 and Above'
                END as age_range
            "), DB::raw('count(*) as count'))
            ->groupBy('age_range')
            ->pluck('count', 'age_range')
            ->toArray();

        // Ensure all buckets exist even if count is 0
        $buckets = [
            'Under 18' => 0,
            '18-21' => 0,
            '22-25' => 0,
            '26-30' => 0,
            '31-35' => 0,
            '36 and Above' => 0
        ];
        $ageBuckets = array_merge($buckets, $ageBuckets);

        // 2. Missing DOB Count
        $missingDobCount = $query->clone()->whereNull('dob')->count();

        // 3. Average Age per Course (Join through Batches)
        $averageAgeByCourseQuery = Course::select('courses.name', DB::raw('AVG(TIMESTAMPDIFF(YEAR, students.dob, CURDATE())) as avg_age'))
            ->join('batches', 'courses.id', '=', 'batches.course_id')
            ->join('students', 'batches.id', '=', 'students.batch_id')
            ->whereNotNull('students.dob')
            ->where('students.status', '!=', 'dropout')
            ->where('batches.is_on_internship', 0);

        if ($courseId)
            $averageAgeByCourseQuery->where('courses.id', $courseId);
        if ($gender)
            $averageAgeByCourseQuery->where('students.gender', $gender);

        $averageAgeByCourse = $averageAgeByCourseQuery->groupBy('courses.id', 'courses.name')->get();

        // 4. Gender-wise Age Distribution
        $genderAgeDist = $query->clone()
            ->whereNotNull('dob')
            ->select('gender', DB::raw('AVG(TIMESTAMPDIFF(YEAR, dob, CURDATE())) as avg_age'))
            ->groupBy('gender')
            ->get();

        // 5. Student List with Age and Sorting
        $studentsQuery = $query->clone()
            ->with(['course', 'batch'])
            ->select('students.*', DB::raw('TIMESTAMPDIFF(YEAR, dob, CURDATE()) as current_age'));

        // Handle Sorting
        if ($sortBy === 'course') {
            $studentsQuery->leftJoin('batches', 'students.batch_id', '=', 'batches.id')
                ->leftJoin('courses', 'batches.course_id', '=', 'courses.id')
                ->orderBy('courses.name', $sortOrder);
        } elseif ($sortBy === 'batch') {
            $studentsQuery->leftJoin('batches', 'students.batch_id', '=', 'batches.id')
                ->orderBy('batches.name', $sortOrder);
        } else {
            $studentsQuery->orderBy($sortBy, $sortOrder);
        }

        $students = $studentsQuery->paginate(50)->appends($request->all());

        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.reports.age._table', compact('students', 'sortBy', 'sortOrder'))->render(),
                'stats' => [
                    'total' => number_format($students->total()),
                    'missing' => number_format($missingDobCount),
                    'ageBuckets' => $ageBuckets,
                    'averageAgeByCourse' => $averageAgeByCourse,
                    'genderAgeDist' => $genderAgeDist
                ]
            ]);
        }

        $courses = Course::all();
        $batches = $batchId || $courseId ? Batch::where(function ($q) use ($courseId) {
            if ($courseId)
                $q->where('course_id', $courseId);
        })->get() : collect();

        $ageGroups = [
            'Under 18' => 'Under 18',
            '18-21' => '18-21',
            '22-25' => '22-25',
            '26-30' => '26-30',
            '31-35' => '31-35',
            '36+' => '36 and Above',
            'Missing' => 'DOB Not Updated'
        ];

        return view('admin.reports.age.index', compact(
            'ageBuckets',
            'missingDobCount',
            'averageAgeByCourse',
            'genderAgeDist',
            'students',
            'courses',
            'batches',
            'ageGroups',
            'courseId',
            'batchId',
            'gender',
            'ageGroup',
            'sortBy',
            'sortOrder'
        ));
    }
}
