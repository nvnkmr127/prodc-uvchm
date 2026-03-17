<?php

namespace App\Imports;

use App\Models\Enquiry;
use App\Models\Course;
use App\Models\Student;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EnquiriesImport implements ToModel, WithHeadingRow
{
    protected $assignedTo;
    protected $leadDistribution;
    protected $defaultSource;
    public $importedCount = 0;
    public $skippedCount = 0;

    public function __construct($assignedTo = null, $leadDistribution = null, $defaultSource = null)
    {
        $this->assignedTo = $assignedTo;
        $this->leadDistribution = $leadDistribution;
        $this->defaultSource = is_string($defaultSource) ? trim($defaultSource) : null;
    }

    public function model(array $row)
    {
        // DEBUG: Log the row to see what keys Laravel Excel is reading
        // Check storage/logs/laravel.log if imports fail silently
        // Log::info('CSV Row:', $row); 

        // 1. Sanitize Phone (Handle mismatched headers gracefully)
        $rawPhone = $row['mobile_number'] ?? $row['phone'] ?? $row['mobile'] ?? null;
        $phone = preg_replace('/[^0-9]/', '', (string) $rawPhone);

        // 2. Basic Validation — must be at least 10 digits
        if (strlen($phone) < 10) {
            $this->skippedCount++;
            return null;
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

        // 5. Course Matching — exact match first, then partial fallback
        $courseId = null;
        if (!empty($row['course'])) {
            $courseName = trim($row['course']);
            $course = Course::where('name', $courseName)->first()
                ?? Course::where('name', 'LIKE', '%' . $courseName . '%')->first();
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

        // 6. Name Validation
        $name = $row['name'] ?? $row['student_name'] ?? $row['student'] ?? null;
        if (empty($name)) {
            $this->skippedCount++;
            return null;
        }

        $this->importedCount++;

        // Support common sheet header variants, then fallback to modal default, then system default.
        $rowSource = $row['source']
            ?? $row['lead_source']
            ?? $row['enquiry_source']
            ?? $row['source_of_enquiry']
            ?? null;

        $resolvedSource = is_string($rowSource) ? trim($rowSource) : null;
        if (empty($resolvedSource)) {
            $resolvedSource = $this->defaultSource;
        }
        if (empty($resolvedSource)) {
            $resolvedSource = 'Bulk Import';
        }

        return new Enquiry([
            'student_name' => $name,
            'phone_number' => $phone,
            'address' => $row['address'] ?? null,
            'email' => $row['email'] ?? null,
            'course_id' => $courseId,
            'source' => $resolvedSource,
            'notes' => $row['notes'] ?? null,
            'status' => 'New',
            'assigned_to_user_id' => $assignedId,
            // next_follow_up_date intentionally null — counselor should set it after review
        ]);
    }
}