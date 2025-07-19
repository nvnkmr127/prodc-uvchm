<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Timetable;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     * This method checks the user's role and redirects them to the appropriate dashboard.
     */
    public function index()
    {
        $user = Auth::user();

        if ($user->hasRole(['super-admin', 'college-admin', 'accountant'])) {
            // All admin-level roles go to the main admin dashboard view
            return redirect()->route('admin.dashboard');
        } 
        
        if ($user->hasRole('staff')) {
            // Faculty see their specific dashboard showing today's classes
            $myClassesToday = Timetable::where('user_id', $user->id)
                                    ->where('schedule_date', now()->format('Y-m-d'))
                                    ->with(['subject', 'batch.course', 'classroom', 'timeSlot'])
                                    ->orderBy('time_slot_id')
                                    ->get();
            return view('faculty.dashboard', compact('myClassesToday'));
        }
        
        if ($user->hasRole('student')) {
            // This is where a future student dashboard would be loaded
            return view('student.dashboard');
        }

        // As a fallback, if a user has no assigned role with a dashboard
        Auth::logout();
        return redirect('/login')->with('error', 'Your user role does not have an assigned dashboard. Please contact an administrator.');
    }
}
