<?php
// In app/Http/Controllers/Api/WebhookController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WebhookSecurityService; // Import the new service
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\Setting;
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
}