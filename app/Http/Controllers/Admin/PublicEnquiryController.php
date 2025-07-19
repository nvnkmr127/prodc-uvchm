<?php

namespace App\Http\Controllers;

use App\Models\Enquiry;
use App\Models\Course;
use App\Services\NotificationService; // ADD THIS IMPORT
use Illuminate\Http\Request;

class PublicEnquiryController extends Controller
{
    protected $notificationService; // ADD THIS PROPERTY

    // ADD CONSTRUCTOR FOR DEPENDENCY INJECTION
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function create()
    {
        $courses = Course::orderBy('name')->get();
        return view('public.enquiry_form', compact('courses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'student_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'mobile' => 'required|string|max:20',
            'course_id' => 'required|exists:courses,id',
            'message' => 'nullable|string',
            'referral_name' => 'nullable|string|max:255',
            'source' => 'required|string|max:255',
        ]);

        $enquiry = $validated = $request->validate([
            'student_name' => 'required|string|max:255',
            'phone_number' => 'nullable|string|max:255',
            'email' => 'required|email|max:255',
            'gender' => 'nullable|string|max:255',
            'date_of_birth' => 'required|date',
            'address' => 'nullable|string|max:255',
            'education_qualification' => 'nullable|string|max:255',
            'course_id' => 'required|exists:courses,id',
            'source' => 'nullable|string|max:255',
            'referral_name' => 'required|string|max:255',
            'notes' => 'nullable|string|max:255',
            'next_follow_up_date' => 'required|date',
            'status' => 'nullable|string|max:255',
            'assigned_to_user_id' => 'required|exists:assigned_to_users,id',
        ]);
        Enquiry::create($validated);
        $course = Course::find($request->course_id);

        // 🔔 NOTIFY ADMINS OF NEW ENQUIRY
        $this->notificationService->send([
            'title' => 'New Enquiry Received',
            'message' => "New enquiry from {$enquiry->student_name} for {$course->name}",
            'type' => 'info',
            'category' => 'academic',
            'priority' => 'normal',
            'roles' => ['super-admin', 'college-admin'],
            'action_url' => route('admin.enquiries.show', $enquiry->id),
            'action_text' => 'Review Enquiry',
            'data' => [
                'enquiry_id' => $enquiry->id,
                'student_name' => $enquiry->student_name,
                'email' => $enquiry->email,
                'mobile' => $enquiry->mobile,
                'course_name' => $course->name,
                'source' => $enquiry->source,
                'referral_name' => $enquiry->referral_name,
                'message' => $enquiry->message,
            ]
        ]);

        // 🔔 SPECIAL NOTIFICATION for referral enquiries
        if ($enquiry->referral_name) {
            $this->notificationService->send([
                'title' => 'Referral Enquiry Received',
                'message' => "Referral enquiry from {$enquiry->student_name} (referred by {$enquiry->referral_name})",
                'type' => 'success',
                'category' => 'academic',
                'priority' => 'high',
                'roles' => ['super-admin', 'college-admin'],
                'action_url' => route('admin.enquiries.show', $enquiry->id),
                'action_text' => 'Review Referral',
                'data' => [
                    'enquiry_id' => $enquiry->id,
                    'is_referral' => true,
                    'referral_name' => $enquiry->referral_name,
                    'student_name' => $enquiry->student_name,
                    'course_name' => $course->name,
                ]
            ]);
        }

        return redirect()->route('enquiry.success')->with('success', 'Your enquiry has been submitted successfully!');
    }
}