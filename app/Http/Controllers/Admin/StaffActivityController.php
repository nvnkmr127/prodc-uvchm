<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\FollowUp;
use App\Models\Payment;
use App\Models\Admission;
use App\Models\Enquiry;
use App\Models\StudentFee;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class StaffActivityController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->input('date', Carbon::today()->toDateString());
        $dateObj = Carbon::parse($date);
        
        $users = User::role(['admin', 'college-admin', 'counselor', 'staff'])->where('status', 'active')->get();
        
        $activitiesByStaff = [];
        
        foreach ($users as $user) {
            // 1. Calls Made (FollowUps created by this user)
            $callsCount = FollowUp::where('user_id', $user->id)
                ->whereDate('created_at', $date)
                ->count();
                
            // 2. Fee Collection (Payments created by this user)
            $feeCollected = Payment::where('created_by', $user->id)
                ->whereDate('payment_date', $date)
                ->sum('amount');
                
            $paymentsCount = Payment::where('created_by', $user->id)
                ->whereDate('payment_date', $date)
                ->count();
                
            // 3. Admissions (Handled by this user - from activity log if possible, or assigned enquiries converted to admissions)
            // For now let's use Activity Log for 'created' events on Admission model if available
            $admissionsCount = Activity::where('causer_id', $user->id)
                ->where('subject_type', Admission::class)
                ->where('description', 'like', '%created%')
                ->whereDate('created_at', $date)
                ->count();
                
            // 4. Enquiries (Added or Updated by user)
            $enquiriesCount = Activity::where('causer_id', $user->id)
                ->where('subject_type', Enquiry::class)
                ->where('description', 'like', '%created%')
                ->whereDate('created_at', $date)
                ->count();
                
            // 5. Pending Tasks (Follow-ups for today NOT completed, or Enquiries assigned but no action)
            // This is more about current status than specific date of activity
            $pendingFollowUps = Enquiry::where('assigned_to_user_id', $user->id)
                ->where('status', '!=', 'converted')
                ->whereDate('next_follow_up_date', '<=', $date)
                ->count();

            $activitiesByStaff[$user->id] = [
                'user' => $user,
                'calls_count' => $callsCount,
                'fee_collected' => $feeCollected,
                'payments_count' => $paymentsCount,
                'admissions_count' => $admissionsCount,
                'enquiries_count' => $enquiriesCount,
                'pending_tasks' => $pendingFollowUps,
            ];
        }
        
        // General Activity Timeline for the selected date
        $timeline = Activity::with('causer')
            ->whereDate('created_at', $date)
            ->whereIn('causer_id', $users->pluck('id'))
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        return view('admin.staff_activity.index', compact('activitiesByStaff', 'date', 'timeline', 'users'));
    }

    public function show($id, Request $request)
    {
        $user = User::findOrFail($id);
        $date = $request->input('date', Carbon::today()->toDateString());
        
        $activities = Activity::where('causer_id', $user->id)
            ->whereDate('created_at', $date)
            ->orderBy('created_at', 'desc')
            ->get();
            
        return view('admin.staff_activity.show', compact('user', 'activities', 'date'));
    }
}
