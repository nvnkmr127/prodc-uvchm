<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\StudentPhotoHelper;
use App\Services\ComponentPaymentService;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class Student extends Model
{
    use HasFactory, LogsActivity, StudentPhotoHelper;

    /**
     * Boot method 
     */
    protected static function boot()
    {
        parent::boot();

        // Apply Global Scope for Academic Year (via Batch)
        if (config('app.enable_academic_year_global_scope', true) && !app()->runningInConsole() && !request()->is('api/*')) {
            static::addGlobalScope('academic_year', function (Builder $builder) {
                $selectedYearId = session('selected_academic_year_id');
                // Default to current year if session not set (optional, consistent with HasAcademicYear)
                if (!$selectedYearId) {
                    $currentYear = \App\Models\AcademicYear::where('is_current', true)->first();
                    $selectedYearId = $currentYear?->id;
                }

                if ($selectedYearId) {
                    $builder->whereHas('batch', function ($q) use ($selectedYearId) {
                        $q->where('academic_year_id', $selectedYearId);
                    });
                }
            });
        }
    }

    // Explicitly define the primary key (Laravel defaults to 'id')
    protected $primaryKey = 'id';

    // If you need to use student_id elsewhere, create a custom accessor
    protected $appends = ['student_id'];

    protected $fillable = [
        'name',
        'email',
        'enrollment_number',
        'biometric_employee_code',
        'gender',
        'father_name',
        'student_mobile',
        'father_mobile',
        'mother_mobile',
        'dob', // Date of Birth
        'course_id',
        'village',
        'current_employer',
        'job_title',
        'admission_date',
        'batch_id',
        'status',
        'photo',
        'payment_terms',
        'source',
        'referral_name',
        // DROPOUT MANAGEMENT FIELDS
        'dropout_date',
        'dropout_reason',
        'final_outstanding_amount',
        'total_paid_amount',
        'dropout_metadata',
        'processed_by',
        'dropout_processed_at',
        'admission_id',
        'referral_commission_paid_at',
        'referral_commission_amount',
        'referral_payment_mode',
        'referral_payment_remarks'
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'admission_date' => 'date',
        'dropout_date' => 'date',
        'dob' => 'date', // Cast DOB to date
        'dropout_metadata' => 'array',
        'status' => 'string',
        'payment_terms' => 'integer',
        'dropout_processed_at' => 'datetime',
        'referral_commission_paid_at' => 'datetime'
    ];

    /**
     * Get the student's age based on DOB.
     * Returns string like "20 Years, 5 Months" or null if DOB is missing.
     */
    public function getAgeAttribute()
    {
        if (!$this->dob) {
            return null;
        }

        $dob = $this->dob;
        $now = now();

        $years = (int) $dob->diffInYears($now);
        $months = (int) $dob->copy()->addYears($years)->diffInMonths($now);

        $ageString = $years . ' Years';
        if ($months > 0) {
            $ageString .= ', ' . $months . ' Months';
        }

        return $ageString;
    }

    /**
     * Get the student who referred this student.
     */
    public function getReferrerAttribute()
    {
        if ($this->source !== 'Student Refer' || empty($this->referral_name)) {
            return null;
        }

        return self::where('name', $this->referral_name)->with('batch')->first();
    }

    protected $dates = [
        'admission_date',
        'created_at',
        'updated_at'
    ];

    // ===================================
    // ACCESSORS
    // ===================================

    public function getStudentIdAttribute()
    {
        return $this->id;
    }

    // Photo accessor methods using the trait
    public function getPhotoUrlAttribute(): string
    {
        return self::getStudentPhotoUrl($this);
    }

    public function getSmallPhotoAttribute(): string
    {
        return self::getStudentCircularPhoto($this);
    }

    public function getMediumPhotoAttribute(): string
    {
        return self::getStudentMediumPhoto($this);
    }

    public function getLargePhotoAttribute(): string
    {
        return self::getStudentLargePhoto($this);
    }

    public function getHasRealPhotoAttribute(): bool
    {
        return self::hasRealPhoto($this);
    }

    /* * --------------------------------------------------------------------
     * ADD THIS METHOD TO STOP SYSTEM LOGS
     * --------------------------------------------------------------------
     */
    protected function shouldLogEvent(string $eventName): bool
    {
        // Prevent "System" logs: Don't log if running from console (CLI/Cron jobs)
        if (app()->runningInConsole()) {
            return false;
        }

        // Optional: Don't log if the user is not logged in (System actions)
        if (!auth()->check()) {
            return false;
        }

        return true;
    }

    // ===================================
    // RELATIONSHIPS (Clean Version)
    // ===================================

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Component-based fee system
     */
    public function studentFees()
    {
        return $this->hasMany(StudentFee::class);
    }

    /**
     * Get the payment defaulter records for this student
     */
    public function paymentDefaulters()
    {
        return $this->hasMany(\App\Models\PaymentDefaulter::class);
    }

    /**
     * Get the active payment defaulter record for this student
     */
    public function activeDefaulter()
    {
        return $this->hasOne(\App\Models\PaymentDefaulter::class)->where('current_status', '!=', 'resolved');
    }

    /**
     * Many-to-many relationship with PracticalGroup
     * A student can belong to multiple practical groups (across different academic years)
     */
    public function practicalGroups()
    {
        return $this->belongsToMany(PracticalGroup::class, 'practical_group_student')
            ->withTimestamps()
            ->orderBy('practical_groups.created_at', 'desc');
    }

    /**
     * Direct payments (component-based system)
     */
    public function payments()
    {
        // Only support direct student payments via student_id
        if (Schema::hasColumn('payments', 'student_id')) {
            return $this->hasMany(Payment::class, 'student_id');
        }

        // Return empty relationship if column doesn't exist
        return $this->hasMany(Payment::class, 'student_id')->where('id', null);
    }

    /**
     * Component payments specifically
     */
    public function componentPayments()
    {
        return $this->hasMany(Payment::class, 'student_id')->where('payment_type', 'component');
    }

    /**
     * Student concessions
     */
    public function concessions()
    {
        return $this->hasMany(StudentConcession::class);
    }

    /**
     * Attendance records
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Admission record (if exists)
     */
    public function admission()
    {
        if (Schema::hasColumn('admissions', 'student_id')) {
            return $this->hasOne(Admission::class, 'student_id');
        }

        // Return empty relationship if column doesn't exist
        return $this->hasOne(Admission::class, 'student_id')->where('id', null);
    }

    /**
     * Payment reminder relationships
     */
    public function paymentReminders()
    {
        return $this->hasMany(PaymentReminder::class);
    }

    public function paymentDefaulter()
    {
        return $this->hasOne(PaymentDefaulter::class);
    }

    public function pendingReminders()
    {
        return $this->paymentReminders()->where('status', 'pending');
    }

    public function recentReminders()
    {
        return $this->paymentReminders()
            ->where('created_at', '>=', now()->subDays(30))
            ->latest();
    }

    public function activities()
    {
        return $this->morphMany(\Spatie\Activitylog\Models\Activity::class, 'subject');
    }

    public function causedActivities()
    {
        return $this->morphMany(\Spatie\Activitylog\Models\Activity::class, 'causer');
    }

    /**
     * DROPOUT MANAGEMENT RELATIONSHIPS
     */
    public function processedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'processed_by');
    }

    // ===================================
    // COMPUTED ATTRIBUTES
    // ===================================

    public function getCourseAttribute()
    {
        return $this->batch ? $this->batch->course : null;
    }

    public function getCourseNameAttribute(): string
    {
        return $this->course ? $this->course->name : 'N/A';
    }

    public function getBatchNameAttribute(): string
    {
        return $this->batch ? $this->batch->name : 'N/A';
    }

    public function getFullMobileInfoAttribute(): string
    {
        $mobiles = array_filter([
            $this->student_mobile ? "Student: {$this->student_mobile}" : null,
            $this->father_mobile ? "Father: {$this->father_mobile}" : null
        ]);
        return implode(', ', $mobiles) ?: 'No Contact Info';
    }

    // ===================================
    // STATUS SCOPES - CRITICAL FOR DROPOUT MANAGEMENT
    // ===================================

    /**
     * Scope for active students only
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for dropout students only
     */
    public function scopeDropout($query)
    {
        return $query->where('status', 'dropout');
    }

    /**
     * Scope for graduated students only
     */
    public function scopeGraduated($query)
    {
        return $query->where('status', 'graduated');
    }

    /**
     * Scope for all non-dropout students (active + graduated)
     */
    public function scopeNonDropout($query)
    {
        return $query->whereIn('status', ['active', 'graduated']);
    }

    /**
     * Scope to disable academic year filtering
     * Use when you need to query students across all years
     */
    public function scopeAllYears($query)
    {
        return $query->withoutGlobalScope('academic_year');
    }

    // ===================================
    // FINANCIAL SCOPES - UPDATED TO EXCLUDE DROPOUTS
    // ===================================

    /**
     * Scope for students with outstanding fees (EXCLUDES DROPOUTS)
     */
    public function scopeWithOutstandingFees($query)
    {
        if (Schema::hasTable('student_fees')) {
            return $query->where('status', '!=', 'dropout')
                ->whereHas('studentFees', function ($q) {
                    $q->whereRaw('amount - COALESCE(paid_amount, 0) - COALESCE(concession_amount, 0) > 0');
                });
        }

        return $query->where('id', null); // No results if no component system
    }

    /**
     * Scope for students with overdue fees (EXCLUDES DROPOUTS)
     */
    public function scopeWithOverdueFees($query)
    {
        if (Schema::hasTable('student_fees')) {
            return $query->where('status', '!=', 'dropout')
                ->whereHas('studentFees', function ($q) {
                    $q->where('due_date', '<', now())
                        ->where('status', '!=', 'paid')
                        ->whereRaw('amount - COALESCE(paid_amount, 0) - COALESCE(concession_amount, 0) > 0');
                });
        }

        return $query->where('id', null); // No results if no component system
    }

    /**
     * Scope for students with fully paid fees (EXCLUDES DROPOUTS)
     */
    public function scopeWithPaidFees($query)
    {
        if (Schema::hasTable('student_fees')) {
            return $query->where('status', '!=', 'dropout')
                ->whereDoesntHave('studentFees', function ($q) {
                    $q->whereRaw('amount - COALESCE(paid_amount, 0) - COALESCE(concession_amount, 0) > 0');
                });
        }

        return $query->where('status', '!=', 'dropout'); // All non-dropout students if no system to check
    }

    /**
     * Get practical groups for a specific academic year
     */
    public function practicalGroupsForYear($academicYearId)
    {
        return $this->practicalGroups()->where('academic_year_id', $academicYearId);
    }

    /**
     * Get current year practical groups
     */
    public function currentPracticalGroups()
    {
        $currentYear = \App\Models\AcademicYear::where('is_current', true)->first();
        if ($currentYear) {
            return $this->practicalGroupsForYear($currentYear->id);
        }
        return $this->practicalGroups()->where('id', null); // Return empty relationship
    }

    /**
     * Check if student is assigned to any practical group for a specific academic year
     */
    public function isAssignedToGroup($academicYearId = null)
    {
        if ($academicYearId) {
            return $this->practicalGroupsForYear($academicYearId)->exists();
        }

        $currentYear = \App\Models\AcademicYear::where('is_current', true)->first();
        if ($currentYear) {
            return $this->practicalGroupsForYear($currentYear->id)->exists();
        }

        return false;
    }

    // ===================================
    // FINANCIAL METHODS (Component-based) - UPDATED FOR DROPOUT
    // ===================================

    /**
     * Get financial summary for the student (UPDATED FOR DROPOUT HANDLING)
     */
    public function getFinancialSummary(): array
    {
        // Special handling for dropout students
        if ($this->isDropout()) {
            return [
                'total_fees' => $this->total_paid_amount + $this->final_outstanding_amount,
                'total_paid' => $this->total_paid_amount,
                'total_outstanding' => 0, // Set to 0 for dropouts
                'total_concession' => 0, // We don't track this separately for dropouts
                'payment_status' => 'dropout',
                'final_outstanding_at_dropout' => $this->final_outstanding_amount,
                'dropout_info' => $this->getDropoutSummary()
            ];
        }

        // Standard logic for active/graduated students
        if (!Schema::hasTable('student_fees')) {
            return [
                'total_fees' => 0,
                'total_paid' => 0,
                'total_outstanding' => 0,
                'total_concession' => 0,
                'payment_status' => 'no_system'
            ];
        }

        $fees = $this->studentFees;

        $totalFees = $fees->sum('amount');
        $totalPaid = $fees->sum('paid_amount');
        $totalConcession = $fees->sum('concession_amount');
        $totalOutstanding = $fees->sum(function ($fee) {
            return max(0, $fee->amount - $fee->concession_amount - $fee->paid_amount);
        });

        return [
            'total_fees' => $totalFees,
            'total_paid' => $totalPaid,
            'total_outstanding' => $totalOutstanding,
            'total_concession' => $totalConcession,
            'payment_status' => $totalOutstanding > 0 ? 'pending' : 'completed'
        ];
    }

    /**
     * Check if student has outstanding fees (EXCLUDES DROPOUTS)
     */
    public function hasOutstandingFees(): bool
    {
        if ($this->isDropout()) {
            return false; // Dropout students don't have "outstanding" fees in the operational sense
        }

        if (!Schema::hasTable('student_fees')) {
            return false;
        }

        return $this->studentFees()
            ->whereRaw('amount - COALESCE(concession_amount, 0) - COALESCE(paid_amount, 0) > 0')
            ->exists();
    }

    /**
     * Get overdue fees (EXCLUDES DROPOUTS)
     */
    public function getOverdueFees()
    {
        if ($this->isDropout() || !Schema::hasTable('student_fees')) {
            return collect();
        }

        return $this->studentFees()
            ->whereRaw('amount - COALESCE(concession_amount, 0) - COALESCE(paid_amount, 0) > 0')
            ->where('due_date', '<', now())
            ->get();
    }

    /**
     * Get latest component payment
     */
    public function getLatestComponentPayment()
    {
        return $this->componentPayments()->latest()->first();
    }

    // ===================================
    // DROPOUT MANAGEMENT METHODS
    // ===================================

    /**
     * Check if student is dropout
     */
    public function isDropout(): bool
    {
        return $this->status === 'dropout';
    }

    /**
     * Check if student is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if student is graduated
     */
    public function isGraduated(): bool
    {
        return $this->status === 'graduated';
    }

    /**
     * Get dropout duration in days
     */
    public function getDropoutDuration(): ?int
    {
        if (!$this->isDropout() || !$this->dropout_date) {
            return null;
        }

        return Carbon::parse($this->admission_date)->diffInDays($this->dropout_date);
    }

    /**
     * Get comprehensive dropout summary
     */
    public function getDropoutSummary(): array
    {
        if (!$this->isDropout()) {
            return [];
        }

        return [
            'dropout_date' => $this->dropout_date,
            'dropout_reason' => $this->dropout_reason,
            'study_duration_days' => $this->getDropoutDuration(),
            'final_outstanding' => $this->final_outstanding_amount,
            'total_paid' => $this->total_paid_amount,
            'processed_by' => $this->processedBy->name ?? 'Unknown',
            'processed_at' => $this->dropout_processed_at,
            'can_be_reactivated' => true // Add business logic here if needed
        ];
    }

    /**
     * Check if student is payment defaulter
     */
    public function isPaymentDefaulter(): bool
    {
        return $this->paymentDefaulters()
            ->where('current_status', '!=', 'resolved')
            ->exists();
    }

    /**
     * Get total outstanding amount for this student
     */
    public function getTotalOutstandingAmount(): float
    {
        if ($this->isDropout()) {
            return 0; // Dropouts don't have operational outstanding amounts
        }

        return $this->studentFees()
            ->where('status', 'unpaid')
            ->get()
            ->sum(function ($fee) {
                return ($fee->amount ?? 0) - ($fee->paid_amount ?? 0) - ($fee->concession_amount ?? 0);
            });
    }

    /**
     * Get overdue amount for this student
     */
    public function getOverdueAmount(): float
    {
        if ($this->isDropout()) {
            return 0; // Dropouts don't have operational overdue amounts
        }

        return $this->studentFees()
            ->where('status', 'unpaid')
            ->where('due_date', '<', now())
            ->get()
            ->sum(function ($fee) {
                return ($fee->amount ?? 0) - ($fee->paid_amount ?? 0) - ($fee->concession_amount ?? 0);
            });
    }

    /**
     * Calculate attendance percentage for a given period
     */
    public function getAttendancePercentage($startDate = null, $endDate = null): float
    {
        if (!$startDate)
            $startDate = now()->startOfMonth();
        if (!$endDate)
            $endDate = now()->endOfMonth();

        $totalClasses = $this->attendances()
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->count();

        if ($totalClasses === 0)
            return 0;

        $presentClasses = $this->attendances()
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->where('status', 'present')
            ->count();

        return round(($presentClasses / $totalClasses) * 100, 1);
    }

    /**
     * Check if student is eligible for discount (example: female students)
     */
    public function isEligibleForDiscount(): bool
    {
        return $this->gender === 'Female';
    }

    /**
     * Generate new enrollment number
     */
    public function generateNewEnrollmentNumber(): string
    {
        if (!$this->batch) {
            return 'UNASSIGNED-' . time();
        }

        $settings = Setting::all()->keyBy('key');
        $collegePrefix = $settings['enrollment_prefix']->value ?? 'UV';
        $coursePrefix = $this->batch->course->enrollment_prefix ?? substr($this->batch->course->name, 0, 2);

        $year = date('Y');
        $lastStudent = static::where('batch_id', $this->batch_id)
            ->where('enrollment_number', 'LIKE', "{$collegePrefix}{$coursePrefix}{$year}%")
            ->orderBy('enrollment_number', 'desc')
            ->first();

        if ($lastStudent) {
            $lastNumber = (int) substr($lastStudent->enrollment_number, -3);
            $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '001';
        }

        return "{$collegePrefix}{$coursePrefix}{$year}{$newNumber}";
    }

    // ===================================
    // HELPER METHODS
    // ===================================

    /**
     * Find student by student ID (alias for ID)
     */
    public static function findByStudentId($studentId)
    {
        return static::where('id', $studentId)->first();
    }

    /**
     * Get component status based on remaining amount
     */
    private function getComponentStatus($remainingAmount)
    {
        if ($remainingAmount <= 0) {
            return 'paid';
        }
        return 'unpaid';
    }

    // ===================================
    // CONVENIENCE RELATIONSHIP ACCESSORS
    // ===================================

    /**
     * Get unpaid student fees
     */
    public function unpaidFees()
    {
        return $this->studentFees()->whereIn('status', ['unpaid', 'partial']);
    }

    /**
     * Get paid student fees
     */
    public function paidFees()
    {
        return $this->studentFees()->where('status', 'paid');
    }

    /**
     * Get student fees by category
     */
    public function feesByCategory($categoryId)
    {
        return $this->studentFees()->where('fee_category_id', $categoryId);
    }

    /**
     * Get total unpaid amount for this student
     */
    public function getTotalUnpaidAmount()
    {
        if ($this->isDropout()) {
            return 0.0; // Dropouts don't have operational unpaid amounts
        }

        if (Schema::hasTable('student_fees')) {
            return $this->unpaidFees()->get()->sum(fn($fee) => $fee->getRemainingAmount());
        }

        return 0.0;
    }

    /**
     * Get total paid amount for this student
     */
    public function getTotalPaidAmount()
    {
        if ($this->isDropout()) {
            return $this->total_paid_amount; // Use preserved amount for dropouts
        }

        if (Schema::hasTable('student_fees')) {
            return $this->paidFees()->sum('paid_amount');
        }

        return 0.0;
    }

    // ===================================
    // ACTIVITY LOGGING - UPDATED FOR DROPOUT
    // ===================================

    /**
     * Activity Logging Configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'email',
                'father_name',
                'student_mobile',
                'father_mobile',
                'village',
                'admission_date',
                'batch_id',
                'gender',
                'status',
                'dropout_date',
                'dropout_reason' // Added dropout fields
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match ($eventName) {
                'created' => 'Student profile created',
                'updated' => 'Student profile updated',
                'deleted' => 'Student profile deleted',
                default => "Student {$eventName}"
            });
    }

    /**
     * Custom activity logging methods
     */
    public function logStatusChange($oldStatus, $newStatus, $reason = null)
    {
        activity()
            ->causedBy(auth()->user())
            ->performedOn($this)
            ->withProperties([
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'reason' => $reason,
                'type' => 'status_change'
            ])
            ->log("Student status changed from {$oldStatus} to {$newStatus}");
    }

    public function logBatchChange($oldBatchId, $newBatchId)
    {
        $oldBatch = $oldBatchId ? Batch::find($oldBatchId) : null;
        $newBatch = $newBatchId ? Batch::find($newBatchId) : null;

        activity()
            ->causedBy(auth()->user())
            ->performedOn($this)
            ->withProperties([
                'old_batch_id' => $oldBatchId,
                'new_batch_id' => $newBatchId,
                'old_batch_name' => $oldBatch?->name,
                'new_batch_name' => $newBatch?->name,
                'type' => 'batch_change'
            ])
            ->log("Student transferred from {$oldBatch?->name} to {$newBatch?->name}");
    }

    public function logFeeGeneration($feeStructureId, $componentsCount)
    {
        activity()
            ->causedBy(auth()->user())
            ->performedOn($this)
            ->withProperties([
                'fee_structure_id' => $feeStructureId,
                'components_generated' => $componentsCount,
                'academic_year' => date('Y') . '-' . (date('Y') + 1),
                'type' => 'fee_generation'
            ])
            ->log("Fee components generated - {$componentsCount} components created");
    }

    /**
     * Log dropout process
     */
    public function logDropout($reason = null, $financialSummary = [])
    {
        activity()
            ->causedBy(auth()->user())
            ->performedOn($this)
            ->withProperties([
                'dropout_date' => $this->dropout_date,
                'dropout_reason' => $reason,
                'financial_summary' => $financialSummary,
                'type' => 'student_dropout'
            ])
            ->log("Student marked as dropout: {$reason}");
    }

    /**
     * Log reactivation
     */
    public function logReactivation($reason = null)
    {
        activity()
            ->causedBy(auth()->user())
            ->performedOn($this)
            ->withProperties([
                'reactivation_reason' => $reason,
                'previous_dropout_date' => $this->dropout_date,
                'type' => 'student_reactivation'
            ])
            ->log("Student reactivated from dropout: {$reason}");
    }
}