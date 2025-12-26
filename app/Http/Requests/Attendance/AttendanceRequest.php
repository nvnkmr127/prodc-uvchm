<?php
// File: app/Http/Requests/Attendance/AttendanceRequest.php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttendanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && (
            auth()->user()->can('take attendance') || 
            auth()->user()->can('edit attendance') ||
            auth()->user()->can('manage attendance')
        );
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'batch_id' => 'required|exists:batches,id',
            'attendance_date' => [
                'required',
                'date',
                'before_or_equal:today'
            ],
            'subject_id' => 'nullable|exists:subjects,id',
        ];

        // For individual attendance marking
        if ($this->has('student_id')) {
            $rules = array_merge($rules, [
                'student_id' => 'required|exists:students,id',
                'status' => [
                    'required', 
                    Rule::in(['present', 'absent', 'late', 'excused'])
                ],
                'late_minutes' => 'nullable|integer|min:0|max:480',
                'notes' => 'nullable|string|max:500',
                'location' => 'nullable|string|max:255',
            ]);
        }

        // For bulk attendance marking
        if ($this->has('attendance_data')) {
            $rules['attendance_data'] = 'required|json';
        }

        // For attendance updates
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['reason'] = 'nullable|string|max:255';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'batch_id.required' => 'Please select a batch.',
            'batch_id.exists' => 'The selected batch is invalid.',
            'attendance_date.required' => 'Please provide an attendance date.',
            'attendance_date.date' => 'Please provide a valid date.',
            'attendance_date.before_or_equal' => 'Attendance date cannot be in the future.',
            'student_id.required' => 'Please select a student.',
            'student_id.exists' => 'The selected student is invalid.',
            'status.required' => 'Please select attendance status.',
            'status.in' => 'Invalid attendance status selected.',
            'late_minutes.integer' => 'Late minutes must be a number.',
            'late_minutes.min' => 'Late minutes cannot be negative.',
            'late_minutes.max' => 'Late minutes cannot exceed 8 hours.',
            'notes.max' => 'Notes cannot exceed 500 characters.',
            'location.max' => 'Location cannot exceed 255 characters.',
            'attendance_data.required' => 'No attendance data provided.',
            'attendance_data.json' => 'Invalid attendance data format.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'batch_id' => 'batch',
            'attendance_date' => 'date',
            'student_id' => 'student',
            'late_minutes' => 'late minutes',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Custom validation logic
            $this->validateAttendanceRules($validator);
            $this->validateDuplicateAttendance($validator);
            $this->validateBatchStudents($validator);
        });
    }

    /**
     * Validate against attendance system rules
     */
    protected function validateAttendanceRules($validator): void
    {
        $date = $this->get('attendance_date');
        
        if ($date) {
            // Check if date is too far in the past
            $allowedDaysBack = config('attendance.validation.allowed_date_range_days', 30);
            $earliestDate = now()->subDays($allowedDaysBack);
            
            if ($date < $earliestDate->format('Y-m-d')) {
                $validator->errors()->add(
                    'attendance_date',
                    "Attendance can only be marked for the last {$allowedDaysBack} days."
                );
            }

            // Check if it's a weekend and weekend working is not allowed
            $weekendDays = config('attendance.rules.weekend_working_days', []);
            $dayOfWeek = strtolower(date('l', strtotime($date)));
            
            if (!in_array($dayOfWeek, $weekendDays) && in_array($dayOfWeek, ['saturday', 'sunday'])) {
                if (!config('attendance.rules.allow_weekend_attendance', false)) {
                    $validator->errors()->add(
                        'attendance_date',
                        'Attendance cannot be marked on weekends.'
                    );
                }
            }
        }

        // Validate late minutes if status is late
        if ($this->get('status') === 'late') {
            $lateMinutes = $this->get('late_minutes');
            if (is_null($lateMinutes) || $lateMinutes <= 0) {
                $validator->errors()->add(
                    'late_minutes',
                    'Late minutes are required when marking student as late.'
                );
            }
        }
    }

    /**
     * Check for duplicate attendance records
     */
    protected function validateDuplicateAttendance($validator): void
    {
        if ($this->isMethod('POST') && $this->has(['student_id', 'batch_id', 'attendance_date'])) {
            $exists = \App\Models\Attendance\Attendance::where([
                'student_id' => $this->get('student_id'),
                'batch_id' => $this->get('batch_id'),
                'attendance_date' => $this->get('attendance_date'),
            ])->exists();

            if ($exists) {
                $validator->errors()->add(
                    'student_id',
                    'Attendance for this student on this date has already been recorded.'
                );
            }
        }
    }

    /**
     * Validate that student belongs to the selected batch
     */
    protected function validateBatchStudents($validator): void
    {
        if ($this->has(['student_id', 'batch_id'])) {
            $student = \App\Models\Student::find($this->get('student_id'));
            
            if ($student && $student->batch_id != $this->get('batch_id')) {
                $validator->errors()->add(
                    'student_id',
                    'The selected student does not belong to the selected batch.'
                );
            }
        }
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Parse JSON attendance data if present
        if ($this->has('attendance_data') && is_string($this->get('attendance_data'))) {
            $attendanceData = json_decode($this->get('attendance_data'), true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->merge(['parsed_attendance_data' => $attendanceData]);
            }
        }

        // Set default values
        if ($this->has('status') && $this->get('status') === 'late' && !$this->has('late_minutes')) {
            $this->merge(['late_minutes' => config('attendance.rules.late_threshold_minutes', 15)]);
        }
    }

    /**
     * Get validated attendance data for bulk operations
     */
    public function getValidatedAttendanceData(): array
    {
        $attendanceData = $this->get('parsed_attendance_data', []);
        $validatedData = [];

        foreach ($attendanceData as $studentId => $status) {
            if (in_array($status, ['present', 'absent', 'late', 'excused'])) {
                $validatedData[$studentId] = [
                    'student_id' => $studentId,
                    'batch_id' => $this->get('batch_id'),
                    'subject_id' => $this->get('subject_id'),
                    'attendance_date' => $this->get('attendance_date'),
                    'status' => $status,
                    'marked_by' => auth()->id(),
                    'marked_at' => now(),
                ];

                // Add late minutes if status is late
                if ($status === 'late') {
                    $validatedData[$studentId]['late_minutes'] = $this->get('late_minutes', 
                        config('attendance.rules.late_threshold_minutes', 15));
                }
            }
        }

        return $validatedData;
    }
}