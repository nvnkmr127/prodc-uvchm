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
        $search = $request->input('search');
        $role = $request->input('role');
        
        $dateObj = Carbon::parse($date);
        
        $usersQuery = User::where('status', 'active');
        
        if ($role) {
            $usersQuery->role($role);
        } else {
            $usersQuery->role(['admin', 'college-admin', 'counselor', 'staff']);
        }
            
        if ($search) {
            $usersQuery->where('name', 'like', "%{$search}%");
        }
            
        $users = $usersQuery->get();
        
        $activitiesByStaff = [];
        // Hardcoded Targets for illustration (in real app, move to settings/model)
        $targets = [
            'calls' => 50,
            'fee' => 20000,
            'admissions' => 1
        ];

        foreach ($users as $user) {
            $callsCount = FollowUp::where('user_id', $user->id)->whereDate('created_at', $date)->count();
            $feeCollected = Payment::where('created_by', $user->id)->whereDate('payment_date', $date)->sum('amount');
            $paymentsCount = Payment::where('created_by', $user->id)->whereDate('payment_date', $date)->count();
            
            $admissionsCount = Activity::where('causer_id', $user->id)
                ->where('subject_type', Admission::class)
                ->where('description', 'like', '%created%')
                ->whereDate('created_at', $date)
                ->count();
                
            $enquiriesCount = Activity::where('causer_id', $user->id)
                ->where('subject_type', Enquiry::class)
                ->where('description', 'like', '%created%')
                ->whereDate('created_at', $date)
                ->count();
                
            $pendingFollowUps = Enquiry::where('assigned_to_user_id', $user->id)
                ->where('status', '!=', 'converted')
                ->whereDate('next_follow_up_date', '<=', $date)
                ->count();

            // Attendance Proxy & Productivity Score
            $firstAction = Activity::where('causer_id', $user->id)->whereDate('created_at', $date)->oldest()->first();
            $lastAction = Activity::where('causer_id', $user->id)->whereDate('created_at', $date)->latest()->first();
            
            // Current Status
            $isOnline = $lastAction && $lastAction->created_at->diffInMinutes(now()) < 10;
            
            // Score Calculation (Weighted)
            $callProgress = ($callsCount / $targets['calls']) * 30;
            $feeProgress = ($feeCollected / $targets['fee']) * 40;
            $admProgress = ($admissionsCount / $targets['admissions']) * 30;
            $productivityScore = min(100, $callProgress + $feeProgress + $admProgress);

            $activitiesByStaff[$user->id] = [
                'user' => $user,
                'calls_count' => $callsCount,
                'fee_collected' => $feeCollected,
                'payments_count' => $paymentsCount,
                'admissions_count' => $admissionsCount,
                'enquiries_count' => $enquiriesCount,
                'pending_tasks' => $pendingFollowUps,
                'first_action' => $firstAction ? $firstAction->created_at : null,
                'last_action' => $lastAction ? $lastAction->created_at : null,
                'is_online' => $isOnline,
                'score' => round($productivityScore),
                'progress' => [
                    'calls' => min(100, ($callsCount / $targets['calls']) * 100),
                    'fee' => min(100, ($feeCollected / $targets['fee']) * 100),
                    'admissions' => min(100, ($admissionsCount / $targets['admissions']) * 100),
                ]
            ];
        }
        
        // Weekly Trend Data (Last 7 Days)
        $trends = [];
        for ($i = 6; $i >= 0; $i--) {
            $tDate = Carbon::today()->subDays($i)->toDateString();
            $trends['labels'][] = Carbon::today()->subDays($i)->format('D');
            $trends['calls'][] = FollowUp::whereDate('created_at', $tDate)->count();
            $trends['fees'][] = Payment::whereDate('payment_date', $tDate)->sum('amount');
            $trends['admissions'][] = Activity::where('subject_type', Admission::class)
                ->where('description', 'like', '%created%')
                ->whereDate('created_at', $tDate)->count();
        }

        // Leaderboard
        $leaderboard = [
            'calls' => collect($activitiesByStaff)->sortByDesc('calls_count')->take(3),
            'fees' => collect($activitiesByStaff)->sortByDesc('fee_collected')->take(3),
            'admissions' => collect($activitiesByStaff)->sortByDesc('admissions_count')->take(3),
        ];
        
        $summary = [
            'total_calls' => FollowUp::whereDate('created_at', $date)->count(),
            'total_admissions' => Activity::where('subject_type', Admission::class)
                ->where('description', 'like', '%created%')
                ->whereDate('created_at', $date)
                ->count(),
            'total_fees' => Payment::whereDate('payment_date', $date)->sum('amount'),
            'online_staff' => collect($activitiesByStaff)->where('is_online', true)->count(),
            'peak_hour' => 'N/A'
        ];

        // Peak Hour Calculation
        $peakHourData = Activity::select(DB::raw('HOUR(created_at) as hour'), DB::raw('count(*) as count'))
            ->whereDate('created_at', $date)
            ->groupBy('hour')
            ->orderBy('count', 'desc')
            ->first();
            
        if ($peakHourData) {
            $hour = $peakHourData->hour;
            $summary['peak_hour'] = Carbon::createFromTime($hour, 0)->format('h A') . ' - ' . Carbon::createFromTime($hour + 1, 0)->format('h A');
        }

        $timeline = Activity::with('causer')
            ->whereDate('created_at', $date)
            ->whereIn('causer_id', $users->pluck('id'))
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        return view('admin.staff_activity.index', compact('activitiesByStaff', 'date', 'timeline', 'users', 'trends', 'leaderboard', 'targets', 'summary'));
    }

    public function export(Request $request)
    {
        $date = $request->input('date', Carbon::today()->toDateString());
        $users = User::role(['admin', 'college-admin', 'counselor', 'staff'])->where('status', 'active')->get();
        
        $filename = "staff_activity_{$date}.csv";
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use($users, $date) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Staff Name', 'Role', 'Calls', 'Adm. Items Taken', 'Admissions', 'Collection (₹)', 'First Action', 'Last Action']);

            foreach ($users as $user) {
                $calls = FollowUp::where('user_id', $user->id)->whereDate('created_at', $date)->count();
                $fees = Payment::where('created_by', $user->id)->whereDate('payment_date', $date)->sum('amount');
                $admissions = Activity::where('causer_id', $user->id)
                    ->where('subject_type', Admission::class)
                    ->where('description', 'like', '%created%')
                    ->whereDate('created_at', $date)->count();
                
                $first = Activity::where('causer_id', $user->id)->whereDate('created_at', $date)->oldest()->first();
                $last = Activity::where('causer_id', $user->id)->whereDate('created_at', $date)->latest()->first();

                fputcsv($file, [
                    $user->name,
                    $user->roles->first()?->name ?? 'Staff',
                    $calls,
                    0, // Placeholder
                    $admissions,
                    $fees,
                    $first ? $first->created_at->format('H:i') : '-',
                    $last ? $last->created_at->format('H:i') : '-'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function show($id, Request $request)
    {
        $user = User::findOrFail($id);
        
        $date = $request->input('date');
        $event = $request->input('event');
        $subjectType = $request->input('subject_type');
        
        $query = Activity::where('causer_id', $user->id);
            
        if ($date) {
            $query->whereDate('created_at', $date);
        }
        
        if ($event) {
            $query->where('event', $event);
        }
        
        if ($subjectType) {
            // Map common names to class paths if needed
            $classMap = [
                'enquiry' => Enquiry::class,
                'admission' => Admission::class,
                'payment' => Payment::class,
                'followup' => FollowUp::class,
            ];
            
            $targetClass = $classMap[strtolower($subjectType)] ?? $subjectType;
            $query->where('subject_type', 'like', "%{$targetClass}%");
        }
        
        $activities = $query->orderBy('created_at', 'desc')
            ->paginate(25)
            ->withQueryString();
            
        // For the filter dropdowns
        $availableEvents = Activity::where('causer_id', $user->id)
            ->distinct()
            ->pluck('event');
            
        $availableModules = Activity::where('causer_id', $user->id)
            ->distinct()
            ->pluck('subject_type')
            ->map(fn($type) => class_basename($type))
            ->unique();
            
        return view('admin.staff_activity.show', compact('user', 'activities', 'date', 'availableEvents', 'availableModules'));
    }
}
