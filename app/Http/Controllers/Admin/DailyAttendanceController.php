<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance\Attendance; // Adjust namespace if needed
use App\Models\Student;
use App\Models\User;
use App\Models\Batch;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DailyAttendanceController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin|super-admin']);
        $this->middleware('permission:manage attendance');
    }

    /**
     * Display daily attendance index
     */
    public function index(Request $request): View
    {
        try {
            $date = $request->get('date', Carbon::today()->format('Y-m-d'));
            $batchId = $request->get('batch_id');
            $type = $request->get('type', 'all');

            // Build query with proper error handling
            $query = Attendance::with(['student', 'batch', 'subject'])
                ->whereDate('attendance_date', $date);

            // Apply filters
            if ($batchId) {
                $query->where('batch_id', $batchId);
            }

            if ($type === 'students') {
                $query->whereNotNull('student_id');
            }

            $attendances = $query->orderBy('created_at', 'desc')->paginate(50);

            // Get filter options
            $batches = Batch::with('course')->orderBy('name')->get();

            // Get statistics (Updated with Internship Logic)
            $stats = $this->getDayStatistics($date, $batchId, $type);

            return view('admin.daily_attendance.index', compact(
                'attendances',
                'batches',
                'date',
                'batchId',
                'type',
                'stats'
            ));

        } catch (\Exception $e) {
            Log::error('Daily attendance index error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            // Return with safe defaults
            return view('admin.daily_attendance.index', [
                'attendances' => collect()->paginate(),
                'batches' => collect(),
                'date' => $request->get('date', Carbon::today()->format('Y-m-d')),
                'batchId' => $request->get('batch_id'),
                'type' => $request->get('type', 'all'),
                'stats' => $this->getDefaultStats()
            ])->with('error', 'Unable to load attendance data. Error: ' . $e->getMessage());
        }
    }

    /**
     * Show daily attendance details
     */
    public function show(Request $request): View
    {
        try {
            $date = $request->get('date', Carbon::today()->format('Y-m-d'));
            $batchId = $request->get('batch_id');
            $type = $request->get('type', 'all');

            // Get attendance records for the day
            $query = Attendance::with(['student', 'subject', 'batch'])
                ->whereDate('attendance_date', $date);

            if ($batchId) {
                $query->where('batch_id', $batchId);
            }

            if ($type === 'students') {
                $query->whereNotNull('student_id');
            }

            $attendances = $query->orderBy('created_at', 'desc')->paginate(50);

            // Get summary statistics
            $stats = $this->getDayStatistics($date, $batchId, $type);

            // Get filter options
            $batches = Batch::with('course')->orderBy('name')->get();

            return view('admin.daily_attendance.show', compact(
                'attendances',
                'batches',
                'date',
                'batchId',
                'type',
                'stats'
            ));

        } catch (\Exception $e) {
            Log::error('Daily attendance show error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return view('admin.daily_attendance.show', [
                'attendances' => collect()->paginate(),
                'batches' => collect(),
                'date' => $request->get('date', Carbon::today()->format('Y-m-d')),
                'batchId' => $request->get('batch_id'),
                'type' => $request->get('type', 'all'),
                'stats' => $this->getDefaultStats()
            ])->with('error', 'Unable to load attendance data. Error: ' . $e->getMessage());
        }
    }

    /**
     * Show create form
     */
    public function create(Request $request)
    {
        try {
            $date = $request->get('date', Carbon::today()->format('Y-m-d'));
            $batchId = $request->get('batch_id');

            $batches = Batch::with('course')->orderBy('name')->get();
            $subjects = Subject::orderBy('name')->get();

            // Get students if batch is selected
            $students = [];
            if ($batchId) {
                $batch = Batch::with('students')->find($batchId);
                $students = $batch ? $batch->students : [];
            }

            return view('admin.daily_attendance.create', compact(
                'batches',
                'students',
                'date',
                'batchId'
            ));

        } catch (\Exception $e) {
            Log::error('Daily attendance create error: ' . $e->getMessage());

            return back()->with('error', 'Unable to load attendance form. Error: ' . $e->getMessage());
        }
    }

    /**
     * Store attendance data
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'batch_id' => 'required|exists:batches,id',
                'attendance_date' => 'required|date',
                'attendances' => 'required|array',
                'attendances.*.student_id' => 'required|exists:students,id',
                'attendances.*.status' => 'required|in:present,absent,late,excused'
            ]);

            DB::beginTransaction();

            foreach ($request->attendances as $attendance) {
                Attendance::updateOrCreate([
                    'student_id' => $attendance['student_id'],
                    'batch_id' => $request->batch_id,
                    'attendance_date' => $request->attendance_date,
                ], [
                    'status' => $attendance['status'],
                    'marked_by' => Auth::id(),
                    'remarks' => $attendance['remarks'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()->route('admin.daily-attendance.index')
                ->with('success', 'Attendance marked successfully for ' . count($request->attendances) . ' students.');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Daily attendance store error: ' . $e->getMessage());

            return back()
                ->withInput()
                ->with('error', 'Failed to save attendance. Error: ' . $e->getMessage());
        }
    }

    /**
     * Get day statistics helper method (Enhanced with Internship Logic)
     */
    private function getDayStatistics($date, $batchId = null, $type = 'all')
    {
        try {
            // 1. Base Metrics from Attendance Table
            $query = Attendance::whereDate('attendance_date', $date);

            if ($batchId) {
                $query->where('batch_id', $batchId);
            }

            if ($type === 'students') {
                $query->whereNotNull('student_id');
            }

            $attendanceMetrics = $query->selectRaw('
                COUNT(*) as total,
                COUNT(CASE WHEN status = "present" THEN 1 END) as present,
                COUNT(CASE WHEN status = "absent" THEN 1 END) as marked_absent,
                COUNT(CASE WHEN status = "late" THEN 1 END) as late,
                COUNT(CASE WHEN status = "excused" THEN 1 END) as excused
            ')->first();

            // 2. Calculate Internship Students
            // Students in batches marked "is_on_internship" who have NOT marked attendance today
            $internshipQuery = Student::where('status', 'active')
                ->whereHas('batch', function ($q) {
                    $q->where('is_on_internship', true);
                })
                ->whereDoesntHave('attendances', function ($q) use ($date) {
                    $q->whereDate('attendance_date', $date);
                });

            if ($batchId) {
                $internshipQuery->where('batch_id', $batchId);
            }

            $internshipCount = $internshipQuery->count();

            // 3. Calculate Total Active Students (for Percentage)
            $totalStudentQuery = Student::where('status', 'active');
            if ($batchId) {
                $totalStudentQuery->where('batch_id', $batchId);
            }
            $totalStudents = $totalStudentQuery->count();

            // 4. Calculate Final Counts
            $present = ($attendanceMetrics->present ?? 0) + ($attendanceMetrics->late ?? 0);
            $markedAbsent = $attendanceMetrics->marked_absent ?? 0;
            $excused = $attendanceMetrics->excused ?? 0;

            // Calculate attendance percentage based on Total Active Students
            $percentage = $totalStudents > 0 ?
                round(($present / $totalStudents) * 100, 2) : 0;

            return [
                'total' => $totalStudents,
                'present' => $present,
                'absent' => $markedAbsent, // Only counts those explicitly marked absent
                'internship' => $internshipCount, // New Metric
                'late' => $attendanceMetrics->late ?? 0,
                'excused' => $excused,
                'percentage' => $percentage
            ];

        } catch (\Exception $e) {
            Log::error('Error getting day statistics: ' . $e->getMessage());
            return $this->getDefaultStats();
        }
    }

    /**
     * Get default statistics
     */
    private function getDefaultStats()
    {
        return [
            'total' => 0,
            'present' => 0,
            'absent' => 0,
            'internship' => 0, // Added
            'late' => 0,
            'excused' => 0,
            'percentage' => 0
        ];
    }

    /**
     * Get students by batch (AJAX)
     */
    public function getBatchStudents(Request $request, $batchId)
    {
        try {
            $batch = Batch::with([
                'students' => function ($query) {
                    $query->where('status', 'active')->orderBy('name');
                }
            ])->find($batchId);

            if (!$batch) {
                return response()->json([
                    'success' => false,
                    'message' => 'Batch not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'students' => $batch->students,
                'batch_name' => $batch->name
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting batch students: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to load students'
            ], 500);
        }
    }
    /**
     * Get student attendance for a specific month
     */
    public function getStudentMonthAttendance(Request $request, $studentId)
    {
        try {
            $month = $request->get('month', Carbon::now()->format('Y-m'));
            $batchId = $request->get('batch_id');

            $startOfMonth = Carbon::parse($month)->startOfMonth();
            $endOfMonth = Carbon::parse($month)->endOfMonth();

            $query = Attendance::where('student_id', $studentId)
                ->whereBetween('attendance_date', [$startOfMonth, $endOfMonth]);

            if ($batchId) {
                $query->where('batch_id', $batchId);
            }

            $attendances = $query->get(['attendance_date', 'status', 'remarks'])
                ->keyBy('attendance_date');

            return response()->json([
                'success' => true,
                'data' => $attendances
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching student month attendance: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load attendance data'
            ], 500);
        }
    }

    /**
     * Store bulk attendance for a student
     */
    public function storeStudentBulkAttendance(Request $request)
    {
        try {
            $request->validate([
                'student_id' => 'required|exists:students,id',
                'batch_id' => 'required|exists:batches,id',
                'attendances' => 'required|array',
                'attendances.*.date' => 'required|date',
                'attendances.*.status' => 'required|in:present,absent,late,excused'
            ]);

            DB::beginTransaction();

            foreach ($request->attendances as $attendanceData) {
                $data = [
                    'status' => $attendanceData['status'],
                    'marked_by' => Auth::id(),
                    'remarks' => $attendanceData['remarks'] ?? null,
                ];

                // Only add subject_id if provided (kept for compatibility if select remains in other modes)
                if ($request->has('subject_id')) {
                    $data['subject_id'] = $request->subject_id;
                }

                Attendance::updateOrCreate([
                    'student_id' => $request->student_id,
                    'batch_id' => $request->batch_id,
                    'attendance_date' => $attendanceData['date'],
                ], $data);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Attendance updated successfully for ' . count($request->attendances) . ' days.'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Student bulk attendance store error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to save attendance: ' . $e->getMessage()
            ], 500);
        }
    }
}