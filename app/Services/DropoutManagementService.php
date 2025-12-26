<?php
namespace App\Services;

use App\Models\Student;
use App\Models\Payment;
use App\Models\StudentFee;
use App\Models\Attendance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DropoutManagementService
{
    /**
     * Process student dropout with complete financial preservation
     */
    public function processDropout(Student $student, array $dropoutData): array
    {
        if ($student->status === 'dropout') {
            return [
                'success' => false,
                'message' => 'Student is already marked as dropout'
            ];
        }
        
        DB::beginTransaction();
        
        try {
            // Store original status for logging
            $originalStatus = $student->status;
            
            // 1. Calculate and preserve financial summary
            $financialSummary = $this->calculateFinalFinancialSummary($student);
            
            // 2. Update student record with dropout information
            $student->update([
                'status' => 'dropout',
                'dropout_date' => $dropoutData['dropout_date'] ?? now()->toDateString(),
                'dropout_reason' => $dropoutData['reason'] ?? '',
                'final_outstanding_amount' => $financialSummary['outstanding_amount'],
                'total_paid_amount' => $financialSummary['total_paid'],
                'dropout_metadata' => $this->generateDropoutMetadata($student, $financialSummary),
                'processed_by' => auth()->id(),
                'dropout_processed_at' => now()
            ]);
            
            // 3. Freeze all unpaid fee components (preserve payment records)
            $this->freezeUnpaidFees($student);
            
            // 4. Remove from active calculations but preserve records
            $this->excludeFromActiveOperations($student);
            
            // 5. Generate dropout report
            $dropoutReport = $this->generateDropoutReport($student, $financialSummary);
            
            // 6. Log the dropout process
            $this->logDropoutProcess($student, $dropoutData, $financialSummary);
            
            // 7. Log activity
            $student->logDropout($dropoutData['reason'] ?? null, $financialSummary);
            $student->logStatusChange($originalStatus, 'dropout', $dropoutData['reason'] ?? null);
            
            DB::commit();
            
            return [
                'success' => true,
                'student' => $student->fresh(),
                'financial_summary' => $financialSummary,
                'dropout_report' => $dropoutReport,
                'message' => "Student {$student->name} has been successfully marked as dropout. All payment records preserved."
            ];
            
        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Dropout processing failed', [
                'student_id' => $student->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Dropout processing failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Calculate comprehensive financial summary before dropout
     */
    protected function calculateFinalFinancialSummary(Student $student): array
    {
        $studentFees = $student->studentFees;
        $payments = $student->payments()->where('payment_type', 'component')->get();
        
        $totalFees = $studentFees->sum('amount');
        $totalPaid = $studentFees->sum('paid_amount');
        $totalConcessions = $studentFees->sum('concession_amount');
        $totalOutstanding = $studentFees->sum(function($fee) {
            return max(0, $fee->amount - $fee->paid_amount - $fee->concession_amount);
        });
        
        return [
            'total_fees' => $totalFees,
            'total_paid' => $totalPaid,
            'total_concessions' => $totalConcessions,
            'outstanding_amount' => $totalOutstanding,
            'payment_count' => $payments->count(),
            'last_payment_date' => $payments->max('payment_date'),
            'fee_categories' => $studentFees->groupBy('fee_category_id')->map(function($fees, $categoryId) {
                return [
                    'category_id' => $categoryId,
                    'category_name' => $fees->first()->feeCategory->name ?? 'Unknown',
                    'total_amount' => $fees->sum('amount'),
                    'paid_amount' => $fees->sum('paid_amount'),
                    'outstanding' => $fees->sum(function($fee) {
                        return max(0, $fee->amount - $fee->paid_amount - $fee->concession_amount);
                    })
                ];
            })->values()
        ];
    }
    
    /**
     * Freeze unpaid fees (preserve records but mark as inactive)
     */
    protected function freezeUnpaidFees(Student $student): void
    {
        // Add notes to unpaid fees indicating dropout
        $student->studentFees()
            ->whereIn('status', ['unpaid', 'partial'])
            ->update([
                'notes' => DB::raw("CONCAT(COALESCE(notes, ''), ' [STUDENT DROPOUT - " . now()->format('Y-m-d') . "]')")
            ]);
    }
    
    /**
     * Generate dropout metadata for archival
     */
    protected function generateDropoutMetadata(Student $student, array $financialSummary): array
    {
        return [
            'dropout_processed_at' => now()->toISOString(),
            'academic_info' => [
                'course' => $student->course->name ?? 'N/A',
                'batch' => $student->batch->name ?? 'N/A',
                'admission_date' => $student->admission_date,
                'study_duration_days' => Carbon::parse($student->admission_date)->diffInDays(now())
            ],
            'financial_snapshot' => $financialSummary,
            'attendance_summary' => [
                'total_classes' => $student->attendances()->count(),
                'present_count' => $student->attendances()->where('status', 'present')->count(),
                'last_attendance' => $student->attendances()->latest()->first()?->created_at
            ],
            'system_info' => [
                'laravel_version' => app()->version(),
                'processed_by_user' => auth()->user()->name ?? 'System',
                'ip_address' => request()->ip()
            ]
        ];
    }
    
    /**
     * Exclude from active operations (update scopes and calculations)
     */
    protected function excludeFromActiveOperations(Student $student): void
    {
        // The student's status is already changed to 'dropout'
        // Laravel scopes will automatically exclude them from active queries
        
        // Clear any cached data
        if (method_exists($student, 'clearCachedData')) {
            $student->clearCachedData();
        }
    }
    
    /**
     * Generate comprehensive dropout report
     */
    protected function generateDropoutReport(Student $student, array $financialSummary): array
    {
        return [
            'report_date' => now()->format('Y-m-d H:i:s'),
            'student_info' => [
                'name' => $student->name,
                'enrollment_number' => $student->enrollment_number,
                'course' => $student->course->name ?? 'N/A',
                'batch' => $student->batch->name ?? 'N/A',
                'admission_date' => $student->admission_date,
                'dropout_date' => $student->dropout_date,
                'dropout_reason' => $student->dropout_reason
            ],
            'financial_summary' => $financialSummary,
            'impact_analysis' => [
                'will_be_excluded_from' => [
                    'Active student counts',
                    'Fee collection calculations',
                    'Attendance tracking',
                    'Academic performance metrics',
                    'Dashboard statistics'
                ],
                'will_be_preserved' => [
                    'All payment records and receipts',
                    'Historical fee data',
                    'Payment transaction history',
                    'Student profile information',
                    'Academic history until dropout'
                ]
            ]
        ];
    }
    
    /**
     * Log dropout process for audit trail
     */
    protected function logDropoutProcess(Student $student, array $dropoutData, array $financialSummary): void
    {
        Log::info('Student dropout processed', [
            'student_id' => $student->id,
            'student_name' => $student->name,
            'enrollment_number' => $student->enrollment_number,
            'dropout_date' => $dropoutData['dropout_date'] ?? now()->toDateString(),
            'reason' => $dropoutData['reason'] ?? 'Not specified',
            'final_outstanding' => $financialSummary['outstanding_amount'],
            'total_paid' => $financialSummary['total_paid'],
            'processed_by' => auth()->user()->name ?? 'System',
            'processed_at' => now()->toISOString()
        ]);
    }
    
    /**
     * Get dropout statistics for dashboard
     */
    public function getDropoutStatistics(): array
    {
        $dropouts = Student::where('status', 'dropout')->get();
        
        return [
            'total_dropouts' => $dropouts->count(),
            'this_month_dropouts' => $dropouts->where('dropout_date', '>=', now()->startOfMonth())->count(),
            'this_year_dropouts' => $dropouts->where('dropout_date', '>=', now()->startOfYear())->count(),
            'total_preserved_payments' => $dropouts->sum('total_paid_amount'),
            'total_outstanding_written_off' => $dropouts->sum('final_outstanding_amount'),
            'average_study_duration' => $dropouts->avg(function($student) {
                return $student->dropout_date ? 
                    Carbon::parse($student->admission_date)->diffInDays($student->dropout_date) : 0;
            }),
            'top_dropout_reasons' => $dropouts->whereNotNull('dropout_reason')
                ->groupBy('dropout_reason')
                ->map(function($group) {
                    return $group->count();
                })
                ->sortDesc()
                ->take(5)
        ];
    }
    
    /**
     * Reactivate a dropout student (if needed)
     */
    public function reactivateStudent(Student $student, string $reason = ''): array
    {
        if ($student->status !== 'dropout') {
            return [
                'success' => false,
                'message' => 'Student is not marked as dropout'
            ];
        }
        
        DB::beginTransaction();
        
        try {
            // Clear dropout information but preserve the history
            $originalDropoutData = [
                'dropout_date' => $student->dropout_date,
                'dropout_reason' => $student->dropout_reason,
                'dropout_metadata' => $student->dropout_metadata
            ];
            
            $student->update([
                'status' => 'active',
                'dropout_date' => null,
                'dropout_reason' => null,
                'final_outstanding_amount' => 0,
                'total_paid_amount' => 0,
                'dropout_metadata' => [
                    'reactivation_history' => [
                        'reactivated_at' => now()->toISOString(),
                        'reactivated_by' => auth()->user()->name ?? 'System',
                        'reactivation_reason' => $reason,
                        'previous_dropout_data' => $originalDropoutData
                    ]
                ],
                'processed_by' => auth()->id(),
                'dropout_processed_at' => null
            ]);
            
            // Remove dropout notes from fees
            $student->studentFees()
                ->where('notes', 'like', '%[STUDENT DROPOUT%')
                ->update([
                    'notes' => DB::raw("REPLACE(notes, SUBSTRING(notes, LOCATE('[STUDENT DROPOUT', notes), LOCATE(']', notes, LOCATE('[STUDENT DROPOUT', notes)) - LOCATE('[STUDENT DROPOUT', notes) + 1), '')")
                ]);
            
            // Log the reactivation
            $student->logReactivation($reason);
            $student->logStatusChange('dropout', 'active', $reason);
            
            DB::commit();
            
            Log::info('Student reactivated from dropout', [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'reactivated_by' => auth()->user()->name ?? 'System',
                'reason' => $reason
            ]);
            
            return [
                'success' => true,
                'message' => "Student {$student->name} has been successfully reactivated"
            ];
            
        } catch (\Exception $e) {
            DB::rollback();
            
            return [
                'success' => false,
                'message' => 'Reactivation failed: ' . $e->getMessage()
            ];
        }
    }
}
