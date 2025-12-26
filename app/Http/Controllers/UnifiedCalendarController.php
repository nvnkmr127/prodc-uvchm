<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Holiday;
use App\Models\Event;
use App\Models\Enquiry;
use App\Models\Timetable;
use Carbon\Carbon;

class UnifiedCalendarController extends Controller
{
    public function index()
    {
        $events = [];
        $enquiries = collect(); 
        $user = Auth::user();

        if ($user->hasAnyRole(['admin', 'super-admin', 'college-admin'])) {
            
            // --- 1. Holidays ---
            $holidays = Holiday::all();
            foreach($holidays as $holiday) {
                $events[] = [
                    'id' => 'holiday-' . $holiday->id,
                    'title' => 'Holiday: ' . $holiday->name,
                    'start' => $holiday->date,
                    'allDay' => true,
                    'backgroundColor' => '#e74a3b', // Red
                    'borderColor' => '#e74a3b',
                    'editable' => false // Holidays cannot be dragged
                ];
            }

            // --- 2. Enquiries (Follow-ups) ---
            $enquiries = Enquiry::whereNotNull('next_follow_up_date')
                ->where('status', '!=', 'Not Interested') 
                ->whereDate('next_follow_up_date', '>=', now()->subDays(30)) 
               ->whereDate('next_follow_up_date', '<=', now()->addDays(90))
                ->orderBy('next_follow_up_date', 'asc')
                ->get();

            // Define status colors map
            $statusColors = [
                'New' => '#4e73df',           // Blue
                'Contacted' => '#36b9cc',     // Cyan
                'Interested' => '#f6c23e',    // Yellow
                'Follow-up' => '#fd7e14',     // Orange
                'Admitted' => '#1cc88a',      // Green
                'Not Interested' => '#858796' // Grey
            ];

            foreach($enquiries as $enquiry) {
                $start = $enquiry->next_follow_up_date instanceof Carbon 
                    ? $enquiry->next_follow_up_date->format('Y-m-d') 
                    : $enquiry->next_follow_up_date;

                // Determine color based on status, fallback to blue if unknown
                $color = $statusColors[$enquiry->status] ?? '#4e73df';
                
                // Highlight overdue items in Red regardless of status if strictly needed, 
                // otherwise use status color. Let's stick to status color for better info.

                $events[] = [
                    'id' => $enquiry->id, // Important for drag-and-drop
                    'title' => $enquiry->student_name,
                    'start' => $start,
                    'allDay' => true,
                    'backgroundColor' => $color,
                    'borderColor' => $color,
                   
                    'extendedProps' => [
                        'phone' => $enquiry->phone_number,
                        'status' => $enquiry->status,
                        'type' => 'enquiry' // Marker to identify type in JS
                    ]
                ];
            }

        } elseif ($user->hasRole('staff') || $user->hasRole('student')) {
             // ... (Keep existing staff/student logic as is) ...
             // Note: Timetable entries should probably have 'editable' => false
        }

        return view('calendar.index', [
            'events' => $events, 
            'enquiries' => $enquiries
        ]);
    }

    /**
     * Handle Drag-and-Drop Event Updates
     */
    public function updateDate(Request $request)
    {
        // Validate input
        $request->validate([
            'id' => 'required|integer',
            'start' => 'required|date',
        ]);

        $enquiry = Enquiry::find($request->id);

        if (!$enquiry) {
            return response()->json(['success' => false, 'message' => 'Enquiry not found'], 404);
        }

        // Update the date
        // FullCalendar sends dates in YYYY-MM-DD format for allDay events
        $enquiry->next_follow_up_date = $request->start;
        $enquiry->save();

        // Optional: Log activity
        // activity()->performOn($enquiry)->log('Follow-up rescheduled via Calendar');

        return response()->json([
            'success' => true, 
            'message' => 'Follow-up rescheduled to ' . $request->start
        ]);
    }
}