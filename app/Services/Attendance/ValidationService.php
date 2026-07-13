<?php

namespace App\Services\Attendance;

use App\Models\Attendance\Attendance;
use App\Models\Batch;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ValidationService
{
    /**
     * Validate attendance data
     */
    public function validateAttendanceData(array $data)
    {
        $rules = [
            'student_id' => 'required|integer|exists:students,id',
            'batch_id' => 'required|integer|exists:batches,id',
            'subject_id' => 'nullable|integer|exists:subjects,id',
            'attendance_date' => 'required|date',
            'status' => 'required|in:present,absent,late,excused',
            'check_in_time' => 'nullable|date_format:H:i:s',
            'check_out_time' => 'nullable|date_format:H:i:s|after:check_in_time',
            'notes' => 'nullable|string|max:500',
        ];

        $messages = [
            'student_id.required' => 'Student is required',
            'student_id.exists' => 'Selected student does not exist',
            'batch_id.required' => 'Batch is required',
            'batch_id.exists' => 'Selected batch does not exist',
            'subject_id.exists' => 'Selected subject does not exist',
            'attendance_date.required' => 'Attendance date is required',
            'attendance_date.date' => 'Invalid attendance date format',
            'status.required' => 'Attendance status is required',
            'status.in' => 'Invalid attendance status',
            'check_in_time.date_format' => 'Invalid check-in time format',
            'check_out_time.date_format' => 'Invalid check-out time format',
            'check_out_time.after' => 'Check-out time must be after check-in time',
            'notes.max' => 'Notes cannot exceed 500 characters',
        ];

        return Validator::make($data, $rules, $messages);
    }

    /**
     * Validate business rules for attendance
     */
    public function validateBusinessRules(array $data)
    {
        $errors = [];

        try {
            // Check if student belongs to the batch
            if (isset($data['student_id']) && isset($data['batch_id'])) {
                $student = Student::find($data['student_id']);
                if ($student && $student->batch_id != $data['batch_id']) {
                    $errors[] = 'Student does not belong to the selected batch';
                }
            }

            // Check for duplicate attendance on same date
            if (isset($data['student_id']) && isset($data['attendance_date'])) {
                $existingAttendance = Attendance::where('student_id', $data['student_id'])
                    ->where('attendance_date', $data['attendance_date'])
                    ->first();

                if ($existingAttendance) {
                    $errors[] = 'Attendance already exists for this student on this date';
                }
            }

            // Validate attendance date is not in future
            if (isset($data['attendance_date'])) {
                $attendanceDate = Carbon::parse($data['attendance_date']);
                if ($attendanceDate->isFuture()) {
                    $errors[] = 'Attendance date cannot be in the future';
                }
            }

            // Validate time ranges
            if (isset($data['check_in_time']) && isset($data['check_out_time'])) {
                $checkIn = Carbon::parse($data['check_in_time']);
                $checkOut = Carbon::parse($data['check_out_time']);

                if ($checkOut->lessThanOrEqualTo($checkIn)) {
                    $errors[] = 'Check-out time must be after check-in time';
                }
            }

            return [
                'valid' => empty($errors),
                'errors' => $errors,
            ];

        } catch (\Exception $e) {
            Log::error('Business rule validation failed', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            return [
                'valid' => false,
                'errors' => ['Validation error occurred'],
            ];
        }
    }

    /**
     * Validate import data format
     */
    public function validateImportData(array $importData)
    {
        $requiredHeaders = ['student_id', 'attendance_date', 'status'];
        $optionalHeaders = ['batch_id', 'subject_id', 'check_in_time', 'check_out_time', 'notes'];

        $errors = [];
        $validRows = [];

        // Check if data is not empty
        if (empty($importData)) {
            return [
                'valid' => false,
                'errors' => ['Import file is empty'],
                'valid_data' => [],
                'summary' => [
                    'total_rows' => 0,
                    'valid_rows' => 0,
                    'error_rows' => 0,
                ],
            ];
        }

        // Validate headers (first row)
        $headers = array_keys($importData[0]);
        $missingHeaders = array_diff($requiredHeaders, $headers);

        if (! empty($missingHeaders)) {
            return [
                'valid' => false,
                'errors' => ['Missing required columns: '.implode(', ', $missingHeaders)],
                'valid_data' => [],
                'summary' => [
                    'total_rows' => count($importData),
                    'valid_rows' => 0,
                    'error_rows' => count($importData),
                ],
            ];
        }

        // Validate each row
        foreach ($importData as $rowIndex => $row) {
            $rowErrors = [];

            // Check required fields
            foreach ($requiredHeaders as $header) {
                if (! isset($row[$header]) || empty($row[$header])) {
                    $rowErrors[] = "Missing or empty {$header}";
                }
            }

            // Validate data types and formats
            if (isset($row['attendance_date'])) {
                try {
                    Carbon::parse($row['attendance_date']);
                } catch (\Exception $e) {
                    $rowErrors[] = 'Invalid date format for attendance_date';
                }
            }

            if (isset($row['status']) && ! in_array($row['status'], ['present', 'absent', 'late', 'excused'])) {
                $rowErrors[] = 'Invalid status value';
            }

            if (isset($row['check_in_time']) && ! empty($row['check_in_time'])) {
                if (! preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $row['check_in_time'])) {
                    $rowErrors[] = 'Invalid time format for check_in_time';
                }
            }

            if (isset($row['check_out_time']) && ! empty($row['check_out_time'])) {
                if (! preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $row['check_out_time'])) {
                    $rowErrors[] = 'Invalid time format for check_out_time';
                }
            }

            if (! empty($rowErrors)) {
                $errors[$rowIndex + 1] = $rowErrors; // +1 for human-readable row numbers
            } else {
                $validRows[$rowIndex] = $row;
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'valid_data' => $validRows,
            'summary' => [
                'total_rows' => count($importData),
                'valid_rows' => count($validRows),
                'error_rows' => count($errors),
            ],
        ];
    }
}
