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
        return app(\App\Services\UnifiedIdentifierService::class)->generateEnrollmentNumber($course, $batch);
    }
}
