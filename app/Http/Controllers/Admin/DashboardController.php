<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Dashboard;
use Illuminate\Support\Facades\Auth;
use App\Models\Student;
use App\Models\Admission;
use App\Models\User;
use App\Models\Payment;
use App\Models\Expense;
use App\Models\Course;
use App\Models\Batch;
use App\Models\Invoice;
use App\Models\Attendance; // <-- Import the Attendance model
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $dashboard = Dashboard::whereHas('role', fn($q) => $q->where('name', $user->roles->first()->name ?? ''))->first();
        
        if ($dashboard && $dashboard->widgets()->exists()) {
            $layout = $dashboard->widgets()->orderBy('pivot_row')->orderBy('pivot_col')->get();
            $widgetData = $this->getWidgetData();
            return view('admin.dashboard.dynamic', compact('layout', 'widgetData'));
        }
        
        $data = $this->getWidgetData();
        return view('admin.dashboard.index', $data);
    }
    
    public function getWidgetData()
    {
        $data = [];
        // Data for Stats Cards
        $data['totalStudents'] = Student::where('status', 'active')->count();
        $data['pendingAdmissionsCount'] = Admission::where('status', 'pending')->count();
        $data['totalFaculty'] = User::role('staff')->count();
        $data['feesCollectedThisMonth'] = Payment::whereMonth('payment_date', Carbon::now()->month)->sum('amount');
        $data['totalOutstandingDues'] = Invoice::whereIn('status', ['unpaid', 'partially_paid'])->sum('due_amount');
        $data['totalCourses'] = Course::count();
        $data['totalBatches'] = Batch::count();
        $data['totalAlumni'] = Student::where('status', 'graduated')->count();

        // Data for Student Distribution Chart
        $studentDistribution = Student::where('status', 'active')
            ->join('batches', 'students.batch_id', '=', 'batches.id')
            ->join('courses', 'batches.course_id', '=', 'courses.id')
            ->select('courses.name as course_name', DB::raw('count(students.id) as student_count'))
            ->groupBy('courses.name')->get();
        $data['courseLabels'] = $studentDistribution->pluck('course_name');
        $data['courseData'] = $studentDistribution->pluck('student_count');

        // Data for Financial Overview Chart
        $financialLabels = [];
        $incomeData = [];
        $expenseData = [];
        for($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $financialLabels[] = $date->format('M d');
            $incomeData[] = Payment::whereDate('payment_date', $date)->sum('amount');
            $expenseData[] = Expense::whereDate('expense_date', $date)->sum('amount');
        }
        $data['financialLabels'] = $financialLabels;
        $data['incomeData'] = $incomeData;
        $data['expenseData'] = $expenseData;

        // ** THE FIX IS HERE **
        // Data for Daily Attendance Chart
        $attendanceLabels = [];
        $presentData = [];
        $absentData = [];
        // Loop through the last 30 days, excluding non-working days if necessary
        for($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            if (!$date->isSunday()) { // Example: Exclude Sundays
                $attendanceLabels[] = $date->format('M d');
                $presentData[] = Attendance::whereDate('attendance_date', $date)->where('status', 'present')->count();
                $absentData[] = Attendance::whereDate('attendance_date', $date)->where('status', 'absent')->count();
            }
        }
        $data['attendanceLabels'] = $attendanceLabels;
        $data['presentData'] = $presentData;
        $data['absentData'] = $absentData;


        // Data for Recent Activity List
        $data['latestActivities'] = Activity::with('causer')->latest()->limit(7)->get();
        
        return $data;
    }
}
