<?php

namespace App\Imports;

use App\Models\Enquiry;
use App\Models\Course;
use App\Models\Student;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; // Import Log

class EnquiriesImport implements ToModel, WithHeadingRow, WithValidation
{
    protected $assignedTo;
    protected $leadDistribution;
    public $importedCount = 0;
    public $skippedCount = 0;

    public function __construct($assignedTo = null, $leadDistribution = null)
    {
        $this->assignedTo = $assignedTo;
        $this->leadDistribution = $leadDistribution;
    }

    public function model(array $row)
    {
        // DEBUG: Log the row to see what keys Laravel Excel is reading
        // Check storage/logs/laravel.log if imports fail silently
        // Log::info('CSV Row:', $row); 

        // 1. Sanitize Phone (Handle mismatched headers gracefully)
        $rawPhone = $row['mobile_number'] ?? $row['phone'] ?? $row['mobile'] ?? null;
        $phone = preg_replace('/[^0-9]/', '', (string) $rawPhone);

        // 2. Basic Validation
        if (empty($phone)) {
            $this->skippedCount++;
            return null; // Skip rows without phone
        }

        // 3. Check Duplicates (Students)
        if (Student::where('student_mobile', $phone)->orWhere('father_mobile', $phone)->exists()) {
            $this->skippedCount++;
            return null;
        }

        // 4. Check Duplicates (Enquiries)
        if (Enquiry::where('phone_number', $phone)->exists()) {
            $this->skippedCount++;
            return null;
        }

        // 5. Course Matching
        $courseId = null;
        if (!empty($row['course'])) {
            $course = Course::where('name', 'LIKE', '%' . trim($row['course']) . '%')->first();
            $courseId = $course ? $course->id : null;
        }

        // Determine Assignment
        // Priority: 1. Manually selected user 2. Round-Robin College Admin 3. Current User (Auth::id())
        $assignedId = $this->assignedTo;

        if (!$assignedId && $this->leadDistribution) {
            $assignedId = $this->leadDistribution->getNextCollegeAdminId();
        }

        // Fallback to current user if still null
        if (!$assignedId) {
            $assignedId = Auth::id();
        }

        $this->importedCount++;

        return new Enquiry([
            'student_name' => $row['name'] ?? $row['student_name'] ?? 'Unknown',
            'phone_number' => $phone,
            'address' => $row['address'] ?? null,
            'email' => $row['email'] ?? null,
            'course_id' => $courseId,
            'source' => $row['source'] ?? 'Bulk Import',
            'notes' => $row['notes'] ?? null,
            'status' => 'New',
            'assigned_to_user_id' => $assignedId,
            'next_follow_up_date' => now()->addDays(1),
        ]);
    }

    public function rules(): array
    {
        return [
            // Loose validation here, strict validation handled in logic to count skips
            'name' => 'required',
        ];
    }
}