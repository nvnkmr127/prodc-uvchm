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
use App\Models\Setting;

class StaffActivityController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::today()->toDateString());
        $endDate = $request->input('end_date', Carbon::today()->toDateString());
        $search = $request->input('search');
        $role = $request->input('role');

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();
        $daysCount = max(1, $start->diffInDays($end) + 1);
        
        $usersQuery = User::where('status', 'active');
        
        // Exclusively filter for college-admin role
        $usersQuery->role('college-admin');
            
        if ($search) {
            $usersQuery->where('name', 'like', "%{$search}%");
        }
            
        $users = $usersQuery->get();
        
        $activitiesByStaff = [];
        // Dynamic Targets from Settings (with fallbacks)
        $baseTargets = [
            'calls' => Setting::get('staff_target_calls', 50),
            'fee' => Setting::get('staff_target_fee', 20000),
            'admissions' => Setting::get('staff_target_admissions', 1)
        ];

        // Scale targets based on date range
        $targets = [
            'calls' => $baseTargets['calls'] * $daysCount,
            'fee' => $baseTargets['fee'] * $daysCount,
            'admissions' => $baseTargets['admissions'] * $daysCount
        ];

        foreach ($users as $user) {
            $callsCount = FollowUp::where('user_id', $user->id)
                ->whereBetween('created_at', [$start, $end])->count();

            $feeCollected = Payment::where('created_by', $user->id)
                ->whereBetween('payment_date', [$startDate, $endDate])->sum('amount');

            $paymentsCount = Payment::where('created_by', $user->id)
                ->whereBetween('payment_date', [$startDate, $endDate])->count();
            
            $admissionsCount = Activity::where('causer_id', $user->id)
                ->where('subject_type', Admission::class)
                ->where('description', 'like', '%created%')
                ->whereBetween('created_at', [$start, $end])
                ->count();
                
            $enquiriesCount = Activity::where('causer_id', $user->id)
                ->where('subject_type', Enquiry::class)
                ->where('description', 'like', '%created%')
                ->whereBetween('created_at', [$start, $end])
                ->count();
                
            $pendingFollowUps = Enquiry::where('assigned_to_user_id', $user->id)
                ->where('status', '!=', 'converted')
                ->whereDate('next_follow_up_date', '<=', $endDate)
                ->count();

            // Attendance Proxy & Productivity Score
            $firstAction = Activity::where('causer_id', $user->id)->whereBetween('created_at', [$start, $end])->oldest()->first();
            $lastAction = Activity::where('causer_id', $user->id)->whereBetween('created_at', [$start, $end])->latest()->first();

            // Current Status (Always based on NOW)
            $lastEverAction = Activity::where('causer_id', $user->id)->latest()->first();
            $isOnline = $lastEverAction && $lastEverAction->created_at->diffInMinutes(now()) < 15;
            $lastSeen = $lastEverAction ? $lastEverAction->created_at->diffForHumans() : 'Never';

            // Conversion Rate
            $convRate = $enquiriesCount > 0 ? ($admissionsCount / $enquiriesCount) * 100 : 0;
            
            // Score Calculation (Weighted)
            $callProgress = ($callsCount / max(1, $targets['calls'])) * 30;
            $feeProgress = ($feeCollected / max(1, $targets['fee'])) * 40;
            $admProgress = ($admissionsCount / max(1, $targets['admissions'])) * 30;
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
                'last_seen' => $lastSeen,
                'conversion_rate' => round($convRate, 1),
                'score' => round($productivityScore),
                'progress' => [
                    'calls' => min(100, ($callsCount / max(1, $targets['calls'])) * 100),
                    'fee' => min(100, ($feeCollected / max(1, $targets['fee'])) * 100),
                    'admissions' => min(100, ($admissionsCount / max(1, $targets['admissions'])) * 100),
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
            'total_calls' => FollowUp::whereBetween('created_at', [$start, $end])->count(),
            'total_admissions' => Activity::where('subject_type', Admission::class)
                ->where('description', 'like', '%created%')
                ->whereBetween('created_at', [$start, $end])
                ->count(),
            'total_fees' => Payment::whereBetween('payment_date', [$startDate, $endDate])->sum('amount'),
            'online_staff' => collect($activitiesByStaff)->where('is_online', true)->count(),
            'peak_hour' => 'N/A'
        ];

        // --- NEW: Heuristic AI Insights ---
        $insights = [];
        $avgConv = collect($activitiesByStaff)->avg('conversion_rate');
        $topCaller = collect($activitiesByStaff)->sortByDesc('calls_count')->first();
        $topCloser = collect($activitiesByStaff)->sortByDesc('admissions_count')->first();
        
        if ($topCaller && $topCaller['calls_count'] > 0) {
            $insights[] = [
                'type' => 'success', 'icon' => 'fa-phone-alt',
                'text' => "<strong>{$topCaller['user']->name}</strong> is leading in outreach volume with {$topCaller['calls_count']} calls."
            ];
        }
        
        if ($topCloser && $topCloser['admissions_count'] > 0) {
            $insights[] = [
                'type' => 'warning', 'icon' => 'fa-graduation-cap',
                'text' => "Peak Performance! <strong>{$topCloser['user']->name}</strong> secured {$topCloser['admissions_count']} admissions in this period."
            ];
        }

        $lowConv = collect($activitiesByStaff)->where('conversion_rate', '<', $avgConv * 0.5)->where('calls_count', '>', 10)->first();
        if ($lowConv) {
            $insights[] = [
                'type' => 'danger', 'icon' => 'fa-exclamation-triangle',
                'text' => "<strong>{$lowConv['user']->name}</strong> has high volume but low conversion. Strategic review suggested."
            ];
        }
        
        if (count($insights) == 0) {
            $insights[] = ['type' => 'info', 'icon' => 'fa-info-circle', 'text' => "Staff activity is steady across all metrics. No anomalies detected."];
        }

        $timeline = Activity::with('causer')
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('causer_id', $users->pluck('id'))
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        $date = $startDate; // For backward compatibility in some views if any
        return view('admin.staff_activity.index', compact('activitiesByStaff', 'startDate', 'endDate', 'date', 'timeline', 'users', 'trends', 'leaderboard', 'targets', 'summary', 'daysCount', 'insights'));
    }


    public function export(Request $request)
    {
        $date = $request->input('date', Carbon::today()->toDateString());
        // Filter only college-admin
        $users = User::role('college-admin')->where('status', 'active')->get();
        
        $filename = "college_admin_activity_{$date}.csv";
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

        $startDate = $request->input('start_date', Carbon::today()->toDateString());
        $endDate = $request->input('end_date', Carbon::today()->toDateString());
        $event = $request->input('event');
        $subjectType = $request->input('subject_type');
        
        $query = Activity::where('causer_id', $user->id);

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()]);
        } elseif ($startDate) {
            $query->whereDate('created_at', $startDate);
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

        return view('admin.staff_activity.show', compact('user', 'activities', 'startDate', 'endDate', 'availableEvents', 'availableModules'));
    }
}
