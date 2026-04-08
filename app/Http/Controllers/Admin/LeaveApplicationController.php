<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Services\NotificationService; // ADD THIS IMPORT
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveApplicationController extends Controller
{
    protected $notificationService; // ADD THIS PROPERTY

    // ADD CONSTRUCTOR FOR DEPENDENCY INJECTION
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    // For Faculty: Show their own leave dashboard
    public function facultyIndex()
    {
        $user = Auth::user();
        $leaveBalances = LeaveBalance::where('user_id', $user->id)->where('year', date('Y'))->with('leaveType')->get();
        $leaveTypes = LeaveType::all();
        $applications = LeaveApplication::where('user_id', $user->id)->with('leaveType')->latest()->get();

        return view('faculty.my_leave', compact('leaveBalances', 'leaveTypes', 'applications'));
    }

    // For Faculty: Store their new leave application
    public function store(Request $request)
    {
        $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string',
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $totalDays = $startDate->diffInDays($endDate) + 1;
        $leaveType = LeaveType::find($request->leave_type_id);

        // Check if user has sufficient leave balance
        $balance = LeaveBalance::where('user_id', Auth::id())
            ->where('leave_type_id', $request->leave_type_id)
            ->where('year', $startDate->year)
            ->first();

        if ($balance && $balance->remaining_days < $totalDays) {
            return redirect()->back()->with('error', 'Insufficient leave balance. Available: '.$balance->remaining_days.' days');
        }

        $application = LeaveApplication::create([
            'user_id' => Auth::id(),
            'leave_type_id' => $request->leave_type_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'reason' => $request->reason,
        ]);

        // 🔔 NOTIFY ADMINS OF NEW LEAVE APPLICATION
        $this->notificationService->send([
            'title' => 'New Leave Application',
            'message' => auth()->user()->name." has applied for {$leaveType->name} from {$startDate->format('M d')} to {$endDate->format('M d')} ({$totalDays} days)",
            'type' => 'info',
            'category' => 'academic',
            'priority' => $this->determineLeaveUrgency($startDate, $totalDays),
            'roles' => ['super-admin', 'college-admin'],
            'action_url' => route('admin.leave-applications.index'),
            'action_text' => 'Review Application',
            'data' => [
                'application_id' => $application->id,
                'applicant_id' => auth()->id(),
                'applicant_name' => auth()->user()->name,
                'leave_type' => $leaveType->name,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'total_days' => $totalDays,
                'reason' => $request->reason,
                'balance_after' => $balance ? $balance->remaining_days - $totalDays : 'N/A',
            ],
        ]);

        // 🔔 URGENT NOTIFICATION for short-notice leaves (less than 3 days notice)
        if ($startDate->diffInDays(now()) < 3) {
            $this->notificationService->send([
                'title' => 'URGENT: Short Notice Leave Application',
                'message' => auth()->user()->name." applied for leave with only {$startDate->diffInDays(now())} days notice",
                'type' => 'warning',
                'category' => 'academic',
                'priority' => 'high',
                'roles' => ['super-admin', 'college-admin'],
                'requires_action' => true,
                'action_url' => route('admin.leave-applications.index'),
                'action_text' => 'Review Immediately',
                'data' => [
                    'application_id' => $application->id,
                    'notice_days' => $startDate->diffInDays(now()),
                    'is_emergency' => true,
                ],
            ]);
        }

        return redirect()->route('faculty.my-leave.index')->with('success', 'Leave application submitted successfully.');
    }

    // For Admin: Show all applications
    public function adminIndex()
    {
        $applications = LeaveApplication::with(['user', 'leaveType', 'approver'])->latest()->get();

        return view('admin.leave_applications.index', compact('applications'));
    }

    // For Admin: Approve or Reject an application
    public function updateStatus(Request $request, LeaveApplication $application)
    {
        $request->validate(['status' => 'required|in:Approved,Rejected']);
        $oldStatus = $application->status;

        // When approving, deduct from leave balance
        if ($request->status == 'Approved') {
            $startDate = Carbon::parse($application->start_date);
            $endDate = Carbon::parse($application->end_date);
            $days = $startDate->diffInDays($endDate) + 1;

            $balance = LeaveBalance::where('user_id', $application->user_id)
                ->where('leave_type_id', $application->leave_type_id)
                ->where('year', $startDate->year)
                ->first();

            if ($balance && $balance->remaining_days >= $days) {
                $balance->remaining_days -= $days;
                $balance->save();
            } else {
                return redirect()->back()->with('error', 'Not enough leave balance to approve this application.');
            }
        }

        $application->status = $request->status;
        $application->approved_by = Auth::id();
        $application->save();

        // 🔔 NOTIFY APPLICANT OF STATUS CHANGE
        if ($oldStatus !== $request->status) {
            $this->notificationService->send([
                'title' => 'Leave Application '.$request->status,
                'message' => "Your leave application from {$application->start_date} to {$application->end_date} has been {$request->status}",
                'type' => $request->status === 'Approved' ? 'success' : 'warning',
                'category' => 'academic',
                'priority' => 'normal',
                'users' => [$application->user_id],
                'action_url' => route('faculty.my-leave.index'),
                'action_text' => 'View Applications',
                'data' => [
                    'application_id' => $application->id,
                    'leave_type' => $application->leaveType->name,
                    'start_date' => $application->start_date,
                    'end_date' => $application->end_date,
                    'total_days' => Carbon::parse($application->start_date)->diffInDays($application->end_date) + 1,
                    'approved_by' => auth()->user()->name,
                    'status' => $request->status,
                ],
            ]);

            // 🔔 NOTIFY OTHER ADMINS OF THE DECISION
            $this->notificationService->send([
                'title' => 'Leave Application '.$request->status,
                'message' => auth()->user()->name." {$request->status} {$application->user->name}'s leave application",
                'type' => 'info',
                'category' => 'academic',
                'priority' => 'low',
                'roles' => ['super-admin', 'college-admin'],
                'data' => [
                    'application_id' => $application->id,
                    'applicant_name' => $application->user->name,
                    'leave_type' => $application->leaveType->name,
                    'decision_by' => auth()->user()->name,
                    'status' => $request->status,
                ],
            ]);
        }

        return redirect()->route('admin.leave-applications.index')->with('success', 'Application status updated.');
    }

    /**
     * Determine leave urgency based on timing
     */
    private function determineLeaveUrgency($startDate, $totalDays)
    {
        $noticeInDays = $startDate->diffInDays(now());

        if ($noticeInDays < 1) {
            return 'urgent';
        }
        if ($noticeInDays < 3) {
            return 'high';
        }
        if ($totalDays > 7) {
            return 'high';
        }

        return 'normal';
    }
}
