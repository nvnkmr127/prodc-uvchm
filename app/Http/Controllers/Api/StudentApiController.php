<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Carbon\Carbon;

class StudentApiController extends Controller
{
    /**
     * Get student profile with comprehensive data
     */
    public function profile(Request $request, Student $student)
    {
        $student->load([
            'batch.course',
            'invoices' => function($query) {
                $query->latest()->limit(5);
            },
            'attendances' => function($query) {
                $query->latest()->limit(10);
            }
        ]);

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
                    'total_invoiced' => $student->invoices->sum('total_amount'),
                    'total_paid' => $student->invoices->sum('paid_amount'),
                    'total_due' => $student->invoices->sum('due_amount'),
                    'recent_invoices' => $student->invoices->map(function($invoice) {
                        return [
                            'id' => $invoice->id,
                            'invoice_number' => $invoice->invoice_number,
                            'total_amount' => $invoice->total_amount,
                            'paid_amount' => $invoice->paid_amount,
                            'due_amount' => $invoice->due_amount,
                            'status' => $invoice->status,
                            'due_date' => $invoice->due_date,
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
     * Get student financial details
     */
    public function financials(Request $request, Student $student)
    {
        $invoices = Invoice::where('student_id', $student->id)
            ->with(['payments', 'invoiceItems'])
            ->orderBy('issue_date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_invoiced' => $invoices->sum('total_amount'),
                    'total_paid' => $invoices->sum('paid_amount'),
                    'total_due' => $invoices->sum('due_amount'),
                    'total_invoices' => $invoices->count(),
                ],
                'invoices' => $invoices->map(function($invoice) {
                    return [
                        'id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'issue_date' => $invoice->issue_date,
                        'due_date' => $invoice->due_date,
                        'total_amount' => $invoice->total_amount,
                        'paid_amount' => $invoice->paid_amount,
                        'due_amount' => $invoice->due_amount,
                        'status' => $invoice->status,
                        'items' => $invoice->invoiceItems->map(function($item) {
                            return [
                                'description' => $item->description,
                                'amount' => $item->amount,
                            ];
                        }),
                        'payments' => $invoice->payments->map(function($payment) {
                            return [
                                'amount' => $payment->amount,
                                'payment_date' => $payment->payment_date,
                                'payment_method' => $payment->payment_method,
                                'receipt_number' => $payment->receipt_number,
                            ];
                        })
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
     * Get student dashboard data
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

        // Get pending dues
        $pendingInvoices = Invoice::where('student_id', $student->id)
            ->where('status', '!=', 'paid')
            ->where('due_date', '>=', $today)
            ->orderBy('due_date')
            ->get();

        // Get overdue invoices
        $overdueInvoices = Invoice::where('student_id', $student->id)
            ->where('status', '!=', 'paid')
            ->where('due_date', '<', $today)
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
                    'pending_amount' => $pendingInvoices->sum('due_amount'),
                    'overdue_amount' => $overdueInvoices->sum('due_amount'),
                    'pending_invoices_count' => $pendingInvoices->count(),
                    'overdue_invoices_count' => $overdueInvoices->count(),
                ],
                'alerts' => [
                    'low_attendance' => $attendancePercentage < 75,
                    'overdue_payments' => $overdueInvoices->count() > 0,
                    'upcoming_due_dates' => $pendingInvoices->where('due_date', '<=', $today->addDays(7))->count(),
                ]
            ]
        ]);
    }
}