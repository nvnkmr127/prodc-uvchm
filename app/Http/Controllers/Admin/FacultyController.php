<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\LeaveType;
use App\Models\LeaveBalance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules; // <-- This is the crucial fix

class FacultyController extends Controller
{
    public function index()
    {
        $faculties = User::role('staff')->orderBy('name')->get();
        return view('admin.faculty.index', compact('faculties'));
    }

    public function create()
    {
        return view('admin.faculty.create');
    }

   public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // This now works correctly because Hash is imported
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole('staff');

        // Automatically create leave balances for the new faculty member
        $leaveTypes = LeaveType::all();
        $currentYear = Carbon::now()->year;

        foreach ($leaveTypes as $type) {
            LeaveBalance::create([
                'user_id' => $user->id,
                'leave_type_id' => $type->id,
                'remaining_days' => $type->days_per_year,
                'year' => $currentYear,
            ]);
        }

        return redirect()->route('admin.faculty.index')->with('success', 'Faculty member created successfully and initial leave balances have been set.');
    }
}
