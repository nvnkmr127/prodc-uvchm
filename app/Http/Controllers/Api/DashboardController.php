<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\Invoice;
use App\Models\User;
use App\Models\Batch;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get comprehensive dashboard statistics
     */
    public function stats(Request $request)
    {
        $today = Carbon::today();
        $currentMonth = Carbon::now()->startOfMonth();
        
        // Student Statistics
        $studentStats = [
            'total_students' => Student::count(),
            'active_students' => Student::where('status', 'active')->count(),
            'graduated_students' => Student::where('status', 'graduated')->count(),
            'dropout_students' => Student::where('status', 'dropout')->count(),
        ];

        // Attendance Statistics
        $todayAttendance = Attendance::where('attendance_date', $today)->get();
        $monthlyAttendance = Attendance::where('attendance_date', '>=', $currentMonth)->get();
        
        $attendanceStats = [
            'today' => [
                'total_marked' => $todayAttendance->count(),
                'present' => $todayAttendance->where('status', 'present')->count(),
                'absent' => $todayAttendance->where('status', 'absent')->count(),
                'percentage' => $todayAttendance->count() > 0 
                    ? round(($todayAttendance->where('status', 'present')->count() / $todayAttendance->count()) * 100, 2)
                    : 0
            ],
            'monthly' => [
                'total_marked' => $monthlyAttendance->count(),
                'present' => $monthlyAttendance->where('status', 'present')->count(),
                'absent' => $monthlyAttendance->where('status', 'absent')->count(),
                'percentage' => $monthlyAttendance->count() > 0 
                    ? round(($monthlyAttendance->where('status', 'present')->count() / $monthlyAttendance->count()) * 100, 2)
                    : 0
            ]
        ];

        // Financial Statistics
        $invoices = Invoice::all();
        $financialStats = [
            'total_invoiced' => $invoices->sum('total_amount'),
            'total_collected' => $invoices->sum('paid_amount'),
            'total_pending' => $invoices->sum('due_amount'),
            'collection_percentage' => $invoices->sum('total_amount') > 0 
                ? round(($invoices->sum('paid_amount') / $invoices->sum('total_amount')) * 100, 2)
                : 0
        ];

        // Recent Activities
        $recentActivities = [
            'new_admissions_today' => Student::whereDate('admission_date', $today)->count(),
            'payments_today' => \App\Models\Payment::whereDate('payment_date', $today)->count(),
            'attendance_marked_today' => $todayAttendance->count(),
        ];

        // Batch-wise Statistics
        $batchStats = Batch::with(['students', 'course'])
            ->get()
            ->map(function($batch) {
                $students = $batch->students;
                return [
                    'batch_name' => $batch->name,
                    'course_name' => $batch->course->name,
                    'total_students' => $students->count(),
                    'active_students' => $students->where('status', 'active')->count(),
                    'start_date' => $batch->start_date,
                    'end_date' => $batch->end_date,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'students' => $studentStats,
                'attendance' => $attendanceStats,
                'financials' => $financialStats,
                'recent_activities' => $recentActivities,
                'batches' => $batchStats,
                'generated_at' => now()->toISOString(),
            ]
        ]);
    }

    /**
     * Get attendance trends for charts
     */
    public function attendanceTrends(Request $request)
    {
        $request->validate([
            'days' => 'nullable|integer|min:7|max:90'
        ]);

        $days = $request->days ?? 30;
        $startDate = Carbon::now()->subDays($days);

        $attendanceData = Attendance::where('attendance_date', '>=', $startDate)
            ->selectRaw('attendance_date, status, COUNT(*) as count')
            ->groupBy('attendance_date', 'status')
            ->orderBy('attendance_date')
            ->get();

        // Format data for charts
        $trendData = [];
        for ($i = $days; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $dayData = $attendanceData->where('attendance_date', $date);
            
            $trendData[] = [
                'date' => $date,
                'present' => $dayData->where('status', 'present')->sum('count'),
                'absent' => $dayData->where('status', 'absent')->sum('count'),
                'total' => $dayData->sum('count'),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'period_days' => $days,
                'trends' => $trendData
            ]
        ]);
    }

    /**
     * Get financial trends
     */
    public function financialTrends(Request $request)
    {
        $request->validate([
            'months' => 'nullable|integer|min:3|max:12'
        ]);

        $months = $request->months ?? 6;
        
        $financialData = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();

            $invoices = Invoice::whereBetween('issue_date', [$monthStart, $monthEnd])->get();
            $payments = \App\Models\Payment::whereBetween('payment_date', [$monthStart, $monthEnd])->get();

            $financialData[] = [
                'month' => $month->format('Y-m'),
                'month_name' => $month->format('M Y'),
                'invoiced' => $invoices->sum('total_amount'),
                'collected' => $payments->sum('amount'),
                'pending' => $invoices->sum('due_amount'),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'period_months' => $months,
                'trends' => $financialData
            ]
        ]);
    }
}