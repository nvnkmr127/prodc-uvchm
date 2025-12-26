<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class FollowUpCalendarController extends Controller
{
    public function index(Request $request)
    {
        // 1. Handle AJAX Request from FullCalendar (JSON Response)
        if ($request->ajax()) {
            
            // Fetch Data
            $query = Enquiry::whereNotNull('next_follow_up_date')
                            ->where('status', '!=', 'Admitted');

            // Role Check
            $user = Auth::user();
            if ($user->hasRole('Counselor')) {
                $query->where('assigned_to_user_id', $user->id);
            }

            $enquiries = $query->get();

            // Format Events
            $events = $enquiries->map(function ($enquiry) {
                $date = $enquiry->next_follow_up_date;
                
                // Safe Date Parsing
                if ($date instanceof Carbon) {
                    $cDate = $date;
                } else {
                    $cDate = Carbon::parse($date);
                }
                
                $start = $cDate->format('Y-m-d');
                $isOverdue = $cDate->endOfDay()->isPast();
                $isToday = $cDate->isToday();

                // Color Logic
                if ($isOverdue && !$isToday) {
                    $color = '#e74a3b'; // Red
                } elseif ($isToday) {
                    $color = '#f6c23e'; // Orange
                } else {
                    $color = '#4e73df'; // Blue
                }

                return [
                    'id' => $enquiry->id,
                    'title' => $enquiry->student_name . ' (' . $enquiry->phone_number . ')',
                    'start' => $start,
                    'url' => route('admin.enquiries.edit', $enquiry->id),
                    'backgroundColor' => $color,
                    'borderColor' => $color,
                    'textColor' => '#fff',
                    'allDay' => true,
                    'extendedProps' => [
                        'phone' => $enquiry->phone_number,
                        'status' => $enquiry->status
                    ]
                ];
            });

            return response()->json($events);
        }

        // 2. Handle Initial Page Load (View Response)
        
        // Re-fetch enquiries if you need a list in the sidebar
        $query = Enquiry::whereNotNull('next_follow_up_date')
                        ->where('status', '!=', 'Admitted');
                        
        $user = Auth::user();
        if ($user->hasRole('Counselor')) {
            $query->where('assigned_to_user_id', $user->id);
        }
        
        $enquiries = $query->orderBy('next_follow_up_date', 'asc')->get();

        // Logic corrected: If admin view exists, return ADMIN view.
        if (view()->exists('admin.calendar.index')) {
             return view('admin.calendar.index', compact('enquiries'));
        }
        
        // Fallback
        return view('calendar.index', compact('enquiries'));
    }
}