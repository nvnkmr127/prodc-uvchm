<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\MissingAcademicYearException;
use App\Exports\AttendanceExport;
use App\Exports\StudentsExport;
use App\Exports\StudentsSampleExport;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Batch;
use App\Models\Course;
use App\Models\Holiday;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentConcession;
use App\Models\StudentFee;
use App\Services\AcademicYearService;
use App\Services\Attendance\AttendanceService;
use App\Services\BiometricMappingService;
use App\Services\ComponentPaymentService;
use App\Services\DropoutManagementService;
use App\Services\EnrollmentService;
use App\Services\SecureFileValidator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Activitylog\Models\Activity;

class StudentController extends Controller
{
    protected $componentPaymentService;

    protected $biometricMappingService;

    public function __construct(ComponentPaymentService $componentPaymentService, BiometricMappingService $biometricMappingService)
    {
        $this->componentPaymentService = $componentPaymentService;
        $this->biometricMappingService = $biometricMappingService;
    }

    public function index(Request $request)
    {
        // Start with a query builder - Respect global scopes as requested
        $query = Student::query()->with('batch.course');

        $selectedAcademicYearId = null;
        // 1. apply academic year filter (Global context)
        if ($request->filled('academic_year_id') || ! $request->has('show_all')) {
            if (\Schema::hasTable('academic_years') && \Schema::hasColumn('batches', 'academic_year_id')) {
                try {
                    $selectedYearId = app(AcademicYearService::class)->getActiveAcademicYearId();
                } catch (MissingAcademicYearException $e) {
                    $selectedYearId = null;
                }
                $selectedAcademicYearId = $request->get(
                    'academic_year_id',
                    session('selected_academic_year_id', $selectedYearId)
                );

                if ($selectedAcademicYearId) {
                    $query->whereHas('batch', function ($q) use ($selectedAcademicYearId) {
                        $q->where('academic_year_id', $selectedAcademicYearId);
                    });
                }
            }
        }

        // 2. Apply common filters (Course, Batch, Search)
        if ($request->filled('course_id')) {
            $query->whereHas('batch', function ($q) use ($request) {
                $q->where('course_id', $request->course_id);
            });
        }
        if ($request->filled('batch_id')) {
            $query->where('batch_id', $request->batch_id);
        }

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('students.name', 'like', "%{$search}%")
                    ->orWhere('students.enrollment_number', 'like', "%{$search}%")
                    ->orWhere('students.student_mobile', 'like', "%{$search}%")
                    ->orWhere('students.father_mobile', 'like', "%{$search}%")
                    ->orWhere('students.email', 'like', "%{$search}%");
            });
        }

        // 3. Capture Query for Stats (Before applying status filter)
        // OPTIMIZED: Use single query with conditional aggregation instead of 5 separate count queries
        $statsData = (clone $query)
            ->leftJoin('batches', 'students.batch_id', '=', 'batches.id')
            ->selectRaw('
                count(*) as total,
                count(case when students.status = "active" then 1 end) as active,
                count(case when students.status = "graduated" then 1 end) as graduated,
                count(case when students.status = "dropout" then 1 end) as dropout,
                count(case when batches.is_on_internship = 1 then 1 end) as on_internship
            ')
            ->first();

        $stats = [
            'total' => $statsData->total ?? 0,
            'active' => $statsData->active ?? 0,
            'graduated' => $statsData->graduated ?? 0,
            'dropout' => $statsData->dropout ?? 0,
            'on_internship' => $statsData->on_internship ?? 0,
        ];

        // 4. Apply Status Filter to main query
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 5. Apply Custom Quick Filters
        if ($request->filled('quick_filter')) {
            if ($request->quick_filter === 'recent') {
                $query->where('students.created_at', '>=', now()->subDays(30));
            } elseif ($request->quick_filter === 'no-contact') {
                $query->where(function ($q) {
                    $q->whereNull('students.student_mobile')
                        ->orWhere('students.student_mobile', '')
                        ->orWhereNull('students.email')
                        ->orWhere('students.email', '');
                });
            }
        }

        // 5. Fetch Students - OPTIMIZED: Use pagination instead of get()
        $students = $query->latest()->paginate(50)->withQueryString();

        // 6. Return response
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'html' => view('admin.students._table_body', compact('students'))->render(),
                'pagination' => (string) $students->links('pagination::bootstrap-4'), // Use Bootstrap 4 pagination
                'stats' => $stats,
                'count' => $students->total(), // Use total() for paginated result
                'firstItem' => $students->firstItem() ?? 0,
                'lastItem' => $students->lastItem() ?? 0,
            ]);
        }

        // Data for filter dropdowns
        $courses = Cache::remember('filter_dropdown_courses', now()->addDay(), function () {
            return Course::select('id', 'name')->orderBy('name')->get();
        });

        $cacheKey = 'filter_dropdown_batches_'.($selectedAcademicYearId ?? 'all');
        $batches = Cache::remember($cacheKey, now()->addDay(), function () use ($selectedAcademicYearId) {
            $query = Batch::with('course:id,name')->orderBy('name');
            if ($selectedAcademicYearId && \Schema::hasColumn('batches', 'academic_year_id')) {
                $query->where('academic_year_id', $selectedAcademicYearId);
            }

            return $query->get();
        });

        return view('admin.students.index', compact('students', 'courses', 'batches', 'stats'));
    }

    /**
     * Updated bulk actions method - REMOVE installments functionality
     */
    public function bulkActions(Request $request)
    {
        // UPDATED: Remove 'create_installments' from allowed actions
        $request->validate([
            'action' => 'required|string|in:delete,change_status,assign_batch',
            'student_ids' => 'required|array',
            'student_ids.*' => 'required|exists:students,id',
        ]);

        // Additional validation based on action
        if ($request->action === 'assign_batch') {
            $request->validate([
                'batch_id' => 'required|exists:batches,id',
            ]);
        }

        if ($request->action === 'change_status') {
            $request->validate([
                'status' => 'required|in:active,graduated,dropout',
            ]);
        }

        $students = Student::whereIn('id', $request->student_ids)->get();
        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($students as $student) {
                /** @var Student $student */
                try {
                    switch ($request->action) {
                        case 'delete':
                            $student->delete();
                            $successCount++;
                            break;

                        case 'change_status':
                            $student->update(['status' => $request->status]);
                            $successCount++;
                            break;

                        case 'assign_batch':
                            $student->update(['batch_id' => $request->batch_id]);
                            $successCount++;
                            break;

                        default:
                            throw new \Exception("Unknown action: {$request->action}");
                    }
                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = "Error processing {$student->name}: ".$e->getMessage();
                }
            }

            DB::commit();

            $message = "Bulk action completed. Success: {$successCount}, Errors: {$errorCount}";

            if ($errorCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'errors' => $errors,
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Bulk action failed: '.$e->getMessage(),
            ], 500);
        }
    }

    public function create()
    {
        $batches = Batch::with('course')->get();

        return view('admin.students.create', compact('batches'));
    }

    /**
     * Helper method to get current academic year
     */
    private function getCurrentAcademicYear(): string
    {
        try {
            $yearStr = app(AcademicYearService::class)->getCurrentAcademicYear()->name;
        } catch (MissingAcademicYearException $e) {
            $yearStr = null;
        }

        return $yearStr ?? Carbon::now()->format('Y').'-'.Carbon::now()->addYear()->format('y');
    }

    /**
     * Store a newly created student and automatically generate fee structure
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'dob' => 'nullable|date_format:Y-m-d',
            'father_name' => 'nullable|string|max:255',
            'student_mobile' => [
                'nullable',
                'string',
                'max:20',
                'unique:students,student_mobile',
                'regex:/^[6-9]\d{9}$/',
            ],
            'father_mobile' => [
                'nullable',
                'string',
                'max:20',
                'unique:students,father_mobile',
                'regex:/^[6-9]\d{9}$/',
            ],
            'village' => 'nullable|string|max:255',
            'admission_date' => 'required|date_format:Y-m-d',
            'source' => 'required|string|in:Website,Social Media,Agent,Referrals,pro,list,Student Refer,Walk-in,Other',
            'referral_name' => 'nullable|string|max:255',
            'batch_id' => 'required|exists:batches,id', // REQUIRED for fee generation
            'gender' => 'required|in:Male,Female,Other',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'is_certificate_received' => 'boolean',
            'certificate_type' => 'nullable|string|in:10th,Inter|required_if:is_certificate_received,true',
        ], [
            'student_mobile.unique' => 'This student mobile number is already registered.',
            'father_mobile.unique' => 'This father mobile number is already registered.',
            'student_mobile.regex' => 'Student mobile must be a valid 10-digit Indian number.',
            'father_mobile.regex' => 'Father mobile must be a valid 10-digit Indian number.',
            'batch_id.required' => 'Please select a batch to assign the student.',
        ]);

        // Additional validation
        if (
            $validated['student_mobile'] && $validated['father_mobile'] &&
            $validated['student_mobile'] === $validated['father_mobile']
        ) {
            return back()->withErrors([
                'father_mobile' => 'Father mobile cannot be the same as student mobile.',
            ])->withInput();
        }

        DB::beginTransaction();
        try {
            // Get batch and its fee structure
            $batch = Batch::with(['course', 'feeStructure.feeCategories'])->findOrFail($validated['batch_id']);

            // Handle photo upload
            $photoPath = null;
            if ($request->hasFile('photo')) {
                $photoPath = $this->processPhoto($request->file('photo'), 'students');
            }

            // Generate enrollment number
            $enrollmentNumber = $this->generateEnrollmentNumber($batch);

            // Create student
            $student = Student::create([
                'name' => $validated['name'],
                'dob' => $validated['dob'] ?? null,
                'father_name' => $validated['father_name'],
                'student_mobile' => $validated['student_mobile'],
                'father_mobile' => $validated['father_mobile'],
                'village' => $validated['village'],
                'admission_date' => $validated['admission_date'],
                'batch_id' => $validated['batch_id'],
                'gender' => $validated['gender'],
                'photo' => $photoPath,
                'enrollment_number' => $enrollmentNumber,
                'status' => 'active',
                'source' => $validated['source'],          // Added
                'referral_name' => $validated['referral_name'], // Added
                'is_certificate_received' => $request->has('is_certificate_received'),
                'certificate_type' => $request->certificate_type,
            ]);

            // 🎯 AUTOMATIC FEE STRUCTURE ASSIGNMENT (Using Service for installment support)
            $this->componentPaymentService->createFeeComponentsForStudent(
                $student->id,
                $batch->id,
                $this->getCurrentAcademicYear()
            );

            // Automatically generate Biometric ID using the injected service
            $this->biometricMappingService->assignBiometricCode($student);

            DB::commit();

            return redirect()->route('admin.students.index')
                ->with('success', 'Student created successfully with Biometric ID!');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Student creation failed: '.$e->getMessage());

            return back()->withInput()
                ->with('error', 'Failed to create student: '.$e->getMessage());
        }
    }

    public function show(Student $student)
    {
        // Re-enabled global scope as requested by user, but bypass for specific record lookup
        $student = Student::withoutGlobalScope('academic_year')->with([
            'batch.course',
            'studentFees.feeCategory',
        ])->findOrFail($student->id);

        // Get payment history with proper relationships and ordering
        $paymentHistory = Payment::withoutGlobalScope('academic_year')
            ->where('student_id', $student->id)
            ->with([
                'createdBy:id,name',
                'updatedBy:id,name',
                'componentItems.studentFee.feeCategory:id,name',
                'componentItems' => function ($query) {
                    $query->orderBy('created_at', 'desc');
                },
            ])
            ->orderBy('payment_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Get fee structure (already eager loaded)
        $studentFees = $student->studentFees;

        // Get recent payments for backward compatibility
        $recentPayments = $paymentHistory->take(10);

        // ✨ Calculate overall attendance across all months
        $overallAttendance = $this->calculateOverallSummary($student);
        $attendancePercentage = $overallAttendance['overall_percentage'];

        // ✨ Calculate attendance data for current month (internal summary)
        $attendanceDataFull = $this->fetchMonthlyAttendanceData($student, now()->format('Y-m'));
        $attendanceData = $attendanceDataFull['monthly'];

        // Use Overall stats for the header cards
        $attendanceData['attendance_percentage'] = $attendancePercentage;
        $attendanceData['month_name'] = 'Overall';
        $presentDays = $overallAttendance['present_days'];
        $absentDays = $overallAttendance['absent_days'];
        $totalWorkingDays = $overallAttendance['total_days'];

        // ✨ NEW: Get comprehensive activity logs
        $recentActivity = $this->getStudentActivityLogs($student);

        // Calculate financial summary
        $totalFees = $studentFees->sum('amount') ?? 0;
        $totalConcessions = $studentFees->sum('concession_amount') ?? 0;
        $totalPaid = $studentFees->sum('paid_amount') ?? 0;
        $pendingAmount = $totalFees - $totalConcessions - $totalPaid;
        $paymentPercentage = $totalFees > 0 ? round((($totalPaid + $totalConcessions) / $totalFees) * 100, 2) : 0;

        $financialSummary = [
            'total_amount' => $totalFees,
            'paid_amount' => $totalPaid,
            'concession_amount' => $totalConcessions,
            'remaining_amount' => max(0, $pendingAmount),
            'payment_percentage' => $paymentPercentage,
        ];

        return view('admin.students.show', compact(
            'student',
            'studentFees',
            'paymentHistory',
            'recentPayments',
            'financialSummary',
            'recentActivity', // ✨ Pass activity logs to view
            'attendanceData', // ✨ Pass attendance data to view
            'presentDays',
            'absentDays',
            'totalWorkingDays',
            'attendancePercentage'
        ));
    }

    public function confirmDropout(Student $student)
    {
        if ($student->status === 'dropout') {
            return redirect()->route('admin.students.show', $student)
                ->with('error', 'Student is already marked as dropout');
        }

        $financialSummary = $student->getFinancialSummary();

        return view('admin.students.confirm-dropout', compact('student', 'financialSummary'));
    }

    public function processDropout(Request $request, Student $student)
    {
        $request->validate([
            'dropout_date' => 'required|date|before_or_equal:today',
            'reason' => 'required|string|max:500',
            'confirm_preservation' => 'required|accepted',
        ]);

        $dropoutService = app(DropoutManagementService::class);
        $result = $dropoutService->processDropout($student, $request->only('dropout_date', 'reason'));

        if ($result['success']) {
            return redirect()->route('admin.students.show', $student)
                ->with('success', $result['message']);
        } else {
            return back()->withInput()
                ->with('error', $result['message']);
        }
    }

    public function reactivateStudent(Request $request, Student $student)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $dropoutService = app(DropoutManagementService::class);
        $result = $dropoutService->reactivateStudent($student, $request->reason ?? '');

        return response()->json($result);
    }

    /**
     * ✨ NEW: Get activity logs count for a student (AJAX endpoint)
     */
    public function getActivityLogsCount(Student $student)
    {
        try {
            // Count Spatie Activity Log entries for this student
            $spatieCount = Activity::where('subject_type', 'App\\Models\\Student')
                ->where('subject_id', $student->id)
                ->orWhere('causer_type', 'App\\Models\\Student')
                ->orWhere('causer_id', $student->id)
                ->count();

            // Count payment activities
            $paymentCount = Payment::where('student_id', $student->id)->count();

            // Count concession activities if the model exists
            $concessionCount = 0;
            if (class_exists('App\\Models\\StudentConcession')) {
                $concessionCount = StudentConcession::where('student_id', $student->id)->count();
            }

            // Count fee generation activities from student fees
            $feeGenerationCount = StudentFee::where('student_id', $student->id)->count();

            $totalCount = $spatieCount + $paymentCount + $concessionCount + $feeGenerationCount;

            return response()->json([
                'success' => true,
                'total_count' => $totalCount,
                'breakdown' => [
                    'spatie_activities' => $spatieCount,
                    'payments' => $paymentCount,
                    'concessions' => $concessionCount,
                    'fee_generations' => $feeGenerationCount,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting activity logs count for student '.$student->id.': '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to get activity logs count',
                'total_count' => 0,
            ], 500);
        }
    }

    /**
     * Get attendance data for a specific student and month (AJAX)
     */
    public function getAttendanceData(Request $request, Student $student)
    {
        try {
            $month = $request->input('month', now()->format('Y-m'));
            $data = $this->fetchMonthlyAttendanceData($student, $month);

            // Add overall percentage to the response for the header card
            $overall = $this->calculateOverallSummary($student);
            $data['overall_percentage'] = $overall['overall_percentage'];

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);

        } catch (\Exception $e) {
            \Log::error('Attendance data error: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Error: '.$e->getMessage()], 500);
        }
    }

    /**
     * Get overall attendance summary for student
     */
    private function getAttendanceSummary(Student $student)
    {
        $totalRecords = Attendance::where('student_id', $student->id)->count();

        $presentRecords = Attendance::where('student_id', $student->id)
            ->whereIn('status', ['present', 'late'])
            ->count();

        $overallPercentage = $totalRecords > 0 ?
            round(($presentRecords / $totalRecords) * 100, 1) : 0;

        return [
            'overall_percentage' => $overallPercentage,
            'status' => $this->getAttendanceStatus($overallPercentage),
        ];
    }

    /**
     * Get attendance status based on percentage
     */
    private function getAttendanceStatus($percentage)
    {
        if ($percentage >= 90) {
            return 'excellent';
        }
        if ($percentage >= 80) {
            return 'good';
        }
        if ($percentage >= 75) {
            return 'satisfactory';
        }

        return 'needs_improvement';
    }

    /**
     * AJAX endpoint for attendance data
     */
    // [DEAD CODE DELETED] getStudentAttendanceData was buggy and unused.

    public function edit(Student $student)
    {
        $batches = Batch::with('course')->get();

        return view('admin.students.edit', compact('student', 'batches'));
    }

    // âœ… SINGLE update() method with enhanced mobile validation
    public function update(Request $request, Student $student)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'dob' => 'nullable|date_format:Y-m-d',
            'enrollment_number' => ['required', 'string', 'max:255', Rule::unique('students')->ignore($student->id)],
            'gender' => 'required|in:Male,Female,Other',
            'father_name' => 'nullable|string|max:255',
            'student_mobile' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('students')->ignore($student->id),
                'regex:/^[6-9]\d{9}$/',
            ],
            'father_mobile' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('students')->ignore($student->id),
                'regex:/^[6-9]\d{9}$/',
            ],
            'village' => 'nullable|string|max:255',
            'admission_date' => 'required|date_format:Y-m-d',
            'source' => 'required|string|in:Website,Social Media,Agent,Referrals,pro,list,Student Refer,Walk-in,Other',
            'referral_name' => 'nullable|string|max:255',
            'batch_id' => 'nullable|exists:batches,id',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'is_certificate_received' => 'boolean',
            'certificate_type' => 'nullable|string|in:10th,Inter|required_if:is_certificate_received,true',
        ], [
            // Custom error messages
            'student_mobile.unique' => 'This student mobile number is already registered with another student.',
            'father_mobile.unique' => 'This father mobile number is already registered with another student.',
            'student_mobile.regex' => 'Student mobile number must be a valid 10-digit Indian mobile number starting with 6, 7, 8, or 9.',
            'father_mobile.regex' => 'Father mobile number must be a valid 10-digit Indian mobile number starting with 6, 7, 8, or 9.',
        ]);

        // âœ… Additional validation: Check if student and father mobiles are the same
        if (
            $validated['student_mobile'] && $validated['father_mobile'] &&
            $validated['student_mobile'] === $validated['father_mobile']
        ) {
            return back()->withErrors([
                'father_mobile' => 'Father mobile number cannot be the same as student mobile number.',
            ])->withInput();
        }

        // âœ… Additional validation: Check cross-field duplicates (excluding current student)
        if ($validated['student_mobile']) {
            $existsAsFatherMobile = Student::where('father_mobile', $validated['student_mobile'])
                ->where('id', '!=', $student->id)
                ->exists();
            if ($existsAsFatherMobile) {
                return back()->withErrors([
                    'student_mobile' => 'This mobile number is already registered as a father mobile number for another student.',
                ])->withInput();
            }
        }

        if ($validated['father_mobile']) {
            $existsAsStudentMobile = Student::where('student_mobile', $validated['father_mobile'])
                ->where('id', '!=', $student->id)
                ->exists();
            if ($existsAsStudentMobile) {
                return back()->withErrors([
                    'father_mobile' => 'This mobile number is already registered as a student mobile number for another student.',
                ])->withInput();
            }
        }

        $originalBatchId = $student->getOriginal('batch_id');

        if ($request->hasFile('photo')) {
            if ($student->photo) {
                Storage::disk('public')->delete($student->photo);
            }
            $validated['photo'] = $this->processPhoto($request->file('photo'), 'student_photos');
        }

        // âœ… CHANGED: If batch changed, update enrollment number and generate new fee components
        if ($validated['batch_id'] && $validated['batch_id'] != $originalBatchId) {
            $batch = Batch::with('course')->find($validated['batch_id']);
            $validated['enrollment_number'] = $this->generateEnrollmentNumber($batch);

            // Delete existing unpaid fee components for the old batch
            $student->studentFees()->where('status', '!=', 'paid')->delete();
        }

        // Handle checkbox boolean logic
        $validated['is_certificate_received'] = $request->has('is_certificate_received');
        $validated['certificate_type'] = $request->certificate_type;

        $student->update($validated);

        return redirect()->route('admin.students.index')->with('success', 'Student details updated successfully.');
    }

    public function destroy(Student $student)
    {
        if ($student->photo) {
            Storage::disk('public')->delete($student->photo);
        }

        $student->delete();

        return redirect()->route('admin.students.index')->with('success', 'Student deleted successfully.');
    }

    /**
     * Generate a unique enrollment number for a student in a specific batch
     */
    private function generateEnrollmentNumber(Batch $batch): string
    {
        return app(EnrollmentService::class)->generateForBatch($batch);
    }

    /**
     * Update student status
     */
    public function updateStatus(Request $request, Student $student)
    {
        $request->validate([
            'status' => 'required|in:active,graduated,dropout',
        ]);

        $student->update(['status' => $request->status]);

        return redirect()->back()->with('success', 'Student status updated successfully.');
    }

    /**
     * Get batches for a specific course (AJAX endpoint)
     */
    public function getBatchesForCourse(Course $course)
    {
        $query = $course->batches()
            ->with('course')
            ->withCount('feeStructure')
            ->select('id', 'name', 'course_id', 'start_date', 'end_date')
            ->orderBy('name');

        if (\Schema::hasTable('academic_years') && \Schema::hasColumn('batches', 'academic_year_id')) {
            try {
                $selectedYearId = app(AcademicYearService::class)->getActiveAcademicYearId();
            } catch (MissingAcademicYearException $e) {
                $selectedYearId = null;
            }
            $selectedAcademicYearId = session('selected_academic_year_id', $selectedYearId);
            if ($selectedAcademicYearId) {
                $query->where('academic_year_id', $selectedAcademicYearId);
            }
        }

        $batches = $query->get()
            ->map(function ($batch) {
                return [
                    'id' => $batch->id,
                    'name' => $batch->name,
                    'course_id' => $batch->course_id,
                    'start_date' => $batch->start_date,
                    'end_date' => $batch->end_date,
                    'has_fee_structure' => $batch->fee_structure_count > 0,
                ];
            });

        return response()->json($batches);
    }

    /**
     * Show biometric mapping interface
     */
    public function biometricMapping(Request $request)
    {
        // Get basic statistics - use a single query for performance
        $statsData = Student::where('status', 'active')
            ->selectRaw('count(*) as total, count(biometric_employee_code) as mapped')
            ->first();

        $totalStudents = $statsData->total ?? 0;
        $mappedStudents = $statsData->mapped ?? 0;
        $unmappedStudents = $totalStudents - $mappedStudents;
        $mappingPercentage = $totalStudents > 0 ? round(($mappedStudents / $totalStudents) * 100, 2) : 0;

        $stats = [
            'total_students' => $totalStudents,
            'mapped_students' => $mappedStudents,
            'unmapped_students' => $unmappedStudents,
            'mapping_percentage' => $mappingPercentage,
        ];

        // Start query for students
        $query = Student::where('status', 'active')->with(['batch.course']);

        // Apply search filter if provided
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('enrollment_number', 'like', "%{$search}%")
                    ->orWhere('biometric_employee_code', 'like', "%{$search}%");
            });
        }

        // Use pagination for performance
        $studentsFetch = $query->paginate(100)->withQueryString();

        $students = $studentsFetch->map(function ($student) {
            return [
                'id' => $student->id,
                'name' => $student->name,
                'enrollment_number' => $student->enrollment_number,
                'biometric_code' => $student->biometric_employee_code,
                'batch_name' => $student->batch->name ?? 'No Batch',
                'course_name' => $student->batch->course->name ?? 'No Course',
                'suggested_code' => $this->biometricMappingService->generateBiometricCode($student),
            ];
        });

        return view('admin.students.biometric-mapping', compact('stats', 'students', 'studentsFetch'));
    }

    /**
     * Import biometric mappings from Excel/CSV
     */
    public function importBiometricMapping(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:5120',
        ]);

        // Enhanced file validation using SecureFileValidator
        $fileValidator = new SecureFileValidator;
        $validationResult = $fileValidator->validateFile($request->file('file'), ['xlsx', 'xls', 'csv']);

        if (! $validationResult['valid']) {
            return back()->with('error', $validationResult['error']);
        }

        try {
            $results = $this->biometricMappingService->importBiometricMappings($request->file('file'));

            if ($results['success']) {
                $message = "Successfully imported {$results['imported_count']} biometric codes";
                if (! empty($results['errors'])) {
                    $message .= ' with '.count($results['errors']).' errors';
                }

                return back()->with('success', $message)
                    ->with('import_errors', $results['errors'] ?? []);
            } else {
                return back()->with('error', 'Import failed: '.$results['error']);
            }

        } catch (\Exception $e) {
            Log::error('Biometric import failed', ['error' => $e->getMessage()]);

            return back()->with('error', 'Import failed: '.$e->getMessage());
        }
    }

    /**
     * Export unmapped students to Excel
     */
    public function exportUnmappedStudents()
    {
        try {
            return $this->biometricMappingService->exportUnmappedStudents();
        } catch (\Exception $e) {
            Log::error('Export unmapped students failed', ['error' => $e->getMessage()]);

            return back()->with('error', 'Export failed: '.$e->getMessage());
        }
    }

    /**
     * Auto-generate biometric codes for all unmapped students
     */
    public function autoGenerateBiometricCodes()
    {
        try {
            $results = $this->biometricMappingService->autoGenerateAllCodes();

            $message = "Auto-generated {$results['success_count']} biometric codes";
            if ($results['error_count'] > 0) {
                $message .= " with {$results['error_count']} errors";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'results' => $results,
            ]);

        } catch (\Exception $e) {
            Log::error('Auto-generate biometric codes failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to auto-generate codes: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download sample Excel file for bulk import
     */
    public function downloadSample()
    {
        return Excel::download(new StudentsSampleExport, 'students_sample.xlsx');
    }

    /**
     * Export students to Excel
     */
    public function export(Request $request)
    {
        $query = Student::with('batch.course');

        if ($request->filled('student_ids') && is_array($request->student_ids)) {
            $query->whereIn('id', $request->student_ids);
        }

        if ($request->filled('course_id')) {
            $query->whereHas('batch', function ($q) use ($request) {
                $q->where('course_id', $request->course_id);
            });
        }
        if ($request->filled('batch_id')) {
            $query->where('batch_id', $request->batch_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $students = $query->get();

        return Excel::download(new StudentsExport($students), 'students_export_'.now()->format('Y-m-d').'.xlsx');
    }

    /**
     * Get student's profile photo URL with fallback to dummy avatar
     */
    public static function getStudentPhotoUrl(Student $student, $size = 100): string
    {
        if ($student->photo && Storage::disk('public')->exists($student->photo)) {
            return asset('storage/'.$student->photo);
        }

        // Generate dummy avatar using UI Avatars service
        $name = urlencode($student->name);
        $backgroundColor = '4e73df'; // Primary color
        $color = 'fff'; // White text

        return "https://ui-avatars.com/api/?name={$name}&size={$size}&background={$backgroundColor}&color={$color}";
    }

    /**
     * Get unpaid fees for a student (API endpoint)
     */
    public function getUnpaidFees(Student $student)
    {
        $unpaidFees = $student->studentFees()
            ->with('feeCategory')
            ->whereIn('status', ['unpaid', 'partial'])
            ->whereRaw('amount - paid_amount - concession_amount > 0')
            ->get()
            ->map(function ($fee) {
                return [
                    'id' => $fee->id,
                    'amount' => $fee->amount,
                    'paid_amount' => $fee->paid_amount ?? 0,
                    'concession_amount' => $fee->concession_amount ?? 0,
                    'remaining_amount' => $fee->amount - ($fee->paid_amount ?? 0) - ($fee->concession_amount ?? 0),
                    'due_date' => $fee->due_date,
                    'due_date_formatted' => $fee->due_date ? Carbon::parse($fee->due_date)->format('M d, Y') : null,
                    'status' => $fee->status,
                    'fee_category' => [
                        'id' => $fee->feeCategory->id,
                        'name' => $fee->feeCategory->name,
                    ],
                ];
            });

        return response()->json($unpaidFees);
    }

    public function getUnassignedFeeComponents($studentId)
    {
        try {
            $student = Student::with('batch')->findOrFail($studentId);

            if (! $student->batch_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student is not assigned to any batch',
                ]);
            }

            // Get the fee structure ID from the relationship
            // Check if the Batch model has a feeStructure relationship
            $feeStructureId = null;

            // Try to get fee_structure_id from batch table directly first
            $batch = DB::table('batches')
                ->select('id', 'name', 'course_id')
                ->where('id', $student->batch_id)
                ->first();

            // Since batches table doesn't have fee_structure_id,
            // we need to get it from fee_structures table using batch_id
            $feeStructure = DB::table('fee_structures')
                ->where('batch_id', $student->batch_id)
                ->first();

            if (! $feeStructure) {
                return response()->json([
                    'success' => false,
                    'message' => 'No fee structure found for this batch',
                ]);
            }

            // Get assigned category IDs for this student
            $assignedIds = DB::table('student_fees')
                ->where('student_id', $studentId)
                ->pluck('fee_category_id')
                ->toArray();

            // Get unassigned categories with amounts from pivot table
            $unassignedCategories = DB::table('fee_categories')
                ->leftJoin('fee_structure_fee_category', function ($join) use ($feeStructure) {
                    $join->on('fee_categories.id', '=', 'fee_structure_fee_category.fee_category_id')
                        ->where('fee_structure_fee_category.fee_structure_id', '=', $feeStructure->id);
                })
                ->whereNotIn('fee_categories.id', $assignedIds)
                ->select(
                    'fee_categories.id',
                    'fee_categories.name',
                    'fee_categories.description',
                    'fee_structure_fee_category.amount'
                )
                ->get();

            return response()->json([
                'success' => true,
                'debug' => [
                    'batch_id' => $student->batch_id,
                    'fee_structure_id' => $feeStructure->id,
                    'assigned_ids' => $assignedIds,
                ],
                'components' => $unassignedCategories->map(function ($category) {
                    $amount = $category->amount ?? 0;

                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'description' => $category->description ?? '',
                        'amount' => (float) $amount,
                        'warning' => $amount > 0 ? null : 'Amount not set - please specify when assigning',
                    ];
                }),
            ]);

        } catch (\Exception $e) {
            \Log::error('Error getting unassigned fee components', [
                'student_id' => $studentId,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function assignFeeComponent(Request $request, $studentId)
    {
        try {
            $request->validate([
                'fee_category_id' => 'required|exists:fee_categories,id',
                'amount' => 'required|numeric|min:0',
            ]);

            $student = Student::findOrFail($studentId);

            // Check if already assigned
            $exists = DB::table('student_fees')
                ->where('student_id', $studentId)
                ->where('fee_category_id', $request->fee_category_id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'This fee component is already assigned to the student',
                ]);
            }

            // Get the fee structure ID from the batch
            $feeStructure = DB::table('fee_structures')
                ->where('batch_id', $student->batch_id)
                ->first();

            if (! $feeStructure) {
                return response()->json([
                    'success' => false,
                    'message' => 'No fee structure found for this student\'s batch',
                ]);
            }

            // Calculate academic year (April to March cycle)
            $currentYear = date('Y');
            $currentMonth = date('n');

            if ($currentMonth >= 4) {
                // April to December = current year to next year
                $academicYear = $currentYear.'-'.($currentYear + 1);
            } else {
                // January to March = previous year to current year
                $academicYear = ($currentYear - 1).'-'.$currentYear;
            }

            // Insert into student_fees with all required fields
            DB::table('student_fees')->insert([
                'student_id' => $studentId,
                'fee_structure_id' => $feeStructure->id,
                'fee_category_id' => $request->fee_category_id,
                'amount' => $request->amount,
                'paid_amount' => 0,
                'concession_amount' => 0,
                'status' => 'unpaid',
                'due_date' => now()->addDays(30),
                'academic_year' => $academicYear, // ← ADD THIS
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Fee component assigned successfully',
            ]);

        } catch (\Exception $e) {
            \Log::error('Error assigning fee component', [
                'student_id' => $studentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export attendance data
     */
    public function exportAttendanceData(Request $request, Student $student, $format)
    {
        try {
            // Get the same data as the AJAX endpoint
            $attendanceDataResponse = $this->getAttendanceData($request, $student);
            $attendanceData = json_decode($attendanceDataResponse->getContent(), true);

            if (! $attendanceData['success']) {
                return back()->with('error', 'Failed to export attendance data');
            }

            $data = $attendanceData['data'];

            if ($format === 'pdf') {
                return $this->exportAttendanceToPDF($student, $data);
            } elseif ($format === 'excel') {
                return $this->exportAttendanceToExcel($student, $data);
            }

            return back()->with('error', 'Invalid export format');

        } catch (\Exception $e) {
            \Log::error('Export attendance failed', [
                'student_id' => $student->id,
                'format' => $format,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Export failed: '.$e->getMessage());
        }
    }

    /**
     * Bulk update biometric codes
     */
    public function bulkUpdateBiometricMapping(Request $request, AttendanceService $attendanceService)
    {
        $request->validate([
            'mappings' => 'required|array',
            'mappings.*.student_id' => 'required|exists:students,id',
            'mappings.*.biometric_code' => 'required|string|distinct',
        ]);

        try {
            // Delegate to service
            $result = $attendanceService->bulkUpdateBiometricCodes($request->mappings);

            $status = ($result['error_count'] > 0) ? 'warning' : 'success';

            return response()->json([
                'success' => true,
                'message' => "Updated {$result['success_count']} students. Failed: {$result['error_count']}",
                'details' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk update failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Auto-generate biometric codes for students without them
     */
    public function autoGenerateBiometricMapping(Request $request, AttendanceService $attendanceService)
    {
        try {
            // Delegate to service
            $result = $attendanceService->autoGenerateBiometricCodes();

            return response()->json([
                'success' => true,
                'message' => "Auto-generated codes for {$result['success_count']} students. Failed: {$result['error_count']}",
                'details' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Auto-generation failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get suggestions for student source fields (AJAX)
     */
    public function getSuggestions(Request $request)
    {
        $search = $request->input('query');
        $source = $request->input('source');

        if (empty($search) || strlen($search) < 2) {
            return response()->json([]);
        }

        $suggestions = [];

        if ($source === 'Student Refer') {
            // Suggest existing student names with context
            // We want students whose own name matches the search
            $suggestions = Student::where('name', 'like', "%{$search}%")
                ->with('batch:id,name')
                ->limit(10)
                ->get(['name', 'enrollment_number', 'batch_id'])
                ->map(function ($student) {
                    return [
                        'value' => $student->name,
                        'label' => $student->name,
                        'extra' => $student->enrollment_number.' ('.($student->batch->name ?? 'No Batch').')',
                    ];
                });
        } elseif (in_array($source, ['Agent', 'Referrals', 'pro', 'list', 'Other'])) {
            // Suggest previously used referral names for this source
            // Group by referral_name to get unique names and their usage count
            $suggestions = Student::where('source', $source)
                ->where('referral_name', 'like', "%{$search}%")
                ->whereNotNull('referral_name')
                ->select('referral_name', \DB::raw('count(*) as total'))
                ->groupBy('referral_name')
                ->orderByDesc('total') // Suggest most frequent ones first
                ->limit(10)
                ->get()
                ->map(function ($item) {
                    return [
                        'value' => $item->referral_name,
                        'label' => $item->referral_name,
                        'extra' => $item->total.' Referral'.($item->total !== 1 ? 's' : ''),
                    ];
                });
        }

        return response()->json($suggestions);
    }

    /**
     * Process, resize and compress student photo.
     */
    private function processPhoto($file, $folder)
    {
        if (! str_starts_with($file->getMimeType(), 'image/')) {
            return $file->store($folder, 'public');
        }

        $image = imagecreatefromstring(file_get_contents($file->getRealPath()));
        $width = imagesx($image);
        $height = imagesy($image);

        $newHeight = 300;
        if ($height > $newHeight) {
            $newWidth = (int) ($width * ($newHeight / $height));
        } else {
            $newWidth = $width;
            $newHeight = $height;
        }

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
        imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);

        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        $filename = time().'_'.Str::random(10).'.jpg';
        $path = $folder.'/'.$filename;
        $fullPath = storage_path('app/public/'.$path);

        if (! file_exists(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        // Save as heavily compressed JPEG for student photos
        imagejpeg($resized, $fullPath, 75);

        imagedestroy($image);
        imagedestroy($resized);

        return $path;
    }

    /**
     * Calculate overall summary across all months
     */
    private function calculateOverallSummary($student)
    {
        // 1. Determine Start Date (Join Date)
        $startDate = $student->admission_date ? Carbon::parse($student->admission_date)->startOfDay() : $student->created_at->startOfDay();

        // 2. Determine End Date (Today)
        $endDate = now();

        // 3. Use the same core analysis logic as monthly
        $stats = $this->calculateAttendanceStatsForPeriod($student, $startDate, $endDate);

        $overallPercentage = $stats['percentage'];

        // Determine status based on percentage
        $status = 'needs_improvement';
        if ($overallPercentage >= 90) {
            $status = 'excellent';
        } elseif ($overallPercentage >= 75) {
            $status = 'good';
        } elseif ($overallPercentage >= 60) {
            $status = 'satisfactory';
        }

        return [
            'overall_percentage' => $overallPercentage,
            'total_days' => $stats['total_working_days'],
            'present_days' => $stats['present'] + $stats['late'] + $stats['internship'],
            'absent_days' => $stats['absent'],
            'status' => $status,
        ];
    }

    /**
     * Helper to fetch comprehensive attendance data for a student and month
     * valid for both Controller and AJAX use
     */
    private function fetchMonthlyAttendanceData(Student $student, $monthInput)
    {
        $startDate = Carbon::parse($monthInput)->startOfMonth();
        $endDate = Carbon::parse($monthInput)->endOfMonth();

        $stats = $this->calculateAttendanceStatsForPeriod($student, $startDate, $endDate);

        $biometricSummary = [
            'valid_working_days' => $stats['present'] + $stats['late'],
            'average_working_hours' => ($stats['present'] + $stats['late']) > 0 ? round($stats['total_work_hours'] / ($stats['present'] + $stats['late']), 1) : 0,
            'late_arrivals' => $stats['late_arrivals'],
            'early_departures' => $stats['early_departures'],
            'average_check_in' => '-',
            'average_check_out' => '-',
        ];

        return [
            'calendar' => $stats['calendar'],
            'biometric_summary' => $biometricSummary,
            'monthly' => [
                'records' => $stats['attendances']->values(),
                'present_days' => $stats['present'],
                'absent_days' => $stats['absent'],
                'late_days' => $stats['late'],
                'excused_days' => $stats['excused'],
                'internship_days' => $stats['internship'],
                'month_name' => $startDate->format('F Y'),
            ],
            'summary' => [
                'overall_percentage' => $stats['percentage'],
                'status' => $stats['percentage'] >= 75 ? 'good' : 'needs_improvement',
            ],
            'overall_percentage' => $stats['percentage'], // for AJAX consistency
        ];
    }

    /**
     * ✨ NEW: Get comprehensive activity logs for a student
     */
    private function getStudentActivityLogs(Student $student, $limit = 20)
    {
        // Get Spatie Activity Log entries for this student - FIXED query grouping
        $spatieActivities = Activity::where(function ($query) use ($student) {
            $query->where(function ($q) use ($student) {
                $q->where('subject_type', 'App\\Models\\Student')
                    ->where('subject_id', $student->id);
            })->orWhere(function ($q) use ($student) {
                $q->where('causer_type', 'App\\Models\\Student')
                    ->where('causer_id', $student->id);
            });
        })
            ->with('causer')
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get()
            // Removed toBase() to allow relationship access
            ->map(function ($activity) {
                return [
                    'type' => 'system',
                    'icon' => $this->getActivityIcon($activity->description),
                    'title' => $activity->description,
                    'description' => $activity->description,
                    'user' => $activity->causer ? $activity->causer->name : 'System',
                    'timestamp' => $activity->created_at,
                    'properties' => $activity->properties->toArray(),
                    'color' => 'primary',
                ];
            });

        // Get payment activities - FIXED with global scope bypass
        $paymentActivities = Payment::withoutGlobalScope('academic_year')
            ->where('student_id', $student->id)
            ->with(['createdBy', 'componentItems.studentFee.feeCategory'])
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get()
            // Removed toBase() to ensure relationships work
            ->map(function ($payment) {
                $amount = $payment->amount ?? 0;
                $method = $payment->payment_method ?? 'Unknown';

                return [
                    'type' => 'payment',
                    'icon' => 'fa-money-bill-wave',
                    'title' => 'Payment Received',
                    'description' => 'Payment of ₹'.number_format($amount, 2).' received via '.ucfirst($method),
                    'user' => optional($payment->createdBy)->name ?? 'System',
                    'timestamp' => $payment->created_at,
                    'properties' => [
                        'amount' => $amount,
                        'method' => $method,
                        'receipt' => $payment->receipt_number ?? 'N/A',
                        'components' => $payment->componentItems ? $payment->componentItems->count() : 0,
                    ],
                    'color' => 'success',
                ];
            });

        // Get concession activities from student_concessions table
        $concessionActivities = collect();
        if (class_exists('App\\Models\\StudentConcession')) {
            // Load with only relationships whose FK columns actually exist in the table
            $concessionActivities = StudentConcession::where('student_id', $student->id)
                ->with(['appliedBy', 'feeCategory'])
                ->orderBy('created_at', 'desc')
                ->take($limit)
                ->get()
                ->map(function ($concession) {
                    // Resolve category name via feeCategory relationship (uses fee_category_id, which exists)
                    $categoryName = optional($concession->feeCategory)->name ?? 'Unknown';

                    return [
                        'type' => 'concession',
                        'icon' => 'fa-tag',
                        'title' => 'Discount / Concession Applied',
                        'description' => 'Concession of ₹'.number_format($concession->concession_amount ?? 0, 2).' applied to '.$categoryName,
                        'user' => optional($concession->appliedBy)->name ?? 'System',
                        'timestamp' => $concession->applied_at ?? $concession->created_at,
                        'properties' => [
                            'amount' => $concession->concession_amount ?? 0,
                            'reason' => $concession->notes ?? 'N/A',
                            'status' => 'applied',
                        ],
                        'color' => 'warning',
                    ];
                });
        }

        // Get attendance activities
        $attendanceActivities = Attendance::withoutGlobalScope('academic_year')
            ->where('student_id', $student->id)
            ->with(['markedBy', 'batch'])
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get()
            ->map(function ($attendance) {
                return [
                    'type' => 'attendance',
                    'icon' => 'fa-user-check',
                    'title' => 'Attendance Marked',
                    'description' => 'Marked as '.ucfirst($attendance->status).($attendance->batch ? ' for '.$attendance->batch->name : ''),
                    'user' => optional($attendance->markedBy)->name ?? 'System',
                    'timestamp' => $attendance->created_at,
                    'properties' => [
                        'status' => $attendance->status,
                        'date' => $attendance->attendance_date?->format('Y-m-d'),
                        'notes' => $attendance->notes ?? 'N/A',
                    ],
                    'color' => $attendance->status === 'present' ? 'success' : ($attendance->status === 'absent' ? 'danger' : 'warning'),
                ];
            });

        // Get fee generation activities from student fees - FIXED with global scope bypass
        $feeActivities = collect();
        $recentFees = StudentFee::withoutGlobalScope('academic_year')
            ->where('student_id', $student->id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        foreach ($recentFees as $fee) {
            $feeActivities->push([
                'type' => 'fee_generation',
                'icon' => 'fa-file-invoice-dollar',
                'title' => 'Fee Component Generated',
                'description' => 'Fee component created: '.optional($fee->feeCategory)->name ?? 'Unknown',
                'user' => 'System',
                'timestamp' => $fee->created_at,
                'properties' => [
                    'amount' => $fee->amount ?? 0,
                    'due_date' => $fee->due_date ?? 'N/A',
                ],
                'color' => 'info',
            ]);
        }

        // Merge and sort all activities
        $allActivities = $spatieActivities->toBase()
            ->merge($paymentActivities)
            ->merge($attendanceActivities)
            ->merge($concessionActivities)
            ->merge($feeActivities)
            ->sortByDesc('timestamp')
            ->values() // Reset keys to avoid any getKey issues
            ->take($limit);

        return $allActivities;
    }

    /**
     * Export to PDF
     */
    private function exportAttendanceToPDF($student, $data)
    {
        $pdf = Pdf::loadView('admin.students.attendance-export-pdf', [
            'student' => $student,
            'data' => $data,
            'generated_at' => now(),
        ]);

        $filename = "attendance_{$student->enrollment_number}_".now()->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Export to Excel
     */
    private function exportAttendanceToExcel($student, $data)
    {
        // You can use Maatwebsite\Excel for this
        // This is a placeholder implementation
        $filename = "attendance_{$student->enrollment_number}_".now()->format('Y-m-d').'.xlsx';

        // Wrap data because AttendanceExport expects array with 'data' key
        return Excel::download(new AttendanceExport(['data' => $data]), $filename);
    }

    /**
     * Core logic to calculate attendance stats for a specific date range.
     * This logic accounts for weekends, holidays, internships, and joining dates.
     */
    private function calculateAttendanceStatsForPeriod(Student $student, Carbon $startDate, Carbon $endDate)
    {
        $todayStr = now()->format('Y-m-d');

        // 1. Fetch Attendance Records
        $attendances = Attendance\Attendance::where('student_id', $student->id)
            ->whereBetween('attendance_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get()
            ->mapWithKeys(function ($item) {
                $d = is_string($item->attendance_date)
                    ? substr($item->attendance_date, 0, 10)
                    : $item->attendance_date->format('Y-m-d');

                return [$d => $item];
            });

        // 2. Fetch Holidays
        $holidays = Holiday::whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->pluck('name', 'date')->toArray();

        // 3. Daily Punch Counts (for Low Attendance check)
        $dailyCounts = Attendance\Attendance::whereBetween('attendance_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->selectRaw('DATE(attendance_date) as date, count(distinct student_id) as count')
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // 4. effective start dates
        $profileStartDate = $student->admission_date ? Carbon::parse($student->admission_date)->startOfDay() : $student->created_at->startOfDay();
        $firstBiometricUse = Attendance\Attendance::where('student_id', $student->id)
            ->whereIn('status', ['present', 'late'])
            ->orderBy('attendance_date', 'asc')
            ->value('attendance_date');

        // 5. Settings
        $isOnInternship = $student->batch && $student->batch->is_on_internship;
        $internshipStartDate = $isOnInternship ? $student->batch->internship_start_date : null;

        // 6. Counters
        $present = 0;
        $absent = 0;
        $late = 0;
        $excused = 0;
        $internship = 0;
        $totalWorkHours = 0;
        $lateArrivals = 0;
        $earlyDepartures = 0;
        $calendar = [];

        // 7. Iterate
        $current = $startDate->copy();
        while ($current <= $endDate) {
            $dateStr = $current->format('Y-m-d');
            $isFuture = ($dateStr > $todayStr);
            $isPast = ($dateStr < $todayStr);
            $isWeekend = $current->isSunday();
            $isExplicitHoliday = isset($holidays[$dateStr]);

            $isLowAttendanceHoliday = false;
            if (! $isFuture && ! $isWeekend && ! $isExplicitHoliday) {
                $dayPunchCount = $dailyCounts[$dateStr] ?? 0;
                if ($dayPunchCount < 10) {
                    $isLowAttendanceHoliday = true;
                }
            }
            $isEffectiveHoliday = $isExplicitHoliday || $isLowAttendanceHoliday;

            $isBeforeProfile = $current->lt($profileStartDate);
            $shouldIgnoreForAbsent = $isBeforeProfile || is_null($firstBiometricUse);

            $status = 'none';
            $checkIn = '-';
            $checkOut = '-';
            $remarks = '';
            $workHours = 0;

            if (isset($attendances[$dateStr])) {
                $att = $attendances[$dateStr];
                $status = strtolower(trim($att->status));
                if (! $isFuture && $status != 'none') {
                    if ($status == 'present') {
                        $present++;
                    } elseif ($status == 'late') {
                        $late++;
                        $lateArrivals++;
                    } elseif ($status == 'absent') {
                        $absent++;
                    } elseif ($status == 'excused') {
                        $excused++;
                    }
                }
                $checkIn = $att->check_in_time ? Carbon::parse($att->check_in_time)->format('h:i A') : '-';
                $checkOut = $att->check_out_time ? Carbon::parse($att->check_out_time)->format('h:i A') : '-';
                $remarks = $att->remarks;
                if ($isLowAttendanceHoliday) {
                    $remarks = $remarks ? $remarks.' (Holiday Declared)' : 'Holiday Declared';
                }
                if ($att->check_in_time && $att->check_out_time) {
                    $diff = Carbon::parse($att->check_in_time)->diffInHours(Carbon::parse($att->check_out_time));
                    $workHours = number_format($diff, 1);
                    $totalWorkHours += $diff;
                }
            } else {
                if ($shouldIgnoreForAbsent || $isFuture) {
                    $status = 'none';
                } elseif ($isWeekend) {
                    $status = 'weekend';
                } elseif ($isEffectiveHoliday) {
                    $status = 'holiday';
                    $remarks = $isExplicitHoliday ? $holidays[$dateStr] : 'Holiday';
                } else {
                    if ($isPast) {
                        $isInternshipDay = $isOnInternship && (! $internshipStartDate || $current->gte(Carbon::parse($internshipStartDate)));
                        if ($isInternshipDay) {
                            $internship++;
                            $status = 'internship';
                            $checkIn = 'OJT';
                            $remarks = 'On Internship';
                        } else {
                            $absent++;
                            $status = 'absent';
                            $remarks = 'Absent';
                        }
                    } else {
                        $status = 'none';
                    }
                }
            }

            $calendar[$dateStr] = [
                'status' => $status,
                'check_in_time' => $checkIn,
                'check_out_time' => $checkOut,
                'working_hours' => $workHours,
                'remarks' => $remarks,
                'is_late_arrival' => ($status == 'late'),
                'is_early_departure' => false,
            ];
            $current->addDay();
        }

        $totalCalculatedDays = $present + $late + $absent + $excused + $internship;
        $percentage = $totalCalculatedDays > 0 ? round((($present + $late + $internship) / $totalCalculatedDays) * 100, 1) : 0;

        return [
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'excused' => $excused,
            'internship' => $internship,
            'total_working_days' => $totalCalculatedDays,
            'percentage' => $percentage,
            'calendar' => $calendar,
            'total_work_hours' => $totalWorkHours,
            'late_arrivals' => $lateArrivals,
            'early_departures' => $earlyDepartures,
            'attendances' => $attendances,
        ];
    }

    /**
     * Get icon for activity type
     */
    private function getActivityIcon($description)
    {
        $description = strtolower($description);

        if (strpos($description, 'payment') !== false) {
            return 'fa-money-bill-wave';
        }
        if (strpos($description, 'concession') !== false) {
            return 'fa-percent';
        }
        if (strpos($description, 'created') !== false) {
            return 'fa-plus-circle';
        }
        if (strpos($description, 'updated') !== false) {
            return 'fa-edit';
        }
        if (strpos($description, 'deleted') !== false) {
            return 'fa-trash';
        }
        if (strpos($description, 'login') !== false) {
            return 'fa-sign-in-alt';
        }
        if (strpos($description, 'fee') !== false) {
            return 'fa-file-invoice-dollar';
        }

        return 'fa-info-circle';
    }
}
