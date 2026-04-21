<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enquiry;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AddressReportController extends Controller
{
    private function getBaseQuery(Request $request)
    {
        $courseId = $request->input('course_id');
        $type = $request->input('type');
        $status = $request->input('status');
        $source = $request->input('source');
        $district = $request->input('district');
        $mandal = $request->input('mandal');
        $search = $request->input('search');

        // Student Subquery
        $studentQuery = DB::table('students')
            ->select(
                'students.id',
                'students.name',
                'students.student_mobile as phone',
                'students.village as address',
                DB::raw("'Student' as entity_type"),
                'courses.name as course_name',
                'batches.course_id',
                'students.status',
                'students.source',
                'students.created_at'
            )
            ->leftJoin('batches', 'students.batch_id', '=', 'batches.id')
            ->leftJoin('courses', 'batches.course_id', '=', 'courses.id');

        // Enquiry Subquery
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
                'enquiries.source',
                'enquiries.created_at'
            )
            ->leftJoin('courses', 'enquiries.course_id', '=', 'courses.id');

        // Apply shared filters
        $applyFilters = function($query, $tableName, $addressCol) use ($courseId, $status, $source, $district, $mandal, $search) {
            if ($courseId) $query->where($tableName == 'students' ? 'batches.course_id' : 'enquiries.course_id', $courseId);
            if ($status) $query->where("{$tableName}.status", $status);
            if ($source) $query->where("{$tableName}.source", $source);
            if ($district) $query->where($addressCol, 'LIKE', "%{$district}%");
            if ($mandal) $query->where($addressCol, 'LIKE', "%{$mandal}%");
            if ($search) {
                $nameCol = $tableName == 'students' ? 'name' : 'student_name';
                $phoneCol = $tableName == 'students' ? 'student_mobile' : 'phone_number';
                $query->where(function($q) use ($search, $tableName, $nameCol, $phoneCol, $addressCol) {
                    $q->where("{$tableName}.{$nameCol}", 'LIKE', "%{$search}%")
                      ->orWhere($addressCol, 'LIKE', "%{$search}%")
                      ->orWhere("{$tableName}.{$phoneCol}", 'LIKE', "%{$search}%");
                });
            }
        };

        if ($type === 'Enquiry') {
            $studentQuery->whereRaw('1=0');
        } else {
            $applyFilters($studentQuery, 'students', 'students.village');
        }

        if ($type === 'Student') {
            $enquiryQuery->whereRaw('1=0');
        } else {
            $applyFilters($enquiryQuery, 'enquiries', 'enquiries.address');
        }

        $combinedQuery = $studentQuery->unionAll($enquiryQuery);

        return DB::table(DB::raw("({$combinedQuery->toSql()}) as combined"))
            ->mergeBindings($combinedQuery);
    }

    public function index(Request $request)
    {
        $finalQuery = $this->getBaseQuery($request);
        $groupBy = $request->input('group_by', 'none');
        $perPage = $request->input('per_page', 25);
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');

        // --- Stats & Distributions for Charts ---
        $stats = [
            'total' => $finalQuery->count(),
            'students' => (clone $finalQuery)->where('entity_type', 'Student')->count(),
            'enquiries' => (clone $finalQuery)->where('entity_type', 'Enquiry')->count(),
            'top_addresses' => (clone $finalQuery)
                ->select('address', DB::raw('count(*) as count'))
                ->where('address', '!=', '')
                ->whereNotNull('address')
                ->groupBy('address')
                ->orderBy('count', 'desc')
                ->limit(7)
                ->get(),
            'course_dist' => (clone $finalQuery)
                ->select('course_name', DB::raw('count(*) as count'))
                ->whereNotNull('course_name')
                ->groupBy('course_name')
                ->get(),
            'status_dist' => (clone $finalQuery)
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get(),
        ];

        // Apply Sorting Logic
        if ($groupBy !== 'none') {
            $sortColumn = $groupBy === 'address' ? 'address' : ($groupBy === 'course' ? 'course_name' : 'entity_type');
            $finalQuery->orderBy($sortColumn, 'asc');
        } else {
            // Validate sort column to prevent SQL injection or errors
            $allowedSorts = ['name', 'phone', 'address', 'course_name', 'status', 'created_at'];
            if (in_array($sortBy, $allowedSorts)) {
                $finalQuery->orderBy($sortBy, $sortDir);
            } else {
                $finalQuery->orderBy('created_at', 'desc');
            }
        }

        $paginatedResults = $finalQuery->paginate($perPage)->appends($request->all());
        $results = $paginatedResults->getCollection();

        if ($groupBy !== 'none') {
            $groupColumn = $groupBy === 'address' ? 'address' : ($groupBy === 'course' ? 'course_name' : 'entity_type');
            $results = $results->groupBy(function($item) use ($groupColumn) {
                return $item->$groupColumn ?: 'N/A';
            });
        }

        // Dropdown Data
        $courses = Course::orderBy('name')->get();
        $sources = array_unique(array_merge(
            Enquiry::whereNotNull('source')->distinct()->pluck('source')->toArray(),
            Student::whereNotNull('source')->distinct()->pluck('source')->toArray()
        ));
        sort($sources);

        return view('admin.reports.address.index', array_merge(
            compact('results', 'paginatedResults', 'courses', 'sources', 'stats', 'perPage', 'sortBy', 'sortDir'),
            $request->all()
        ));
    }

    public function export(Request $request)
    {
        $query = $this->getBaseQuery($request);
        $results = $query->orderBy('created_at', 'desc')->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=Address_Report_".now()->format('Ymd_His').".csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($results) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['#', 'Name', 'Type', 'Phone', 'Address', 'Course', 'Status', 'Source', 'Created At']);

            foreach ($results as $index => $row) {
                fputcsv($file, [
                    $index + 1,
                    $row->name,
                    $row->entity_type,
                    $row->phone,
                    $row->address,
                    $row->course_name,
                    $row->status,
                    $row->source,
                    $row->created_at
                ]);
            }
            fclose($file);
        };

        return new StreamedResponse($callback, 200, $headers);
    }
}
