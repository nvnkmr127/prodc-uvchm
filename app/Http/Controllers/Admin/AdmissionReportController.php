<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdmissionReportController extends Controller
{
    public function index(Request $request)
    {
        // Set default date range to the last 90 days if not provided
        $startDate = $request->input('start_date', Carbon::now()->subDays(90)->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());

        // --- Fetch base data for the selected period ---
        $admissionsQuery = Admission::whereBetween('created_at', [$startDate, $endDate]);

        // --- 1. Data for Stats Cards ---
        $totalApplications = $admissionsQuery->clone()->count();
        $approvedCount = $admissionsQuery->clone()->where('status', 'approved')->count();
        $rejectedCount = $admissionsQuery->clone()->where('status', 'rejected')->count();
        $pendingCount = $admissionsQuery->clone()->where('status', 'pending')->count();
        $approvalRate = ($totalApplications > 0) ? round(($approvedCount / ($approvedCount + $rejectedCount)) * 100) : 0;

        // --- 2. Data for the Breakdown Table and Funnel Chart ---
        $admissionsBySource = $admissionsQuery->clone()
            ->select('source', 
                     DB::raw('count(*) as total'),
                     DB::raw("sum(case when status = 'approved' then 1 else 0 end) as approved"),
                     DB::raw("sum(case when status = 'rejected' then 1 else 0 end) as rejected")
            )
            ->groupBy('source')
            ->orderBy('total', 'desc')
            ->get()
            ->map(function ($item) {
                $totalDecided = $item->approved + $item->rejected;
                $item->conversion_rate = ($totalDecided > 0) ? round(($item->approved / $totalDecided) * 100) : 0;
                return $item;
            });

        // Prepare data for the funnel chart
        $funnelLabels = $admissionsBySource->pluck('source');
        $funnelData = $admissionsBySource->pluck('total');

        return view('admin.reports.admissions.index', compact(
            'totalApplications', 'approvedCount', 'rejectedCount', 'pendingCount', 'approvalRate',
            'admissionsBySource', 'funnelLabels', 'funnelData',
            'startDate', 'endDate'
        ));
    }
}