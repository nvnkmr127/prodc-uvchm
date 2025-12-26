<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use App\Helpers\ErrorHandler;

class StudentController extends Controller
{
    /**
     * Search for students by name, enrollment number, or mobile number.
     */
    public function search(Request $request)
    {
        $searchTerm = $request->input('q');

        if (empty($searchTerm)) {
            return response()->json(['data' => []]);
        }

        $students = Student::with('batch.course')
                           ->where('name', 'LIKE', "%{$searchTerm}%")
                           ->orWhere('enrollment_number', 'LIKE', "%{$searchTerm}%")
                           ->orWhere('student_mobile', 'LIKE', "%{$searchTerm}%")
                           ->limit(10)
                           ->get();

        // Format the data for a cleaner API response
        $formattedStudents = $students->map(function ($student) {
            return [
                'id' => $student->id,
                'name' => $student->name,
                'enrollment_number' => $student->enrollment_number,
                'course' => $student->batch->course->name ?? 'N/A',
                'batch' => $student->batch->name ?? 'N/A',
            ];
        });

        return response()->json(['data' => $formattedStudents]);
    }

    /**
     * Get the full details for a single student.
     * 
     * ✅ FIXED: Updated to use current relationship structure
     */
    public function show(Student $student)
    {
        try {
            // ✅ FIXED: Load relationships that actually exist in current Student model
            // Removed 'invoices' and replaced with 'studentFees' and 'payments'
            $student->load([
                'batch.course',
                'studentFees.feeCategory',  // Component-based fee system
                'attendances' => function($query) {
                    $query->latest()->limit(10);
                }
            ]);

            // Calculate financial summary from studentFees (not invoices)
            $studentFees = $student->studentFees;
            $totalFeeAmount = $studentFees->sum('amount');
            $totalPaidAmount = $studentFees->sum('paid_amount');
            $totalConcessionAmount = $studentFees->sum('concession_amount');
            $totalDueAmount = $totalFeeAmount - $totalPaidAmount - $totalConcessionAmount;

            // Get recent payments
            $recentPayments = \App\Models\Payment::where('student_id', $student->id)
                ->with(['createdBy:id,name', 'componentItems.studentFee.feeCategory'])
                ->latest()
                ->limit(5)
                ->get();

            // Format response data
            $responseData = [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
                'enrollment_number' => $student->enrollment_number,
                'gender' => $student->gender,
                'student_mobile' => $student->student_mobile,
                'father_name' => $student->father_name,
                'father_mobile' => $student->father_mobile,
                'village' => $student->village,
                'admission_date' => $student->admission_date,
                'status' => $student->status,
                'photo_url' => $student->photo_url ?? null,
                
                // Academic Information
                'batch' => [
                    'id' => $student->batch->id ?? null,
                    'name' => $student->batch->name ?? null,
                    'course' => [
                        'id' => $student->batch->course->id ?? null,
                        'name' => $student->batch->course->name ?? null,
                    ]
                ],
                
                // Financial Summary (using component-based system)
                'financial_summary' => [
                    'total_fee_amount' => $totalFeeAmount,
                    'total_paid_amount' => $totalPaidAmount,
                    'total_concession_amount' => $totalConcessionAmount,
                    'total_due_amount' => $totalDueAmount,
                    'payment_percentage' => $totalFeeAmount > 0 ? round(($totalPaidAmount / $totalFeeAmount) * 100, 2) : 0,
                ],
                
                // Fee Components
                'fee_components' => $studentFees->map(function($fee) {
                    return [
                        'id' => $fee->id,
                        'category' => $fee->feeCategory->name ?? 'Unknown',
                        'amount' => $fee->amount,
                        'paid_amount' => $fee->paid_amount,
                        'concession_amount' => $fee->concession_amount,
                        'remaining_amount' => $fee->amount - $fee->paid_amount - $fee->concession_amount,
                        'due_date' => $fee->due_date,
                        'status' => $fee->status,
                        'academic_year' => $fee->academic_year,
                    ];
                }),
                
                // Recent Payments
                'recent_payments' => $recentPayments->map(function($payment) {
                    return [
                        'id' => $payment->id,
                        'receipt_number' => $payment->receipt_number,
                        'amount' => $payment->amount,
                        'payment_date' => $payment->payment_date,
                        'payment_method' => $payment->payment_method,
                        'created_by' => $payment->createdBy->name ?? 'System',
                        'components_paid' => $payment->componentItems->map(function($item) {
                            return [
                                'fee_category' => $item->studentFee->feeCategory->name ?? 'Unknown',
                                'amount_paid' => $item->amount_paid,
                            ];
                        }),
                    ];
                }),
                
                // Recent Attendance
                'recent_attendance' => $student->attendances->map(function($attendance) {
                    return [
                        'id' => $attendance->id,
                        'date' => $attendance->attendance_date,
                        'status' => $attendance->status,
                        'marked_by' => $attendance->faculty->name ?? 'System',
                        'created_at' => $attendance->created_at,
                    ];
                }),
                
                // Timestamps
                'created_at' => $student->created_at,
                'updated_at' => $student->updated_at,
            ];

            return response()->json([
                'success' => true,
                'data' => $responseData
            ]);

        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('API Student Show Error: ' . $e->getMessage(), [
                'student_id' => $student->id,
                'trace' => $e->getTraceAsString()
            ]);

            return ErrorHandler::handleApiException(
                $e,
                'Unable to retrieve student details',
                'Unable to retrieve student details',
                500
            );
        }
    }
}