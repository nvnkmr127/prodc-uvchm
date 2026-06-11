<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Course;
use App\Models\Setting;
use App\Models\Student;
use App\Services\EnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EnrollmentGenerationTest extends TestCase
{
    use RefreshDatabase;

    private $enrollmentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->enrollmentService = app(EnrollmentService::class);
        
        // Mock setting
        Setting::updateOrCreate(
            ['key' => 'enrollment_prefix'],
            ['value' => 'UV', 'type' => 'text']
        );
    }

    public function test_generates_correct_format_for_course_code()
    {
        $course = Course::factory()->create([
            'name' => 'Advanced Diploma in Hospitality',
            'code' => 'ADHM',
            'enrollment_prefix' => 'ADVA'
        ]);

        $batch = Batch::factory()->create([
            'course_id' => $course->id,
            'created_at' => Carbon::create(2026, 1, 1)
        ]);

        $enrollmentNumber = $this->enrollmentService->generateForBatch($batch);

        // Expected: UV-ADHM-26001
        $this->assertEquals('UV-ADHM-26001', $enrollmentNumber);
    }

    public function test_sequence_increments_correctly()
    {
        $course = Course::factory()->create([
            'code' => 'TEST'
        ]);

        $batch = Batch::factory()->create([
            'course_id' => $course->id,
            'created_at' => Carbon::create(2025, 1, 1)
        ]);

        // Create an existing student
        Student::factory()->create([
            'batch_id' => $batch->id,
            'enrollment_number' => 'UV-TEST-25047'
        ]);

        $enrollmentNumber = $this->enrollmentService->generateForBatch($batch);

        // Expected to be the next one
        $this->assertEquals('UV-TEST-25048', $enrollmentNumber);
    }

    public function test_generates_correct_format_for_course_without_batch()
    {
        $course = Course::factory()->create([
            'name' => 'General Science',
            'code' => 'GSCI',
        ]);
        
        $year = date('y');

        $enrollmentNumber = $this->enrollmentService->generateForCourse($course);

        // Expected: UV-GSCI-{CurrentYear}001
        $this->assertEquals("UV-GSCI-{$year}001", $enrollmentNumber);
    }

    public function test_course_code_changes_affect_new_ids()
    {
        $course = Course::factory()->create([
            'code' => 'OLD'
        ]);

        $batch = Batch::factory()->create([
            'course_id' => $course->id,
            'created_at' => Carbon::create(2026, 1, 1)
        ]);

        $enrollmentNumber1 = $this->enrollmentService->generateForBatch($batch);
        $this->assertEquals('UV-OLD-26001', $enrollmentNumber1);

        // Change course code
        $course->update(['code' => 'NEW']);
        $batch->refresh();

        $enrollmentNumber2 = $this->enrollmentService->generateForBatch($batch);
        $this->assertEquals('UV-NEW-26001', $enrollmentNumber2);
    }
}
