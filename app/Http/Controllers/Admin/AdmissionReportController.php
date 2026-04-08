<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\Enquiry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdmissionReportController extends Controller
{
    public function index(Request $request)
    {
        // Set default date range to the last 90 days if not provided
        $startDate = $request->input('start_date', Carbon::now()->subDays(90)->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());

        // --- 1. Fetch Enquiry Stats ---
        $enquiriesQuery = Enquiry::whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate);

        $totalEnquiries = $enquiriesQuery->clone()->count();
        $convertedEnquiries = $enquiriesQuery->clone()
            ->whereIn('status', ['converted', 'Admitted'])
            ->count();

        // --- 2. Fetch Admission Stats ---
        $admissionsQuery = Admission::whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate);

        $totalAdmissions = $admissionsQuery->clone()->count();
        $approvedAdmissions = $admissionsQuery->clone()
            ->where('status', 'approved')
            ->count();
        $rejectedAdmissions = $admissionsQuery->clone()
            ->where('status', 'rejected')
            ->count();
        $pendingAdmissions = $admissionsQuery->clone()
            ->where('status', 'pending')
            ->count();

        // --- 3. Calculate Conversion Rates ---
        $enquiryToAdmissionRate = ($totalEnquiries > 0) ? round(($totalAdmissions / $totalEnquiries) * 100, 1) : 0;
        $admissionApprovalRate = ($totalAdmissions > 0) ? round(($approvedAdmissions / $totalAdmissions) * 100, 1) : 0;
        $overallConversionRate = ($totalEnquiries > 0) ? round(($approvedAdmissions / $totalEnquiries) * 100, 1) : 0;

        // --- 4. Data for the Breakdown Table and Funnel Chart ---
        $admissionsBySource = $admissionsQuery->clone()
            ->select(
                'source',
                DB::raw('count(*) as total_admissions'),
                DB::raw("sum(case when status = 'approved' then 1 else 0 end) as approved"),
                DB::raw("sum(case when status = 'rejected' then 1 else 0 end) as rejected")
            )
            ->groupBy('source')
            ->orderBy('total_admissions', 'desc')
            ->get();

        // Add Enquiry Counts per Source
        $enquiriesBySource = $enquiriesQuery->clone()
            ->select('source', DB::raw('count(*) as total_enquiries'))
            ->groupBy('source')
            ->pluck('total_enquiries', 'source');

        $sourceBreakdown = $admissionsBySource->map(function ($item) use ($enquiriesBySource) {
            $source = $item->source ?? 'N/A';
            $item->total_enquiries = $enquiriesBySource[$source] ?? 0;
            $totalDecided = $item->approved + $item->rejected;
            $item->approval_rate = ($totalDecided > 0) ? round(($item->approved / $totalDecided) * 100) : 0;
            $item->conversion_rate = ($item->total_enquiries > 0) ? round(($item->total_admissions / $item->total_enquiries) * 100) : 0;

            return $item;
        });

        // Prepare data for the funnel chart
        $funnelLabels = ['Total Enquiries', 'Converted to Admission', 'Total Admissions', 'Approved Admissions'];
        $funnelData = [$totalEnquiries, $convertedEnquiries, $totalAdmissions, $approvedAdmissions];

        // Legacy compatibility aliases
        $totalApplications = $totalAdmissions;
        $approvedCount = $approvedAdmissions;
        $rejectedCount = $rejectedAdmissions;
        $pendingCount = $pendingAdmissions;
        $approvalRate = $admissionApprovalRate;
        $admissionsBySource = $sourceBreakdown;

        return view('admin.reports.admissions.index', compact(
            'totalEnquiries',
            'convertedEnquiries',
            'totalAdmissions',
            'totalApplications',
            'approvedAdmissions',
            'approvedCount',
            'pendingAdmissions',
            'pendingCount',
            'rejectedAdmissions',
            'rejectedCount',
            'enquiryToAdmissionRate',
            'admissionApprovalRate',
            'approvalRate',
            'overallConversionRate',
            'sourceBreakdown',
            'admissionsBySource',
            'funnelLabels',
            'funnelData',
            'startDate',
            'endDate'
        ));
    }
}
