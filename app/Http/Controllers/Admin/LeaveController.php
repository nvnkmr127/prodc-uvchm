<?php

// app/Http/Controllers/Admin/LeaveController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:manage leaves')->except(['index', 'show']);
        $this->middleware('permission:view leaves')->only(['index', 'show']);
    }

    /**
     * Display a listing of leaves
     */
    public function index()
    {
        $leaves = \App\Models\Leave::with(['user', 'approvedBy'])
            ->latest()
            ->paginate(20);

        return view('admin.leaves.index', compact('leaves'));
    }

    /**
     * Show the form for creating a new leave
     */
    public function create()
    {
        return view('admin.leaves.create');
    }

    /**
     * Store a newly created leave
     */
    public function store(Request $request)
    {
        $request->validate([
            'leave_type' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string',
        ]);

        \App\Models\Leave::create([
            'user_id' => auth()->id(),
            'leave_type' => $request->leave_type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        return redirect()->route('admin.leaves.index')
            ->with('success', 'Leave application submitted successfully.');
    }

    // Add other methods as needed (show, edit, update, destroy)
}
