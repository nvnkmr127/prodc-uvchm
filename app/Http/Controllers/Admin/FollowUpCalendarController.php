<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FollowUpCalendarController extends Controller
{
    public function index(Request $request)
    {
        // 1. Handle AJAX Request from FullCalendar (JSON Response)
        if ($request->ajax()) {
            $startDate = $request->filled('start') ? Carbon::parse($request->input('start'))->startOfDay() : now()->subMonths(1)->startOfDay();
            $endDate = $request->filled('end') ? Carbon::parse($request->input('end'))->endOfDay() : now()->addMonths(2)->endOfDay();

            // Fetch Data
            $query = Enquiry::whereNotNull('next_follow_up_date')
                ->where('status', '!=', 'Admitted')
                ->whereBetween('next_follow_up_date', [$startDate, $endDate])
                ->select(['id', 'student_name', 'phone_number', 'status', 'next_follow_up_date']);

            // Role Check
            $user = Auth::user();
            if ($user->hasRole('Counselor')) {
                $query->where('assigned_to_user_id', $user->id);
            }

            $enquiries = $query->orderBy('next_follow_up_date')->get();

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
                if ($isOverdue && ! $isToday) {
                    $color = '#e74a3b'; // Red
                } elseif ($isToday) {
                    $color = '#f6c23e'; // Orange
                } else {
                    $color = '#4e73df'; // Blue
                }

                return [
                    'id' => $enquiry->id,
                    'title' => $enquiry->student_name.' ('.$enquiry->phone_number.')',
                    'start' => $start,
                    'url' => route('admin.enquiries.edit', $enquiry->id),
                    'backgroundColor' => $color,
                    'borderColor' => $color,
                    'textColor' => '#fff',
                    'allDay' => true,
                    'extendedProps' => [
                        'phone' => $enquiry->phone_number,
                        'status' => $enquiry->status,
                    ],
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

        $enquiries = $query->orderBy('next_follow_up_date', 'asc')
            ->select(['id', 'student_name', 'phone_number', 'status', 'next_follow_up_date'])
            ->limit(500)
            ->get();

        // Logic corrected: If admin view exists, return ADMIN view.
        if (view()->exists('admin.calendar.index')) {
            return view('admin.calendar.index', compact('enquiries'));
        }

        // Fallback
        return view('calendar.index', compact('enquiries'));
    }
}
