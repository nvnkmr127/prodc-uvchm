<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentDuplicateCheckController extends Controller
{
    /**
     * Check if mobile number is duplicate
     */
    public function checkMobileDuplicate(Request $request)
    {
        $request->validate([
            'mobile' => 'required|string',
            'field' => 'required|in:student_mobile,father_mobile',
            'student_id' => 'nullable|integer|exists:students,id',
        ]);

        $mobile = $request->mobile;
        $field = $request->field;
        $studentId = $request->student_id;

        // Check for duplicate in the specified field
        $duplicateQuery = Student::where($field, $mobile);

        // Exclude current student if updating
        if ($studentId) {
            $duplicateQuery->where('id', '!=', $studentId);
        }

        $isDuplicate = $duplicateQuery->exists();

        if ($isDuplicate) {
            $existingStudent = $duplicateQuery->first();
            $fieldLabel = $field === 'student_mobile' ? 'student mobile' : 'father mobile';

            return response()->json([
                'duplicate' => true,
                'message' => "This {$fieldLabel} number is already registered with {$existingStudent->name} (ID: {$existingStudent->enrollment_number})",
                'existing_student' => [
                    'id' => $existingStudent->id,
                    'name' => $existingStudent->name,
                    'enrollment_number' => $existingStudent->enrollment_number,
                ],
            ]);
        }

        // Check cross-field duplicates (student mobile in father mobile field and vice versa)
        $crossField = $field === 'student_mobile' ? 'father_mobile' : 'student_mobile';
        $crossDuplicateQuery = Student::where($crossField, $mobile);

        if ($studentId) {
            $crossDuplicateQuery->where('id', '!=', $studentId);
        }

        $isCrossDuplicate = $crossDuplicateQuery->exists();

        if ($isCrossDuplicate) {
            $existingStudent = $crossDuplicateQuery->first();
            $crossFieldLabel = $crossField === 'student_mobile' ? 'student mobile' : 'father mobile';

            return response()->json([
                'duplicate' => true,
                'message' => "This number is already registered as {$crossFieldLabel} for {$existingStudent->name} (ID: {$existingStudent->enrollment_number})",
                'existing_student' => [
                    'id' => $existingStudent->id,
                    'name' => $existingStudent->name,
                    'enrollment_number' => $existingStudent->enrollment_number,
                ],
            ]);
        }

        return response()->json([
            'duplicate' => false,
            'message' => 'Mobile number is available',
        ]);
    }

    /**
     * Check if enrollment number is duplicate
     */
    public function checkEnrollmentDuplicate(Request $request)
    {
        $request->validate([
            'enrollment_number' => 'required|string',
            'student_id' => 'nullable|integer|exists:students,id',
        ]);

        $enrollmentNumber = $request->enrollment_number;
        $studentId = $request->student_id;

        $duplicateQuery = Student::where('enrollment_number', $enrollmentNumber);

        if ($studentId) {
            $duplicateQuery->where('id', '!=', $studentId);
        }

        $isDuplicate = $duplicateQuery->exists();

        if ($isDuplicate) {
            $existingStudent = $duplicateQuery->first();

            return response()->json([
                'duplicate' => true,
                'message' => "This enrollment number is already assigned to {$existingStudent->name}",
                'existing_student' => [
                    'id' => $existingStudent->id,
                    'name' => $existingStudent->name,
                    'enrollment_number' => $existingStudent->enrollment_number,
                ],
            ]);
        }

        return response()->json([
            'duplicate' => false,
            'message' => 'Enrollment number is available',
        ]);
    }

    /**
     * Check if email is duplicate
     */
    public function checkEmailDuplicate(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'student_id' => 'nullable|integer|exists:students,id',
        ]);

        $email = $request->email;
        $studentId = $request->student_id;

        $duplicateQuery = Student::where('email', $email);

        if ($studentId) {
            $duplicateQuery->where('id', '!=', $studentId);
        }

        $isDuplicate = $duplicateQuery->exists();

        if ($isDuplicate) {
            $existingStudent = $duplicateQuery->first();

            return response()->json([
                'duplicate' => true,
                'message' => "This email is already registered with {$existingStudent->name} (ID: {$existingStudent->enrollment_number})",
                'existing_student' => [
                    'id' => $existingStudent->id,
                    'name' => $existingStudent->name,
                    'enrollment_number' => $existingStudent->enrollment_number,
                ],
            ]);
        }

        return response()->json([
            'duplicate' => false,
            'message' => 'Email is available',
        ]);
    }

    /**
     * Bulk check for duplicates (useful for imports)
     */
    public function bulkCheckDuplicates(Request $request)
    {
        $request->validate([
            'data' => 'required|array',
            'data.*.student_mobile' => 'nullable|string',
            'data.*.father_mobile' => 'nullable|string',
            'data.*.email' => 'nullable|email',
            'data.*.enrollment_number' => 'nullable|string',
        ]);

        $results = [];

        foreach ($request->data as $index => $row) {
            $rowResults = [
                'index' => $index,
                'duplicates' => [],
            ];

            // Check student mobile
            if (! empty($row['student_mobile'])) {
                if (Student::where('student_mobile', $row['student_mobile'])->exists()) {
                    $rowResults['duplicates'][] = [
                        'field' => 'student_mobile',
                        'value' => $row['student_mobile'],
                        'message' => 'Student mobile number already exists',
                    ];
                }
            }

            // Check father mobile
            if (! empty($row['father_mobile'])) {
                if (Student::where('father_mobile', $row['father_mobile'])->exists()) {
                    $rowResults['duplicates'][] = [
                        'field' => 'father_mobile',
                        'value' => $row['father_mobile'],
                        'message' => 'Father mobile number already exists',
                    ];
                }
            }

            // Check email
            if (! empty($row['email'])) {
                if (Student::where('email', $row['email'])->exists()) {
                    $rowResults['duplicates'][] = [
                        'field' => 'email',
                        'value' => $row['email'],
                        'message' => 'Email already exists',
                    ];
                }
            }

            // Check enrollment number
            if (! empty($row['enrollment_number'])) {
                if (Student::where('enrollment_number', $row['enrollment_number'])->exists()) {
                    $rowResults['duplicates'][] = [
                        'field' => 'enrollment_number',
                        'value' => $row['enrollment_number'],
                        'message' => 'Enrollment number already exists',
                    ];
                }
            }

            $results[] = $rowResults;
        }

        return response()->json([
            'success' => true,
            'results' => $results,
            'summary' => [
                'total_rows' => count($request->data),
                'rows_with_duplicates' => count(array_filter($results, function ($row) {
                    return ! empty($row['duplicates']);
                })),
            ],
        ]);
    }
}
