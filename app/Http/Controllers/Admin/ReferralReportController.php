<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Student;
use App\Models\StudentFee;
use Illuminate\Support\Facades\DB;

class ReferralReportController extends Controller
{
    public function index(Request $request)
    {
        // 1. Handle Export Request
        if ($request->input('export') === 'excel') {
            $data = $this->fetchData($request);
            return $this->streamCsv($data['students']);
        }

        // 2. Handle AJAX Request
        if ($request->ajax()) {
            return response()->json($this->fetchData($request));
        }

        // 3. Normal Request - Return View with Options
        $courses = \App\Models\Course::select('id', 'name')->get();
        // Load all batches grouped by course_id for dynamic JS filtering
        $batches = \App\Models\Batch::select('id', 'name', 'course_id')->get()->groupBy('course_id');

        $uniqueSources = \App\Models\Student::withoutGlobalScope('academic_year')
            ->select('source')
            ->distinct()
            ->whereNotNull('source')
            ->where('source', '!=', '')
            ->pluck('source');

        $statuses = ['active', 'graduated', 'dropout', 'completed'];

        return view('admin.reports.referrals.index', compact('courses', 'batches', 'uniqueSources', 'statuses'));
    }

    private function fetchData(Request $request)
    {
        // 1. Parse Filters
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $courseId = $request->input('course_id');
        $batchId = $request->input('batch_id');
        $source = $request->input('source');
        $status = $request->input('status');
        $search = $request->input('search');

        // 2. Build Query
        $query = Student::allYears()
            ->with(['batch', 'batch.course', 'studentFees', 'studentFees.payments'])
            ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                $q->whereDate('admission_date', '>=', $startDate)
                    ->whereDate('admission_date', '<=', $endDate);
            })
            ->when($courseId, function ($q) use ($courseId) {
                $q->whereHas('batch', fn($b) => $b->where('course_id', $courseId));
            })
            ->when($batchId, function ($q) use ($batchId) {
                $q->where('batch_id', $batchId);
            })
            ->when($source, function ($q) use ($source) {
                $q->where('source', $source);
            })
            ->when($status, function ($q) use ($status) {
                $q->where('status', $status);
            })
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('referral_name', 'like', "%{$search}%")
                        ->orWhere('source', 'like', "%{$search}%")
                        ->orWhere('enrollment_number', 'like', "%{$search}%");
                });
            });

        $students = $query->get();

        // 3. Process Data
        $totalReferrals = 0;
        $referralStats = [];
        $sourceStats = [];
        $detailedAdmissions = [];

        foreach ($students as $student) {
            $referralName = $student->referral_name;
            $src = ucfirst($student->source ?: 'Direct/Unknown');

            // Financial Calculations
            $totalAmount = $student->studentFees->sum('amount') ?? 0;
            $concession = $student->studentFees->sum('concession_amount') ?? 0;
            $paid = $student->studentFees->sum('paid_amount') ?? 0;

            $finalTotal = $totalAmount - $concession;
            $remaining = $finalTotal - $paid;

            // Percentage & Eligibility Calculation
            $percentagePaid = ($finalTotal > 0) ? ($paid / $finalTotal) * 100 : 0;
            // Round to 1 decimal place for cleaner display
            $percentagePaid = round($percentagePaid, 1);

            // Eligibility Check: >= 30% paid
            $isEligible = $percentagePaid >= 30;

            // Commission Status
            $commissionPaidAt = $student->referral_commission_paid_at;
            $isCommissionPaid = !is_null($commissionPaidAt);

            // --- Stats Processing ---
            if ($referralName) {
                $totalReferrals++;
                if (!isset($referralStats[$referralName])) {
                    $referralStats[$referralName] = ['referral_name' => $referralName, 'total_admissions' => 0];
                }
                $referralStats[$referralName]['total_admissions']++;
            }

            if (!isset($sourceStats[$src])) {
                $sourceStats[$src] = ['source' => $src, 'total_admissions' => 0];
            }
            $sourceStats[$src]['total_admissions']++;

            // --- Detailed Row Data ---
            $detailedAdmissions[] = [
                'admission_date' => $student->admission_date ? $student->admission_date->format('d M, Y') : '-',
                'student_name' => $student->name,
                'enrollment_number' => $student->enrollment_number ?? '-',
                'course_name' => $student->batch->course->name ?? '-',
                'batch_name' => $student->batch->name ?? '-',
                'source' => $src,
                'referral_name' => $referralName ?: '-',
                'status' => ucfirst($student->status),
                'total_amount' => number_format($totalAmount, 2),
                'concession' => number_format($concession, 2),
                'paid' => number_format($paid, 2),
                'remaining' => number_format($remaining, 2),
                'raw_remaining' => $remaining,
                'percentage_paid' => $percentagePaid,
                'is_eligible' => $isEligible,
                'is_commission_paid' => $isCommissionPaid,
                'commission_paid_at' => $commissionPaidAt ? $commissionPaidAt->format('d M, Y') : null,
                'commission_amount' => $student->referral_commission_amount,
                'payment_mode' => $student->referral_payment_mode,
                'student_id' => $student->id
            ];
        }

        // 4. Calculate Stats & Shares
        $totalStudents = $students->count();

        usort($referralStats, fn($a, $b) => $b['total_admissions'] <=> $a['total_admissions']);
        foreach ($referralStats as &$stat) {
            $stat['share'] = ($totalReferrals > 0) ? round(($stat['total_admissions'] / $totalReferrals) * 100, 1) : 0;

            // Calculate Paid/Remaining counts for this referral
            $referralStudents = collect($detailedAdmissions)->where('referral_name', $stat['referral_name']);
            $stat['paid_count'] = $referralStudents->where('is_commission_paid', true)->count();
            $stat['remaining_count'] = $stat['total_admissions'] - $stat['paid_count'];
            $stat['total_payout'] = $referralStudents->sum('commission_amount');
        }

        usort($sourceStats, fn($a, $b) => $b['total_admissions'] <=> $a['total_admissions']);
        foreach ($sourceStats as &$stat) {
            $stat['share'] = ($totalStudents > 0) ? round(($stat['total_admissions'] / $totalStudents) * 100, 1) : 0;
        }

        return [
            'total_students' => $totalStudents,
            'total_referrals' => $totalReferrals,
            'source_stats' => array_values($sourceStats),
            'referral_stats' => array_values($referralStats),
            'students' => $detailedAdmissions
        ];
    }

    private function streamCsv($data)
    {
        $headers = [
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Content-type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=referral_report_' . Carbon::now()->format('Y_m_d_H_i') . '.csv',
            'Expires' => '0',
            'Pragma' => 'public',
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');

            // Add BOM for Excel compatibility
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header Row
            fputcsv($file, [
                'Admission Date',
                'Enrollment No',
                'Student Name',
                'Course',
                'Batch',
                'Source',
                'Referral Name',
                'Status',
                'Total Fee',
                'Concession',
                'Paid Amount',
                'Due Amount'
            ]);

            foreach ($data as $row) {
                fputcsv($file, [
                    $row['admission_date'],
                    $row['enrollment_number'],
                    $row['student_name'],
                    $row['course_name'],
                    $row['batch_name'],
                    $row['source'],
                    $row['referral_name'],
                    $row['status'],
                    str_replace(',', '', $row['total_amount']), // Remove commas for calculations
                    str_replace(',', '', $row['concession']),
                    str_replace(',', '', $row['paid']),
                    str_replace(',', '', $row['remaining'])
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function markCommissionPaid(Request $request, Student $student)
    {
        // Validation
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_mode' => 'required|string',
            'remarks' => 'nullable|string'
        ]);

        // Update Student Commission Details
        $student->update([
            'referral_commission_paid_at' => Carbon::now(),
            'referral_commission_amount' => $request->amount,
            'referral_payment_mode' => $request->payment_mode,
            'referral_payment_remarks' => $request->remarks
        ]);

        $message = 'Commission marked as paid successfully';
        $warning = null;

        // Auto-apply concession if mode is "Fee Discount"
        if ($request->payment_mode === 'Fee Discount') {
            // Find Referrer Student
            $referrer = Student::where('name', $student->referral_name)->first();

            if ($referrer) {
                // Find Active Fee Record (latest unpaid/partial first, fallback to any latest)
                $feeRecord = StudentFee::withoutGlobalScope('academic_year')
                    ->where('student_id', $referrer->id)
                    ->whereIn('status', ['unpaid', 'partial'])
                    ->orderBy('created_at', 'desc')
                    ->first();

                // Fallback: any fee record if no unpaid found
                if (!$feeRecord) {
                    $feeRecord = StudentFee::withoutGlobalScope('academic_year')
                        ->where('student_id', $referrer->id)
                        ->orderBy('created_at', 'desc')
                        ->first();
                }

                if ($feeRecord) {
                    $appliedAmount = $feeRecord->applyConcession(
                        $request->amount,
                        "Referral Reward for referred student: " . $student->name
                    );

                    // Log a StudentConcession record so it appears in the activity timeline.
                    // Using raw DB insert to bypass the model boot() which auto-injects
                    // 'status' and 'requested_by' columns that don't exist in the actual DB table.
                    if ($appliedAmount > 0) {
                        $now = Carbon::now();
                        DB::table('student_concessions')->insert([
                            'student_id' => $referrer->id,
                            'fee_category_id' => $feeRecord->fee_category_id,
                            'concession_type' => 'fixed',
                            'concession_value' => $appliedAmount,
                            'concession_amount' => $appliedAmount,
                            'notes' => 'Referral Reward for: ' . $student->name . '. Applied via Referral Commission (Fee Discount mode)',
                            'applied_by' => auth()->id(),
                            'applied_at' => $now,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }

                    $message .= ". Discount of ₹" . number_format($appliedAmount, 2) . " applied to referrer {$referrer->name}.";

                    if ($appliedAmount < $request->amount) {
                        $warning = "Only ₹" . number_format($appliedAmount, 2) . " applied (requested ₹" . number_format($request->amount, 2) . "). Fee balance was insufficient for the full amount.";
                    }
                } else {
                    $warning = "Referrer found ({$referrer->name}), but no fee record exists to apply discount.";
                }
            } else {
                $warning = "Referrer '{$student->referral_name}' not found in students list. Concession NOT applied.";
            }
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'warning' => $warning,
            'paid_date' => Carbon::now()->format('d M, Y'),
            'amount' => number_format($request->amount, 2),
            'mode' => $request->payment_mode
        ]);
    }
}
