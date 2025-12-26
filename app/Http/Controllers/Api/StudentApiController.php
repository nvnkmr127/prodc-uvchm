<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\StudentFee; // Replaced Invoice with StudentFee
use App\Models\Payment;    // Added Payment model
use Illuminate\Http\Request;
use Carbon\Carbon;

class StudentApiController extends Controller
{
    /**
     * Get student profile with comprehensive data using the new component-based system
     */
    public function profile(Request $request, Student $student)
    {
        $student->load([
            'batch.course',
            // MODIFIED: Eager load studentFees instead of invoices
            'studentFees' => function($query) {
                $query->latest('due_date')->limit(5);
            },
            'attendances' => function($query) {
                $query->latest()->limit(10);
            }
        ]);

        // MODIFIED: Calculate financial summary from studentFees
        $allFees = $student->studentFees;
        $totalFeeAmount = $allFees->sum('amount');
        $totalPaidAmount = $allFees->sum('paid_amount');
        $totalConcession = $allFees->sum('concession_amount');
        $totalDue = $totalFeeAmount - $totalPaidAmount - $totalConcession;

        return response()->json([
            'success' => true,
            'data' => [
                'personal_info' => [
                    'id' => $student->id,
                    'name' => $student->name,
                    'email' => $student->email,
                    'enrollment_number' => $student->enrollment_number,
                    'gender' => $student->gender,
                    'student_mobile' => $student->student_mobile,
                    'father_name' => $student->father_name,
                    'father_mobile' => $student->father_mobile,
                    'village' => $student->village,
                    'admission_date' => $student->admission_date,
                    'status' => $student->status,
                ],
                'academic_info' => [
                    'course' => $student->batch->course->name ?? null,
                    'batch' => $student->batch->name ?? null,
                    'batch_start_date' => $student->batch->start_date ?? null,
                    'batch_end_date' => $student->batch->end_date ?? null,
                ],
                'financial_summary' => [
                    'total_billed' => $totalFeeAmount,
                    'total_paid' => $totalPaidAmount,
                    'total_concession' => $totalConcession,
                    'total_due' => $totalDue,
                    // MODIFIED: Show recent fee components instead of invoices
                    'recent_fees' => $student->studentFees->map(function($fee) {
                        return [
                            'id' => $fee->id,
                            'fee_category' => $fee->feeCategory->name ?? 'N/A',
                            'total_amount' => $fee->amount,
                            'paid_amount' => $fee->paid_amount,
                            'remaining_amount' => $fee->getRemainingAmount(),
                            'status' => $fee->status,
                            'due_date' => $fee->due_date->format('Y-m-d'),
                        ];
                    })
                ],
                'attendance_summary' => [
                    'total_classes' => $student->attendances->count(),
                    'present_days' => $student->attendances->where('status', 'present')->count(),
                    'absent_days' => $student->attendances->where('status', 'absent')->count(),
                    'attendance_percentage' => $student->attendances->count() > 0 
                        ? round(($student->attendances->where('status', 'present')->count() / $student->attendances->count()) * 100, 2)
                        : 0,
                ]
            ]
        ]);
    }

    /**
     * Get student attendance for a specific month
     */
    public function attendance(Request $request, Student $student)
    {
        $request->validate([
            'month' => 'nullable|date_format:Y-m',
            'limit' => 'nullable|integer|min:1|max:100'
        ]);

        $month = $request->month ?? now()->format('Y-m');
        $limit = $request->limit ?? 30;

        $attendances = Attendance::where('student_id', $student->id)
            ->whereYear('attendance_date', substr($month, 0, 4))
            ->whereMonth('attendance_date', substr($month, 5, 2))
            ->with(['batch', 'faculty'])
            ->orderBy('attendance_date', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'month' => $month,
                'total_records' => $attendances->count(),
                'present_days' => $attendances->where('status', 'present')->count(),
                'absent_days' => $attendances->where('status', 'absent')->count(),
                'attendance_records' => $attendances->map(function($attendance) {
                    return [
                        'date' => $attendance->attendance_date,
                        'status' => $attendance->status,
                        'faculty' => $attendance->faculty->name ?? 'Unknown',
                        'batch' => $attendance->batch->name ?? 'Unknown',
                    ];
                })
            ]
        ]);
    }

    /**
     * Get student financial details using the new component-based system
     */
    public function financials(Request $request, Student $student)
    {
        // MODIFIED: Fetch fee components and component-based payments
        $fees = StudentFee::where('student_id', $student->id)
            ->with('feeCategory')
            ->orderBy('due_date', 'desc')
            ->get();

        $payments = Payment::where('student_id', $student->id)
            ->where('payment_type', 'component')
            ->with('componentItems.studentFee.feeCategory')
            ->orderBy('payment_date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_billed' => $fees->sum('amount'),
                    'total_paid' => $fees->sum('paid_amount'),
                    'total_concession' => $fees->sum('concession_amount'),
                    'total_due' => $fees->sum(fn($fee) => $fee->getRemainingAmount()),
                    'total_fee_components' => $fees->count(),
                ],
                'fee_components' => $fees->map(function($fee) {
                    return [
                        'id' => $fee->id,
                        'category' => $fee->feeCategory->name ?? 'N/A',
                        'due_date' => $fee->due_date->format('Y-m-d'),
                        'total_amount' => $fee->amount,
                        'paid_amount' => $fee->paid_amount,
                        'concession' => $fee->concession_amount,
                        'remaining_amount' => $fee->getRemainingAmount(),
                        'status' => $fee->status,
                    ];
                }),
                'payments' => $payments->map(function($payment) {
                    return [
                        'id' => $payment->id,
                        'receipt_number' => $payment->receipt_number,
                        'payment_date' => $payment->payment_date->format('Y-m-d'),
                        'total_amount' => $payment->amount,
                        'payment_method' => $payment->payment_method,
                        'transaction_id' => $payment->transaction_id,
                        'notes' => $payment->notes,
                        'items' => $payment->componentItems->map(function($item) {
                            return [
                                'category' => $item->studentFee->feeCategory->name ?? 'N/A',
                                'amount_paid' => $item->amount_paid,
                            ];
                        }),
                    ];
                })
            ]
        ]);
    }

    /**
     * Update student profile (limited fields)
     */
    public function updateProfile(Request $request, Student $student)
    {
        $request->validate([
            'student_mobile' => 'nullable|string|max:15',
            'father_mobile' => 'nullable|string|max:15',
            'village' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:students,email,' . $student->id,
        ]);

        $student->update($request->only([
            'student_mobile',
            'father_mobile', 
            'village',
            'email'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $student->fresh()
        ]);
    }

    /**
     * Get student dashboard data using the new component-based system
     */
    public function dashboard(Student $student)
    {
        $today = Carbon::today();
        $currentMonth = Carbon::now()->format('Y-m');

        // Get recent attendance
        $recentAttendance = Attendance::where('student_id', $student->id)
            ->orderBy('attendance_date', 'desc')
            ->limit(5)
            ->get();

        // MODIFIED: Get pending and overdue fees from StudentFee model
        $pendingFees = StudentFee::where('student_id', $student->id)
            ->whereIn('status', ['unpaid', 'partial'])
            ->where('due_date', '>=', $today)
            ->get();

        $overdueFees = StudentFee::where('student_id', $student->id)
            ->where(function ($query) use ($today) {
                $query->where('status', 'overdue')
                      ->orWhere(function ($subQuery) use ($today) {
                          $subQuery->whereIn('status', ['unpaid', 'partial'])
                                   ->where('due_date', '<', $today);
                      });
            })
            ->get();
            
        // Calculate attendance percentage for current month
        $monthlyAttendance = Attendance::where('student_id', $student->id)
            ->whereYear('attendance_date', substr($currentMonth, 0, 4))
            ->whereMonth('attendance_date', substr($currentMonth, 5, 2))
            ->get();

        $attendancePercentage = $monthlyAttendance->count() > 0 
            ? round(($monthlyAttendance->where('status', 'present')->count() / $monthlyAttendance->count()) * 100, 2)
            : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'student_info' => [
                    'name' => $student->name,
                    'enrollment_number' => $student->enrollment_number,
                    'course' => $student->batch->course->name ?? 'Unknown',
                    'batch' => $student->batch->name ?? 'Unknown',
                ],
                'attendance_stats' => [
                    'current_month_percentage' => $attendancePercentage,
                    'total_present' => $monthlyAttendance->where('status', 'present')->count(),
                    'total_classes' => $monthlyAttendance->count(),
                    'recent_attendance' => $recentAttendance->map(function($att) {
                        return [
                            'date' => $att->attendance_date,
                            'status' => $att->status,
                        ];
                    })
                ],
                'financial_stats' => [
                    'pending_amount' => $pendingFees->sum(fn($fee) => $fee->getRemainingAmount()),
                    'overdue_amount' => $overdueFees->sum(fn($fee) => $fee->getRemainingAmount()),
                    'pending_fees_count' => $pendingFees->count(),
                    'overdue_fees_count' => $overdueFees->count(),
                ],
                'alerts' => [
                    'low_attendance' => $attendancePercentage < 75,
                    'overdue_payments' => $overdueFees->count() > 0,
                    'upcoming_due_dates' => $pendingFees->where('due_date', '<=', $today->addDays(7))->count(),
                ]
            ]
        ]);
    }
}