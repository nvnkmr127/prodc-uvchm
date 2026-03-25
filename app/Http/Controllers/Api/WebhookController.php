<?php
// In app/Http/Controllers/Api/WebhookController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WebhookSecurityService; // Import the new service
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\Setting;
use App\Models\Enquiry;
use App\Models\Course;
use App\Services\LeadDistributionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    protected WebhookSecurityService $securityService;

    // Inject the service via the constructor.
    public function __construct(WebhookSecurityService $securityService)
    {
        $this->securityService = $securityService;
    }

    /**
     * Handle incoming attendance data from the biometric device.
     */
    public function handleBiometric(Request $request)
    {
        // 1. Authenticate the request using the strong HMAC method.
        $signingSecret = Setting::where('key', 'biometric_api_key')->value('value');

        if (!$signingSecret || !$this->securityService->verify($request, $signingSecret)) {
            Log::warning('Unauthorized biometric webhook attempt. Signature validation failed.');
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // 2. Validate the incoming data (this remains the same).
        $validated = $request->validate([
            'EmployeeCode' => 'required|string',
            'LogDateTime' => 'required|date',
            // Add any other fields the device sends
        ]);

        // 3. Process the data (this remains the same).
        try {
            $student = Student::where('enrollment_number', $validated['EmployeeCode'])->first();

            if ($student) {
                $logDateTime = Carbon::parse($validated['LogDateTime']);

                Attendance::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'attendance_date' => $logDateTime->toDateString(),
                    ],
                    [
                        'status' => 'present',
                        'check_in_time' => $logDateTime->toTimeString(),
                    ]
                );

                Log::info("Processed webhook attendance for: {$student->user->name}");
                return response()->json(['message' => 'Success'], 200);

            } else {
                Log::warning("Biometric webhook: Student not found with EmployeeCode: {$validated['EmployeeCode']}");
                return response()->json(['message' => 'Student not found'], 404);
            }
        } catch (\Exception $e) {
            Log::error('Error processing biometric webhook: ' . $e->getMessage());
            return response()->json(['message' => 'Server Error'], 500);
        }
    }

    /**
     * Handle incoming leads from external services like Pabbly Connect or Zapier.
     * This connects Facebook Lead Ads to the enquiry list.
     */
    public function handleExternalLeads(Request $request, LeadDistributionService $leadService)
    {
        // 1. Basic Security Check
        // We look for a token in the query or header to verify the source.
        // It's configurable via Settings > Facebook Leads in the dashboard.
        $webhookToken = Setting::get('facebook_lead_webhook_token') 
                        ?? env('WEBHOOK_TOKEN') 
                        ?? 'b97fcbb4a2fb607a5366fbf06614dcbc';
        $sentToken = $request->header('X-Webhook-Token') ?? $request->input('token');

        if ($webhookToken && $sentToken !== $webhookToken) {
            Log::warning('Unauthorized external lead webhook attempt.');
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // 2. Validate & Sanitize Input
        // Map common Facebook/Pabbly/Zapier field names if they vary
        $data = $request->all();
        
        $fullName = $request->input('full_name') ?? $request->input('name') ?? $request->input('lead_name');
        $phone = $request->input('phone_number') ?? $request->input('phone') ?? $request->input('mobile');
        $courseName = $request->input('course') ?? $request->input('program') ?? $request->input('course_name');
        $notes = $request->input('notes') ?? $request->input('comments') ?? '';
        
        if (!$fullName || !$phone) {
            return response()->json([
                'message' => 'Validation failed: name and phone are required.',
                'received' => $data
            ], 422);
        }

        try {
            // 3. Find Course ID if course name is provided
            $courseId = null;
            if ($courseName) {
                $course = Course::where('name', 'like', "%{$courseName}%")
                               ->orWhere('code', 'like', "%{$courseName}%")
                               ->first();
                $courseId = $course?->id;
                
                if (!$courseId) {
                    $notes .= "\nInternal Note: Interested in Course: {$courseName}";
                }
            }

            // 4. Get Automatic Assignment (Round Robin)
            $assignedUserId = $leadService->getNextCounselorId();

            // 5. Create the Enquiry
            $enquiry = Enquiry::create([
                'student_name' => $fullName,
                'phone_number' => $phone,
                'course_id'    => $courseId,
                'source'       => 'Social Media', // Facebook Campaign
                'notes'        => trim($notes),
                'status'       => 'New',
                'assigned_to_user_id' => $assignedUserId,
            ]);

            Log::info("New lead created via webhook: #{$enquiry->id} - {$fullName}");

            return response()->json([
                'message' => 'Lead processed successfully',
                'enquiry_id' => $enquiry->id,
                'assigned_to' => $enquiry->assignedTo?->name ?? 'None'
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error processing external lead webhook: ' . $e->getMessage());
            return response()->json(['message' => 'Server Error: ' . $e->getMessage()], 500);
        }
    }
}