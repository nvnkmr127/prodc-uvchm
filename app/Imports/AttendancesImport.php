<?php

namespace App\Imports;

use App\Models\Attendance;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class AttendancesImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row)
    {
        // 1. Find the student using the enrollment number from the CSV
        $student = Student::where('enrollment_number', $row['enrollment_number'])->first();

        // 2. If no student is found, skip this row
        if (! $student) {
            return null;
        }

        // 3. Use updateOrCreate to prevent duplicates for the same student on the same day
        return Attendance::updateOrCreate(
            [
                'student_id' => $student->id,
                'attendance_date' => \Carbon\Carbon::parse($row['attendance_date'])->format('Y-m-d'),
            ],
            [
                'batch_id' => $student->batch_id,
                'faculty_id' => Auth::id(), // Attribute the import to the logged-in admin
                'status' => strtolower($row['status']),
            ]
        );
    }

    // Add validation rules for each row
    public function rules(): array
    {
        return [
            'enrollment_number' => 'required|exists:students,enrollment_number',
            'attendance_date' => 'required|date',
            'status' => 'required|in:present,absent',
        ];
    }
}
