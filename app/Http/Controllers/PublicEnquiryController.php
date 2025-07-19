<?php

namespace App\Http\Controllers;

use App\Models\Enquiry; // CHANGED: We now use the Enquiry model
use App\Models\Course;
use Illuminate\Http\Request;

// RENAMED: The class is now PublicEnquiryController to be more descriptive
class PublicEnquiryController extends Controller
{
    // This method can be renamed to showPublicEnquiryForm() for clarity
    public function create()
    {
        $courses = Course::orderBy('name')->get();
        // You might want to rename 'admission_form' to 'public_enquiry_form'
        return view('admission_form', compact('courses'));
    }

    // This method now creates an Enquiry
   public function store(Request $request, LeadDistributionService $leadDistribution) // MODIFIED: Inject the service
    {
        $validated = $request->validate([
            'student_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'address' => 'nullable|string',
            'course_id' => 'nullable|exists:courses,id',
            'source' => 'nullable|string|max:255',
            'referral_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        // ADDED: Get the next counselor ID from the service
        $nextCounselorId = $leadDistribution->getNextCounselorId();

        // MODIFIED: Use the ID from the service, with the logged-in user as a fallback
        $enquiry = Enquiry::create($validated + [
            'assigned_to_user_id' => $nextCounselorId ?? Auth::id()
        ]);
        
        // Redirect directly to the manage page for the new enquiry
        return redirect()->route('admin.enquiries.edit', $enquiry)->with('success', 'Enquiry logged successfully and assigned.');
    }
}