<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\Enquiry; // ADDED
use App\Models\Student;
use App\Models\Course;
use App\Models\FeeStructure;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
// use App\Traits\GeneratesThreePartInvoices; // This trait was not provided, but assuming it exists
use Carbon\Carbon;

class AdmissionController extends Controller
{
    public function index(Request $request)
    {
        $query = Admission::with('course');

        if ($request->filled('search')) {
            $query->where('full_name', 'LIKE', '%' . $request->search . '%');
        }
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Changed from paginate to get() for consistency, change back if pagination is essential
        $admissions = $query->latest()->get(); 
        $courses = Course::orderBy('name')->get();

        return view('admin.admissions.index', compact('admissions', 'courses'));
    }
    
    public function show(Admission $admission)
    {
        return view('admin.admissions.show', compact('admission'));
    }

    // ##### NEW METHOD TO SHOW THE FINALIZATION FORM #####
    public function create(Enquiry $enquiry)
    {
        // Pass the enquiry object to a new view for finalization
        return view('admin.admissions.create', compact('enquiry'));
    }

    // ##### NEW METHOD TO FINALIZE AND APPROVE IN ONE STEP #####
    public function finalizeAndApprove(Request $request)
    {
        // 1. Validate the final required fields from the form
        $validated = $request->validate([
            'enquiry_id' => 'required|exists:enquiries,id',
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:students,email', // Check uniqueness on the students table
            'gender' => 'required|in:Male,Female,Other',
            'phone_number' => 'required|string|max:20',
            'date_of_birth' => 'required|date',
            'address' => 'required|string',
            'course_id' => 'required|exists:courses,id',
        ]);
        
        $enquiry = Enquiry::findOrFail($validated['enquiry_id']);
        $course = Course::find($validated['course_id']);

        // 2. Create the final Admission record
        $admission = Admission::create([
            'enquiry_id' => $enquiry->id,
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'phone_number' => $validated['phone_number'],
            'date_of_birth' => $validated['date_of_birth'],
            'address' => $validated['address'],
            'course_id' => $validated['course_id'],
            'source' => $enquiry->source,
            'referral_name' => $enquiry->referral_name,
            'status' => 'approved', // Set status to approved directly
        ]);

        // 3. Update the original Enquiry status
        $enquiry->update(['status' => 'Admitted']);
        // 🔥 Fire the webhook event
        event(new \App\Events\AdmissionApproved($admission));
        // --- ALL LOGIC FROM OLD 'approve' METHOD IS NOW HERE ---

        // 4. Generate Smart Enrollment ID
        $studentCountInCourse = Student::whereHas('batch.course', fn($q) => $q->where('id', $course->id))->count();
        $nextId = $studentCountInCourse + 1;
        $prefix = $course->enrollment_prefix ?? 'STD';
        $enrollmentNumber = $prefix . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

        // 5. Create the Student Record
        $student = Student::create([
            'admission_id' => $admission->id,
            'name' => $admission->full_name,
            'email' => $admission->email,
            'gender' => $validated['gender'],
            'student_mobile' => $admission->phone_number,
            'village' => $admission->address,
            'admission_date' => now(),
            'enrollment_number' => $enrollmentNumber,
            'status' => 'active',
        ]);

        // 6. Automated Invoicing Logic
        $this->generateInvoicesForStudent($student, $course);
        
        return redirect()->route('admin.students.show', $student)->with('success', 'Admission finalized and student enrolled successfully!');
    }

    // The old 'approve' and 'reject' methods can be removed or kept for other purposes,
    // but the main workflow no longer uses them.
    public function reject(Admission $admission)
    {
        // Update both admission and related enquiry if it exists
        if ($admission->enquiry) {
            $admission->enquiry->update(['status' => 'Not Interested']);
        }
        $admission->update(['status' => 'rejected']);
        return redirect()->route('admin.admissions.index')->with('success', 'Admission has been rejected.');
    }

    /**
     * Helper function to contain the invoicing logic.
     */
    private function generateInvoicesForStudent(Student $student, Course $course)
    {
        $feeItems = FeeStructure::where('course_id', $course->id)->get();
        if ($feeItems->isEmpty()) {
            return; // No fee structure, so no invoices to create
        }

        $totalCourseFee = $feeItems->sum('amount');
        // This setting() helper is from a popular settings package, assuming it's available
        $discountPercentage = (float) setting('womens_discount_percentage', 0);
        $concessionAmount = 0;
        $concessionNotes = null;

        if ($student->gender === 'Female' && $discountPercentage > 0) {
            $concessionAmount = ($totalCourseFee * $discountPercentage) / 100;
            $concessionNotes = "Women's Discount (" . $discountPercentage . "%) Applied";
        }
        
        $payableAmount = $totalCourseFee - $concessionAmount;
        $installmentAmount = round($payableAmount / 3, 2);

        $dueDates = [
            now()->addDays(15),
            now()->addMonths(4),
            now()->addMonths(8),
        ];

        for ($i = 0; $i < 3; $i++) {
            $invoice = Invoice::create([
                'token' => Str::uuid(),
                'student_id' => $student->id,
                'invoice_number' => 'INV-TERM' . ($i + 1) . '-S' . $student->id,
                'issue_date' => now(),
                'due_date' => $dueDates[$i],
                'total_amount' => $installmentAmount,
                'concession_amount' => ($i === 0) ? $concessionAmount : 0,
                'concession_notes' => ($i === 0) ? $concessionNotes : null,
                'paid_amount' => 0,
                'due_amount' => ($i === 0) ? $installmentAmount - $concessionAmount : $installmentAmount,
                'status' => 'unpaid',
            ]);

            $invoice->items()->create(['description' => 'Installment ' . ($i + 1) . ' Fee', 'amount' => $installmentAmount]);
        }
    }
}