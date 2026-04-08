<?php

// tests/Feature/ComprehensiveFixTest.php
// This test file verifies all the fixes we've implemented

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ComprehensiveFixTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an admin user for testing
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');
    }

    /** @test */
    public function course_controller_validation_works_correctly()
    {
        $this->actingAs($this->adminUser);

        // Test course creation with correct validation
        $courseData = [
            'name' => 'Computer Science Engineering',
            'code' => 'CSE',
            'duration_months' => 48,
            'description' => 'Four-year engineering program in computer science',
        ];

        $response = $this->post(route('admin.courses.store'), $courseData);

        $response->assertRedirect(route('admin.courses.index'));
        $response->assertSessionHas('success', 'Course created successfully.');

        $this->assertDatabaseHas('courses', [
            'name' => 'Computer Science Engineering',
            'code' => 'CSE',
            'duration_months' => 48,
        ]);

        // Test validation fails for invalid data
        $invalidData = [
            'name' => '', // Required field empty
            'duration_months' => 0, // Below minimum
        ];

        $response = $this->post(route('admin.courses.store'), $invalidData);
        $response->assertSessionHasErrors(['name', 'duration_months']);
    }

    /** @test */
    public function course_controller_update_validation_works()
    {
        $this->actingAs($this->adminUser);

        $course = Course::factory()->create([
            'name' => 'Original Course',
            'code' => 'OC',
            'duration_months' => 12,
        ]);

        $updateData = [
            'name' => 'Updated Course Name',
            'code' => 'UC',
            'duration_months' => 24,
            'description' => 'Updated description',
        ];

        $response = $this->put(route('admin.courses.update', $course), $updateData);

        $response->assertRedirect(route('admin.courses.index'));
        $response->assertSessionHas('success', 'Course updated successfully.');

        $course->refresh();
        $this->assertEquals('Updated Course Name', $course->name);
        $this->assertEquals('UC', $course->code);
        $this->assertEquals(24, $course->duration_months);
    }

    /** @test */
    public function holiday_controller_validation_works_correctly()
    {
        $this->actingAs($this->adminUser);

        $holidayData = [
            'name' => 'Independence Day',
            'date' => '2025-08-15',
            'description' => 'National holiday celebrating independence',
        ];

        $response = $this->post(route('admin.holidays.store'), $holidayData);

        $response->assertRedirect(route('admin.holidays.index'));
        $response->assertSessionHas('success', 'Holiday created successfully.');

        $this->assertDatabaseHas('holidays', [
            'name' => 'Independence Day',
            'date' => '2025-08-15',
        ]);

        // Test duplicate date validation
        $duplicateData = [
            'name' => 'Another Holiday',
            'date' => '2025-08-15', // Same date
            'description' => 'Should fail',
        ];

        $response = $this->post(route('admin.holidays.store'), $duplicateData);
        $response->assertSessionHasErrors(['date']);
    }

    /** @test */
    public function certificate_template_controller_validation_works()
    {
        $this->actingAs($this->adminUser);

        $templateData = [
            'name' => 'Graduation Certificate',
            'body' => 'This is to certify that {{student_name}} has successfully completed...',
            'description' => 'Template for graduation certificates',
        ];

        $response = $this->post(route('admin.certificate-templates.store'), $templateData);

        $response->assertRedirect(route('admin.certificate-templates.index'));
        $response->assertSessionHas('success', 'Certificate template created successfully.');

        $this->assertDatabaseHas('certificate_templates', [
            'name' => 'Graduation Certificate',
        ]);

        // Test minimum content validation
        $invalidData = [
            'name' => 'Short Template',
            'body' => 'Short', // Too short
        ];

        $response = $this->post(route('admin.certificate-templates.store'), $invalidData);
        $response->assertSessionHasErrors(['body']);
    }

    /** @test */
    public function id_card_template_controller_validation_works()
    {
        $this->actingAs($this->adminUser);

        $templateData = [
            'name' => 'Student ID Card',
            'content' => 'Student ID Card Template with {{student_name}} and {{enrollment_number}}',
            'description' => 'Standard student ID card template',
            'is_active' => true,
        ];

        $response = $this->post(route('admin.id-card-templates.store'), $templateData);

        $response->assertRedirect(route('admin.id-card-templates.index'));
        $response->assertSessionHas('success', 'ID Card Template created successfully.');

        $this->assertDatabaseHas('id_card_templates', [
            'name' => 'Student ID Card',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function student_model_relationships_work_correctly()
    {
        // Create related models
        $course = Course::factory()->create();
        $batch = \App\Models\Batch::factory()->create(['course_id' => $course->id]);

        $student = Student::factory()->create([
            'batch_id' => $batch->id,
            'enrollment_number' => '2025CSE001',
        ]);

        // Test relationships
        $this->assertInstanceOf(\App\Models\Batch::class, $student->batch);
        $this->assertInstanceOf(\App\Models\Course::class, $student->course);
        $this->assertEquals($course->name, $student->course_name);
        $this->assertEquals($batch->name, $student->batch_name);

        // Test photo URL methods
        $this->assertIsString($student->photo_url);
        $this->assertIsString($student->small_photo);
        $this->assertIsBool($student->has_real_photo);
    }

    /** @test */
    public function settings_helper_functions_work_correctly()
    {
        // Test setting creation and retrieval
        update_setting('test_setting', 'test_value', 'test_group', 'text');

        $this->assertEquals('test_value', setting('test_setting'));
        $this->assertEquals('default', setting('nonexistent_setting', 'default'));

        // Test boolean settings
        update_setting('boolean_setting', true, 'general', 'boolean');
        $this->assertTrue(setting('boolean_setting'));

        // Test array settings
        update_setting('array_setting', ['item1', 'item2'], 'general', 'array');
        $this->assertEquals(['item1', 'item2'], setting('array_setting'));

        // Test formatted values
        $this->assertEquals('Yes', format_setting_value('boolean_setting', true, 'boolean'));
        $this->assertEquals('item1, item2', format_setting_value('array_setting', ['item1', 'item2'], 'array'));
    }

    /** @test */
    public function settings_controller_works_correctly()
    {
        $this->actingAs($this->adminUser);

        // Test settings index page loads
        $response = $this->get(route('admin.settings.index'));
        $response->assertOk();
        $response->assertViewIs('admin.settings.index');

        // Test settings update
        $settingsData = [
            'group' => 'general',
            'app_name' => 'Test College Management System',
            'app_tagline' => 'Test Tagline',
            'timezone' => 'Asia/Kolkata',
            'date_format' => 'd-m-Y',
        ];

        $response = $this->post(route('admin.settings.update'), $settingsData);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Settings updated successfully!');

        $this->assertEquals('Test College Management System', setting('app_name'));
        $this->assertEquals('Test Tagline', setting('app_tagline'));
    }

    /** @test */
    public function file_upload_settings_work_correctly()
    {
        Storage::fake('public');
        $this->actingAs($this->adminUser);

        $file = UploadedFile::fake()->image('logo.jpg');

        $settingsData = [
            'group' => 'college',
            'college_name' => 'Test College',
            'college_logo' => $file,
        ];

        $response = $this->post(route('admin.settings.update'), $settingsData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify file was stored
        $logoPath = setting('college_logo');
        $this->assertNotNull($logoPath);

        $filename = basename($logoPath);
        Storage::disk('public')->assertExists('settings/'.$filename);
    }

    /** @test */
    public function cache_clearing_works_correctly()
    {
        $this->actingAs($this->adminUser);

        // Set a setting to cache it
        update_setting('cached_setting', 'cached_value');
        $this->assertEquals('cached_value', setting('cached_setting'));

        // Clear cache via controller
        $response = $this->post(route('admin.settings.clear-cache'));

        $response->assertJson(['success' => true]);

        // Verify cache was cleared by checking if helper function works
        clear_settings_cache();
        $this->assertEquals('cached_value', setting('cached_setting')); // Should still work
    }

    /** @test */
    public function email_test_functionality_works()
    {
        $this->actingAs($this->adminUser);

        // Mock mail
        \Mail::fake();

        $response = $this->post(route('admin.settings.test-email'), [
            'test_email' => 'test@example.com',
        ]);

        $response->assertJson(['success' => true]);

        \Mail::assertSent(\Illuminate\Mail\Mailable::class, function ($mail) {
            return $mail->hasTo('test@example.com');
        });
    }

    /** @test */
    public function settings_export_import_works()
    {
        $this->actingAs($this->adminUser);

        // Create some settings
        update_setting('export_test_1', 'value1', 'general', 'text');
        update_setting('export_test_2', 'value2', 'general', 'text');

        // Test export
        $response = $this->get(route('admin.settings.export'));
        $response->assertOk();

        $exportData = $response->getContent();
        $decodedData = json_decode($exportData, true);

        $this->assertArrayHasKey('settings', $decodedData);
        $this->assertArrayHasKey('export_test_1', $decodedData['settings']);

        // Test import
        Storage::fake('local');
        $importFile = UploadedFile::fake()->createWithContent(
            'settings.json',
            json_encode([
                'settings' => [
                    'import_test' => [
                        'value' => 'imported_value',
                        'group' => 'general',
                        'type' => 'text',
                    ],
                ],
            ])
        );

        $response = $this->post(route('admin.settings.import'), [
            'settings_file' => $importFile,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('imported_value', setting('import_test'));
    }

    /** @test */
    public function enrollment_number_generation_works()
    {
        $course = Course::factory()->create(['code' => 'CSE']);
        $batch = \App\Models\Batch::factory()->create([
            'course_id' => $course->id,
            'code' => 'CSE2025',
        ]);

        // Create student without enrollment number
        $student = Student::factory()->make([
            'batch_id' => $batch->id,
            'enrollment_number' => null,
        ]);

        $student->save();

        // Should auto-generate enrollment number
        $this->assertNotNull($student->enrollment_number);
        $this->assertStringContainsString(date('Y'), $student->enrollment_number);
    }

    /** @test */
    public function validation_helper_functions_work()
    {
        // Test email validation
        $this->assertTrue(validate_setting_value('test@example.com', 'email'));
        $this->assertFalse(validate_setting_value('invalid-email', 'email'));

        // Test URL validation
        $this->assertTrue(validate_setting_value('https://example.com', 'url'));
        $this->assertFalse(validate_setting_value('not-a-url', 'url'));

        // Test number validation with range
        $this->assertTrue(validate_setting_value(50, 'number', ['min' => 0, 'max' => 100]));
        $this->assertFalse(validate_setting_value(150, 'number', ['min' => 0, 'max' => 100]));

        // Test boolean validation
        $this->assertTrue(validate_setting_value('1', 'boolean'));
        $this->assertTrue(validate_setting_value(true, 'boolean'));
        $this->assertFalse(validate_setting_value('invalid', 'boolean'));
    }
}
