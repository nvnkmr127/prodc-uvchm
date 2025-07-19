<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeaveTypeController extends Controller
{
    public function index()
    {
        $leaveTypes = LeaveType::latest()->get();
        return view('admin.leave_types.index', compact('leaveTypes'));
    }

    public function create()
    {
        return view('admin.leave_types.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:leave_types,name',
            'days_per_year' => 'required|integer|min:0',
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:leave_types,name',
            'days_per_year' => 'required|integer|min:0|max:365',
        ]);
        LeaveType::create($validated);
        return redirect()->route('admin.leave-types.index')->with('success', 'Leave type created successfully.');
    }

    public function edit(LeaveType $leaveType)
    {
        return view('admin.leave_types.edit', compact('leaveType'));
    }

    public function update(Request $request, LeaveType $leaveType)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('leave_types')->ignore($leaveType->id)],
            'days_per_year' => 'required|integer|min:0',
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:leave_types,name',
            'days_per_year' => 'required|integer|min:0|max:365',
        ]);
        $leaveType->update($validated);
        return redirect()->route('admin.leave-types.index')->with('success', 'Leave type updated successfully.');
    }

    public function destroy(LeaveType $leaveType)
    {
        $leaveType->delete();
        return redirect()->route('admin.leave-types.index')->with('success', 'Leave type deleted successfully.');
    }
}