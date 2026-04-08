<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index()
    {
        $events = Event::with(['classroom', 'user'])->orderBy('event_date')->orderBy('start_time')->get();

        return view('admin.events.index', compact('events'));
    }

    public function create()
    {
        $classrooms = Classroom::orderBy('name')->get();
        $faculties = User::role('staff')->orderBy('name')->get();

        return view('admin.events.create', compact('classrooms', 'faculties'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'event_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'course_id' => 'required|exists:courses,id',
            'subject_id' => 'required|exists:subjects,id',
            'user_id' => 'required|exists:users,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'event_date' => 'required|date',
            'start_time' => 'nullable|string|max:255',
            'end_time' => 'nullable|string|max:255',
        ]);
        Event::create($validated);

        return redirect()->route('admin.events.index')->with('success', 'Event scheduled successfully.');
    }

    public function destroy(Event $event)
    {
        $event->delete();

        return redirect()->route('admin.events.index')->with('success', 'Event deleted successfully.');
    }
}
