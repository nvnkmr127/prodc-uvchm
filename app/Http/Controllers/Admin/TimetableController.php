<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\Batch;
use App\Models\User;
use App\Models\Classroom;
use App\Models\Timetable;
use App\Models\TimeSlot;
use App\Models\Holiday;
use App\Models\Event;
use App\Models\CourseTerm;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use PDF;

class TimetableController extends Controller
{
    /**
     * Display the main timetable hub page with filters.
     */
  public function index()
    {
        $courses = Course::orderBy('name')->get();
        $faculties = User::role('staff')->orderBy('name')->get();
        $classrooms = Classroom::orderBy('name')->get();

        return view('admin.timetable.hub', compact('courses', 'faculties', 'classrooms'));
    }

    /**
     * Provides the event data in JSON format for the FullCalendar.
     */
    public function getEvents(Request $request)
    {
        $query = Timetable::with(['batch.course', 'subject', 'user', 'classroom', 'timeSlot']);

        // Apply filters from the calendar page if they are present
        if ($request->filled('course_id')) {
            $batchIds = Batch::where('course_id', $request->course_id)->pluck('id');
            $query->whereIn('batch_id', $batchIds);
        }
        if ($request->filled('faculty_id')) {
            $query->where('user_id', $request->faculty_id);
        }
        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', $request->classroom_id);
        }

        $entries = $query->get();

        // Format the data into the structure FullCalendar needs
        $calendarEvents = $entries->map(function ($entry) {
            return [
                'id'          => $entry->id,
                'title'       => $entry->batch->name . ' - ' . $entry->subject->name,
                'start'       => $entry->schedule_date . 'T' . $entry->timeSlot->start_time,
                'end'         => $entry->schedule_date . 'T' . $entry->timeSlot->end_time,
                'description' => 'Faculty: ' . ($entry->user->name ?? 'N/A') . ' | Room: ' . ($entry->classroom->name ?? 'N/A'),
                'color'       => $this->stringToColor($entry->batch->name), // Assign a unique color to each batch
            ];
        });

        return response()->json($calendarEvents);
    }
    
    
  /**
     * The main "smart" generator algorithm.
     */
  public function generate(Request $request)
    {
        $request->validate([
            'course_id'      => 'required|exists:courses,id',
            'course_term_id' => 'required|exists:course_terms,id',
            'start_date'     => 'required|date',
            'end_date'       => 'required|date|after_or_equal:start_date',
        ]);
        
        $course = Course::with('batches.subjects.faculty')->findOrFail($request->course_id);
        $term = CourseTerm::find($request->course_term_id);

        if ($course->batches->isEmpty()) {
            return redirect()->route('admin.timetable.hub')->with('error', 'This course has no batches. Please create a batch first.');
        }

        $timeSlots = TimeSlot::all();
        $classrooms = Classroom::all();
        $holidays = Holiday::whereBetween('date', [$request->start_date, $request->end_date])->pluck('date')->map(fn($date) => Carbon::parse($date)->format('Y-m-d'));
        $events = Event::whereBetween('event_date', [$request->start_date, $request->end_date])->get();
        
        $batchIds = $course->batches->pluck('id');
        Timetable::whereIn('batch_id', $batchIds)->whereBetween('schedule_date', [$request->start_date, $request->end_date])->delete();

        $period = CarbonPeriod::create($request->start_date, $request->end_date);

        foreach ($period as $date) {
            if ($date->isSunday() || $holidays->contains($date->format('Y-m-d'))) continue;

            foreach ($timeSlots as $slot) {
                $eventAtThisTime = $events->first(fn($e) => Carbon::parse($e->event_date)->isSameDay($date) && $slot->start_time >= $e->start_time && $slot->start_time < $e->end_time);
                if ($eventAtThisTime) continue;

                foreach ($course->batches as $batch) {
                    $subjectsToSchedule = $course->subjects->shuffle();

                    foreach ($subjectsToSchedule as $subject) {
                        $isClassScheduledForBatch = Timetable::where('batch_id', $batch->id)->where('schedule_date', $date)->where('time_slot_id', $slot->id)->exists();
                        if ($isClassScheduledForBatch) continue 2; 

                        $availableClassrooms = $subject->requires_lab ? $classrooms->where('type', 'lab') : $classrooms->where('type', 'lecture');
                        $availableFaculty = $subject->faculty;

                        $classroom = $availableClassrooms->first(function($c) use ($date, $slot) {
                            return !Timetable::where('classroom_id', $c->id)->where('schedule_date', $date)->where('time_slot_id', $slot->id)->exists();
                        });
                        $faculty = $availableFaculty->first(function($f) use ($date, $slot) {
                            return !Timetable::where('user_id', $f->id)->where('schedule_date', $date)->where('time_slot_id', $slot->id)->exists();
                        });
                        
                        if ($classroom && $faculty) {
                            Timetable::create([
                                'schedule_date' => $date, 
                                'batch_id'      => $batch->id, 
                                'subject_id'    => $subject->id,
                                'user_id'       => $faculty->id, 
                                'classroom_id'  => $classroom->id, 
                                'time_slot_id'  => $slot->id,
                            ]);
                            break;
                        }
                    }
                }
            }
        }
        return redirect()->route('admin.timetable.hub')->with('success', 'Timetable generated successfully for ' . $course->name . ' - ' . $term->name);
    }
    
 public function downloadPDF(Request $request)
    {
        // 1. Get the filters from the request
        $courseId = $request->input('course_id');
        $facultyId = $request->input('faculty_id');
        $classroomId = $request->input('classroom_id');
        $termId = $request->input('course_term_id'); // Get the term ID
        
        // Use the date range from the request, or default to the current week
        $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date) : Carbon::now()->startOfWeek();
        $endDate = $request->filled('end_date') ? Carbon::parse($request->end_date) : Carbon::now()->endOfWeek();

        // 2. Fetch the filtered data
        $query = Timetable::with(['batch.course', 'subject', 'user', 'classroom', 'timeSlot']);

        if ($courseId) {
            $batchIds = Batch::where('course_id', $courseId)->pluck('id');
            $query->whereIn('batch_id', $batchIds);
        }
        if ($facultyId) {
            $query->where('user_id', $facultyId);
        }
        if ($classroomId) {
            $query->where('classroom_id', $classroomId);
        }
        
        // Filter by the date range
        $query->whereBetween('schedule_date', [$startDate, $endDate]);

        $entries = $query->get();

        // 3. Group data for the table view
        $timetable = $entries->groupBy(function($entry) {
            return Carbon::parse($entry->schedule_date)->format('l'); // Group by Weekday Name
        })->map(function($dayEntries) {
            return $dayEntries->keyBy('time_slot_id');
        });
        
        // 4. Get all necessary data for the PDF header
        $timeSlots = TimeSlot::orderBy('start_time')->get();
        $weekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $course = $courseId ? Course::find($courseId) : null;
        $term = $termId ? CourseTerm::find($termId) : null;

        // 5. Load the special PDF view with all the data
        $pdf = PDF::loadView('admin.timetable.pdf', [
            'timetable' => $timetable,
            'timeSlots' => $timeSlots,
            'weekdays' => $weekdays,
            'course' => $course,
            'term' => $term,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('timetable-' . ($course->name ?? 'report') . '.pdf');
    }

    /**
     * Handles manual drag-and-drop updates from the calendar.
     */
    public function manualUpdate(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:timetables,id',
            'new_date' => 'required|date',
            'new_time_slot_id' => 'required|exists:time_slots,id',
        ]);
        
        $timetableEntry = Timetable::findOrFail($request->id);
        
        // Advanced conflict checking logic would go here before updating
        
        $timetableEntry->update([
            'schedule_date' => $request->new_date,
            'time_slot_id' => $request->new_time_slot_id,
        ]);

        return response()->json(['success' => true, 'message' => 'Schedule updated successfully.']);
    }
    
    /**
     * A helper function to generate a consistent color for each batch name.
     */
    private function stringToColor($str) {
        $hash = 0;
        for ($i = 0; $i < strlen($str); $i++) {
            $hash = ord($str[$i]) + (($hash << 5) - $hash);
        }
        $color = '#';
        for ($i = 0; $i < 3; $i++) {
            $value = ($hash >> ($i * 8)) & 0xFF;
            $color .= ('00' . dechex($value))[-2];
        }
        return $color;
    }
}
