<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Student;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StudentPortalProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure student_profile_requests table exists and has necessary columns
        // (Migrations run via RefreshDatabase)
    }

    public function test_student_can_request_mobile_update_and_it_logs_activity()
    {
        // Mock the geolocation API
        Http::fake([
            'ip-api.com/*' => Http::response([
                'status' => 'success',
                'country' => 'India',
                'city' => 'Hyderabad',
                'lat' => 17.3850,
                'lon' => 78.4867
            ], 200)
        ]);

        $student = Student::factory()->create([
            'student_mobile' => '9876543210',
            'enrollment_number' => 'TEST001'
        ]);

        // Simulate login session
        session(['student_portal_auth' => $student->id]);
        session(['student_portal_mobile' => '9876543210']);

        $response = $this->postJson(route('student.request.update'), [
            'field_group' => 'personal',
            'mobile_type' => 'student',
            'mobile_number' => '1234567890'
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Request submitted for approval.']);

        // Check if request was stored
        $this->assertDatabaseHas('student_profile_requests', [
            'student_id' => $student->id,
            'field_group' => 'personal',
            'status' => 'pending'
        ]);

        // Check if activity was logged
        $this->assertDatabaseHas('student_portal_activity_logs', [
            'student_id' => $student->id,
            'action' => 'profile_update_request'
        ]);
    }

    public function test_profile_update_works_even_if_geolocation_api_times_out()
    {
        // Mock the geolocation API to timeout
        Http::fake([
            'ip-api.com/*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException("Connection timed out", 0);
            }
        ]);

        $student = Student::factory()->create([
            'student_mobile' => '9876543210',
            'enrollment_number' => 'TEST002'
        ]);

        // Simulate login session
        session(['student_portal_auth' => $student->id]);

        $response = $this->postJson(route('student.request.update'), [
            'field_group' => 'personal',
            'mobile_type' => 'student',
            'mobile_number' => '1234567890'
        ]);

        // The user's request should STILL succeed even if logging the location fails/times out
        $response->assertStatus(200)
            ->assertJson(['message' => 'Request submitted for approval.']);

        $this->assertDatabaseHas('student_profile_requests', [
            'student_id' => $student->id,
            'field_group' => 'personal'
        ]);
    }
}
