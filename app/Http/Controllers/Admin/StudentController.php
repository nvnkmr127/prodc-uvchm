<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Batch;
use App\Models\Course;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\Setting;
use App\Services\ComponentPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Maatwebsite\Excel\Validators\ValidationException;
use App\Imports\StudentsImport;
use App\Exports\StudentsSampleExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\StudentsExport;
use Spatie\Activitylog\Models\Activity;
use App\Http\Requests\StudentFormRequest;
use App\Models\StudentFee;
use App\Models\Payment;
use App\Services\BiometricMappingService;
use App\Services\SecureFileValidator;
use Barryvdh\DomPDF\Facade\Pdf;

class StudentController extends Controller
{
    protected $componentPaymentService;
    protected $biometricMappingService;

    public function __construct(ComponentPaymentService $componentPaymentService, BiometricMappingService $biometricMappingService)
    {
        $this->componentPaymentService = $componentPaymentService;
        $this->biometricMappingService = $biometricMappingService;
    }

    private function generateFeeComponentsForStudentUsingService(Student $student, Batch $batch): void
    {
        try {
            if (!$batch->feeStructure) {
                Log::warning("No fee structure found for batch: {$batch->name}");
                return;
            }

            // Use the existing service method
            $result = $this->componentPaymentService->createFeeComponentsForBatch(
                $batch->id,
                $batch->feeStructure->id,
                $this->getCurrentAcademicYear()
            );

            if ($result['success']) {
                Log::info("Fee components created via service for student: {$student->name}");
            } else {
                Log::error("Failed to create fee components for student: {$student->name}");
            }
        } catch (\Exception $e) {
            Log::error("Service error creating fee components: " . $e->getMessage());
        }
    }


    public function index(Request $request)
    {
        // Start with a query builder - Respect global scopes as requested
        $query = Student::query()->with('batch.course');

        // 1. apply academic year filter (Global context)
        if ($request->filled('academic_year_id') || !$request->has('show_all')) {
            if (\Schema::hasTable('academic_years') && \Schema::hasColumn('batches', 'academic_year_id')) {
                $selectedAcademicYearId = $request->get(
                    'academic_year_id',
                    session('selected_academic_year_id', \App\Models\AcademicYear::where('is_current', true)->value('id'))
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
            ->join('batches', 'students.batch_id', '=', 'batches.id')
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

        // 4. Apply Status Filter to main query (Default to active, unless searching)
        if (!$request->has('status') && !$request->has('show_all') && !$request->filled('search')) {
            $query->where('status', 'active');
        } elseif ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 5. Fetch Students - OPTIMIZED: Use pagination instead of get()
        $students = $query->latest()->paginate(50)->withQueryString();

        // 6. Return response
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'html' => view('admin.students._table_body', compact('students'))->render(),
                'pagination' => (string) $students->links('pagination::bootstrap-5'), // Use Bootstrap 5 pagination
                'stats' => $stats,
                'count' => $students->total() // Use total() for paginated result
            ]);
        }

        // Data for filter dropdowns
        $courses = Course::select('id', 'name')->orderBy('name')->get();
        $batches = Batch::with('course:id,name')->orderBy('name')->get();

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
                'batch_id' => 'required|exists:batches,id'
            ]);
        }

        if ($request->action === 'change_status') {
            $request->validate([
                'status' => 'required|in:active,inactive,graduated,dropout'
            ]);
        }

        $students = Student::whereIn('id', $request->student_ids)->get();
        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($students as $student) {
                /** @var \App\Models\Student $student */
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
                    $errors[] = "Error processing {$student->name}: " . $e->getMessage();
                }
            }

            DB::commit();

            $message = "Bulk action completed. Success: {$successCount}, Errors: {$errorCount}";

            if ($errorCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'errors' => $errors
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Bulk action failed: ' . $e->getMessage()
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
        return \App\Models\AcademicYear::where('is_current', true)->value('name') 
            ?? (date('n') >= 4 ? date('Y') . '-' . (date('Y') + 1) : (date('Y') - 1) . '-' . date('Y'));
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
                'regex:/^[6-9]\d{9}$/'
            ],
            'father_mobile' => [
                'nullable',
                'string',
                'max:20',
                'unique:students,father_mobile',
                'regex:/^[6-9]\d{9}$/'
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
                'father_mobile' => 'Father mobile cannot be the same as student mobile.'
            ])->withInput();
        }

        DB::beginTransaction();
        try {
            // Get batch and its fee structure
            $batch = Batch::with(['course', 'feeStructure.feeCategories'])->findOrFail($validated['batch_id']);

            // Handle photo upload
            $photoPath = null;
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('students', 'public');
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

            // 🎯 AUTOMATIC FEE STRUCTURE ASSIGNMENT
            $this->generateFeeComponentsForStudent($student, $batch);

            // Automatically generate Biometric ID using the injected service
            $this->biometricMappingService->assignBiometricCode($student);

            DB::commit();

            return redirect()->route('admin.students.index')
                ->with('success', 'Student created successfully with Biometric ID!');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Student creation failed: ' . $e->getMessage());

            return back()->withInput()
                ->with('error', 'Failed to create student: ' . $e->getMessage());
        }
    }

    /**
     * AUTO-GENERATE FEE COMPONENTS FOR NEW STUDENT
     */
    private function generateFeeComponentsForStudent(Student $student, Batch $batch): void
    {
        // Check if batch has a fee structure
        if (!$batch->feeStructure) {
            Log::warning("No fee structure found for batch: {$batch->name}");
            return;
        }

        $feeStructure = $batch->feeStructure;
        $academicYear = $this->getCurrentAcademicYear();

        // Create one StudentFee record per fee category (no installments)
        foreach ($feeStructure->feeCategories as $category) {
            StudentFee::create([
                'student_id' => $student->id,
                'fee_structure_id' => $feeStructure->id,
                'fee_category_id' => $category->id,
                'academic_year' => $academicYear,
                'amount' => $category->pivot->amount,         // Full amount
                'due_date' => now()->addDays(30),            // 30 days from creation
                'status' => 'unpaid',
                'installment_number' => 1,                   // Always 1 (no installments)
                'total_installments' => 1,                   // Always 1 (no installments)
            ]);
        }

        Log::info("Fee components created for student: {$student->name}");
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
                }
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
            'payment_percentage' => $paymentPercentage
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
                    'color' => 'primary'
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
                    'description' => 'Payment of ₹' . number_format($amount, 2) . ' received via ' . ucfirst($method),
                    'user' => optional($payment->createdBy)->name ?? 'System',
                    'timestamp' => $payment->created_at,
                    'properties' => [
                        'amount' => $amount,
                        'method' => $method,
                        'receipt' => $payment->receipt_number ?? 'N/A',
                        'components' => $payment->componentItems ? $payment->componentItems->count() : 0
                    ],
                    'color' => 'success'
                ];
            });

        // Get concession activities from student_concessions table
        $concessionActivities = collect();
        if (class_exists('App\\Models\\StudentConcession')) {
            // Load with only relationships whose FK columns actually exist in the table
            $concessionActivities = \App\Models\StudentConcession::where('student_id', $student->id)
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
                        'description' => 'Concession of ₹' . number_format($concession->concession_amount ?? 0, 2) . ' applied to ' . $categoryName,
                        'user' => optional($concession->appliedBy)->name ?? 'System',
                        'timestamp' => $concession->applied_at ?? $concession->created_at,
                        'properties' => [
                            'amount' => $concession->concession_amount ?? 0,
                            'reason' => $concession->notes ?? 'N/A',
                            'status' => 'applied',
                        ],
                        'color' => 'warning'
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
                    'description' => 'Marked as ' . ucfirst($attendance->status) . ($attendance->batch ? ' for ' . $attendance->batch->name : ''),
                    'user' => optional($attendance->markedBy)->name ?? 'System',
                    'timestamp' => $attendance->created_at,
                    'properties' => [
                        'status' => $attendance->status,
                        'date' => $attendance->attendance_date?->format('Y-m-d'),
                        'notes' => $attendance->notes ?? 'N/A'
                    ],
                    'color' => $attendance->status === 'present' ? 'success' : ($attendance->status === 'absent' ? 'danger' : 'warning')
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
                'description' => 'Fee component created: ' . optional($fee->feeCategory)->name ?? 'Unknown',
                'user' => 'System',
                'timestamp' => $fee->created_at,
                'properties' => [
                    'amount' => $fee->amount ?? 0,
                    'due_date' => $fee->due_date ?? 'N/A'
                ],
                'color' => 'info'
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
     * Get icon for activity type
     */
    private function getActivityIcon($description)
    {
        $description = strtolower($description);

        if (strpos($description, 'payment') !== false)
            return 'fa-money-bill-wave';
        if (strpos($description, 'concession') !== false)
            return 'fa-percent';
        if (strpos($description, 'created') !== false)
            return 'fa-plus-circle';
        if (strpos($description, 'updated') !== false)
            return 'fa-edit';
        if (strpos($description, 'deleted') !== false)
            return 'fa-trash';
        if (strpos($description, 'login') !== false)
            return 'fa-sign-in-alt';
        if (strpos($description, 'fee') !== false)
            return 'fa-file-invoice-dollar';

        return 'fa-info-circle';
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
            'confirm_preservation' => 'required|accepted'
        ]);

        $dropoutService = app(\App\Services\DropoutManagementService::class);
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
            'reason' => 'nullable|string|max:500'
        ]);

        $dropoutService = app(\App\Services\DropoutManagementService::class);
        $result = $dropoutService->reactivateStudent($student, $request->reason ?? '');

        return response()->json($result);
    }

    /**
     * Get color for activity type
     */
    private function getActivityColor($description)
    {
        $description = strtolower($description);

        if (strpos($description, 'payment') !== false)
            return 'success';
        if (strpos($description, 'concession') !== false)
            return 'warning';
        if (strpos($description, 'created') !== false)
            return 'primary';
        if (strpos($description, 'updated') !== false)
            return 'info';
        if (strpos($description, 'deleted') !== false)
            return 'danger';
        if (strpos($description, 'error') !== false)
            return 'danger';

        return 'secondary';
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
                $concessionCount = \App\Models\StudentConcession::where('student_id', $student->id)->count();
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
                    'fee_generations' => $feeGenerationCount
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting activity logs count for student ' . $student->id . ': ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to get activity logs count',
                'total_count' => 0
            ], 500);
        }
    }

    /**
     * Format activity description
     */
    private function formatActivityDescription($activity)
    {
        $properties = $activity->properties->toArray();

        if (isset($properties['attributes']) && isset($properties['old'])) {
            $changes = [];
            foreach ($properties['attributes'] as $key => $newValue) {
                if (isset($properties['old'][$key]) && $properties['old'][$key] !== $newValue) {
                    $changes[] = ucfirst(str_replace('_', ' ', $key)) . " changed from '{$properties['old'][$key]}' to '{$newValue}'";
                }
            }

            if (!empty($changes)) {
                return implode(', ', $changes);
            }
        }

        return $activity->description;
    }

    /**
     * Get attendance data for a specific student and month (AJAX)
     */
    public function getAttendanceData(Request $request, \App\Models\Student $student)
    {
        try {
            $month = $request->input('month', now()->format('Y-m'));
            $data = $this->fetchMonthlyAttendanceData($student, $month);
            
            // Add overall percentage to the response for the header card
            $overall = $this->calculateOverallSummary($student);
            $data['overall_percentage'] = $overall['overall_percentage'];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            \Log::error('Attendance data error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Helper to fetch comprehensive attendance data for a student and month
     * valid for both Controller and AJAX use
     */
    private function fetchMonthlyAttendanceData(\App\Models\Student $student, $monthInput)
    {
        $startDate = \Carbon\Carbon::parse($monthInput)->startOfMonth();
        $endDate = \Carbon\Carbon::parse($monthInput)->endOfMonth();

        $stats = $this->calculateAttendanceStatsForPeriod($student, $startDate, $endDate);

        $biometricSummary = [
            'valid_working_days' => $stats['present'] + $stats['late'],
            'average_working_hours' => ($stats['present'] + $stats['late']) > 0 ? round($stats['total_work_hours'] / ($stats['present'] + $stats['late']), 1) : 0,
            'late_arrivals' => $stats['late_arrivals'],
            'early_departures' => $stats['early_departures'],
            'average_check_in' => '-',
            'average_check_out' => '-'
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
                'month_name' => $startDate->format('F Y')
            ],
            'summary' => [
                'overall_percentage' => $stats['percentage'],
                'status' => $stats['percentage'] >= 75 ? 'good' : 'needs_improvement'
            ],
            'overall_percentage' => $stats['percentage'] // for AJAX consistency
        ];
    }

    /**
     * Core logic to calculate attendance stats for a specific date range.
     * This logic accounts for weekends, holidays, internships, and joining dates.
     */
    private function calculateAttendanceStatsForPeriod(\App\Models\Student $student, \Carbon\Carbon $startDate, \Carbon\Carbon $endDate)
    {
        $todayStr = now()->format('Y-m-d');

        // 1. Fetch Attendance Records
        $attendances = \App\Models\Attendance\Attendance::where('student_id', $student->id)
            ->whereBetween('attendance_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get()
            ->mapWithKeys(function ($item) {
                $d = is_string($item->attendance_date)
                    ? substr($item->attendance_date, 0, 10)
                    : $item->attendance_date->format('Y-m-d');
                return [$d => $item];
            });

        // 2. Fetch Holidays
        $holidays = \App\Models\Holiday::whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->pluck('name', 'date')->toArray();

        // 3. Daily Punch Counts (for Low Attendance check)
        $dailyCounts = \App\Models\Attendance\Attendance::whereBetween('attendance_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->selectRaw('DATE(attendance_date) as date, count(distinct student_id) as count')
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // 4. effective start dates
        $profileStartDate = $student->admission_date ? \Carbon\Carbon::parse($student->admission_date)->startOfDay() : $student->created_at->startOfDay();
        $firstBiometricUse = \App\Models\Attendance\Attendance::where('student_id', $student->id)
            ->whereIn('status', ['present', 'late'])
            ->orderBy('attendance_date', 'asc')
            ->value('attendance_date');

        // 5. Settings
        $isOnInternship = $student->batch && $student->batch->is_on_internship;
        $internshipStartDate = $isOnInternship ? $student->batch->internship_start_date : null;

        // 6. Counters
        $present = 0; $absent = 0; $late = 0; $excused = 0; $internship = 0;
        $totalWorkHours = 0; $lateArrivals = 0; $earlyDepartures = 0;
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
            if (!$isFuture && !$isWeekend && !$isExplicitHoliday) {
                $dayPunchCount = $dailyCounts[$dateStr] ?? 0;
                if ($dayPunchCount < 10) $isLowAttendanceHoliday = true;
            }
            $isEffectiveHoliday = $isExplicitHoliday || $isLowAttendanceHoliday;
            
            $isBeforeProfile = $current->lt($profileStartDate);
            $shouldIgnoreForAbsent = $isBeforeProfile || is_null($firstBiometricUse);

            $status = 'none'; $checkIn = '-'; $checkOut = '-'; $remarks = ''; $workHours = 0;

            if (isset($attendances[$dateStr])) {
                $att = $attendances[$dateStr];
                $status = strtolower(trim($att->status));
                if (!$isFuture && $status != 'none') {
                    if ($status == 'present') $present++;
                    elseif ($status == 'late') { $late++; $lateArrivals++; }
                    elseif ($status == 'absent') $absent++;
                    elseif ($status == 'excused') $excused++;
                }
                $checkIn = $att->check_in_time ? \Carbon\Carbon::parse($att->check_in_time)->format('h:i A') : '-';
                $checkOut = $att->check_out_time ? \Carbon\Carbon::parse($att->check_out_time)->format('h:i A') : '-';
                $remarks = $att->remarks;
                if ($isLowAttendanceHoliday) $remarks = $remarks ? $remarks . ' (Holiday Declared)' : 'Holiday Declared';
                if ($att->check_in_time && $att->check_out_time) {
                    $diff = \Carbon\Carbon::parse($att->check_in_time)->diffInHours(\Carbon\Carbon::parse($att->check_out_time));
                    $workHours = number_format($diff, 1);
                    $totalWorkHours += $diff;
                }
            } else {
                if ($shouldIgnoreForAbsent || $isFuture) $status = 'none';
                elseif ($isWeekend) $status = 'weekend';
                elseif ($isEffectiveHoliday) {
                    $status = 'holiday';
                    $remarks = $isExplicitHoliday ? $holidays[$dateStr] : 'Holiday';
                } else {
                    if ($isPast) {
                        $isInternshipDay = $isOnInternship && (!$internshipStartDate || $current->gte(\Carbon\Carbon::parse($internshipStartDate)));
                        if ($isInternshipDay) { $internship++; $status = 'internship'; $checkIn = 'OJT'; $remarks = 'On Internship'; }
                        else { $absent++; $status = 'absent'; $remarks = 'Absent'; }
                    } else $status = 'none';
                }
            }

            $calendar[$dateStr] = [
                'status' => $status,
                'check_in_time' => $checkIn,
                'check_out_time' => $checkOut,
                'working_hours' => $workHours,
                'remarks' => $remarks,
                'is_late_arrival' => ($status == 'late'),
                'is_early_departure' => false
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
            'attendances' => $attendances
        ];
    }

    /**
     * Get attendance patterns
     */
    private function getAttendancePatterns($attendanceRecords)
    {
        // This is a placeholder - you can implement pattern analysis
        // like most frequent late days, attendance trends, etc.
        return [
            'most_late_day' => 'Monday', // Example
            'best_attendance_day' => 'Wednesday', // Example
            'trend' => 'improving' // Example
        ];
    }

    /**
     * Calculate monthly summary
     */
    private function calculateMonthlySummary($attendanceRecords, $monthDate)
    {
        $presentDays = $attendanceRecords->whereIn('status', ['present', 'late'])->count();
        $absentDays = $attendanceRecords->where('status', 'absent')->count();
        $lateDays = $attendanceRecords->where('status', 'late')->count();
        $totalRecords = $attendanceRecords->count();

        $workingDays = max($totalRecords, $this->getWorkingDaysInMonth($monthDate));

        return [
            'month_name' => $monthDate->format('F Y'),
            'present_days' => $presentDays,
            'absent_days' => $absentDays,
            'late_days' => $lateDays,
            'total_working_days' => $workingDays,
            'attendance_percentage' => $workingDays > 0 ? round(($presentDays / $workingDays) * 100, 1) : 0,
            'records' => $attendanceRecords->sortByDesc('attendance_date')->values()->take(10)->map(function ($record) {
                return [
                    'id' => $record->id,
                    'attendance_date' => is_string($record->attendance_date) ? $record->attendance_date : $record->attendance_date->format('Y-m-d'),
                    'status' => $record->status,
                    'check_in_time' => $record->check_in_time ? (is_string($record->check_in_time) ? $record->check_in_time : $record->check_in_time->format('H:i:s')) : null,
                    'check_out_time' => $record->check_out_time ? (is_string($record->check_out_time) ? $record->check_out_time : $record->check_out_time->format('H:i:s')) : null,
                    'late_minutes' => $record->late_minutes ?? 0,
                    'subject' => $record->subject ?? null,
                    'remarks' => $record->notes ?? null
                ];
            })
        ];
    }


    /**
     * Get working days in a month (excluding weekends)
     */
    private function getWorkingDaysInMonth($monthDate)
    {
        $start = $monthDate->copy()->startOfMonth();
        $end = $monthDate->copy()->endOfMonth();

        $workingDays = 0;

        while ($start <= $end) {
            // Skip weekends (Saturday = 6, Sunday = 0)
            if ($start->dayOfWeek !== 0 && $start->dayOfWeek !== 6) {
                $workingDays++;
            }
            $start->addDay();
        }

        return $workingDays;
    }


    /**
     * Calculate overall summary across all months
     */
    /**
     * Calculate overall summary across all months
     */
    private function calculateOverallSummary($student)
    {
        // 1. Determine Start Date (Join Date)
        $startDate = $student->admission_date ? \Carbon\Carbon::parse($student->admission_date)->startOfDay() : $student->created_at->startOfDay();
        
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
            'status' => $status
        ];
    }

    /**
     * Generate biometric summary
     */
    private function generateBiometricSummary($attendanceRecords, $monthDate)
    {
        $workingRecords = $attendanceRecords->where('check_in_time', '!=', null);

        $totalWorkingHours = 0;
        $validDays = 0;
        $lateArrivals = 0;
        $earlyDepartures = 0;
        $checkInTimes = [];
        $checkOutTimes = [];

        foreach ($workingRecords as $record) {
            if ($record->check_in_time) {
                $checkInTimes[] = $record->check_in_time;

                if (($record->late_minutes ?? 0) > 0) {
                    $lateArrivals++;
                }
            }

            if ($record->check_out_time) {
                $checkOutTimes[] = $record->check_out_time;
            }

            $workingHours = $this->calculateWorkingHours($record->check_in_time, $record->check_out_time);
            if ($workingHours) {
                $totalWorkingHours += $workingHours;
                $validDays++;
            }
        }

        return [
            'valid_working_days' => $validDays,
            'average_working_hours' => $validDays > 0 ? round($totalWorkingHours / $validDays, 1) : 0,
            'late_arrivals' => $lateArrivals,
            'early_departures' => $earlyDepartures, // You can implement logic for this
            'average_check_in' => $this->calculateAverageTime($checkInTimes),
            'average_check_out' => $this->calculateAverageTime($checkOutTimes),
        ];
    }


    private function generateCalendarData($attendanceRecords, $monthDate)
    {
        $calendarData = [];

        foreach ($attendanceRecords as $record) {
            // Handle both Carbon and string dates safely
            try {
                if (is_string($record->attendance_date)) {
                    $dateStr = $record->attendance_date;
                } else {
                    $dateStr = $record->attendance_date->format('Y-m-d');
                }

                // Handle time formatting safely
                $checkInTime = null;
                if ($record->check_in_time) {
                    $checkInTime = is_string($record->check_in_time)
                        ? $record->check_in_time
                        : $record->check_in_time->format('H:i:s');
                }

                $checkOutTime = null;
                if ($record->check_out_time) {
                    $checkOutTime = is_string($record->check_out_time)
                        ? $record->check_out_time
                        : $record->check_out_time->format('H:i:s');
                }

                $calendarData[$dateStr] = [
                    'status' => $record->status ?? 'absent',
                    'check_in_time' => $checkInTime,
                    'check_out_time' => $checkOutTime,
                    'working_hours' => $this->calculateWorkingHours($checkInTime, $checkOutTime),
                    'is_late_arrival' => ($record->late_minutes ?? 0) > 0,
                    'is_early_departure' => false,
                    'subject' => $record->subject ?? null,
                    'remarks' => $record->notes ?? null,
                    'device_id' => $record->device_id ?? null
                ];
            } catch (\Exception $e) {
                \Log::error('Error formatting attendance record', [
                    'record_id' => $record->id ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }

        \Log::info('Calendar data generated', [
            'total_records' => count($calendarData),
            'dates' => array_keys($calendarData)
        ]);

        return $calendarData;
    }

    /**
     * Get attendance calendar for the month with enhanced biometric data
     */
    private function getAttendanceCalendar(Student $student, string $month)
    {
        $currentDate = Carbon::parse($month);
        $startOfMonth = $currentDate->copy()->startOfMonth();
        $endOfMonth = $currentDate->copy()->endOfMonth();

        $records = \App\Models\Attendance::where('student_id', $student->id)
            ->with(['subject']) // Eager load relationships if they exist
            ->whereBetween('attendance_date', [$startOfMonth, $endOfMonth])
            ->get()
            ->keyBy(function ($record) {
                return $record->attendance_date->format('Y-m-d');
            });

        // Enhance each record with calculated data
        $enhancedRecords = $records->map(function ($record) {
            $workingHours = $this->calculateWorkingHours($record->check_in_time, $record->check_out_time);
            $isLateArrival = $this->isLateArrival($record->check_in_time);
            $isEarlyDeparture = $this->isEarlyDeparture($record->check_out_time);

            return [
                'id' => $record->id,
                'date' => $record->attendance_date->format('Y-m-d'),
                'status' => $record->status,
                'check_in_time' => $record->check_in_time ? $record->check_in_time->format('H:i:s') : null,
                'check_out_time' => $record->check_out_time ? $record->check_out_time->format('H:i:s') : null,
                'working_hours' => $workingHours,
                'is_late_arrival' => $isLateArrival,
                'is_early_departure' => $isEarlyDeparture,
                'subject' => $record->subject ? $record->subject->name : null,
                'remarks' => $record->remarks,
                'source' => $record->source
            ];
        });

        return $enhancedRecords;
    }

    /**
     * Calculate date range based on filter type
     */
    private function calculateAttendanceDateRange($dateRange, $monthDate, $request)
    {
        switch ($dateRange) {
            case 'current_month':
                return [
                    'start' => $monthDate->copy()->startOfMonth(),
                    'end' => $monthDate->copy()->endOfMonth()
                ];

            case 'last_month':
                $lastMonth = $monthDate->copy()->subMonth();
                return [
                    'start' => $lastMonth->copy()->startOfMonth(),
                    'end' => $lastMonth->copy()->endOfMonth()
                ];

            case 'last_3_months':
                return [
                    'start' => $monthDate->copy()->subMonths(2)->startOfMonth(),
                    'end' => $monthDate->copy()->endOfMonth()
                ];

            case 'current_semester':
                // Assuming academic year starts in July
                $semesterStart = now()->month >= 7 ?
                    now()->startOfYear()->addMonths(6) : // July if current year
                    now()->subYear()->startOfYear()->addMonths(6); // July of previous year
                return [
                    'start' => $semesterStart,
                    'end' => now()->endOfMonth()
                ];

            case 'custom':
                return [
                    'start' => \Carbon\Carbon::parse($request->get('start_date', $monthDate->startOfMonth())),
                    'end' => \Carbon\Carbon::parse($request->get('end_date', $monthDate->endOfMonth()))
                ];

            default:
                return [
                    'start' => $monthDate->copy()->startOfMonth(),
                    'end' => $monthDate->copy()->endOfMonth()
                ];
        }
    }




    /**
     * Get overall attendance summary for student
     */
    private function getAttendanceSummary(Student $student)
    {
        $totalRecords = \App\Models\Attendance::where('student_id', $student->id)->count();

        $presentRecords = \App\Models\Attendance::where('student_id', $student->id)
            ->whereIn('status', ['present', 'late'])
            ->count();

        $overallPercentage = $totalRecords > 0 ?
            round(($presentRecords / $totalRecords) * 100, 1) : 0;

        return [
            'overall_percentage' => $overallPercentage,
            'status' => $this->getAttendanceStatus($overallPercentage)
        ];
    }

    /**
     * Get attendance status based on percentage
     */
    private function getAttendanceStatus($percentage)
    {
        if ($percentage >= 90)
            return 'excellent';
        if ($percentage >= 80)
            return 'good';
        if ($percentage >= 75)
            return 'satisfactory';
        return 'needs_improvement';
    }

    /**
     * Calculate biometric summary for attendance records
     */
    private function calculateBiometricSummary($attendanceRecords)
    {
        $totalWorkingHours = 0;
        $validWorkingDays = 0;
        $lateArrivals = 0;
        $earlyDepartures = 0;
        $averageCheckInTime = [];
        $averageCheckOutTime = [];

        foreach ($attendanceRecords as $record) {
            if ($record->check_in_time && $record->check_out_time) {
                $workingHours = $this->calculateWorkingHours($record->check_in_time, $record->check_out_time);
                if ($workingHours > 0) {
                    $totalWorkingHours += $workingHours;
                    $validWorkingDays++;
                }

                $averageCheckInTime[] = $record->check_in_time->format('H:i');
                $averageCheckOutTime[] = $record->check_out_time->format('H:i');
            }

            if ($this->isLateArrival($record->check_in_time)) {
                $lateArrivals++;
            }

            if ($this->isEarlyDeparture($record->check_out_time)) {
                $earlyDepartures++;
            }
        }

        $averageWorkingHours = $validWorkingDays > 0 ? round($totalWorkingHours / $validWorkingDays, 2) : 0;

        return [
            'total_working_hours' => round($totalWorkingHours, 2),
            'average_working_hours' => $averageWorkingHours,
            'valid_working_days' => $validWorkingDays,
            'late_arrivals' => $lateArrivals,
            'early_departures' => $earlyDepartures,
            'average_check_in' => $this->calculateAverageTime($averageCheckInTime),
            'average_check_out' => $this->calculateAverageTime($averageCheckOutTime)
        ];
    }

    /**
     * Calculate attendance patterns
     */
    private function calculateAttendancePatterns($attendanceRecords)
    {
        $weeklyPattern = [];
        $monthlyTrend = [];

        foreach ($attendanceRecords as $record) {
            $dayOfWeek = $record->attendance_date->format('l');
            $week = $record->attendance_date->format('W');

            if (!isset($weeklyPattern[$dayOfWeek])) {
                $weeklyPattern[$dayOfWeek] = ['total' => 0, 'present' => 0];
            }

            $weeklyPattern[$dayOfWeek]['total']++;
            if (in_array($record->status, ['present', 'late'])) {
                $weeklyPattern[$dayOfWeek]['present']++;
            }

            if (!isset($monthlyTrend[$week])) {
                $monthlyTrend[$week] = ['total' => 0, 'present' => 0];
            }

            $monthlyTrend[$week]['total']++;
            if (in_array($record->status, ['present', 'late'])) {
                $monthlyTrend[$week]['present']++;
            }
        }

        // Calculate percentages
        foreach ($weeklyPattern as $day => &$data) {
            $data['percentage'] = $data['total'] > 0 ? round(($data['present'] / $data['total']) * 100, 1) : 0;
        }

        foreach ($monthlyTrend as $week => &$data) {
            $data['percentage'] = $data['total'] > 0 ? round(($data['present'] / $data['total']) * 100, 1) : 0;
        }

        return [
            'weekly_pattern' => $weeklyPattern,
            'monthly_trend' => $monthlyTrend
        ];
    }

    /**
     * Calculate working hours between check-in and check-out
     */
    private function calculateWorkingHours($checkInTime, $checkOutTime)
    {
        if (!$checkInTime || !$checkOutTime) {
            return null;
        }

        try {
            // Handle both string and Carbon inputs
            if (is_string($checkInTime)) {
                $checkIn = \Carbon\Carbon::createFromFormat('H:i:s', $checkInTime);
            } else {
                $checkIn = \Carbon\Carbon::parse($checkInTime);
            }

            if (is_string($checkOutTime)) {
                $checkOut = \Carbon\Carbon::createFromFormat('H:i:s', $checkOutTime);
            } else {
                $checkOut = \Carbon\Carbon::parse($checkOutTime);
            }

            // Handle case where check-out is next day
            if ($checkOut->lt($checkIn)) {
                $checkOut->addDay();
            }

            $diffInHours = $checkOut->diffInMinutes($checkIn) / 60;
            return round($diffInHours, 1);

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if arrival is late (after 9:30 AM)
     */
    private function isLateArrival($checkInTime)
    {
        if (!$checkInTime) {
            return false;
        }

        $checkIn = Carbon::parse($checkInTime);
        $lateThreshold = Carbon::parse($checkInTime)->setTime(9, 30, 0);

        return $checkIn->gt($lateThreshold);
    }

    /**
     * Check if departure is early (before 5:00 PM)
     */
    private function isEarlyDeparture($checkOutTime)
    {
        if (!$checkOutTime) {
            return false;
        }

        $checkOut = Carbon::parse($checkOutTime);
        $earlyThreshold = Carbon::parse($checkOutTime)->setTime(17, 0, 0);

        return $checkOut->lt($earlyThreshold);
    }

    /**
     * Calculate average time from array of time strings
     */
    private function calculateAverageTime($times)
    {
        if (empty($times)) {
            return 'N/A';
        }

        $totalMinutes = 0;
        $count = 0;

        foreach ($times as $time) {
            try {
                $carbon = \Carbon\Carbon::createFromFormat('H:i:s', $time);
                $totalMinutes += ($carbon->hour * 60) + $carbon->minute;
                $count++;
            } catch (\Exception $e) {
                continue;
            }
        }

        if ($count === 0) {
            return 'N/A';
        }

        $averageMinutes = round($totalMinutes / $count);
        $hours = floor($averageMinutes / 60);
        $minutes = $averageMinutes % 60;

        return sprintf('%02d:%02d', $hours, $minutes);
    }

    /**
     * AJAX endpoint for attendance data
     */
    // [DEAD CODE DELETED] getStudentAttendanceData was buggy and unused.
    /**
     * Get holidays for the month
     */
    private function getHolidays(string $month)
    {
        // Add your holiday logic here
        // This is a placeholder - implement based on your holiday system

        return collect(); // Return empty collection for now
    }

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
                'regex:/^[6-9]\d{9}$/'
            ],
            'father_mobile' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('students')->ignore($student->id),
                'regex:/^[6-9]\d{9}$/'
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
                'father_mobile' => 'Father mobile number cannot be the same as student mobile number.'
            ])->withInput();
        }

        // âœ… Additional validation: Check cross-field duplicates (excluding current student)
        if ($validated['student_mobile']) {
            $existsAsFatherMobile = Student::where('father_mobile', $validated['student_mobile'])
                ->where('id', '!=', $student->id)
                ->exists();
            if ($existsAsFatherMobile) {
                return back()->withErrors([
                    'student_mobile' => 'This mobile number is already registered as a father mobile number for another student.'
                ])->withInput();
            }
        }

        if ($validated['father_mobile']) {
            $existsAsStudentMobile = Student::where('student_mobile', $validated['father_mobile'])
                ->where('id', '!=', $student->id)
                ->exists();
            if ($existsAsStudentMobile) {
                return back()->withErrors([
                    'father_mobile' => 'This mobile number is already registered as a student mobile number for another student.'
                ])->withInput();
            }
        }

        $originalBatchId = $student->getOriginal('batch_id');

        if ($request->hasFile('photo')) {
            if ($student->photo) {
                Storage::disk('public')->delete($student->photo);
            }
            $validated['photo'] = $request->file('photo')->store('student_photos', 'public');
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

    /**
     * Helper method to create installments for multiple students in a batch
     */
    public function createInstallmentsForBatch($batchId, array $options = [])
    {
        DB::beginTransaction();
        try {
            $batch = Batch::with('students')->find($batchId);

            if (!$batch) {
                throw new \Exception('Batch not found');
            }

            $currentAcademicYear = $this->getCurrentAcademicYear();

            // Check if fee structure exists
            if (!$batch->feeStructure) {
                throw new \Exception('No fee structure assigned to this batch');
            }

            $results = $this->componentPaymentService->createFeeComponentsForBatch(
                $batch->id,
                $batch->feeStructure->id,
                $currentAcademicYear
            );

            DB::commit();

            return $results;

        } catch (\Exception $e) {
            DB::rollback();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
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
        $settings = Setting::all()->keyBy('key');
        $collegePrefix = $settings['enrollment_prefix']->value ?? 'UV';
        $coursePrefix = $batch->course->enrollment_prefix ?? strtoupper(substr($batch->course->name, 0, 4));
        // Remove any spaces or special chars if generated from name
        $coursePrefix = trim($coursePrefix);
        $batchYear = Carbon::parse($batch->created_at)->format('y');

        $prefix = "{$collegePrefix}-{$coursePrefix}-{$batchYear}";

        // Find the last student with this prefix in this batch
        // We order by length first to handle 9, 10, 100 correctly if we rely on string comparison
        // But since we use fixed padding of 3, strict string sort is usually fine.
        // However, extraction is safer.
        $lastStudent = Student::where('batch_id', $batch->id)
            ->where('enrollment_number', 'like', "{$prefix}%")
            ->orderByRaw('LENGTH(enrollment_number) DESC') // Ensure we don't mix lengths
            ->orderBy('enrollment_number', 'desc')
            ->first();

        if ($lastStudent) {
            // Extract the numeric part (last 3 digits)
            $lastSequence = (int) substr($lastStudent->enrollment_number, -3);
            $nextSequence = $lastSequence + 1;
        } else {
            $nextSequence = 1;
        }

        // Loop to ensure uniqueness (in case of race conditions or manual interfering)
        // This is a safety net, but the primary logic is the max+1 above.
        $maxAttempts = 10;
        $attempt = 0;

        do {
            $paddedRollNo = str_pad($nextSequence + $attempt, 3, '0', STR_PAD_LEFT);
            $enrollmentNumber = "{$collegePrefix}-{$coursePrefix}-{$batchYear}{$paddedRollNo}";

            $exists = Student::where('enrollment_number', $enrollmentNumber)->exists();

            if ($exists) {
                $attempt++;
            }

        } while ($exists && $attempt < $maxAttempts);

        // Fallback for extreme cases (if 10 consecutive collisions happen)
        if ($attempt >= $maxAttempts) {
            $enrollmentNumber = "{$collegePrefix}-{$coursePrefix}-{$batchYear}" . substr(time(), -3);
        }

        return $enrollmentNumber;
    }

    /**
     * Update student status
     */
    public function updateStatus(Request $request, Student $student)
    {
        $request->validate([
            'status' => 'required|in:active,graduated,dropout'
        ]);

        $student->update(['status' => $request->status]);

        return redirect()->back()->with('success', 'Student status updated successfully.');
    }

    /**
     * Get batches for a specific course (AJAX endpoint)
     */
    public function getBatchesForCourse(Course $course)
    {
        $batches = $course->batches()
            ->with('course')
            ->select('id', 'name', 'course_id', 'start_date', 'end_date')
            ->orderBy('name')
            ->get();

        return response()->json($batches);
    }

    /**
     * Show biometric mapping interface
     */
    public function biometricMapping()
    {
        // Get basic statistics
        $totalStudents = Student::where('status', 'active')->count();
        $mappedStudents = Student::where('status', 'active')
            ->whereNotNull('biometric_employee_code')
            ->count();
        $unmappedStudents = $totalStudents - $mappedStudents;
        $mappingPercentage = $totalStudents > 0 ? round(($mappedStudents / $totalStudents) * 100, 2) : 0;

        $stats = [
            'total_students' => $totalStudents,
            'mapped_students' => $mappedStudents,
            'unmapped_students' => $unmappedStudents,
            'mapping_percentage' => $mappingPercentage
        ];

        // Get all students with current biometric codes and suggestions
        $students = Student::where('status', 'active')
            ->with(['batch.course'])
            ->get()
            ->map(function ($student) {
                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'enrollment_number' => $student->enrollment_number,
                    'biometric_code' => $student->biometric_employee_code,
                    'batch_name' => $student->batch->name ?? 'No Batch',
                    'course_name' => $student->batch->course->name ?? 'No Course',
                    'suggested_code' => $this->generateBiometricCodeSuggestion($student->enrollment_number)
                ];
            });

        return view('admin.students.biometric-mapping', compact('stats', 'students'));
    }

    /**
     * Generate biometric code suggestion from enrollment number - NUMBERS ONLY
     */
    private function generateBiometricCodeSuggestion($enrollmentNumber)
    {
        return $this->generateBiometricCodeFromEnrollment($enrollmentNumber);
    }

    private function generateBiometricCodeFromEnrollment(string $enrollmentNumber): string
    {
        try {
            // Course mapping to numbers
            $courseMapping = [
                'ADHM' => '1',
                'DHM' => '2',
                'PDHM' => '3',
                'MDHM' => '4',
            ];

            // Convert to uppercase for comparison
            $enrollmentUpper = strtoupper($enrollmentNumber);

            // Find course code
            $courseCode = '25'; // Default course code
            foreach ($courseMapping as $course => $number) {
                if (strpos($enrollmentUpper, $course) !== false) {
                    $courseCode = $number;
                    break;
                }
            }

            // MODIFICATION 1: Extract all numbers to be used as the student number
            $studentNumber = preg_replace('/[^0-9]/', '', $enrollmentNumber);

            if (empty($studentNumber)) {
                // Fallback if no numbers are found in the enrollment string
                $studentNumber = '0001';
            }

            // MODIFICATION 2: Combine in the order: Course Code + Student Number
            $biometricCode = $courseCode . $studentNumber;

            return $biometricCode;

        } catch (\Exception $e) {
            // A simplified fallback that follows the new logic
            $numbers = preg_replace('/[^0-9]/', '', $enrollmentNumber);
            $numbers = empty($numbers) ? '0001' : $numbers;

            return '25' . $numbers;
        }
    }

    /**
     * Bulk update biometric codes via AJAX
     */
    public function bulkUpdateBiometric(Request $request)
    {
        // Enhanced logging for debugging
        Log::info('Bulk biometric update started', [
            'request_data' => $request->all(),
            'mappings_count' => count($request->input('mappings', [])),
            'user_id' => auth()->id(),
            'ip' => $request->ip()
        ]);

        $request->validate([
            'mappings' => 'required|array',
            'mappings.*.student_id' => 'required|integer|exists:students,id',
            'mappings.*.biometric_code' => 'nullable|string|max:50|regex:/^[a-zA-Z0-9\-]*$/',
        ]);

        try {
            Log::info('Validation passed, calling biometric mapping service', [
                'mappings' => $request->mappings
            ]);

            $results = $this->biometricMappingService->bulkUpdateCodes($request->mappings);

            Log::info('Bulk biometric update completed', [
                'results' => $results,
                'success_count' => $results['success_count'],
                'error_count' => $results['error_count']
            ]);

            return response()->json([
                'success' => true,
                'message' => "Updated {$results['success_count']} students successfully" .
                    ($results['error_count'] > 0 ? " with {$results['error_count']} errors" : ""),
                'results' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk biometric update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update biometric codes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import biometric mappings from Excel/CSV
     */
    public function importBiometricMapping(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:5120'
        ]);

        // Enhanced file validation using SecureFileValidator
        $fileValidator = new SecureFileValidator();
        $validationResult = $fileValidator->validateFile($request->file('file'), ['xlsx', 'xls', 'csv']);

        if (!$validationResult['valid']) {
            return back()->with('error', $validationResult['error']);
        }

        try {
            $results = $this->biometricMappingService->importBiometricMappings($request->file('file'));

            if ($results['success']) {
                $message = "Successfully imported {$results['imported_count']} biometric codes";
                if (!empty($results['errors'])) {
                    $message .= " with " . count($results['errors']) . " errors";
                }

                return back()->with('success', $message)
                    ->with('import_errors', $results['errors'] ?? []);
            } else {
                return back()->with('error', 'Import failed: ' . $results['error']);
            }

        } catch (\Exception $e) {
            Log::error('Biometric import failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Import failed: ' . $e->getMessage());
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
            return back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }

    /**
     * Download sample biometric mapping file
     */
    public function downloadBiometricSample()
    {
        return $this->biometricMappingService->exportUnmappedStudents();
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
                'results' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('Auto-generate biometric codes failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to auto-generate codes: ' . $e->getMessage()
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

        return Excel::download(new StudentsExport($students), 'students_export_' . now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * Get student's profile photo URL with fallback to dummy avatar
     */
    public static function getStudentPhotoUrl(Student $student, $size = 100): string
    {
        if ($student->photo && Storage::disk('public')->exists($student->photo)) {
            return asset('storage/' . $student->photo);
        }

        // Generate dummy avatar using UI Avatars service
        $name = urlencode($student->name);
        $backgroundColor = '4e73df'; // Primary color
        $color = 'fff'; // White text

        return "https://ui-avatars.com/api/?name={$name}&size={$size}&background={$backgroundColor}&color={$color}";
    }

    /**
     * Get all students with their profile photos (including dummy ones)
     */
    public function getStudentsWithPhotos(Request $request)
    {
        $students = Student::with('batch.course')->get();

        $studentsWithPhotos = $students->map(function (Student $student) {
            return [
                'id' => $student->id,
                'name' => $student->name,
                'enrollment_number' => $student->enrollment_number,
                'email' => $student->email,
                'photo_url' => self::getStudentPhotoUrl($student),
                'course' => $student->batch->course->name ?? 'N/A',
                'batch' => $student->batch->name ?? 'N/A',
                'status' => $student->status,
            ];
        });

        return response()->json($studentsWithPhotos);
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
                    'due_date_formatted' => $fee->due_date ? \Carbon\Carbon::parse($fee->due_date)->format('M d, Y') : null,
                    'status' => $fee->status,
                    'fee_category' => [
                        'id' => $fee->feeCategory->id,
                        'name' => $fee->feeCategory->name
                    ]
                ];
            });

        return response()->json($unpaidFees);
    }

    public function getUnassignedFeeComponents($studentId)
    {
        try {
            $student = Student::with('batch')->findOrFail($studentId);

            if (!$student->batch_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student is not assigned to any batch'
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

            if (!$feeStructure) {
                return response()->json([
                    'success' => false,
                    'message' => 'No fee structure found for this batch'
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
                    'assigned_ids' => $assignedIds
                ],
                'components' => $unassignedCategories->map(function ($category) {
                    $amount = $category->amount ?? 0;

                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'description' => $category->description ?? '',
                        'amount' => (float) $amount,
                        'warning' => $amount > 0 ? null : 'Amount not set - please specify when assigning'
                    ];
                })
            ]);

        } catch (\Exception $e) {
            \Log::error('Error getting unassigned fee components', [
                'student_id' => $studentId,
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function assignFeeComponent(Request $request, $studentId)
    {
        try {
            $request->validate([
                'fee_category_id' => 'required|exists:fee_categories,id',
                'amount' => 'required|numeric|min:0'
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
                    'message' => 'This fee component is already assigned to the student'
                ]);
            }

            // Get the fee structure ID from the batch
            $feeStructure = DB::table('fee_structures')
                ->where('batch_id', $student->batch_id)
                ->first();

            if (!$feeStructure) {
                return response()->json([
                    'success' => false,
                    'message' => 'No fee structure found for this student\'s batch'
                ]);
            }

            // Calculate academic year (April to March cycle)
            $currentYear = date('Y');
            $currentMonth = date('n');

            if ($currentMonth >= 4) {
                // April to December = current year to next year
                $academicYear = $currentYear . '-' . ($currentYear + 1);
            } else {
                // January to March = previous year to current year
                $academicYear = ($currentYear - 1) . '-' . $currentYear;
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
                'updated_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Fee component assigned successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error assigning fee component', [
                'student_id' => $studentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
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

            if (!$attendanceData['success']) {
                return back()->with('error', 'Failed to export attendance data');
            }

            $data = $attendanceData['data'];

            if ($format === 'pdf') {
                return $this->exportAttendanceToPDF($student, $data);
            } else if ($format === 'excel') {
                return $this->exportAttendanceToExcel($student, $data);
            }

            return back()->with('error', 'Invalid export format');

        } catch (\Exception $e) {
            \Log::error('Export attendance failed', [
                'student_id' => $student->id,
                'format' => $format,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }

    /**
     * Export to PDF
     */
    private function exportAttendanceToPDF($student, $data)
    {
        $pdf = Pdf::loadView('admin.students.attendance-export-pdf', [
            'student' => $student,
            'data' => $data,
            'generated_at' => now()
        ]);

        $filename = "attendance_{$student->enrollment_number}_" . now()->format('Y-m-d') . ".pdf";

        return $pdf->download($filename);
    }

    /**
     * Bulk update biometric codes
     */
    public function bulkUpdateBiometricMapping(\Illuminate\Http\Request $request, \App\Services\Attendance\AttendanceService $attendanceService)
    {
        $request->validate([
            'mappings' => 'required|array',
            'mappings.*.student_id' => 'required|exists:students,id',
            'mappings.*.biometric_code' => 'required|string|distinct'
        ]);

        try {
            // Delegate to service
            $result = $attendanceService->bulkUpdateBiometricCodes($request->mappings);

            $status = ($result['error_count'] > 0) ? 'warning' : 'success';

            return response()->json([
                'success' => true,
                'message' => "Updated {$result['success_count']} students. Failed: {$result['error_count']}",
                'details' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk update failed: ' . $e->getMessage()
            ], 500);
        }
    }




    /**
     * Auto-generate biometric codes for students without them
     */
    public function autoGenerateBiometricMapping(\Illuminate\Http\Request $request, \App\Services\Attendance\AttendanceService $attendanceService)
    {
        try {
            // Delegate to service
            $result = $attendanceService->autoGenerateBiometricCodes();

            return response()->json([
                'success' => true,
                'message' => "Auto-generated codes for {$result['success_count']} students. Failed: {$result['error_count']}",
                'details' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Auto-generation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export to Excel  
     */
    private function exportAttendanceToExcel($student, $data)
    {
        // You can use Maatwebsite\Excel for this
        // This is a placeholder implementation
        $filename = "attendance_{$student->enrollment_number}_" . now()->format('Y-m-d') . ".xlsx";

        // Wrap data because AttendanceExport expects array with 'data' key
        return Excel::download(new \App\Exports\AttendanceExport(['data' => $data]), $filename);
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
                        'extra' => $student->enrollment_number . ' (' . ($student->batch->name ?? 'No Batch') . ')'
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
                        'extra' => $item->total . ' Referral' . ($item->total !== 1 ? 's' : '')
                    ];
                });
        }

        return response()->json($suggestions);
    }
}