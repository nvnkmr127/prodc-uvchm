<?php

namespace App\Http\Controllers;

use App\Models\Enquiry;
use App\Models\Course;
use App\Services\LeadDistributionService; // ✅ ADD THIS IMPORT
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // ✅ ADD THIS IMPORT

class PublicEnquiryController extends Controller
{
    public function create()
    {
        $courses = Course::orderBy('name')->get();
        return view('admission_form', compact('courses'));
    }

    public function store(Request $request, LeadDistributionService $leadDistribution)
    {
        $validated = $request->validate([
            'student_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'email' => 'nullable|email|max:255', // ✅ ADD EMAIL VALIDATION
            'gender' => 'nullable|in:Male,Female,Other', // ✅ ADD GENDER VALIDATION
            'date_of_birth' => 'nullable|date', // ✅ ADD DOB VALIDATION
            'address' => 'nullable|string',
            'education_qualification' => 'nullable|string', // ✅ ADD EDUCATION VALIDATION
            'course_id' => 'nullable|exists:courses,id',
            'source' => 'nullable|string|max:255',
            'referral_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        try {
            // Get the next counselor ID from the service
            $nextCounselorId = $leadDistribution->getNextCounselorId();

            $enquiry = Enquiry::create($validated + [
                'assigned_to_user_id' => $nextCounselorId ?? optional(Auth::user())->id,
                'status' => 'New' // ✅ SET DEFAULT STATUS
            ]);
            
            // ✅ REDIRECT TO SUCCESS PAGE INSTEAD OF ADMIN AREA
            return redirect()->route('enquiry.success')->with('success', 'Thank you! Your enquiry has been submitted successfully. Our team will contact you soon.');
            
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to submit enquiry. Please try again.');
        }
    }
}