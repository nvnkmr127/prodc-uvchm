<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Course, Subject, Batch, User, Classroom, Timetable, TimeSlot, PracticalGroup, AcademicYear, Setting};
use App\Services\EnhancedTimetableGenerationService;
use App\Exports\TimetableExport;
use Carbon\Carbon;
use Illuminate\Support\Facades\{DB, Log, Validator};
use Maatwebsite\Excel\Facades\Excel;
use PDF;

class EnhancedTimetableController extends Controller
{
    protected $timetableService;

    public function __construct(EnhancedTimetableGenerationService $timetableService)
    {
        $this->timetableService = $timetableService;
    }

    /**
     * Show the enhanced timetable generation interface
     */
    public function showGenerationInterface()
    {
        try {
            $courses = Course::with(['batches', 'subjects', 'terms'])
                            ->withCount('batches')
                            ->orderBy('name')
                            ->get();
            
            $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();
            $currentAcademicYear = AcademicYear::where('is_current', true)->first();
            
            $faculties = User::role('staff')->orderBy('name')->get();
            $classrooms = Classroom::orderBy('name')->get();
            $timeSlots = TimeSlot::orderBy('start_time')->get();
            $practicalGroups = PracticalGroup::with(['batch.course', 'classroom'])
                                            ->orderBy('name')
                                            ->get();

            $workingDaysConfig = $this->getWorkingDaysConfig();
            $systemStatus = $this->checkSystemReadiness();

            return view('admin.timetable.enhanced_generation', compact(
                'courses', 
                'academicYears', 
                'currentAcademicYear',
                'faculties', 
                'classrooms', 
                'timeSlots',
                'practicalGroups',
                'workingDaysConfig',
                'systemStatus'
            ));
        } catch (\Exception $e) {
            Log::error('Failed to load enhanced timetable interface', ['error' => $e->getMessage()]);
            
            return redirect()->route('admin.dashboard')
                ->with('error', 'Failed to load timetable interface: ' . $e->getMessage());
        }
    }

    /**
     * ✅ ADDED: Generate weekly timetable using the enhanced service
     */
    public function generateWeeklyTimetable(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_ids' => 'required|array|min:1',
            'course_ids.*' => 'exists:courses,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'week_start' => 'required|date',
            'clear_existing' => 'boolean',
            'generate_labs' => 'boolean',
            'generate_theory' => 'boolean',
            'allow_conflicts' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $academicYear = AcademicYear::findOrFail($request->academic_year_id);
            $weekStart = Carbon::parse($request->week_start);

            // Clear existing if requested
            if ($request->boolean('clear_existing')) {
                $cleared = $this->clearExistingTimetable($request->course_ids, $academicYear, $weekStart);
                Log::info('Cleared existing timetable entries', ['cleared_count' => $cleared]);
            }

            $options = [
                'generate_labs' => $request->boolean('generate_labs', true),
                'generate_theory' => $request->boolean('generate_theory', true),
                'allow_conflicts' => $request->boolean('allow_conflicts', false)
            ];

            // Use the timetable service to generate the schedule
            $result = $this->timetableService->generateWeeklyTimetable(
                $request->course_ids,
                $academicYear,
                $weekStart,
                $options
            );

            Log::info('Weekly timetable generated', [
                'course_ids' => $request->course_ids,
                'week_start' => $weekStart->format('Y-m-d'),
                'success' => $result['success'] ?? false
            ]);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Weekly timetable generation failed', [
                'error' => $e->getMessage(),
                'course_ids' => $request->course_ids ?? [],
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate timetable: ' . $e->getMessage(),
                'error_details' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * ✅ ADDED: Get calendar events for timetable display
     */
    public function getEvents(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'start' => 'required|date',
                'end' => 'required|date',
                'course_ids' => 'nullable|array',
                'course_ids.*' => 'exists:courses,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $query = Timetable::with(['batch.course', 'subject', 'user', 'classroom', 'timeSlot'])
                ->whereBetween('schedule_date', [$request->start, $request->end]);

            if ($request->filled('course_ids')) {
                $query->whereHas('batch', function($q) use ($request) {
                    $q->whereIn('course_id', $request->course_ids);
                });
            }

            $timetables = $query->get();

            $events = $timetables->map(function($timetable) {
                return [
                    'id' => $timetable->id,
                    'title' => "{$timetable->subject->name} - {$timetable->batch->name}",
                    'start' => $timetable->schedule_date . 'T' . $timetable->timeSlot->start_time,
                    'end' => $timetable->schedule_date . 'T' . $timetable->timeSlot->end_time,
                    'backgroundColor' => $timetable->is_lab_session ? '#28a745' : '#007bff',
                    'borderColor' => $timetable->is_lab_session ? '#1e7e34' : '#0056b3',
                    'extendedProps' => [
                        'faculty' => $timetable->user->name,
                        'classroom' => $timetable->classroom->name,
                        'batch' => $timetable->batch->name,
                        'subject' => $timetable->subject->name,
                        'is_lab' => $timetable->is_lab_session,
                        'notes' => $timetable->notes
                    ]
                ];
            });

            return response()->json($events);

        } catch (\Exception $e) {
            Log::error('Failed to get timetable events', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to load events'
            ], 500);
        }
    }

    /**
     * ✅ ADDED: Quick schedule a single class
     */
    public function quickSchedule(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'batch_id' => 'required|exists:batches,id',
            'subject_id' => 'required|exists:subjects,id',
            'user_id' => 'required|exists:users,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'schedule_date' => 'required|date|after_or_equal:today',
            'time_slot_id' => 'required|exists:time_slots,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'is_lab_session' => 'boolean',
            'practical_group_id' => 'nullable|exists:practical_groups,id',
            'notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Check for conflicts
            $conflicts = $this->checkSchedulingConflicts($request->all());
            if (!empty($conflicts)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Scheduling conflicts detected',
                    'conflicts' => $conflicts
                ], 409);
            }

            DB::beginTransaction();

            $timetable = Timetable::create([
                'batch_id' => $request->batch_id,
                'subject_id' => $request->subject_id,
                'user_id' => $request->user_id,
                'classroom_id' => $request->classroom_id,
                'schedule_date' => $request->schedule_date,
                'time_slot_id' => $request->time_slot_id,
                'academic_year_id' => $request->academic_year_id,
                'is_lab_session' => $request->boolean('is_lab_session'),
                'practical_group_id' => $request->practical_group_id,
                'notes' => $request->notes
            ]);

            Log::info('Quick schedule created', [
                'timetable_id' => $timetable->id,
                'batch' => $timetable->batch->name ?? 'Unknown',
                'subject' => $timetable->subject->name ?? 'Unknown',
                'created_by' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Class scheduled successfully!',
                'timetable' => $timetable->load(['batch', 'subject', 'user', 'classroom', 'timeSlot'])
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Quick schedule failed', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to schedule class: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ ADDED: Move/reschedule a class
     */
    public function move(Request $request)
    {
        return $this->moveClass($request);
    }

    /**
     * ✅ ADDED: Setup lab subjects
     */
    public function setupLabSubjects(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subjects' => 'required|array|min:1',
            'subjects.*' => 'exists:subjects,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Mark selected subjects as requiring lab
            Subject::whereIn('id', $request->subjects)
                ->update(['requires_lab' => true]);

            // Unmark others if they were previously marked
            Subject::whereNotIn('id', $request->subjects)
                ->update(['requires_lab' => false]);

            Log::info('Lab subjects setup completed', [
                'subjects' => $request->subjects,
                'updated_by' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Lab subjects configuration updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Lab subjects setup failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to setup lab subjects: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ ADDED: Get lab allocation status
     */
    public function getLabAllocationStatus(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'course_ids' => 'required|array|min:1',
                'academic_year_id' => 'required|exists:academic_years,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $courses = Course::whereIn('id', $request->course_ids)
                ->with(['batches.practicalGroups.classroom'])
                ->get();

            $allocationStatus = [];

            foreach ($courses as $course) {
                $courseStatus = [
                    'course_name' => $course->name,
                    'batches' => []
                ];

                foreach ($course->batches as $batch) {
                    $batchStatus = [
                        'batch_name' => $batch->name,
                        'practical_groups' => $batch->practicalGroups->count(),
                        'allocated_labs' => $batch->practicalGroups->whereNotNull('classroom_id')->count(),
                        'unallocated_groups' => $batch->practicalGroups->whereNull('classroom_id')->count()
                    ];

                    $courseStatus['batches'][] = $batchStatus;
                }

                $allocationStatus[] = $courseStatus;
            }

            return response()->json([
                'success' => true,
                'allocation_status' => $allocationStatus
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get lab allocation status', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to get allocation status'
            ], 500);
        }
    }

    /**
     * ✅ ADDED: Bulk schedule lab sessions
     */
    public function bulkScheduleLabSessions(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_ids' => 'required|array|min:1',
            'academic_year_id' => 'required|exists:academic_years,id',
            'week_start' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $academicYear = AcademicYear::findOrFail($request->academic_year_id);
            $weekStart = Carbon::parse($request->week_start);

            $result = $this->timetableService->generateWeeklyTimetable(
                $request->course_ids,
                $academicYear,
                $weekStart,
                ['generate_labs' => true, 'generate_theory' => false]
            );

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Bulk lab scheduling failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to schedule lab sessions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ ADDED: Clear existing timetable entries for the given week
     */
    private function clearExistingTimetable($courseIds, $academicYear, $weekStart)
    {
        $batchIds = Batch::whereIn('course_id', $courseIds)->pluck('id');
        $weekEnd = Carbon::parse($weekStart)->endOfWeek();

        $deletedCount = Timetable::whereIn('batch_id', $batchIds)
            ->where('academic_year_id', $academicYear->id)
            ->whereBetween('schedule_date', [$weekStart, $weekEnd])
            ->delete();

        Log::info('Cleared existing timetable entries', [
            'deleted_count' => $deletedCount,
            'week_start' => $weekStart->format('Y-m-d'),
            'week_end' => $weekEnd->format('Y-m-d')
        ]);

        return $deletedCount;
    }

    /**
     * Get working days configuration
     */
    private function getWorkingDaysConfig()
    {
        $workingDays = Setting::where('key', 'working_days')->first();
        return $workingDays ? json_decode($workingDays->value, true) : 
            ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
    }

    /**
     * Check system readiness with proper structure for blade template
     */
    private function checkSystemReadiness()
    {
        $status = [
            'courses' => Course::count() > 0,
            'subjects' => Subject::count() > 0,
            'faculties' => User::role('staff')->count() > 0,
            'faculty' => User::role('staff')->count() > 0, // Added for blade compatibility
            'classrooms' => Classroom::count() > 0,
            'time_slots' => TimeSlot::count() > 0,
            'academic_years' => AcademicYear::count() > 0,
            'practical_groups' => PracticalGroup::count() > 0,
            'lab_subjects' => Subject::where('requires_lab', true)->count() >= 4,
        ];

        // Calculate overall status
        $allReady = collect($status)->every(fn($value) => $value === true);
        
        // Add overall status for blade template
        $status['ready'] = $allReady;
        $status['overall'] = $allReady ? 'ready' : 'warning';

        return $status;
    }

    /**
     * Get classroom utilization
     */
    private function getClassroomUtilization()
    {
        return Classroom::withCount(['timetableEntries' => function($query) {
            $query->whereBetween('schedule_date', [now()->startOfWeek(), now()->endOfWeek()]);
        }])->get()->map(function($classroom) {
            $workingDaysCount = count($this->getWorkingDaysConfig());
            $totalSlots = TimeSlot::count() * $workingDaysCount; 
            $utilization = $totalSlots > 0 ? ($classroom->timetable_entries_count / $totalSlots) * 100 : 0;
            return [
                'name' => $classroom->name,
                'utilization' => round($utilization, 1),
                'capacity' => $classroom->capacity,
                'is_lab' => $classroom->is_lab
            ];
        });
    }

    /**
     * Get time slot utilization
     */
    private function getTimeSlotUtilization()
    {
        return TimeSlot::withCount(['timetableEntries' => function($query) {
            $query->whereBetween('schedule_date', [now()->startOfWeek(), now()->endOfWeek()]);
        }])->get()->map(function($slot) {
            $totalDays = count($this->getWorkingDaysConfig());
            $classrooms = Classroom::count();
            $maxCapacity = $totalDays * $classrooms;
            $utilization = $maxCapacity > 0 ? ($slot->timetable_entries_count / $maxCapacity) * 100 : 0;
            return [
                'name' => $slot->name,
                'time' => $slot->start_time . ' - ' . $slot->end_time,
                'utilization' => round($utilization, 1),
                'sessions' => $slot->timetable_entries_count
            ];
        });
    }

    /**
     * Detect all conflicts
     */
    private function detectAllConflicts()
    {
        $conflicts = [];
        $currentWeek = [now()->startOfWeek(), now()->endOfWeek()];
        
        // Check for batch conflicts
        $batchConflicts = DB::table('timetables as t1')
            ->join('timetables as t2', function($join) {
                $join->on('t1.batch_id', '=', 't2.batch_id')
                     ->on('t1.schedule_date', '=', 't2.schedule_date')
                     ->on('t1.time_slot_id', '=', 't2.time_slot_id')
                     ->where('t1.id', '!=', DB::raw('t2.id'));
            })
            ->whereBetween('t1.schedule_date', $currentWeek)
            ->select('t1.batch_id', 't1.schedule_date', 't1.time_slot_id')
            ->distinct()
            ->get();

        foreach ($batchConflicts as $conflict) {
            $conflicts[] = [
                'type' => 'batch',
                'message' => "Batch has multiple classes at the same time",
                'date' => $conflict->schedule_date,
                'time_slot_id' => $conflict->time_slot_id
            ];
        }

        // Check for faculty conflicts
        $facultyConflicts = DB::table('timetables as t1')
            ->join('timetables as t2', function($join) {
                $join->on('t1.user_id', '=', 't2.user_id')
                     ->on('t1.schedule_date', '=', 't2.schedule_date')
                     ->on('t1.time_slot_id', '=', 't2.time_slot_id')
                     ->where('t1.id', '!=', DB::raw('t2.id'));
            })
            ->whereBetween('t1.schedule_date', $currentWeek)
            ->select('t1.user_id', 't1.schedule_date', 't1.time_slot_id')
            ->distinct()
            ->get();

        foreach ($facultyConflicts as $conflict) {
            $conflicts[] = [
                'type' => 'faculty',
                'message' => "Faculty assigned to multiple classes at the same time",
                'date' => $conflict->schedule_date,
                'time_slot_id' => $conflict->time_slot_id
            ];
        }

        // Check for classroom conflicts
        $classroomConflicts = DB::table('timetables as t1')
            ->join('timetables as t2', function($join) {
                $join->on('t1.classroom_id', '=', 't2.classroom_id')
                     ->on('t1.schedule_date', '=', 't2.schedule_date')
                     ->on('t1.time_slot_id', '=', 't2.time_slot_id')
                     ->where('t1.id', '!=', DB::raw('t2.id'));
            })
            ->whereBetween('t1.schedule_date', $currentWeek)
            ->select('t1.classroom_id', 't1.schedule_date', 't1.time_slot_id')
            ->distinct()
            ->get();

        foreach ($classroomConflicts as $conflict) {
            $conflicts[] = [
                'type' => 'classroom',
                'message' => "Classroom booked for multiple classes at the same time",
                'date' => $conflict->schedule_date,
                'time_slot_id' => $conflict->time_slot_id
            ];
        }

        return $conflicts;
    }

    /**
     * Check for scheduling conflicts
     */
    private function checkSchedulingConflicts($data, $excludeId = null)
    {
        $conflicts = [];

        if (!isset($data['schedule_date'], $data['time_slot_id'])) {
            return ['Missing required scheduling information'];
        }

        $query = Timetable::where('schedule_date', $data['schedule_date'])
            ->where('time_slot_id', $data['time_slot_id']);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        // Check batch conflict
        if (isset($data['batch_id'])) {
            $batchConflict = (clone $query)->where('batch_id', $data['batch_id'])->with('subject')->first();
            if ($batchConflict) {
                $subject = $batchConflict->subject->name ?? 'Unknown Subject';
                $conflicts[] = "Batch already has {$subject} scheduled at this time";
            }
        }

        // Check faculty conflict
        if (isset($data['user_id'])) {
            $facultyConflict = (clone $query)->where('user_id', $data['user_id'])->with('batch')->first();
            if ($facultyConflict) {
                $batch = $facultyConflict->batch->name ?? 'Unknown Batch';
                $conflicts[] = "Faculty is already teaching {$batch} at this time";
            }
        }

        // Check classroom conflict
        if (isset($data['classroom_id'])) {
            $classroomConflict = (clone $query)->where('classroom_id', $data['classroom_id'])->with('batch')->first();
            if ($classroomConflict) {
                $batch = $classroomConflict->batch->name ?? 'Unknown Batch';
                $conflicts[] = "Classroom is already occupied by {$batch} at this time";
            }
        }

        // Check for practical group conflicts
        if (isset($data['practical_group_id']) && $data['practical_group_id']) {
            $groupConflict = (clone $query)->where('practical_group_id', $data['practical_group_id'])->first();
            if ($groupConflict) {
                $conflicts[] = "Practical group is already scheduled at this time";
            }
        }

        return $conflicts;
    }

    /**
     * Detect comprehensive timetable conflicts
     */
    private function detectTimetableConflicts($courseIds, $academicYear, $weekStart)
    {
        $conflicts = [];
        $batchIds = Batch::whereIn('course_id', $courseIds)->pluck('id');
        $weekEnd = $weekStart->copy()->endOfWeek();

        $timetables = Timetable::whereIn('batch_id', $batchIds)
            ->where('academic_year_id', $academicYear->id)
            ->whereBetween('schedule_date', [$weekStart, $weekEnd])
            ->with(['batch.course', 'subject', 'user', 'classroom', 'timeSlot'])
            ->get();

        $grouped = $timetables->groupBy(function($item) {
            return $item->schedule_date . '_' . $item->time_slot_id;
        });

        foreach ($grouped as $key => $group) {
            if ($group->count() > 1) {
                $batchConflicts = [];
                $facultyConflicts = [];
                $classroomConflicts = [];

                foreach ($group as $session) {
                    // Check batch conflicts
                    $batchKey = $session->batch_id;
                    if (isset($batchConflicts[$batchKey])) {
                        $conflicts[] = [
                            'type' => 'batch_double_booking',
                            'message' => "Batch {$session->batch->name} has multiple sessions at the same time",
                            'date' => $session->schedule_date,
                            'time' => $session->timeSlot->name,
                            'sessions' => $batchConflicts[$batchKey]
                        ];
                    }
                    $batchConflicts[$batchKey] = $session;

                    // Check faculty conflicts
                    $facultyKey = $session->user_id;
                    if (isset($facultyConflicts[$facultyKey])) {
                        $conflicts[] = [
                            'type' => 'faculty_double_booking',
                            'message' => "Faculty {$session->user->name} is assigned to multiple sessions at the same time",
                            'date' => $session->schedule_date,
                            'time' => $session->timeSlot->name,
                            'sessions' => [$facultyConflicts[$facultyKey], $session]
                        ];
                    }
                    $facultyConflicts[$facultyKey] = $session;

                    // Check classroom conflicts
                    $classroomKey = $session->classroom_id;
                    if (isset($classroomConflicts[$classroomKey])) {
                        $conflicts[] = [
                            'type' => 'classroom_double_booking',
                            'message' => "Classroom {$session->classroom->name} is booked for multiple sessions at the same time",
                            'date' => $session->schedule_date,
                            'time' => $session->timeSlot->name,
                            'sessions' => [$classroomConflicts[$classroomKey], $session]
                        ];
                    }
                    $classroomConflicts[$classroomKey] = $session;
                }
            }
        }

        return $conflicts;
    }

    /**
     * Store a new timetable entry
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'batch_id' => 'required|exists:batches,id',
            'subject_id' => 'required|exists:subjects,id',
            'user_id' => 'required|exists:users,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'time_slot_id' => 'required|exists:time_slots,id',
            'schedule_date' => 'required|date|after_or_equal:today',
            'academic_year_id' => 'required|exists:academic_years,id',
            'practical_group_id' => 'nullable|exists:practical_groups,id',
            'is_lab_session' => 'boolean',
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $conflicts = $this->checkSchedulingConflicts($request->all());
            if (!empty($conflicts)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Scheduling conflicts detected',
                    'conflicts' => $conflicts
                ], 409);
            }

            DB::beginTransaction();

            $timetable = Timetable::create($request->all());

            Log::info('Timetable entry created', [
                'id' => $timetable->id,
                'batch' => $timetable->batch->name ?? 'Unknown',
                'subject' => $timetable->subject->name ?? 'Unknown',
                'date' => $timetable->schedule_date,
                'created_by' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Timetable entry created successfully',
                'timetable' => $timetable->load(['batch', 'subject', 'user', 'classroom', 'timeSlot', 'practicalGroup'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Timetable creation failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create timetable entry: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show a specific timetable entry
     */
    public function show($id)
    {
        try {
            $timetable = Timetable::with([
                'batch.course',
                'subject',
                'user',
                'classroom',
                'timeSlot',
                'practicalGroup',
                'academicYear'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'timetable' => $timetable
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Timetable entry not found'
            ], 404);
        }
    }

    /**
     * Show edit form for timetable entry
     */
    public function edit($id)
    {
        try {
            $timetable = Timetable::with(['batch', 'subject', 'user', 'classroom', 'timeSlot', 'practicalGroup'])
                                    ->findOrFail($id);
            
            $courses = Course::orderBy('name')->get();
            $subjects = Subject::orderBy('name')->get();
            $faculties = User::role('staff')->orderBy('name')->get();
            $classrooms = Classroom::orderBy('name')->get();
            $timeSlots = TimeSlot::orderBy('start_time')->get();
            $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();
            $practicalGroups = PracticalGroup::orderBy('name')->get();
            
            return view('admin.timetable.edit', compact(
                'timetable', 'courses', 'subjects', 'faculties', 
                'classrooms', 'timeSlots', 'academicYears', 'practicalGroups'
            ));

        } catch (\Exception $e) {
            return redirect()->route('admin.timetable.enhanced.index')
                ->with('error', 'Timetable entry not found');
        }
    }

    /**
     * Update timetable entry
     */
    public function update(Request $request, $id)
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
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $timetable = Timetable::findOrFail($id);
            
            $conflicts = $this->checkSchedulingConflicts($request->all(), $id);
            if (!empty($conflicts)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Update failed due to conflicts',
                    'conflicts' => $conflicts
                ], 409);
            }

            DB::beginTransaction();

            $oldData = $timetable->toArray();
            $timetable->update($request->only([
                'batch_id',
                'subject_id',
                'user_id',
                'classroom_id',
                'time_slot_id',
                'schedule_date',
                'academic_year_id',
                'practical_group_id',
                'is_lab_session',
                'notes'
            ]));

            Log::info('Timetable entry updated', [
                'id' => $timetable->id,
                'old_date' => $oldData['schedule_date'],
                'new_date' => $timetable->schedule_date,
                'updated_by' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Timetable entry updated successfully',
                'timetable' => $timetable->fresh()->load(['batch', 'subject', 'user', 'classroom', 'timeSlot'])
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Timetable update failed', ['error' => $e->getMessage(), 'id' => $id]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update timetable entry: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete timetable entry
     */
    public function destroy($id)
    {
        try {
            $timetable = Timetable::findOrFail($id);
            
            DB::beginTransaction();

            $className = $this->getDisplayName($timetable);
            $timetable->delete();

            Log::info('Timetable entry deleted', [
                'id' => $id,
                'class_name' => $className,
                'deleted_by' => auth()->id()
            ]);

            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => "'{$className}' deleted successfully!"
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Timetable deletion failed', ['error' => $e->getMessage(), 'id' => $id]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete class: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk delete timetable entries
     */
    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'timetable_ids' => 'required|array|min:1',
            'timetable_ids.*' => 'exists:timetables,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid timetable IDs provided',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $deleted = Timetable::whereIn('id', $request->timetable_ids)->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$deleted} timetable entries",
                'deleted_count' => $deleted
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Bulk delete failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete timetable entries: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk move timetable entries
     */
    public function bulkMove(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'timetable_ids' => 'required|array|min:1',
            'timetable_ids.*' => 'exists:timetables,id',
            'new_date' => 'required|date|after_or_equal:today',
            'time_offset_hours' => 'nullable|integer|between:-12,12'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $moved = 0;
            $errors = [];

            foreach ($request->timetable_ids as $id) {
                $timetable = Timetable::findOrFail($id);
                
                $updateData = ['schedule_date' => $request->new_date];
                
                if ($request->filled('time_offset_hours')) {
                    $currentSlot = $timetable->timeSlot;
                    $newStartTime = Carbon::parse($currentSlot->start_time)->addHours($request->time_offset_hours);
                    $newSlot = TimeSlot::whereTime('start_time', $newStartTime->format('H:i:s'))->first();
                    
                    if ($newSlot) {
                        $updateData['time_slot_id'] = $newSlot->id;
                    } else {
                        $errors[] = "Session {$id}: No matching time slot found for offset";
                        continue;
                    }
                }

                $conflicts = $this->checkSchedulingConflicts(
                    array_merge($timetable->toArray(), $updateData), 
                    $id
                );
                
                if (empty($conflicts)) {
                    $timetable->update($updateData);
                    $moved++;
                } else {
                    $errors[] = "Session {$id}: " . implode(', ', $conflicts);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully moved {$moved} sessions",
                'moved_count' => $moved,
                'errors' => $errors,
                'total_requested' => count($request->timetable_ids)
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Bulk move failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to move sessions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export timetable data
     */
    public function export(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'format' => 'required|in:excel,csv,pdf',
            'course_id' => 'nullable|exists:courses,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'week_start' => 'nullable|date',
            'include_conflicts' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $filename = 'timetable_' . now()->format('Y-m-d_H-i-s');
            
            switch ($request->format) {
                case 'excel':
                    return Excel::download(
                        new TimetableExport($request->all()), 
                        $filename . '.xlsx'
                    );
                case 'csv':
                    return Excel::download(
                        new TimetableExport($request->all()), 
                        $filename . '.csv'
                    );
                case 'pdf':
                    return $this->generatePDFExport($request->all(), $filename);
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Unsupported export format'
                    ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Export failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Export failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate PDF export
     */
    private function generatePDFExport($filters, $filename)
    {
        $query = Timetable::with(['batch.course', 'subject', 'user', 'classroom', 'timeSlot']);
        
        if (isset($filters['course_id'])) {
            $query->whereHas('batch', function($q) use ($filters) {
                $q->where('course_id', $filters['course_id']);
            });
        }
        
        if (isset($filters['academic_year_id'])) {
            $query->where('academic_year_id', $filters['academic_year_id']);
        }
        
        if (isset($filters['week_start'])) {
            $weekStart = Carbon::parse($filters['week_start']);
            $weekEnd = $weekStart->copy()->endOfWeek();
            $query->whereBetween('schedule_date', [$weekStart, $weekEnd]);
        }
        
        $timetables = $query->orderBy('schedule_date')
                            ->orderBy('time_slot_id')
                            ->get();

        $pdf = PDF::loadView('admin.timetable.pdf_export', compact('timetables', 'filters'));
        
        return $pdf->download($filename . '.pdf');
    }

    /**
     * Get available faculties
     */
    public function getAvailableFaculties(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'time_slot_id' => 'required|exists:time_slots,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'exclude_id' => 'nullable|exists:timetables,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $query = User::role('staff')->orderBy('name');
            
            if ($request->filled('subject_id')) {
                $query->whereHas('qualifiedSubjects', function($q) use ($request) {
                    $q->where('subject_id', $request->subject_id);
                });
            }
            
            $faculties = $query->get();
            
            $occupiedFacultyIds = Timetable::where('schedule_date', $request->date)
                ->where('time_slot_id', $request->time_slot_id)
                ->when($request->filled('exclude_id'), function($q) use ($request) {
                    $q->where('id', '!=', $request->exclude_id);
                })
                ->pluck('user_id');
            
            $availableFaculties = $faculties->whereNotIn('id', $occupiedFacultyIds);
            
            return response()->json([
                'success' => true,
                'faculties' => $availableFaculties->map(function($faculty) {
                    return [
                        'id' => $faculty->id,
                        'name' => $faculty->name,
                        'email' => $faculty->email,
                        'specializations' => $faculty->qualifiedSubjects ? 
                            $faculty->qualifiedSubjects->pluck('name')->join(', ') : ''
                    ];
                })
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get available faculties', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve available faculties: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available classrooms
     */
    public function getAvailableClassrooms(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'time_slot_id' => 'required|exists:time_slots,id',
            'is_lab' => 'boolean',
            'min_capacity' => 'nullable|integer|min:1',
            'exclude_id' => 'nullable|exists:timetables,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $query = Classroom::orderBy('name');
            
            if ($request->has('is_lab')) {
                $query->where('is_lab', $request->boolean('is_lab'));
            }
            
            if ($request->filled('min_capacity')) {
                $query->where('capacity', '>=', $request->min_capacity);
            }
            
            $classrooms = $query->get();
            
            $occupiedClassroomIds = Timetable::where('schedule_date', $request->date)
                ->where('time_slot_id', $request->time_slot_id)
                ->when($request->filled('exclude_id'), function($q) use ($request) {
                    $q->where('id', '!=', $request->exclude_id);
                })
                ->pluck('classroom_id');
            
            $availableClassrooms = $classrooms->whereNotIn('id', $occupiedClassroomIds);
            
            return response()->json([
                'success' => true,
                'classrooms' => $availableClassrooms->map(function($classroom) {
                    return [
                        'id' => $classroom->id,
                        'name' => $classroom->name,
                        'capacity' => $classroom->capacity,
                        'is_lab' => $classroom->is_lab,
                        'location' => $classroom->location ?? 'Not specified',
                        'facilities' => $classroom->facilities ?? []
                    ];
                })
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get available classrooms', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve available classrooms: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get scheduling conflicts
     */
    public function getConflicts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_ids' => 'required|array|min:1',
            'academic_year_id' => 'required|exists:academic_years,id',
            'week_start' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $academicYear = AcademicYear::findOrFail($request->academic_year_id);
            $weekStart = Carbon::parse($request->week_start);
            
            $conflicts = $this->detectTimetableConflicts($request->course_ids, $academicYear, $weekStart);

            return response()->json([
                'success' => true,
                'conflicts' => $conflicts,
                'total_conflicts' => count($conflicts)
            ]);

        } catch (\Exception $e) {
            Log::error('Conflict detection failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to detect conflicts: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update working days configuration
     */
    public function updateWorkingDays(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'working_days' => 'required|array|min:1|max:7',
            'working_days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid working days configuration',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            Setting::updateOrCreate(
                ['key' => 'working_days'],
                ['value' => json_encode($request->working_days)]
            );

            Log::info('Working days updated', [
                'old_days' => $this->getWorkingDaysConfig(),
                'new_days' => $request->working_days,
                'updated_by' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Working days updated successfully',
                'working_days' => $request->working_days
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Working days update failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update working days: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate timetable
     */
    public function validateTimetable(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_ids' => 'required|array|min:1',
            'academic_year_id' => 'required|exists:academic_years,id',
            'week_start' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $academicYear = AcademicYear::findOrFail($request->academic_year_id);
            $weekStart = Carbon::parse($request->week_start);
            
            $validationResults = [
                'errors' => [],
                'warnings' => [],
                'info' => []
            ];

            $conflicts = $this->detectTimetableConflicts($request->course_ids, $academicYear, $weekStart);
            if (!empty($conflicts)) {
                $validationResults['errors'] = array_merge($validationResults['errors'], $conflicts);
            }

            $utilization = $this->getClassroomUtilization();
            foreach ($utilization as $classroom) {
                if ($classroom['utilization'] < 30) {
                    $validationResults['warnings'][] = [
                        'type' => 'low_utilization',
                        'message' => "Classroom {$classroom['name']} is under-utilized ({$classroom['utilization']}%)"
                    ];
                }
            }

            $courses = Course::whereIn('id', $request->course_ids)->with('subjects')->get();
            foreach ($courses as $course) {
                $totalHours = $course->subjects->sum('weekly_hours') ?? 0;
                $scheduledHours = Timetable::whereHas('batch', function($q) use ($course) {
                    $q->where('course_id', $course->id);
                })->whereBetween('schedule_date', [$weekStart, $weekStart->copy()->endOfWeek()])
                ->count();

                if ($scheduledHours < $totalHours) {
                    $validationResults['warnings'][] = [
                        'type' => 'insufficient_hours',
                        'message' => "Course {$course->name} has insufficient scheduled hours ({$scheduledHours}/{$totalHours})"
                    ];
                }
            }

            $validationResults['summary'] = [
                'total_errors' => count($validationResults['errors']),
                'total_warnings' => count($validationResults['warnings']),
                'validation_passed' => empty($validationResults['errors'])
            ];

            return response()->json([
                'success' => true,
                'validation_results' => $validationResults
            ]);

        } catch (\Exception $e) {
            Log::error('Timetable validation failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate comprehensive reports
     */
    public function generateReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'report_type' => 'required|in:utilization,conflicts,faculty_load,classroom_usage',
            'course_ids' => 'nullable|array',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'date_range' => 'nullable|in:week,month,semester'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $reportData = [];
            
            switch ($request->report_type) {
                case 'utilization':
                    $reportData = $this->generateUtilizationReport($request->all());
                    break;
                case 'conflicts':
                    $reportData = $this->generateConflictReport($request->all());
                    break;
                case 'faculty_load':
                    $reportData = $this->generateFacultyLoadReport($request->all());
                    break;
                case 'classroom_usage':
                    $reportData = $this->generateClassroomUsageReport($request->all());
                    break;
            }

            return response()->json([
                'success' => true,
                'report_type' => $request->report_type,
                'data' => $reportData,
                'generated_at' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Report generation failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Report generation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate utilization report
     */
    private function generateUtilizationReport($filters)
    {
        return [
            'classroom_utilization' => $this->getClassroomUtilization(),
            'time_slot_utilization' => $this->getTimeSlotUtilization(),
            'overall_stats' => [
                'total_classrooms' => Classroom::count(),
                'total_time_slots' => TimeSlot::count(),
                'total_sessions_this_week' => Timetable::whereBetween('schedule_date', [now()->startOfWeek(), now()->endOfWeek()])->count()
            ]
        ];
    }

    /**
     * Generate conflict report
     */
    private function generateConflictReport($filters)
    {
        $allConflicts = $this->detectAllConflicts();
        return [
            'conflicts' => $allConflicts,
            'summary' => [
                'total_conflicts' => count($allConflicts),
                'by_type' => collect($allConflicts)->countBy('type')
            ]
        ];
    }

    /**
     * Generate faculty load report
     */
    private function generateFacultyLoadReport($filters)
    {
        $faculties = User::role('staff')
            ->withCount(['timetableEntries' => function($query) {
                $query->whereBetween('schedule_date', [now()->startOfWeek(), now()->endOfWeek()]);
            }])
            ->get();

        return [
            'faculty_loads' => $faculties->map(function($faculty) {
                return [
                    'name' => $faculty->name,
                    'weekly_hours' => $faculty->timetable_entries_count,
                    'load_percentage' => ($faculty->timetable_entries_count / 25) * 100 // Assuming 25 hours is a full load
                ];
            }),
            'average_load' => $faculties->avg('timetable_entries_count'),
            'max_load' => $faculties->max('timetable_entries_count'),
            'min_load' => $faculties->min('timetable_entries_count')
        ];
    }

    /**
     * Generate classroom usage report
     */
    private function generateClassroomUsageReport($filters)
    {
        $classrooms = Classroom::withCount(['timetableEntries' => function($query) {
            $query->whereBetween('schedule_date', [now()->startOfWeek(), now()->endOfWeek()]);
        }])->get();
        
        $workingDaysCount = count($this->getWorkingDaysConfig());

        return [
            'usage_by_classroom' => $classrooms->map(function($classroom) use ($workingDaysCount) {
                $totalSlots = TimeSlot::count() * $workingDaysCount;
                return [
                    'name' => $classroom->name,
                    'capacity' => $classroom->capacity,
                    'type' => $classroom->is_lab ? 'Lab' : 'Regular',
                    'sessions_this_week' => $classroom->timetable_entries_count,
                    'utilization_percentage' => $totalSlots > 0 ? round(($classroom->timetable_entries_count / $totalSlots) * 100, 2) : 0,
                    'peak_usage_days' => $this->getClassroomPeakDays($classroom->id)
                ];
            }),
            'summary' => [
                'most_used' => $classrooms->sortByDesc('timetable_entries_count')->first()?->name,
                'least_used' => $classrooms->sortBy('timetable_entries_count')->first()?->name,
                'average_utilization' => $classrooms->avg(function($classroom) use ($workingDaysCount) {
                    $totalSlots = TimeSlot::count() * $workingDaysCount;
                    return $totalSlots > 0 ? ($classroom->timetable_entries_count / $totalSlots) * 100 : 0;
                })
            ]
        ];
    }

    /**
     * Get classroom peak usage days
     */
    private function getClassroomPeakDays($classroomId)
    {
        return DB::table('timetables')
            ->select(DB::raw('DAYNAME(schedule_date) as day'), DB::raw('COUNT(*) as sessions'))
            ->where('classroom_id', $classroomId)
            ->whereBetween('schedule_date', [now()->startOfWeek(), now()->endOfWeek()])
            ->groupBy(DB::raw('DAYNAME(schedule_date)'))
            ->orderByDesc('sessions')
            ->limit(3)
            ->pluck('sessions', 'day')
            ->toArray();
    }

    /**
     * Get timetable statistics
     */
    public function getStatistics()
    {
        try {
            $currentWeek = [now()->startOfWeek(), now()->endOfWeek()];
            
            $stats = [
                'total_sessions_this_week' => Timetable::whereBetween('schedule_date', $currentWeek)->count(),
                'total_conflicts' => count($this->detectAllConflicts()),
                'classroom_utilization' => round($this->getClassroomUtilization()->avg('utilization') ?? 0, 2),
                'faculty_load_average' => round(User::role('staff')
                    ->withCount(['timetableEntries' => function($query) use ($currentWeek) {
                        $query->whereBetween('schedule_date', $currentWeek);
                    }])
                    ->get()
                    ->avg('timetable_entries_count') ?? 0, 2),
                'upcoming_sessions_today' => Timetable::where('schedule_date', now()->toDateString())
                    ->whereHas('timeSlot', function($query) {
                        $query->where('start_time', '>', now()->format('H:i:s'));
                    })
                    ->count()
            ];

            return response()->json([
                'success' => true,
                'statistics' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get timetable statistics', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics'
            ], 500);
        }
    }

    /**
     * Get dashboard data
     */
    public function getDashboardData()
    {
        try {
            return response()->json([
                'success' => true,
                'data' => [
                    'recent_sessions' => Timetable::with(['batch', 'subject', 'user', 'classroom'])
                        ->whereBetween('schedule_date', [now()->subDays(7), now()])
                        ->orderBy('schedule_date', 'desc')
                        ->limit(5)
                        ->get(),
                    'upcoming_sessions' => Timetable::with(['batch', 'subject', 'user', 'classroom'])
                        ->whereBetween('schedule_date', [now(), now()->addDays(7)])
                        ->orderBy('schedule_date')
                        ->limit(5)
                        ->get(),
                    'conflicts' => array_slice($this->detectAllConflicts(), 0, 5),
                    'utilization_summary' => [
                        'classrooms' => $this->getClassroomUtilization()->take(5),
                        'time_slots' => $this->getTimeSlotUtilization()->take(5)
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get dashboard data', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dashboard data'
            ], 500);
        }
    }

    /**
     * Legacy support methods
     */
    public function deleteClass($id)
    {
        return $this->destroy($id);
    }

    /**
     * Move class method for legacy support
     */
    public function moveClass(Request $request)
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

            Log::info('Class moved successfully', [
                'id' => $timetable->id,
                'new_date' => $request->new_date,
                'new_time_slot' => $timeSlot->name ?? 'Unknown',
                'moved_by' => auth()->id()
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
     * Get display name for timetable entry
     */
    private function getDisplayName($timetable)
    {
        return sprintf(
            "%s - %s (%s)",
            $timetable->batch->name ?? 'Unknown Batch',
            $timetable->subject->name ?? 'Unknown Subject',
            $timetable->timeSlot->name ?? 'Unknown Time'
        );
    }

    /**
     * ✅ ADDED: Additional helper methods for enhanced functionality
     */

    /**
     * Get system status for health checks
     */
    public function getSystemStatus()
    {
        try {
            $status = $this->checkSystemReadiness();
            
            return response()->json([
                'success' => true,
                'system_status' => $status
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get system status', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve system status'
            ], 500);
        }
    }

    /**
     * Get batches for a specific course (AJAX helper)
     */
    public function getBatchesForCourse(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_id' => 'required|exists:courses,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $batches = Batch::where('course_id', $request->course_id)
                            ->orderBy('name')
                            ->get(['id', 'name', 'strength']);

            return response()->json([
                'success' => true,
                'batches' => $batches
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get batches for course', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve batches'
            ], 500);
        }
    }

    /**
     * Get subjects for a specific course (AJAX helper)
     */
    public function getSubjectsForCourse(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_id' => 'required|exists:courses,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $subjects = Subject::whereHas('courses', function($query) use ($request) {
                $query->where('course_id', $request->course_id);
            })->orderBy('name')->get(['id', 'name', 'code', 'requires_lab']);

            return response()->json([
                'success' => true,
                'subjects' => $subjects
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get subjects for course', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve subjects'
            ], 500);
        }
    }

    /**
     * Check if faculty is available at specific time
     */
    public function checkFacultyAvailability(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'faculty_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'time_slot_id' => 'required|exists:time_slots,id',
            'exclude_id' => 'nullable|exists:timetables,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $query = Timetable::where('user_id', $request->faculty_id)
                                ->where('schedule_date', $request->date)
                                ->where('time_slot_id', $request->time_slot_id);

            if ($request->filled('exclude_id')) {
                $query->where('id', '!=', $request->exclude_id);
            }

            $isAvailable = !$query->exists();

            return response()->json([
                'success' => true,
                'available' => $isAvailable,
                'message' => $isAvailable ? 'Faculty is available' : 'Faculty is already assigned at this time'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to check faculty availability', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to check availability'
            ], 500);
        }
    }

    /**
     * Check if classroom is available at specific time
     */
    public function checkClassroomAvailability(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'classroom_id' => 'required|exists:classrooms,id',
            'date' => 'required|date',
            'time_slot_id' => 'required|exists:time_slots,id',
            'exclude_id' => 'nullable|exists:timetables,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $query = Timetable::where('classroom_id', $request->classroom_id)
                                ->where('schedule_date', $request->date)
                                ->where('time_slot_id', $request->time_slot_id);

            if ($request->filled('exclude_id')) {
                $query->where('id', '!=', $request->exclude_id);
            }

            $isAvailable = !$query->exists();

            return response()->json([
                'success' => true,
                'available' => $isAvailable,
                'message' => $isAvailable ? 'Classroom is available' : 'Classroom is already booked at this time'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to check classroom availability', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to check availability'
            ], 500);
        }
    }

    /**
     * Get timetable for a specific date range
     */
    public function getTimetableByDateRange(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'course_ids' => 'nullable|array',
            'course_ids.*' => 'exists:courses,id',
            'batch_ids' => 'nullable|array',
            'batch_ids.*' => 'exists:batches,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $query = Timetable::with(['batch.course', 'subject', 'user', 'classroom', 'timeSlot'])
                ->whereBetween('schedule_date', [$request->start_date, $request->end_date]);

            if ($request->filled('course_ids')) {
                $query->whereHas('batch', function($q) use ($request) {
                    $q->whereIn('course_id', $request->course_ids);
                });
            }

            if ($request->filled('batch_ids')) {
                $query->whereIn('batch_id', $request->batch_ids);
            }

            $timetables = $query->orderBy('schedule_date')
                                ->orderBy('time_slot_id')
                                ->get();

            return response()->json([
                'success' => true,
                'timetables' => $timetables->map(function($timetable) {
                    return [
                        'id' => $timetable->id,
                        'date' => $timetable->schedule_date,
                        'time' => $timetable->timeSlot->start_time . ' - ' . $timetable->timeSlot->end_time,
                        'course' => $timetable->batch->course->name,
                        'batch' => $timetable->batch->name,
                        'subject' => $timetable->subject->name,
                        'faculty' => $timetable->user->name,
                        'classroom' => $timetable->classroom->name,
                        'is_lab' => $timetable->is_lab_session,
                        'notes' => $timetable->notes
                    ];
                }),
                'total_sessions' => $timetables->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get timetable by date range', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve timetable data'
            ], 500);
        }
    }
    
    /**
     * ✅ FALLBACK: Simple timetable generation method
     * Add this method to your EnhancedTimetableController if the service still doesn't work
     */
    public function generateWeeklyTimetableSimple(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_ids' => 'required|array|min:1',
            'course_ids.*' => 'exists:courses,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'week_start' => 'required|date',
            'clear_existing' => 'boolean',
            'generate_labs' => 'boolean',
            'generate_theory' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $academicYear = AcademicYear::findOrFail($request->academic_year_id);
            $weekStart = Carbon::parse($request->week_start);
            
            // Clear existing if requested
            if ($request->boolean('clear_existing')) {
                $this->clearExistingTimetable($request->course_ids, $academicYear, $weekStart);
            }

            $result = [
                'success' => true,
                'message' => '',
                'sessions_created' => 0,
                'lab_sessions' => 0,
                'theory_sessions' => 0,
                'conflicts' => 0,
                'report' => "🚀 Simple Timetable Generation Started\n" . str_repeat("=", 50) . "\n"
            ];

            // Get basic data
            $timeSlots = TimeSlot::orderBy('start_time')->get();
            $faculties = User::role('staff')->get();
            $classrooms = Classroom::all();

            if ($timeSlots->isEmpty()) {
                throw new \Exception('No time slots configured');
            }

            if ($faculties->isEmpty()) {
                throw new \Exception('No faculty members found');
            }

            if ($classrooms->isEmpty()) {
                throw new \Exception('No classrooms configured');
            }

            $result['report'] .= "📊 Available Resources:\n";
            $result['report'] .= "   Time Slots: {$timeSlots->count()}\n";
            $result['report'] .= "   Faculty: {$faculties->count()}\n";
            $result['report'] .= "   Classrooms: {$classrooms->count()}\n\n";

            // Get working days (default to Mon-Fri)
            $workingDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
            
            // Generate week dates
            $weekDates = collect();
            $dayMap = [
                'monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 
                'thursday' => 4, 'friday' => 5, 'saturday' => 6, 'sunday' => 0
            ];

            foreach ($workingDays as $day) {
                if (isset($dayMap[$day])) {
                    $date = $weekStart->copy()->startOfWeek()->addDays($dayMap[$day] - 1);
                    $weekDates->push($date);
                }
            }

            $result['report'] .= "📅 Week Dates: " . $weekDates->map(fn($d) => $d->format('D M d'))->join(', ') . "\n\n";

            // Process each course
            foreach ($request->course_ids as $courseId) {
                $course = Course::with(['batches', 'subjects'])->findOrFail($courseId);
                $result['report'] .= "🎓 Processing Course: {$course->name}\n";

                // Get course subjects
                $subjects = $course->subjects ?? collect();
                $labSubjects = $subjects->where('requires_lab', true);
                $theorySubjects = $subjects->where('requires_lab', false);

                $result['report'] .= "   📚 Subjects: {$subjects->count()} (Lab: {$labSubjects->count()}, Theory: {$theorySubjects->count()})\n";

                // Process each batch
                foreach ($course->batches as $batch) {
                    $result['report'] .= "   📋 Batch: {$batch->name}\n";

                    // Generate sessions for each subject
                    foreach ($subjects as $subject) {
                        $isLab = $subject->requires_lab ?? false;
                        
                        // Check generation options
                        if ($isLab && !$request->boolean('generate_labs', true)) {
                            continue;
                        }
                        if (!$isLab && !$request->boolean('generate_theory', true)) {
                            continue;
                        }

                        $sessionsNeeded = $subject->weekly_hours ?? 2;
                        $sessionsCreated = 0;

                        $result['report'] .= "      📖 Subject: {$subject->name} " . ($isLab ? "(LAB)" : "(THEORY)") . "\n";

                        // Try to schedule sessions across the week
                        foreach ($weekDates as $date) {
                            if ($sessionsCreated >= $sessionsNeeded) {
                                break;
                            }

                            foreach ($timeSlots as $timeSlot) {
                                if ($sessionsCreated >= $sessionsNeeded) {
                                    break;
                                }

                                // Check for batch conflict
                                $batchConflict = Timetable::where('batch_id', $batch->id)
                                    ->where('schedule_date', $date->format('Y-m-d'))
                                    ->where('time_slot_id', $timeSlot->id)
                                    ->exists();

                                if ($batchConflict) {
                                    continue; // Skip this slot
                                }

                                // Find available faculty
                                $availableFaculty = null;
                                foreach ($faculties as $faculty) {
                                    $facultyBusy = Timetable::where('user_id', $faculty->id)
                                        ->where('schedule_date', $date->format('Y-m-d'))
                                        ->where('time_slot_id', $timeSlot->id)
                                        ->exists();

                                    if (!$facultyBusy) {
                                        $availableFaculty = $faculty;
                                        break;
                                    }
                                }

                                if (!$availableFaculty) {
                                    continue; // No faculty available
                                }

                                // Find available classroom
                                $availableClassroom = null;
                                $classroomQuery = $classrooms;
                                
                                if ($isLab) {
                                    $classroomQuery = $classrooms->where('is_lab', true);
                                }

                                foreach ($classroomQuery as $classroom) {
                                    $classroomBusy = Timetable::where('classroom_id', $classroom->id)
                                        ->where('schedule_date', $date->format('Y-m-d'))
                                        ->where('time_slot_id', $timeSlot->id)
                                        ->exists();

                                    if (!$classroomBusy) {
                                        $availableClassroom = $classroom;
                                        break;
                                    }
                                }

                                if (!$availableClassroom) {
                                    continue; // No classroom available
                                }

                                // Create timetable entry
                                try {
                                    Timetable::create([
                                        'batch_id' => $batch->id,
                                        'subject_id' => $subject->id,
                                        'user_id' => $availableFaculty->id,
                                        'classroom_id' => $availableClassroom->id,
                                        'time_slot_id' => $timeSlot->id,
                                        'schedule_date' => $date->format('Y-m-d'),
                                        'academic_year_id' => $academicYear->id,
                                        'is_lab_session' => $isLab,
                                        'notes' => 'Auto-generated: ' . now()->format('Y-m-d H:i:s')
                                    ]);

                                    $sessionsCreated++;
                                    $result['sessions_created']++;
                                    
                                    if ($isLab) {
                                        $result['lab_sessions']++;
                                    } else {
                                        $result['theory_sessions']++;
                                    }

                                    $result['report'] .= "         ✅ {$date->format('D M d')} {$timeSlot->start_time}-{$timeSlot->end_time} | {$availableFaculty->name} | {$availableClassroom->name}\n";

                                } catch (\Exception $e) {
                                    $result['conflicts']++;
                                    Log::error('Failed to create timetable session', [
                                        'error' => $e->getMessage(),
                                        'batch_id' => $batch->id,
                                        'subject_id' => $subject->id
                                    ]);
                                }
                            }
                        }

                        if ($sessionsCreated < $sessionsNeeded) {
                            $result['report'] .= "         ⚠️ Only scheduled {$sessionsCreated}/{$sessionsNeeded} sessions\n";
                        }
                    }
                }
                $result['report'] .= "\n";
            }

            DB::commit();

            $result['message'] = "✅ Timetable generated successfully! Created {$result['sessions_created']} sessions " .
                               "({$result['theory_sessions']} theory, {$result['lab_sessions']} lab)";

            $result['report'] .= str_repeat("=", 50) . "\n";
            $result['report'] .= "📊 FINAL SUMMARY:\n";
            $result['report'] .= "   Total Sessions: {$result['sessions_created']}\n";
            $result['report'] .= "   Theory Sessions: {$result['theory_sessions']}\n";
            $result['report'] .= "   Lab Sessions: {$result['lab_sessions']}\n";
            $result['report'] .= "   Conflicts: {$result['conflicts']}\n";
            $result['report'] .= "✅ Generation Complete!\n";

            Log::info('Simple timetable generation completed', $result);

            return response()->json($result);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Simple timetable generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate timetable: ' . $e->getMessage(),
                'sessions_created' => 0,
                'lab_sessions' => 0,
                'theory_sessions' => 0,
                'conflicts' => 0,
                'report' => "❌ Error: " . $e->getMessage()
            ], 500);
        }
    }



    

/**
 * ✅ CHECK WHAT DATA EXISTS
 */
public function checkPrerequisites()
{
    try {
        // Simple data check
        $data = [
            'courses' => \App\Models\Course::count(),
            'batches' => \App\Models\Batch::count(), 
            'subjects' => \App\Models\Subject::count(),
            'users' => \App\Models\User::count(),
            'classrooms' => \App\Models\Classroom::count(),
            'time_slots' => \App\Models\TimeSlot::count(),
            'academic_years' => \App\Models\AcademicYear::count(),
            'timetable_entries' => \App\Models\Timetable::count()
        ];
        
        return response()->json([
            'success' => true,
            'message' => 'Prerequisites check completed',
            'data' => $data,
            'ready' => collect($data)->filter(fn($count, $key) => $key !== 'timetable_entries')->every(fn($count) => $count > 0)
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Check failed: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * ✅ CREATE MINIMAL TEST DATA
 */
public function quickSetup(Request $request)
{
    try {
        \DB::beginTransaction();
        
        $created = [];
        
        // Create academic year if missing
        if (\App\Models\AcademicYear::count() === 0) {
            \App\Models\AcademicYear::create([
                'name' => '2024-25',
                'start_date' => '2024-04-01',
                'end_date' => '2025-03-31', 
                'is_current' => true
            ]);
            $created[] = 'Academic Year';
        }
        
        // Create course if missing  
        if (\App\Models\Course::count() === 0) {
            \App\Models\Course::create([
                'name' => 'Test Course',
                'code' => 'TC101'
            ]);
            $created[] = 'Course';
        }
        
        // Create batch if missing
        if (\App\Models\Batch::count() === 0) {
            $course = \App\Models\Course::first();
            $academicYear = \App\Models\AcademicYear::first();
            
            \App\Models\Batch::create([
                'name' => 'Test Batch A',
                'course_id' => $course->id,
                'strength' => 30,
                'academic_year_id' => $academicYear->id
            ]);
            $created[] = 'Batch';
        }
        
        // Create subject if missing
        if (\App\Models\Subject::count() === 0) {
            \App\Models\Subject::create([
                'name' => 'Test Subject',
                'code' => 'TS101',
                'requires_lab' => false,
                'weekly_hours' => 2
            ]);
            $created[] = 'Subject';
        }
        
        // Create user if missing staff
        $staffCount = 0;
        try {
            $staffCount = \App\Models\User::role('staff')->count();
        } catch (\Exception $e) {
            // Role might not exist, create a regular user
            $staffCount = 0;
        }
        
        if ($staffCount === 0) {
            $user = \App\Models\User::create([
                'name' => 'Test Faculty',
                'email' => 'test.faculty@example.com',
                'password' => bcrypt('password123'),
                'email_verified_at' => now()
            ]);
            
            try {
                $user->assignRole('staff');
            } catch (\Exception $e) {
                // Role assignment failed, but user created
            }
            $created[] = 'Faculty User';
        }
        
        // Create classroom if missing
        if (\App\Models\Classroom::count() === 0) {
            \App\Models\Classroom::create([
                'name' => 'Room 101',
                'capacity' => 40,
                'is_lab' => false
            ]);
            $created[] = 'Classroom';
        }
        
        // Create time slot if missing
        if (\App\Models\TimeSlot::count() === 0) {
            \App\Models\TimeSlot::create([
                'name' => '9:00 AM - 10:00 AM',
                'start_time' => '09:00:00',
                'end_time' => '10:00:00'
            ]);
            $created[] = 'Time Slot';
        }
        
        \DB::commit();
        
        return response()->json([
            'success' => true,
            'message' => 'Quick setup completed!',
            'created' => $created,
            'note' => empty($created) ? 'All data already exists' : 'Created: ' . implode(', ', $created)
        ]);
        
    } catch (\Exception $e) {
        \DB::rollback();
        
        return response()->json([
            'success' => false, 
            'message' => 'Setup failed: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * ✅ SIMPLE TEST: Just return success to verify route works
 */
public function testTimetableCreation(Request $request)
{
    try {
        // First, just test if the route works
        return response()->json([
            'success' => true,
            'message' => '✅ Route is working! Now testing data...',
            'timestamp' => now()->format('Y-m-d H:i:s')
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Route test failed: ' . $e->getMessage()
        ], 500);
    }
}


    /**
     * Get setup recommendations based on missing data
     */
    private function getSetupRecommendations($prerequisites)
    {
        $recommendations = [];

        if ($prerequisites['courses']['count'] === 0) {
            $recommendations[] = 'Create at least one course in Course Management';
        }

        if ($prerequisites['batches']['count'] === 0) {
            $recommendations[] = 'Create batches for your courses in Batch Management';
        }

        if ($prerequisites['subjects']['count'] === 0) {
            $recommendations[] = 'Add subjects to your courses in Subject Management';
        }

        if ($prerequisites['faculty']['count'] === 0) {
            $recommendations[] = 'Add faculty members with "staff" role in User Management';
        }

        if ($prerequisites['classrooms']['count'] === 0) {
            $recommendations[] = 'Create classrooms in Classroom Management';
        }

        if ($prerequisites['time_slots']['count'] === 0) {
            $recommendations[] = 'Configure time slots in Time Slot Management';
        }

        if ($prerequisites['academic_years']['count'] === 0) {
            $recommendations[] = 'Set up academic years in Academic Year Management';
        }

        if (empty($recommendations)) {
            $recommendations[] = 'All basic requirements are met! You can now generate timetables.';
        }

        return $recommendations;
    }
    /**
     * Clone/duplicate timetable entries to another week
     */
    public function cloneTimetable(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'source_week_start' => 'required|date',
            'target_week_start' => 'required|date|after:source_week_start',
            'course_ids' => 'required|array|min:1',
            'course_ids.*' => 'exists:courses,id',
            'skip_conflicts' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $sourceWeekStart = Carbon::parse($request->source_week_start);
            $sourceWeekEnd = $sourceWeekStart->copy()->endOfWeek();
            $targetWeekStart = Carbon::parse($request->target_week_start);
            
            $batchIds = Batch::whereIn('course_id', $request->course_ids)->pluck('id');

            // Get source timetable entries
            $sourceTimetables = Timetable::whereIn('batch_id', $batchIds)
                ->whereBetween('schedule_date', [$sourceWeekStart, $sourceWeekEnd])
                ->get();

            $cloned = 0;
            $skipped = 0;
            $errors = [];

            foreach ($sourceTimetables as $source) {
                // Calculate target date
                $daysDiff = Carbon::parse($source->schedule_date)->diffInDays($sourceWeekStart);
                $targetDate = $targetWeekStart->copy()->addDays($daysDiff);

                $newData = [
                    'batch_id' => $source->batch_id,
                    'subject_id' => $source->subject_id,
                    'user_id' => $source->user_id,
                    'classroom_id' => $source->classroom_id,
                    'time_slot_id' => $source->time_slot_id,
                    'schedule_date' => $targetDate->format('Y-m-d'),
                    'academic_year_id' => $source->academic_year_id,
                    'practical_group_id' => $source->practical_group_id,
                    'is_lab_session' => $source->is_lab_session,
                    'notes' => 'Cloned from ' . $sourceWeekStart->format('Y-m-d')
                ];

                // Check for conflicts
                $conflicts = $this->checkSchedulingConflicts($newData);

                if (!empty($conflicts)) {
                    if ($request->boolean('skip_conflicts')) {
                        $skipped++;
                        continue;
                    } else {
                        $errors[] = "Cannot clone session on {$targetDate->format('Y-m-d')}: " . implode(', ', $conflicts);
                        continue;
                    }
                }

                // Create new timetable entry
                Timetable::create($newData);
                $cloned++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully cloned {$cloned} sessions",
                'cloned_count' => $cloned,
                'skipped_count' => $skipped,
                'errors' => $errors,
                'total_source' => $sourceTimetables->count()
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Timetable clone failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to clone timetable: ' . $e->getMessage()
            ], 500);
        }
    }
}