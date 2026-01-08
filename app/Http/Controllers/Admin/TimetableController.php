<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Timetable;
use App\Models\Batch;
use App\Models\Subject;
use App\Models\User;
use App\Models\Classroom;
use App\Models\TimeSlot;
use App\Models\AcademicYear;
use App\Models\PracticalGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class TimetableController extends Controller
{
    /**
     * Display a listing of the timetable entries
     */
    public function index(Request $request)
    {
        try {
            $query = Timetable::with(['batch', 'subject', 'user', 'classroom', 'timeSlot', 'practicalGroup']);

            // Apply filters if provided
            if ($request->filled('batch_id')) {
                $query->where('batch_id', $request->batch_id);
            }

            if ($request->filled('date_from')) {
                $query->where('schedule_date', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->where('schedule_date', '<=', $request->date_to);
            }

            $timetables = $query->orderBy('schedule_date')->orderBy('time_slot_id')->paginate(50);

            // Get filter options
            $batches = Batch::where('is_active', true)->orderBy('name')->get();

            return view('admin.timetable.index', compact('timetables', 'batches'));

        } catch (\Exception $e) {
            Log::error('Error loading timetable index', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Unable to load timetable data.');
        }
    }

    public function create()
    {
        try {
            // Fetch necessary data for the create form
            $batches = Batch::where('is_active', true)->orderBy('name')->get();
            $subjects = Subject::where('is_active', true)->orderBy('name')->get();
            $faculty = User::role('staff')->orderBy('name')->get();
            $classrooms = Classroom::where('is_active', true)->orderBy('name')->get();
            $timeSlots = TimeSlot::orderBy('start_time')->get();
            $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();

            return view('admin.timetable.create', compact(
                'batches',
                'subjects',
                'faculty',
                'classrooms',
                'timeSlots',
                'academicYears'
            ));

        } catch (\Exception $e) {
            Log::error('Error loading timetable create form', ['error' => $e->getMessage()]);
            return redirect()->route('admin.timetable.index')
                ->with('error', 'Unable to load timetable creation form.');
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'batch_id' => 'required|exists:batches,id',
            'subject_id' => 'required|exists:subjects,id',
            'user_id' => 'required|exists:users,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'time_slot_id' => 'required|exists:time_slots,id',
            'schedule_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            // Create timetable entry
            $timetable = Timetable::create([
                'batch_id' => $request->batch_id,
                'subject_id' => $request->subject_id,
                'user_id' => $request->user_id,
                'classroom_id' => $request->classroom_id,
                'time_slot_id' => $request->time_slot_id,
                'schedule_date' => $request->schedule_date,
                'status' => 'scheduled', // Default status
            ]);

            // Log the creation
            Log::info('Timetable entry created', [
                'timetable_id' => $timetable->id,
                'created_by' => auth()->id()
            ]);

            DB::commit();

            return redirect()->route('timetable.index')
                ->with('success', 'Timetable entry created successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Timetable creation failed', ['error' => $e->getMessage()]);

            return redirect()->back()
                ->with('error', 'Failed to create timetable entry: ' . $e->getMessage())
                ->withInput();
        }
    }


    public function today(Request $request)
    {
        try {
            $today = Carbon::today();

            $timetables = Timetable::with(['batch', 'subject', 'user', 'classroom', 'timeSlot'])
                ->whereDate('schedule_date', $today)
                ->orderBy('time_slot_id')
                ->get();

            return view('admin.timetable.today', compact('timetables', 'today'));

        } catch (\Exception $e) {
            Log::error('Error loading today\'s timetable', ['error' => $e->getMessage()]);
            return redirect()->route('admin.dashboard')->with('error', 'Unable to load today\'s timetable.');
        }
    }

    /**
     * Display the specified timetable entry
     */
    public function show(Timetable $timetable)
    {
        try {
            $timetable->load(['batch', 'subject', 'user', 'classroom', 'timeSlot', 'practicalGroup', 'academicYear']);

            return view('admin.timetable.show', compact('timetable'));

        } catch (\Exception $e) {
            Log::error('Error loading timetable details', ['error' => $e->getMessage()]);
            return redirect()->route('admin.timetable.index')->with('error', 'Unable to load timetable details.');
        }
    }

    /**
     * Show the form for editing the specified timetable entry
     */
    public function edit(Timetable $timetable)
    {
        try {
            $batches = Batch::where('is_active', true)->orderBy('name')->get();
            $subjects = Subject::where('is_active', true)->orderBy('name')->get();
            $faculty = User::role('staff')->orderBy('name')->get();
            $classrooms = Classroom::where('is_active', true)->orderBy('name')->get();
            $timeSlots = TimeSlot::orderBy('start_time')->get();
            $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();
            $practicalGroups = PracticalGroup::where('is_active', true)->orderBy('name')->get();

            return view('admin.timetable.edit', compact(
                'timetable',
                'batches',
                'subjects',
                'faculty',
                'classrooms',
                'timeSlots',
                'academicYears',
                'practicalGroups'
            ));

        } catch (\Exception $e) {
            Log::error('Error loading timetable edit form', ['error' => $e->getMessage()]);
            return redirect()->route('admin.timetable.index')->with('error', 'Unable to load edit form.');
        }
    }

    /**
     * Update the specified timetable entry
     */
    public function update(Request $request, Timetable $timetable)
    {
        $validator = Validator::make($request->all(), [
            'batch_id' => 'required|exists:batches,id',
            'subject_id' => 'required|exists:subjects,id',
            'user_id' => 'required|exists:users,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'time_slot_id' => 'required|exists:time_slots,id',
            'schedule_date' => 'required|date',
            'academic_year_id' => 'required|exists:academic_years,id',
            'practical_group_id' => 'nullable|exists:practical_groups,id',
            'is_lab_session' => 'boolean',
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Check for conflicts (excluding current timetable entry)
            $conflicts = $this->checkSchedulingConflicts($request->all(), $timetable->id);
            if (!empty($conflicts)) {
                return redirect()->back()
                    ->with('error', 'Scheduling conflicts detected: ' . implode(', ', $conflicts))
                    ->withInput();
            }

            DB::beginTransaction();

            $timetable->update([
                'batch_id' => $request->batch_id,
                'subject_id' => $request->subject_id,
                'user_id' => $request->user_id,
                'classroom_id' => $request->classroom_id,
                'time_slot_id' => $request->time_slot_id,
                'schedule_date' => $request->schedule_date,
                'academic_year_id' => $request->academic_year_id,
                'practical_group_id' => $request->practical_group_id,
                'is_lab_session' => $request->boolean('is_lab_session'),
                'notes' => $request->notes,
            ]);

            DB::commit();

            Log::info('Timetable entry updated', [
                'id' => $timetable->id,
                'updated_by' => auth()->id()
            ]);

            return redirect()->route('admin.timetable.index')
                ->with('success', 'Timetable entry updated successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Timetable update failed', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Failed to update timetable entry: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified timetable entry
     */
    public function destroy(Timetable $timetable)
    {
        try {
            DB::beginTransaction();

            $timetableInfo = [
                'id' => $timetable->id,
                'batch' => $timetable->batch->name ?? 'Unknown',
                'subject' => $timetable->subject->name ?? 'Unknown',
                'date' => $timetable->schedule_date
            ];

            $timetable->delete();

            DB::commit();

            Log::info('Timetable entry deleted', [
                'timetable_info' => $timetableInfo,
                'deleted_by' => auth()->id()
            ]);

            return redirect()->route('admin.timetable.index')
                ->with('success', 'Timetable entry deleted successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Timetable deletion failed', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to delete timetable entry.');
        }
    }

    /**
     * Display timetable hub/calendar view
     */
    public function hub(Request $request)
    {
        try {
            $batches = Batch::where('is_active', true)->orderBy('name')->get();
            $currentWeek = $this->getCurrentWeek();

            return view('admin.timetable.hub', compact('batches', 'currentWeek'));

        } catch (\Exception $e) {
            Log::error('Error loading timetable hub', ['error' => $e->getMessage()]);
            return redirect()->route('admin.dashboard')->with('error', 'Unable to load timetable hub.');
        }
    }

    /**
     * Get timetable events for calendar
     */
    public function events(Request $request)
    {
        try {
            $query = Timetable::with(['batch', 'subject', 'user', 'classroom', 'timeSlot']);

            if ($request->filled('start') && $request->filled('end')) {
                $query->whereBetween('schedule_date', [$request->start, $request->end]);
            }

            if ($request->filled('batch_id')) {
                $query->where('batch_id', $request->batch_id);
            }

            $timetables = $query->get();

            $events = $timetables->map(function ($timetable) {
                return [
                    'id' => $timetable->id,
                    'title' => $this->getEventTitle($timetable),
                    'start' => $timetable->schedule_date . 'T' . $timetable->timeSlot->start_time,
                    'end' => $timetable->schedule_date . 'T' . $timetable->timeSlot->end_time,
                    'backgroundColor' => $this->getEventColor($timetable),
                    'borderColor' => $this->getEventColor($timetable),
                    'extendedProps' => [
                        'batch' => $timetable->batch->name ?? 'Unknown',
                        'subject' => $timetable->subject->name ?? 'Unknown',
                        'faculty' => $timetable->user->name ?? 'Unknown',
                        'classroom' => $timetable->classroom->name ?? 'Unknown',
                        'is_lab' => $timetable->is_lab_session,
                        'notes' => $timetable->notes
                    ]
                ];
            });

            return response()->json($events);

        } catch (\Exception $e) {
            Log::error('Error loading timetable events', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Unable to load events'], 500);
        }
    }

    /**
     * Check for scheduling conflicts
     */
    private function checkSchedulingConflicts($data, $excludeId = null)
    {
        $conflicts = [];

        $query = Timetable::where('schedule_date', $data['schedule_date'])
            ->where('time_slot_id', $data['time_slot_id']);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        // Check faculty conflict
        $facultyConflict = $query->where('user_id', $data['user_id'])->exists();
        if ($facultyConflict) {
            $conflicts[] = 'Faculty is already scheduled at this time';
        }

        // Check classroom conflict
        $classroomConflict = $query->where('classroom_id', $data['classroom_id'])->exists();
        if ($classroomConflict) {
            $conflicts[] = 'Classroom is already booked at this time';
        }

        // Check batch conflict
        $batchConflict = $query->where('batch_id', $data['batch_id'])->exists();
        if ($batchConflict) {
            $conflicts[] = 'Batch already has a class at this time';
        }

        return $conflicts;
    }

    /**
     * Get current week dates
     */
    private function getCurrentWeek()
    {
        $start = Carbon::now()->startOfWeek();
        $end = Carbon::now()->endOfWeek();

        return [$start->toDateString(), $end->toDateString()];
    }

    /**
     * Get event title for calendar
     */
    private function getEventTitle($timetable)
    {
        $title = ($timetable->batch->name ?? 'Unknown') . ' - ' . ($timetable->subject->name ?? 'Unknown');

        if ($timetable->is_lab_session) {
            $title .= ' (Lab)';
        }

        return $title;
    }

    /**
     * Get event color based on session type
     */
    private function getEventColor($timetable)
    {
        return $timetable->is_lab_session ? '#28a745' : '#007bff';
    }

    /**
     * Generate timetable (placeholder method)
     */
    public function generate(Request $request)
    {
        // This method should be implemented based on your timetable generation logic
        return response()->json([
            'success' => false,
            'message' => 'Timetable generation not implemented yet'
        ]);
    }

    /**
     * Quick schedule method (placeholder)
     */
    public function quickSchedule(Request $request)
    {
        // This method should be implemented based on your quick scheduling logic
        return response()->json([
            'success' => false,
            'message' => 'Quick schedule not implemented yet'
        ]);
    }

    /**
     * Move timetable entry
     */
    public function move(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:timetables,id',
            'new_date' => 'required|date',
            'new_start_time' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $timetable = Timetable::findOrFail($request->id);

            $timeSlot = TimeSlot::where('start_time', $request->new_start_time)->first();

            if (!$timeSlot) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid time slot'
                ], 400);
            }

            $updateData = [
                'batch_id' => $timetable->batch_id,
                'user_id' => $timetable->user_id,
                'classroom_id' => $timetable->classroom_id,
                'time_slot_id' => $timeSlot->id,
                'schedule_date' => $request->new_date,
                'practical_group_id' => $timetable->practical_group_id
            ];

            $conflicts = $this->checkSchedulingConflicts($updateData, $timetable->id);

            if (!empty($conflicts)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot move class: ' . implode(', ', $conflicts)
                ], 409);
            }

            DB::beginTransaction();

            $timetable->update([
                'schedule_date' => $request->new_date,
                'time_slot_id' => $timeSlot->id
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Class moved successfully!'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Class move failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to move class: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get conflicts
     */
    public function conflicts(Request $request)
    {
        try {
            // This should return detected conflicts in the timetable
            return response()->json([
                'success' => true,
                'conflicts' => []
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting conflicts', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to get conflicts'
            ], 500);
        }
    }

    /**
     * Delete class (legacy support)
     */
    public function deleteClass($id)
    {
        try {
            $timetable = Timetable::findOrFail($id);
            return $this->destroy($timetable);

        } catch (\Exception $e) {
            Log::error('Error deleting class', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to delete class.');
        }
    }

    /**
     * Export Hub View to PDF
     */
    public function exportPdf(Request $request)
    {
        return redirect()->back()->with('info', 'PDF Export for Hub is under development. Please print the page for now.');
    }

    /**
     * Generate Specific Timetable PDF
     */
    public function generatePdf(Request $request)
    {
        return redirect()->back()->with('info', 'PDF Generation is under development.');
    }

    /**
     * Export Timetable Data
     */
    public function export(Request $request, $format)
    {
        return redirect()->back()->with('info', "Export to {$format} is under development.");
    }
}