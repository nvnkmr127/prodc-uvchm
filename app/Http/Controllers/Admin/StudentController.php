<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Batch;
use App\Models\Course;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\Setting;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Maatwebsite\Excel\Validators\ValidationException;
use App\Imports\StudentsImport;
use App\Exports\StudentsSampleExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\StudentsExport;
use Spatie\Activitylog\Models\Activity;
use App\Http\Requests\StudentFormRequest;

class StudentController extends Controller
{
    protected $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    public function index(Request $request)
    {
        // Start with a query builder
        $query = Student::with('batch.course');

        // Apply filters if they exist in the request
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

        $students = $query->latest()->get();

        // Data for filter dropdowns
        $courses = Course::orderBy('name')->get();
        $batches = Batch::orderBy('name')->get();

        return view('admin.students.index', compact('students', 'courses', 'batches'));
    }
    
    /**
     * Enhanced bulk actions method to handle batch assignments and invoice generation
     */
    public function bulkActions(Request $request)
    {
        // First validate basic requirements
        $request->validate([
            'action' => 'required|string|in:delete,change_status,assign_batch',
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
        ]);

        // Then validate based on action type
        if ($request->action === 'change_status') {
            $request->validate([
                'new_status' => 'required|in:active,graduated,dropout'
            ]);
        }

        if ($request->action === 'assign_batch') {
            $request->validate([
                'batch_id' => 'required|exists:batches,id'
            ]);
        }

        $studentIds = $request->input('student_ids');

        try {
            DB::beginTransaction();

            if ($request->action === 'delete') {
                Student::whereIn('id', $studentIds)->delete();
                DB::commit();
                return redirect()->back()->with('success', 'Selected students have been deleted successfully.');
            }

            if ($request->action === 'change_status') {
                Student::whereIn('id', $studentIds)->update(['status' => $request->new_status]);
                DB::commit();
                return redirect()->back()->with('success', 'Status has been updated for selected students.');
            }

            if ($request->action === 'assign_batch') {
                $batch = Batch::with('course')->findOrFail($request->batch_id);
                $students = Student::whereIn('id', $studentIds)->get();
                
                $successCount = 0;
                $errorMessages = [];

                foreach ($students as $student) {
                    try {
                        // Generate new enrollment number for this batch
                        $newEnrollmentNumber = $this->generateEnrollmentNumber($batch);
                        
                        // Update student with new batch and enrollment number
                        $student->update([
                            'batch_id' => $batch->id,
                            'enrollment_number' => $newEnrollmentNumber
                        ]);

                        // Generate invoices for the student's new batch/course
                        $this->invoiceService->generateTermInvoicesForStudent($student->fresh());
                        
                        $successCount++;
                    } catch (\Exception $e) {
                        $errorMessages[] = "Failed to assign {$student->name}: " . $e->getMessage();
                    }
                }

                DB::commit();

                $message = "Successfully assigned {$successCount} students to batch {$batch->name}.";
                if (!empty($errorMessages)) {
                    $message .= " Errors: " . implode(', ', $errorMessages);
                }

                return redirect()->back()->with('success', $message);
            }

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Bulk action failed: ' . $e->getMessage());
        }

        return redirect()->back()->with('error', 'Invalid action specified.');
    }

    public function create()
    {
        $batches = Batch::with('course')->get();
        return view('admin.students.create', compact('batches'));
    }

    // ✅ SINGLE store() method with enhanced mobile validation
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:students,email',
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
            'batch_id' => 'nullable|exists:batches,id',
            'gender' => 'required|in:Male,Female,Other',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ], [
            // Custom error messages
            'student_mobile.unique' => 'This student mobile number is already registered with another student.',
            'father_mobile.unique' => 'This father mobile number is already registered with another student.',
            'student_mobile.regex' => 'Student mobile number must be a valid 10-digit Indian mobile number starting with 6, 7, 8, or 9.',
            'father_mobile.regex' => 'Father mobile number must be a valid 10-digit Indian mobile number starting with 6, 7, 8, or 9.',
        ]);

        // ✅ Additional validation: Check if student and father mobiles are the same
        if ($validated['student_mobile'] && $validated['father_mobile'] && 
            $validated['student_mobile'] === $validated['father_mobile']) {
            return back()->withErrors([
                'father_mobile' => 'Father mobile number cannot be the same as student mobile number.'
            ])->withInput();
        }

        // ✅ Additional validation: Check cross-field duplicates
        if ($validated['student_mobile']) {
            $existsAsFatherMobile = Student::where('father_mobile', $validated['student_mobile'])->exists();
            if ($existsAsFatherMobile) {
                return back()->withErrors([
                    'student_mobile' => 'This mobile number is already registered as a father mobile number for another student.'
                ])->withInput();
            }
        }

        if ($validated['father_mobile']) {
            $existsAsStudentMobile = Student::where('student_mobile', $validated['father_mobile'])->exists();
            if ($existsAsStudentMobile) {
                return back()->withErrors([
                    'father_mobile' => 'This mobile number is already registered as a student mobile number for another student.'
                ])->withInput();
            }
        }
        
        $student = DB::transaction(function () use (&$validated, $request) {
            
            // Handle photo upload
            if ($request->hasFile('photo')) {
                $validated['photo'] = $request->file('photo')->store('student_photos', 'public');
            }
            
            if ($request->filled('batch_id')) {
                $batch = Batch::with('course')->find($request->batch_id);
                $validated['enrollment_number'] = $this->generateEnrollmentNumber($batch);
            } else {
                $validated['enrollment_number'] = 'UNASSIGNED-' . time();
            }

            $student = Student::create($validated);

            // Generate invoices if student has a batch assigned
            if ($student && $student->batch_id) {
                $this->invoiceService->generateTermInvoicesForStudent($student);
            }
            
            return $student;
        });

        // Send new admission notification
        app(\App\Services\NotificationService::class)->sendAcademicNotification('new_admission', [
            'student_id' => $student->id,
            'student_name' => $student->name,
            'course_name' => $student->batch->course->name ?? 'Unknown Course',
        ]);

        return redirect()->route('admin.students.index')->with('success', 'Student created successfully. Enrollment number: ' . $student->enrollment_number);
    }

    
    public function show(Request $request, Student $student)
    {
        // Load relationships but handle missing admission relationship gracefully
        $student->load(['batch.course', 'invoices.payments']);
        
        // Try to load admission relationship safely
        try {
            $student->load('admission');
        } catch (\Exception $e) {
            // If admission relationship fails, continue without it
        }
        
        $invoiceIds = $student->invoices->pluck('id');
        $activities = Activity::where(function($query) use ($student, $invoiceIds) {
            $query->where(function($q) use ($student) {
                $q->where('subject_type', Student::class)
                  ->where('subject_id', $student->id);
            })->orWhere(function($q) use ($invoiceIds) {
                $q->where('subject_type', \App\Models\Invoice::class)
                  ->whereIn('subject_id', $invoiceIds);
            });
        })->latest()->limit(50)->get();
        
        $month = $request->input('month', Carbon::now()->format('Y-m'));
        $startOfMonth = Carbon::parse($month)->startOfMonth();
        $endOfMonth = Carbon::parse($month)->endOfMonth();

        $attendances = Attendance::where('student_id', $student->id)
            ->whereBetween('attendance_date', [$startOfMonth, $endOfMonth])
            ->get()->keyBy(fn($item) => Carbon::parse($item->attendance_date)->format('Y-m-d'));
            
        $holidays = Holiday::whereBetween('date', [$startOfMonth, $endOfMonth])
            ->get()->keyBy(fn($item) => Carbon::parse($item->date)->format('Y-m-d'));

        $presentDays = $attendances->where('status', 'present')->count();
        $absentDays = $attendances->where('status', 'absent')->count();
        $totalWorkingDays = $startOfMonth->diffInDaysFiltered(fn($date) => !$date->isSunday() && !isset($holidays[$date->format('Y-m-d')]), $endOfMonth);
        $attendancePercentage = ($totalWorkingDays > 0) ? round(($presentDays / $totalWorkingDays) * 100, 1) : 0;

        // Calculate attendance data for charts and statistics
        $attendanceData = [
            'present_days' => $presentDays,
            'absent_days' => $absentDays,
            'total_working_days' => $totalWorkingDays,
            'attendance_percentage' => $attendancePercentage,
            'month_name' => Carbon::parse($month)->format('F Y'),
            'late_days' => $attendances->where('status', 'late')->count(),
            'excused_days' => $attendances->where('status', 'excused')->count(),
        ];

        // Calculate overall attendance statistics (not just current month)
        $overallAttendances = Attendance::where('student_id', $student->id)->get();
        $overallPresentDays = $overallAttendances->where('status', 'present')->count();
        $overallTotalDays = $overallAttendances->count();
        $overallAttendancePercentage = ($overallTotalDays > 0) ? round(($overallPresentDays / $overallTotalDays) * 100, 1) : 0;

        $overallAttendanceData = [
            'present_days' => $overallPresentDays,
            'absent_days' => $overallAttendances->where('status', 'absent')->count(),
            'total_days' => $overallTotalDays,
            'attendance_percentage' => $overallAttendancePercentage,
            'late_days' => $overallAttendances->where('status', 'late')->count(),
            'excused_days' => $overallAttendances->where('status', 'excused')->count(),
        ];

        return view('admin.students.show', compact(
            'student', 
            'activities', 
            'attendances', 
            'holidays', 
            'month', 
            'presentDays', 
            'absentDays', 
            'totalWorkingDays', 
            'attendancePercentage',
            'attendanceData',
            'overallAttendanceData'
        ));
    }
    
    public function edit(Student $student)
    {
        $batches = Batch::with('course')->get();
        return view('admin.students.edit', compact('student', 'batches'));
    }
    
    // ✅ SINGLE update() method with enhanced mobile validation
    public function update(Request $request, Student $student)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('students')->ignore($student->id)],
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
            'batch_id' => 'nullable|exists:batches,id',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ], [
            // Custom error messages
            'student_mobile.unique' => 'This student mobile number is already registered with another student.',
            'father_mobile.unique' => 'This father mobile number is already registered with another student.',
            'student_mobile.regex' => 'Student mobile number must be a valid 10-digit Indian mobile number starting with 6, 7, 8, or 9.',
            'father_mobile.regex' => 'Father mobile number must be a valid 10-digit Indian mobile number starting with 6, 7, 8, or 9.',
        ]);

        // ✅ Additional validation: Check if student and father mobiles are the same
        if ($validated['student_mobile'] && $validated['father_mobile'] && 
            $validated['student_mobile'] === $validated['father_mobile']) {
            return back()->withErrors([
                'father_mobile' => 'Father mobile number cannot be the same as student mobile number.'
            ])->withInput();
        }

        // ✅ Additional validation: Check cross-field duplicates (excluding current student)
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

        // If batch changed, update enrollment number and generate new invoices
        if ($validated['batch_id'] && $validated['batch_id'] != $originalBatchId) {
            $batch = Batch::with('course')->find($validated['batch_id']);
            $validated['enrollment_number'] = $this->generateEnrollmentNumber($batch);
            
            // Delete existing invoices for the old batch
            $student->invoices()->delete();
            
            // Generate new invoices for the new batch
            $this->invoiceService->generateTermInvoicesForStudent($student->fresh()); 
        }

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
        $settings = Setting::all()->keyBy('key');
        $collegePrefix = $settings['enrollment_prefix']->value ?? 'UV';
        $coursePrefix = $batch->course->enrollment_prefix ?? strtoupper(substr($batch->course->name, 0, 4));
        $batchYear = Carbon::parse($batch->created_at)->format('y');
        
        // Use a more robust approach to prevent duplicates
        $maxAttempts = 100;
        $attempt = 0;
        
        do {
            // Get count of students already in this batch and add attempt number for uniqueness
            $studentCount = Student::where('batch_id', $batch->id)->count() + $attempt + 1;
            $paddedRollNo = str_pad($studentCount, 3, '0', STR_PAD_LEFT);
            $enrollmentNumber = "{$collegePrefix}-{$coursePrefix}-{$batchYear}{$paddedRollNo}";
            
            // Check if this enrollment number already exists
            $exists = Student::where('enrollment_number', $enrollmentNumber)->exists();
            $attempt++;
            
        } while ($exists && $attempt < $maxAttempts);
        
        if ($attempt >= $maxAttempts) {
            // Fallback to timestamp-based unique number
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
        $batches = Batch::where('course_id', $course->id)
                       ->orderBy('name')
                       ->get(['id', 'name']);
        
        return response()->json($batches);
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
        
        $studentsWithPhotos = $students->map(function ($student) {
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
}