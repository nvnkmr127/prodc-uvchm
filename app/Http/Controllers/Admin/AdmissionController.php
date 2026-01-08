<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\Course;
use App\Models\Enquiry;
use App\Models\Student;
use App\Models\FollowUp;
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

        // Filter by academic year
        if ($selectedAcademicYearId) {
            $query->where('academic_year_id', $selectedAcademicYearId);
        }

        // Apply filters
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

        return view('admin.admissions.index', compact('admissions', 'courses'));
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
     * NOTE: Admissions are typically created via PublicEnquiryController or internal Enquiry conversion.
     * This method is retained if manual internal creation is needed, but typically unused in this specific workflow.
     * Use create() to view form.
     */
    // public function store(Request $request) ... [Removed as primary flow is via Enquiry]
    // Keeping store() if it was used by internal create form, but review said it's dead.
    // Wait, the plan said remove store/edit/update/destroy.
    // Let's remove them completely.

    /**
     * Remove the specified admission
     */
    // destroy removed.

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
            ->where('enrollment_number', 'LIKE', $prefix . $year . '%')
            ->orderBy('enrollment_number', 'desc')
            ->first();

        if ($lastStudent) {
            // Extract the sequence number and increment
            $lastSequence = (int) substr($lastStudent->enrollment_number, -4);
            $nextSequence = $lastSequence + 1;
        } else {
            $nextSequence = 1;
        }

        return $prefix . $year . str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
    }
}