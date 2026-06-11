<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Course;
use App\Models\Setting;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EnrollmentService
{
    /**
     * Generate enrollment number using a Course (e.g. from Admission without batch)
     */
    public function generateForCourse(Course $course): string
    {
        return $this->generate($course, null);
    }

    /**
     * Generate enrollment number using a Batch
     */
    public function generateForBatch(Batch $batch): string
    {
        return $this->generate($batch->course, $batch);
    }

    /**
     * Core generation logic
     * Format: {CollegePrefix}-{CourseCode}-{YY}{SEQ}
     * Example: UV-ADHM-26047
     *
     * @param Course $course
     * @param Batch|null $batch
     * @return string
     */
    private function generate(Course $course, ?Batch $batch = null): string
    {
        // 1. Get College Prefix
        $settings = Setting::all()->keyBy('key');
        $collegePrefix = $settings['enrollment_prefix']->value ?? 'UV';

        // 2. Get Course Code / Prefix
        $coursePrefix = $course->code ?? $course->enrollment_prefix ?? strtoupper(substr($course->name, 0, 4));
        $coursePrefix = trim($coursePrefix);

        // 3. Determine Year (2 digits)
        if ($batch && $batch->created_at) {
            $year = Carbon::parse($batch->created_at)->format('y');
        } else {
            $year = date('y');
        }

        $prefix = "{$collegePrefix}-{$coursePrefix}-{$year}";

        // 4. Find the last student with this prefix (optionally scoped by batch if provided)
        $query = Student::where('enrollment_number', 'LIKE', "{$prefix}%");
        
        if ($batch) {
            $query->where('batch_id', $batch->id);
        }

        // Lock for update if we are inside a transaction to prevent race conditions
        if (DB::transactionLevel() > 0) {
            $query->lockForUpdate();
        }

        $lastStudent = $query->orderByRaw('LENGTH(enrollment_number) DESC')
            ->orderBy('enrollment_number', 'desc')
            ->first();

        // We assume a 3-digit sequence following the 2-digit year, so the last 3 chars are the sequence.
        $nextSequence = 1;
        if ($lastStudent) {
            // Extract the last 3 characters
            $lastSequence = (int) substr($lastStudent->enrollment_number, -3);
            $nextSequence = $lastSequence + 1;
        }

        $maxAttempts = 10;
        $attempt = 0;

        do {
            $paddedRollNo = str_pad($nextSequence + $attempt, 3, '0', STR_PAD_LEFT);
            $enrollmentNumber = "{$prefix}{$paddedRollNo}";

            $exists = Student::where('enrollment_number', $enrollmentNumber)->exists();

            if ($exists) {
                $attempt++;
            }
        } while ($exists && $attempt < $maxAttempts);

        // Fallback for extreme cases
        if ($attempt >= $maxAttempts) {
            $enrollmentNumber = "{$prefix}" . substr(time(), -3);
        }

        return $enrollmentNumber;
    }
}
