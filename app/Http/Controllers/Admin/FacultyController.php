<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\LeaveType;
use App\Models\LeaveBalance;
use App\Models\Timetable; // Added missing import
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;

class FacultyController extends Controller
{
    /**
     * Display a listing of faculty members
     */
    public function index()
    {
        try {
            $faculties = User::role('staff')
                           ->with(['subjects', 'leaveBalances']) // Eager load relationships
                           ->orderBy('name')
                           ->get();
                           
            return view('admin.faculty.index', compact('faculties'));
            
        } catch (\Exception $e) {
            Log::error('Error loading faculty index', [
                'error' => $e->getMessage()
            ]);
            
            return view('admin.faculty.index', ['faculties' => collect()]);
        }
    }

    /**
     * Show the form for creating a new faculty member
     */
    public function create()
    {
        return view('admin.faculty.create');
    }

    /**
     * Store a newly created faculty member
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'phone' => ['nullable', 'string', 'max:20'],
            'department' => ['nullable', 'string', 'max:100'],
            'employee_id' => ['nullable', 'string', 'max:50', 'unique:users,employee_id'],
        ]);

        DB::beginTransaction();
        
        try {
            // Create the user - only include fields that exist in database
            $userData = [
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'email_verified_at' => now(), // Auto-verify faculty emails
            ];

            // Add optional fields only if they exist in database schema
            if ($request->phone) {
                $userData['phone'] = $request->phone;
            }
            if ($request->department) {
                $userData['department'] = $request->department;
            }
            if ($request->employee_id) {
                $userData['employee_id'] = $request->employee_id;
            }

            $user = User::create($userData);

            // Assign the staff role
            $user->assignRole('staff');

            // Create leave balances for the new faculty member (only if LeaveType exists)
            if (class_exists(LeaveType::class)) {
                $this->createInitialLeaveBalances($user);
            }
            
            DB::commit();
            
            Log::info('Faculty member created successfully', [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_by' => auth()->id()
            ]);

            return redirect()->route('admin.faculty.index')
                           ->with('success', 'Faculty member created successfully and initial leave balances have been set.');
                           
        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Error creating faculty member', [
                'error' => $e->getMessage(),
                'request_data' => $request->except('password', 'password_confirmation'),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                           ->with('error', 'Failed to create faculty member. Please try again.')
                           ->withInput($request->except('password', 'password_confirmation'));
        }
    }

    /**
     * Display the specified faculty member
     */
    public function show(User $faculty)
    {
        if (!$faculty->hasRole('staff')) {
            abort(404, 'Faculty member not found');
        }

        // Load relationships only if they exist
        $relationships = ['subjects'];
        
        if (method_exists($faculty, 'leaveBalances')) {
            $relationships[] = 'leaveBalances.leaveType';
        }
        
        if (method_exists($faculty, 'leaveApplications')) {
            $relationships['leaveApplications'] = function($query) {
                $query->latest()->limit(5);
            };
        }

        $faculty->load($relationships);

        return view('admin.faculty.show', compact('faculty'));
    }

    /**
     * Show the form for editing the specified faculty member
     */
    public function edit(User $faculty)
    {
        if (!$faculty->hasRole('staff')) {
            abort(404, 'Faculty member not found');
        }

        return view('admin.faculty.edit', compact('faculty'));
    }

    /**
     * Update the specified faculty member
     */
    public function update(Request $request, User $faculty)
    {
        if (!$faculty->hasRole('staff')) {
            abort(404, 'Faculty member not found');
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$faculty->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'department' => ['nullable', 'string', 'max:100'],
            'employee_id' => ['nullable', 'string', 'max:50', 'unique:users,employee_id,'.$faculty->id],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
        ]);

        try {
            $updateData = [
                'name' => $request->name,
                'email' => $request->email,
            ];

            // Add optional fields only if they exist in database schema
            if ($request->has('phone')) {
                $updateData['phone'] = $request->phone;
            }
            if ($request->has('department')) {
                $updateData['department'] = $request->department;
            }
            if ($request->has('employee_id')) {
                $updateData['employee_id'] = $request->employee_id;
            }

            // Only update password if provided
            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $faculty->update($updateData);

            Log::info('Faculty member updated', [
                'user_id' => $faculty->id,
                'updated_by' => auth()->id(),
                'changes' => $faculty->getChanges()
            ]);

            return redirect()->route('admin.faculty.index')
                           ->with('success', 'Faculty member updated successfully.');
                           
        } catch (\Exception $e) {
            Log::error('Error updating faculty member', [
                'user_id' => $faculty->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                           ->with('error', 'Failed to update faculty member.')
                           ->withInput();
        }
    }

    /**
     * Remove the specified faculty member
     */
    public function destroy(User $faculty)
    {
        if (!$faculty->hasRole('staff')) {
            abort(404, 'Faculty member not found');
        }

        try {
            DB::beginTransaction();
            
            // Check if faculty has any active assignments
            $hasActiveAssignments = $faculty->subjects()->exists();
            
            // Check timetable entries only if the relationship exists
            if (method_exists($faculty, 'timetableEntries') || method_exists($faculty, 'timetables')) {
                $timetableMethod = method_exists($faculty, 'timetableEntries') ? 'timetableEntries' : 'timetables';
                $hasActiveAssignments = $hasActiveAssignments || $faculty->{$timetableMethod}()->exists();
            }
            
            if ($hasActiveAssignments) {
                return redirect()->back()
                               ->with('error', 'Cannot delete faculty member with active subject assignments or timetable entries.');
            }

            $facultyName = $faculty->name;
            
            // Remove role before deleting (optional, but cleaner)
            $faculty->removeRole('staff');
            
            $faculty->delete();
            
            DB::commit();
            
            Log::info('Faculty member deleted', [
                'deleted_user_name' => $facultyName,
                'deleted_by' => auth()->id()
            ]);

            return redirect()->route('admin.faculty.index')
                           ->with('success', "Faculty member {$facultyName} deleted successfully.");
                           
        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Error deleting faculty member', [
                'user_id' => $faculty->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                           ->with('error', 'Failed to delete faculty member.');
        }
    }

    /**
     * Create initial leave balances for a new faculty member
     */
    private function createInitialLeaveBalances(User $user)
    {
        try {
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
        } catch (\Exception $e) {
            Log::warning('Failed to create initial leave balances', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            // Don't throw exception - faculty creation should still succeed
        }
    }
}