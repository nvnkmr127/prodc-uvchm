<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Holiday;
use App\Models\Event;
use App\Models\Enquiry;
use App\Models\Timetable;

class UnifiedCalendarController extends Controller
{
    public function index()
    {
        $events = [];
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            // Admins see everything: Holidays, Events, and Follow-ups
            $holidays = Holiday::all();
            foreach($holidays as $holiday) {
                $events[] = [
                    'title' => $holiday->name,
                    'start' => $holiday->date,
                    'allDay' => true,
                    'backgroundColor' => '#e74a3b', // Red for holidays
                    'borderColor' => '#e74a3b'
                ];
            }

            $specialEvents = Event::all();
            foreach($specialEvents as $event) {
                $events[] = [
                    'title' => $event->name,
                    'start' => $event->event_date . 'T' . $event->start_time,
                    'end' => $event->event_date . 'T' . $event->end_time,
                    'backgroundColor' => '#f6c23e', // Yellow for events
                    'borderColor' => '#f6c23e'
                ];
            }

            $enquiries = Enquiry::whereNotNull('next_follow_up_date')->get();
            foreach($enquiries as $enquiry) {
                $events[] = [
                    'title' => 'Follow-up: ' . $enquiry->student_name,
                    'start' => $enquiry->next_follow_up_date,
                    'allDay' => true,
                    'backgroundColor' => '#1cc88a', // Green for follow-ups
                    'borderColor' => '#1cc88a',
                    'url' => route('admin.enquiries.edit', $enquiry) // Make it clickable
                ];
            }

        } elseif ($user->hasRole('staff') || $user->hasRole('student')) {
            // Faculty and Students see their personal timetables
            $query = Timetable::query();

            if ($user->hasRole('staff')) {
                $query->where('user_id', $user->id);
            } else {
                // For student, we need to get their batch's course ID
                $studentProfile = $user->studentProfile; // Assuming a 'studentProfile' relationship on User model
                if ($studentProfile && $studentProfile->batch_id) {
                     $query->where('batch_id', $studentProfile->batch_id);
                }
            }

            $timetableEntries = $query->with(['subject', 'classroom'])->get();
            foreach($timetableEntries as $entry) {
                $events[] = [
                    'title' => $entry->subject->name,
                    'start' => $entry->schedule_date . 'T' . $entry->timeSlot->start_time,
                    'end' => $entry->schedule_date . 'T' . $entry->timeSlot->end_time,
                    'description' => 'In ' . $entry->classroom->name,
                    'backgroundColor' => '#4e73df', // Blue for classes
                    'borderColor' => '#4e73df'
                ];
            }
        }

        return view('calendar.index', ['events' => json_encode($events)]);
    }
}