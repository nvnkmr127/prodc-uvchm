<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InboundWebhook;
use App\Models\InboundWebhookLog;
use App\Models\Enquiry;
use App\Models\Course;
use App\Services\LeadDistributionService;
use Illuminate\Support\Str;
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
        Log::channel('inbound-webhooks')->info('Inbound webhook request received', [
            'slug' => $slug,
            'ip' => $request->ip(),
            'method' => $request->method(),
            'payload_keys' => array_keys($request->all()),
        ]);

        $webhook = InboundWebhook::where('slug', $slug)->where('is_active', true)->first();

        if (!$webhook) {
            Log::channel('inbound-webhooks')->warning('Inbound webhook not found or inactive', [
                'slug' => $slug,
                'ip' => $request->ip(),
            ]);

            return response()->json(['message' => 'Webhook not found or inactive'], 404);
        }

        // 1. Security Check
        $token = $this->extractIncomingToken($request);
        
        $isAuthorized = true;
        $authError = null;

        if ($webhook->secret_token) {
            if (!$token) {
                $isAuthorized = false;
                $authError = 'No token provided in headers or parameters';
            } elseif (!$this->tokenMatches($token, $webhook->secret_token)) {
                $isAuthorized = false;
                $authError = 'Invalid security token provided';
            }
        }

        if (!$isAuthorized) {
            $webhook->increment('failure_count');

            Log::channel('inbound-webhooks')->warning('Inbound webhook unauthorized request', [
                'webhook_id' => $webhook->id,
                'slug' => $webhook->slug,
                'ip' => $request->ip(),
                'error' => $authError,
                'token_present' => (bool) $token,
                'token_masked' => $token ? Str::mask($token, '*', 2, -2) : null,
                'headers' => [
                    'x-webhook-token' => $request->hasHeader('X-Webhook-Token'),
                    'authorization' => $request->hasHeader('Authorization'),
                    'x-api-key' => $request->hasHeader('X-API-Key'),
                ],
            ]);

            $this->logCall($webhook->id, $request->all(), 401, 'Unauthorized: ' . $authError);
            return response()->json([
                'message' => 'Unauthorized',
                'error' => $authError,
                'tip' => 'Ensure you are sending the X-Webhook-Token header or token parameter correctly.'
            ], 401);
        }


        $payload = $request->all();

        if (!is_array($payload) || empty($payload)) {
            $webhook->increment('failure_count');

            Log::channel('inbound-webhooks')->warning('Inbound webhook invalid payload', [
                'webhook_id' => $webhook->id,
                'slug' => $webhook->slug,
                'ip' => $request->ip(),
            ]);

            $this->logCall($webhook->id, $request->all(), 400, 'Invalid data format. Expected JSON.');
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

                Log::channel('inbound-webhooks')->warning('Inbound webhook missing required fields', [
                    'webhook_id' => $webhook->id,
                    'slug' => $webhook->slug,
                    'has_student_name' => (bool) $studentName,
                    'has_phone_number' => (bool) $phoneNumber,
                ]);

                $this->logCall($webhook->id, $payload, 422, 'Name and Phone are required');
                return response()->json(['message' => 'Name and Phone are required'], 422);
            }

            // 2.5 Duplicate Check (Simple window-based check: same name & phone within 24h)
            $isDuplicate = Enquiry::where('phone_number', $phoneNumber)
                ->where('student_name', $studentName)
                ->where('created_at', '>=', now()->subHours(24))
                ->exists();

            if ($isDuplicate) {
                Log::channel('inbound-webhooks')->info("Duplicate lead ignored from webhook [{$webhook->name}]: {$studentName} ({$phoneNumber})", [
                    'webhook_id' => $webhook->id,
                    'slug' => $webhook->slug,
                    'student_name' => $studentName,
                    'phone_number' => $phoneNumber,
                ]);
                $this->logCall($webhook->id, $payload, 200, 'Duplicate lead ignored');
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
            
            Log::channel('inbound-webhooks')->info("Lead created via dynamic webhook [{$webhook->name}]: #{$enquiry->id}", [
                'webhook_id' => $webhook->id,
                'slug' => $webhook->slug,
                'enquiry_id' => $enquiry->id,
                'assigned_to_user_id' => $assignedUserId,
            ]);

            $this->logCall($webhook->id, $payload, 201, null, $enquiry->id);

            return response()->json([
                'message' => 'Lead processed successfully',
                'enquiry_id' => $enquiry->id
            ], 201);

        } catch (\Exception $e) {
            $webhook->increment('failure_count');

            Log::channel('inbound-webhooks')->error("Dynamic Webhook Error [{$webhook->name}]: " . $e->getMessage(), [
                'webhook_id' => $webhook->id,
                'slug' => $webhook->slug,
                'exception' => get_class($e),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            $this->logCall($webhook->id, $payload, 500, $e->getMessage());
            return response()->json(['message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Helper to get data from nested array using dot notation or advanced array search.
     * Supports syntax: "fields[type=contact].answer.first_name"
     */
    private function getData($data, $key)
    {
        if (empty($key)) return null;

        // If the key uses advanced syntax like fields[type=contact].value
        if (str_contains($key, '[') && str_contains($key, ']')) {
            return $this->resolveAdvancedPath($data, $key);
        }
        
        // Support dot notation for nested JSON like "data.user.name"
        return data_get($data, $key);
    }

    /**
     * Resolve advanced paths like "fields[type=contact].answer.first_name"
     */
    private function resolveAdvancedPath($data, $path)
    {
        $segments = explode('.', $path);
        $current = $data;

        foreach ($segments as $segment) {
            if (str_contains($segment, '[') && str_contains($segment, ']')) {
                // Handle segments like fields[type=contact]
                preg_match('/(.*)\[(.*)=(.*)\]/', $segment, $matches);
                if (count($matches) === 4) {
                    $arrayKey = $matches[1];
                    $searchKey = $matches[2];
                    $searchValue = $matches[3];

                    $array = data_get($current, $arrayKey);
                    if (!is_array($array)) return null;

                    // Find the item in the array
                    $found = false;
                    foreach ($array as $item) {
                        if (isset($item[$searchKey]) && (string)$item[$searchKey] === (string)$searchValue) {
                            $current = $item;
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) return null;
                    continue;
                }
            }

            $current = data_get($current, $segment);
            if (is_null($current)) return null;
        }

        return $current;
    }


    /**
     * Extract token from common webhook auth locations.
     */
    private function extractIncomingToken(Request $request): ?string
    {
        $authorization = $request->header('Authorization');

        if (is_string($authorization) && str_starts_with($authorization, 'Bearer ')) {
            return trim(substr($authorization, 7));
        }

        return $request->header('X-Webhook-Token')
            ?? $request->header('X-API-Key')
            ?? $request->input('token')
            ?? $request->input('secret_token')
            ?? $request->input('api_key');
    }

    /**
     * Timing-safe token comparison.
     */
    private function tokenMatches(?string $incomingToken, ?string $expectedToken): bool
    {
        if ($incomingToken === null || $expectedToken === null) {
            return false;
        }

        return hash_equals((string) $expectedToken, (string) $incomingToken);
    }
    /**
     * Log the webhook call to database.
     */
    private function logCall($webhookId, $payload, $statusCode, $errorMessage = null, $enquiryId = null)
    {
        try {
            InboundWebhookLog::create([
                'inbound_webhook_id' => $webhookId,
                'payload' => $payload,
                'status_code' => $statusCode,
                'error_message' => $errorMessage,
                'ip_address' => request()->ip(),
                'method' => request()->method(),
                'enquiry_id' => $enquiryId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log inbound webhook call: ' . $e->getMessage());
        }
    }
}
