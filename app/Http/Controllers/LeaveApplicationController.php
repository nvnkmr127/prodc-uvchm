<?php

namespace App\Http\Controllers;

use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveApplicationController extends Controller
{
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
        $request->validate(['leave_type_id' => 'required|exists:leave_types,id', 'start_date' => 'required|date', 'end_date' => 'required|date|after_or_equal:start_date', 'reason' => 'required|string']);
        array_merge(LeaveApplication::create(['user_id' => Auth::id()], $validated));

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

        return redirect()->route('admin.leave-applications.index')->with('success', 'Application status updated.');
    }
}
