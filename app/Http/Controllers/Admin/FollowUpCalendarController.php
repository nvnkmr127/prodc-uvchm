<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FollowUpCalendarController extends Controller
{
    /**
     * Display the follow-up calendar view and provide event data.
     */
    public function index(Request $request)
    {
        // Check if the request is for JSON data (from the calendar's AJAX call)
        if ($request->ajax()) {
            
            $query = Enquiry::whereNotNull('next_follow_up_date');

            // If the user is a counselor, only show their own assigned enquiries.
            // Admins will see all scheduled follow-ups.
            $user = Auth::user();
            if ($user->hasRole('Counselor')) {
                $query->where('assigned_to_user_id', $user->id);
            }

            $enquiries = $query->get();

            // Format the data into the structure required by FullCalendar.js
            $events = $enquiries->map(function ($enquiry) {
                return [
                    'title' => $enquiry->student_name . ' (Follow-up)',
                    'start' => $enquiry->next_follow_up_date,
                    'url'   => route('admin.enquiries.edit', $enquiry),
                    'backgroundColor' => '#ffc107', // A nice yellow color for events
                    'borderColor' => '#ffc107',
                ];
            });

            return response()->json($events);
        }

        // For initial page loads, just return the view.
        return view('admin.calendar.index');
    }
}
