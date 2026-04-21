<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AddressReportController extends Controller
{
    public function index(Request $request)
    {
        $courseId = $request->input('course_id');
        $type = $request->input('type'); // 'Student' or 'Enquiry'
        $status = $request->input('status');
        $groupBy = $request->input('group_by', 'none'); // 'address', 'course', 'type'
        $search = $request->input('search');

        // Build Student Query
        $studentQuery = DB::table('students')
            ->select(
                'students.id',
                'students.name',
                'students.student_mobile as phone',
                'students.village as address',
                DB::raw("'Student' as entity_type"),
                'courses.name as course_name',
                'students.course_id',
                'students.status',
                'students.created_at'
            )
            ->leftJoin('courses', 'students.course_id', '=', 'courses.id');

        // Build Enquiry Query
        $enquiryQuery = DB::table('enquiries')
            ->select(
                'enquiries.id',
                'enquiries.student_name as name',
                'enquiries.phone_number as phone',
                'enquiries.address',
                DB::raw("'Enquiry' as entity_type"),
                'courses.name as course_name',
                'enquiries.course_id',
                'enquiries.status',
                'enquiries.created_at'
            )
            ->leftJoin('courses', 'enquiries.course_id', '=', 'courses.id');

        // Apply Filters to Student Query
        if ($type && $type !== 'Student') {
            $studentQuery->whereRaw('1=0'); // Exclude students
        } else {
            if ($courseId) $studentQuery->where('students.course_id', $courseId);
            if ($status) $studentQuery->where('students.status', $status);
            if ($search) {
                $studentQuery->where(function($q) use ($search) {
                    $q->where('students.name', 'LIKE', "%{$search}%")
                      ->orWhere('students.village', 'LIKE', "%{$search}%")
                      ->orWhere('students.student_mobile', 'LIKE', "%{$search}%");
                });
            }
        }

        // Apply Filters to Enquiry Query
        if ($type && $type !== 'Enquiry') {
            $enquiryQuery->whereRaw('1=0'); // Exclude enquiries
        } else {
            if ($courseId) $enquiryQuery->where('enquiries.course_id', $courseId);
            if ($status) $enquiryQuery->where('enquiries.status', $status);
            if ($search) {
                $enquiryQuery->where(function($q) use ($search) {
                    $q->where('enquiries.student_name', 'LIKE', "%{$search}%")
                      ->orWhere('enquiries.address', 'LIKE', "%{$search}%")
                      ->orWhere('enquiries.phone_number', 'LIKE', "%{$search}%");
                });
            }
        }

        // Combine
        $combinedQuery = $studentQuery->unionAll($enquiryQuery);

        // Sorting & Grouping Logic
        $finalQuery = DB::table(DB::raw("({$combinedQuery->toSql()}) as combined"))
            ->mergeBindings($combinedQuery);

        // --- CALCULATE ANALYTICS BEFORE GETTING ALL RESULTS ---
        $stats = [
            'total' => $finalQuery->count(),
            'students' => (clone $finalQuery)->where('entity_type', 'Student')->count(),
            'enquiries' => (clone $finalQuery)->where('entity_type', 'Enquiry')->count(),
            'top_addresses' => (clone $finalQuery)
                ->select('address', DB::raw('count(*) as count'))
                ->whereNotNull('address')
                ->where('address', '!=', '')
                ->groupBy('address')
                ->orderBy('count', 'desc')
                ->limit(5)
                ->get(),
        ];

        // Apply Sort
        if ($groupBy !== 'none') {
            $sortColumn = $groupBy === 'address' ? 'address' : ($groupBy === 'course' ? 'course_name' : 'entity_type');
            $finalQuery->orderBy($sortColumn, 'asc');
        } else {
            $finalQuery->orderBy('created_at', 'desc');
        }

        $results = $finalQuery->get();

        // If Grouped, restructure the data
        if ($groupBy !== 'none') {
            $groupColumn = $groupBy === 'address' ? 'address' : ($groupBy === 'course' ? 'course_name' : 'entity_type');
            $results = $results->groupBy(function($item) use ($groupColumn) {
                return $item->$groupColumn ?: 'Unknown/Empty';
            });
        }

        $courses = Course::orderBy('name')->get();
        
        return view('admin.reports.address.index', compact('results', 'courses', 'courseId', 'type', 'status', 'groupBy', 'search', 'stats'));
    }
}
