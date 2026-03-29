<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InboundWebhook;
use App\Models\Enquiry;
use App\Models\Course;
use App\Services\LeadDistributionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InboundWebhookController extends Controller
{
    /**
     * Handle the incoming webhook call for a specific slug.
     */
    public function handle(Request $request, $slug, LeadDistributionService $leadService)
    {
        $webhook = InboundWebhook::where('slug', $slug)->where('is_active', true)->first();

        if (!$webhook) {
            return response()->json(['message' => 'Webhook not found or inactive'], 404);
        }

        // 1. Security Check
        $token = $request->header('X-Webhook-Token') ?? $request->input('token');
        if ($webhook->secret_token && $token !== $webhook->secret_token) {
            $webhook->increment('failure_count');
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payload = $request->all();

        if (!is_array($payload) || empty($payload)) {
            $webhook->increment('failure_count');
            return response()->json(['message' => 'Invalid data format. Expected JSON.'], 400);
        }
        
        // Update stats and store last payload for mapping UI
        $webhook->update([
            'last_called_at' => now(),
            'last_payload' => $payload
        ]);

        try {
            // 2. Mapping Logic
            $rules = $webhook->mapping_rules ?? [];
            
            // Extract data using rules or fallback to common names
            $studentName  = $this->getData($payload, $rules['student_name'] ?? 'full_name');
            $phoneNumber  = $this->getData($payload, $rules['phone_number'] ?? 'phone_number');
            $courseName   = $this->getData($payload, $rules['course_name'] ?? 'course');
            $notes        = $this->getData($payload, $rules['notes'] ?? 'notes');
            $gender       = $this->getData($payload, $rules['gender'] ?? 'gender');
            $dob          = $this->getData($payload, $rules['date_of_birth'] ?? 'dob');
            $address      = $this->getData($payload, $rules['address'] ?? 'address');
            $qualification = $this->getData($payload, $rules['education_qualification'] ?? 'qualification');
            $referral     = $this->getData($payload, $rules['referral_name'] ?? 'referral');
            $email        = $this->getData($payload, $rules['email'] ?? 'email');

            if (!$studentName || !$phoneNumber) {
                $webhook->increment('failure_count');
                return response()->json(['message' => 'Name and Phone are required'], 422);
            }

            // 2.5 Duplicate Check (Simple window-based check: same name & phone within 24h)
            $isDuplicate = Enquiry::where('phone_number', $phoneNumber)
                ->where('student_name', $studentName)
                ->where('created_at', '>=', now()->subHours(24))
                ->exists();

            if ($isDuplicate) {
                Log::info("Duplicate lead ignored from webhook [{$webhook->name}]: {$studentName} ({$phoneNumber})");
                return response()->json(['message' => 'Duplicate lead ignored. Recieved within last 24h.'], 200);
            }

            // 3. Find Course if provided
            $courseId = null;
            if ($courseName) {
                // If the mapped value is a numeric ID, check if it exists
                if (is_numeric($courseName)) {
                    $courseId = Course::where('id', $courseName)->exists() ? $courseName : null;
                }
                
                // If not found yet, try finding by name or code
                if (!$courseId) {
                    $course = Course::where('name', 'like', "%{$courseName}%")
                                   ->orWhere('code', 'like', "%{$courseName}%")
                                   ->first();
                    $courseId = $course?->id;
                }
            }

            // 4. Distribution
            $assignedUserId = $leadService->getNextCounselorId();

            // 5. Create Enquiry
            $enquiryData = [
                'student_name' => $studentName,
                'phone_number' => $phoneNumber,
                'email'        => $email,
                'gender'       => $gender,
                'date_of_birth'=> $dob ? Carbon::parse($dob)->toDateString() : null,
                'address'      => $address,
                'education_qualification' => $qualification,
                'course_id'    => $courseId,
                'source'       => $webhook->source_name ?? 'Inbound Webhook',
                'referral_name'=> $referral,
                'notes'        => trim($notes),
                'status'       => 'New',
                'assigned_to_user_id' => $assignedUserId,
                'next_follow_up_date' => Carbon::today()->addDays((int)$webhook->auto_followup_days)->toDateString(),
            ];

            $enquiry = Enquiry::create($enquiryData);

            $webhook->increment('success_count');
            
            Log::info("Lead created via dynamic webhook [{$webhook->name}]: #{$enquiry->id}");

            return response()->json([
                'message' => 'Lead processed successfully',
                'enquiry_id' => $enquiry->id
            ], 201);

        } catch (\Exception $e) {
            $webhook->increment('failure_count');
            Log::error("Dynamic Webhook Error [{$webhook->name}]: " . $e->getMessage());
            return response()->json(['message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Helper to get data from nested array using dot notation or simple key.
     */
    private function getData($data, $key)
    {
        if (empty($key)) return null;
        
        // Support dot notation for nested JSON like "data.user.name"
        return data_get($data, $key);
    }
}
