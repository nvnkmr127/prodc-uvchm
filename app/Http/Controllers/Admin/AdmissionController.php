<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\Course;
use App\Models\Enquiry;
use App\Models\FollowUp;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdmissionController extends Controller
{
    /**
     * Display a listing of admissions
     */
    public function index(Request $request)
    {
        // Get selected academic year from session (only if table exists)
        $selectedAcademicYearId = null;
        if (\Schema::hasTable('academic_years') && \Schema::hasColumn('admissions', 'academic_year_id')) {
            $selectedAcademicYearId = session('selected_academic_year_id', \App\Models\AcademicYear::where('is_current', true)->value('id'));
        }

        $query = Admission::with(['course', 'enquiry']);

        // Stats calculation (unfiltered pool for cards, or filtered? Usually unfiltered by status but filtered by course/academic year)
        $statsQuery = Admission::query();
        if ($selectedAcademicYearId) {
            $statsQuery->where('academic_year_id', $selectedAcademicYearId);
        }
        if ($request->filled('course_id')) {
            $statsQuery->where('course_id', $request->course_id);
        }

        $counts = $statsQuery->selectRaw('status, count(*) as count')->groupBy('status')->pluck('count', 'status')->toArray();
        $totalCounts = [
            'pending' => $counts['pending'] ?? 0,
            'approved' => $counts['approved'] ?? 0,
            'rejected' => $counts['rejected'] ?? 0,
            'total' => array_sum($counts),
        ];

        // Apply filters to list query
        if ($selectedAcademicYearId) {
            $query->where('academic_year_id', $selectedAcademicYearId);
        }

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('full_name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('email', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('phone_number', 'LIKE', "%{$searchTerm}%");
            });
        }

        // Get admissions with pagination
        $admissions = $query->latest()->paginate(15)->withQueryString();

        // Get courses for filter dropdown
        $courses = Course::orderBy('name')->get();

        return view('admin.admissions.index', compact('admissions', 'courses', 'totalCounts'));
    }

    /**
     * Show the form for creating a new admission from an enquiry
     */
    public function create(Enquiry $enquiry)
    {
        $courses = Course::orderBy('name')->get();

        return view('admin.admissions.create', compact('enquiry', 'courses'));
    }

    /**
     * Store a newly created admission
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'enquiry_id' => 'required|exists:enquiries,id',
            'course_id' => 'required|exists:courses,id',
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone_number' => 'required|string|max:20',
            'address' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'guardian_name' => 'nullable|string|max:255',
            'guardian_phone' => 'nullable|string|max:20',
            'previous_education' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated) {
            // Create the admission
            $admission = Admission::create($validated + [
                'status' => 'pending',
                'application_date' => now(),
                'processed_by' => Auth::id(),
            ]);

            // Update the enquiry status
            $enquiry = Enquiry::findOrFail($validated['enquiry_id']);
            $enquiry->update(['status' => 'converted']);

            return $admission;
        });

        return redirect()->route('admin.admissions.index')
            ->with('success', 'Admission application created successfully.');
    }

    /**
     * Display the specified admission
     */
    public function show(Admission $admission)
    {
        $admission->load(['course', 'enquiry', 'followUps.user']);

        return view('admin.admissions.show', compact('admission'));
    }

    /**
     * Show the form for editing the specified admission
     */
    public function edit(Admission $admission)
    {
        $courses = Course::orderBy('name')->get();

        return view('admin.admissions.edit', compact('admission', 'courses'));
    }

    /**
     * Update the specified admission
     */
    public function update(Request $request, Admission $admission)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone_number' => 'required|string|max:20',
            'address' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'guardian_name' => 'nullable|string|max:255',
            'guardian_phone' => 'nullable|string|max:20',
            'previous_education' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $admission->update($validated);

        return redirect()->route('admin.admissions.show', $admission)
            ->with('success', 'Admission updated successfully.');
    }

    /**
     * Approve an admission and optionally create a student record
     */
    public function approve(Request $request, Admission $admission)
    {
        if ($admission->status !== 'pending') {
            return back()->with('error', 'Only pending admissions can be approved.');
        }

        DB::transaction(function () use ($admission, $request) {
            // Update admission status
            $admission->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => Auth::id(),
            ]);

            // Create student record if requested
            if ($request->boolean('create_student')) {
                $this->createStudentFromAdmission($admission);
            }

            // Add follow-up entry
            FollowUp::create([
                'admission_id' => $admission->id,
                'user_id' => Auth::id(),
                'follow_up_date' => now(),
                'notes' => 'Admission approved and processed.',
                'status' => 'completed',
            ]);
        });

        return back()->with('success', 'Admission approved successfully.');
    }

    /**
     * Reject an admission
     */
    public function reject(Request $request, Admission $admission)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        if ($admission->status !== 'pending') {
            return back()->with('error', 'Only pending admissions can be rejected.');
        }

        DB::transaction(function () use ($admission, $request) {
            // Update admission status
            $admission->update([
                'status' => 'rejected',
                'rejected_at' => now(),
                'rejected_by' => Auth::id(),
                'rejection_reason' => $request->rejection_reason,
            ]);

            // Add follow-up entry
            FollowUp::create([
                'admission_id' => $admission->id,
                'user_id' => Auth::id(),
                'follow_up_date' => now(),
                'notes' => 'Admission rejected. Reason: '.$request->rejection_reason,
                'status' => 'completed',
            ]);
        });

        return back()->with('success', 'Admission rejected.');
    }

    /**
     * Add a follow-up note to an admission
     */
    public function addFollowUp(Request $request, Admission $admission)
    {
        $validated = $request->validate([
            'notes' => 'required|string|max:1000',
            'follow_up_date' => 'nullable|date',
            'status' => 'required|in:pending,completed,cancelled',
        ]);

        FollowUp::create([
            'admission_id' => $admission->id,
            'user_id' => Auth::id(),
            'follow_up_date' => $validated['follow_up_date'] ?? now(),
            'notes' => $validated['notes'],
            'status' => $validated['status'],
        ]);

        return back()->with('success', 'Follow-up added successfully.');
    }

    /**
     * Finalize and approve an admission (used for bulk operations)
     */
    public function finalizeAndApprove(Request $request)
    {
        $validated = $request->validate([
            'admission_id' => 'required|exists:admissions,id',
            'create_student' => 'boolean',
        ]);

        $admission = Admission::findOrFail($validated['admission_id']);

        return $this->approve($request, $admission);
    }

    /**
     * Remove the specified admission
     */
    public function destroy(Admission $admission)
    {
        if ($admission->status === 'approved') {
            return back()->with('error', 'Cannot delete approved admissions.');
        }

        $admission->delete();

        return redirect()->route('admin.admissions.index')
            ->with('success', 'Admission deleted successfully.');
    }

    /**
     * Create a student record from an approved admission
     */
    private function createStudentFromAdmission(Admission $admission)
    {
        // Generate enrollment number
        $enrollmentNumber = $this->generateEnrollmentNumber($admission->course);

        Student::create([
            'name' => $admission->full_name,
            'email' => $admission->email,
            'phone_number' => $admission->phone_number,
            'address' => $admission->address,
            'date_of_birth' => $admission->date_of_birth,
            'guardian_name' => $admission->guardian_name,
            'guardian_phone' => $admission->guardian_phone,
            'enrollment_number' => $enrollmentNumber,
            'course_id' => $admission->course_id,
            'admission_date' => now(),
            'status' => 'active',
            'batch_id' => null, // Will be assigned later
        ]);
    }

    /**
     * Generate enrollment number for a student
     */
    private function generateEnrollmentNumber(Course $course)
    {
        $prefix = $course->enrollment_prefix ?? 'STU';
        $year = date('Y');

        // Get the last enrollment number for this course and year
        $lastStudent = Student::where('course_id', $course->id)
            ->where('enrollment_number', 'LIKE', $prefix.$year.'%')
            ->orderBy('enrollment_number', 'desc')
            ->first();

        if ($lastStudent) {
            // Extract the sequence number and increment
            $lastSequence = (int) substr($lastStudent->enrollment_number, -4);
            $nextSequence = $lastSequence + 1;
        } else {
            $nextSequence = 1;
        }

        return $prefix.$year.str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
    }
}
