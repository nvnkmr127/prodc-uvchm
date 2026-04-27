<?php

use App\Http\Controllers\Admin\AcademicYearController;
use App\Http\Controllers\Admin\AddressReportController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AdmissionController as AdminAdmissionController;
use App\Http\Controllers\Admin\AdmissionReportController;
use App\Http\Controllers\Admin\AgeReportController;
use App\Http\Controllers\Admin\AlumniController;
/*
|--------------------------------------------------------------------------
| Controller Imports
|--------------------------------------------------------------------------
| For clarity, all controller imports are grouped here.
*/
// Public & Generic Auth
use App\Http\Controllers\Admin\ApiTokenController;
use App\Http\Controllers\Admin\AssetCategoryController;
use App\Http\Controllers\Admin\AssetController;
use App\Http\Controllers\Admin\AssetReportController;
use App\Http\Controllers\Admin\AttendanceImportController;
use App\Http\Controllers\Admin\AttendanceReportController;
// Admin Controllers
use App\Http\Controllers\Admin\AttendanceSettingsController;
use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\BatchController;
use App\Http\Controllers\Admin\CertificateGeneratorController;
use App\Http\Controllers\Admin\CertificateTemplateController;
use App\Http\Controllers\Admin\ClassroomController;
use App\Http\Controllers\Admin\ComponentPaymentController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\CourseStructureController;
use App\Http\Controllers\Admin\CourseSubjectController;
use App\Http\Controllers\Admin\DailyAttendanceController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DropoutController;
use App\Http\Controllers\Admin\EnquiryController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\ExpenseCategoryController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\FacultyController;
use App\Http\Controllers\Admin\FacultySubjectController;
use App\Http\Controllers\Admin\FeeCategoryAnalysisController;
use App\Http\Controllers\Admin\FeeCategoryController;
use App\Http\Controllers\Admin\FeeStructureController;
use App\Http\Controllers\Admin\FinancialReportController;
use App\Http\Controllers\Admin\FollowUpCalendarController;
use App\Http\Controllers\Admin\GlobalSearchController;
use App\Http\Controllers\Admin\HolidayController;
use App\Http\Controllers\Admin\IdCardController;
use App\Http\Controllers\Admin\IdCardTemplateController;
use App\Http\Controllers\Admin\LabAllocationController;
use App\Http\Controllers\Admin\LeaveTypeController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Admin\NotificationManagementController;
use App\Http\Controllers\Admin\NotificationSettingsController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PaymentEditController;
use App\Http\Controllers\Admin\PaymentReminderController;
use App\Http\Controllers\Admin\PaymentReminderSettingsController;
use App\Http\Controllers\Admin\PayslipController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\PermissionManagementController;
use App\Http\Controllers\Admin\ReferralReportController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SalaryComponentController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StaffActivityController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\StudentFeeController;
use App\Http\Controllers\Admin\StudentImportController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\SystemHealthController;
use App\Http\Controllers\Admin\TimeSlotController;
use App\Http\Controllers\Admin\TimetableController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserSalaryController;
use App\Http\Controllers\Admin\VisitorController;
use App\Http\Controllers\Admin\WebhookController;
use App\Http\Controllers\Api\DashboardController as ApiDashboardController;
use App\Http\Controllers\Api\StudentApiController;
use App\Http\Controllers\Api\StudentController as ApiStudentController;
use App\Http\Controllers\Api\TestController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Faculty\DashboardController as FacultyDashboardController;
use App\Http\Controllers\LeaveApplicationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicEnquiryController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
// Faculty & Student Controllers
use App\Http\Controllers\UnifiedCalendarController;
use App\Models\Payment;
use App\Models\Student;
// API Controllers
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/*
|--------------------------------------------------------------------------
|                                 WEB ROUTES
|--------------------------------------------------------------------------
*/

// --- 1. Public Routes ---
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('admin.dashboard');
    }

    return redirect()->route('login');
})->name('home');

// ============================================================
// TEMPORARY DEBUG ROUTE — REMOVE AFTER ROOT CAUSE IS FOUND
// Visit: https://portal.uvchm.com/csrf-debug in your browser
// ============================================================
Route::get('/csrf-debug', function (\Illuminate\Http\Request $request) {
    $cookieName = config('session.cookie');
    $sessionToken = $request->session()->token();

    return response()->json([
        'status' => 'debug_active',
        'timestamp' => now()->toDateTimeString(),
        'session_id' => $request->session()->getId(),
        'session_driver' => config('session.driver'),
        'session_domain' => config('session.domain') ?? '(not set)',
        'session_secure' => config('session.secure') ? 'true' : 'false',
        'csrf_token_preview' => $sessionToken ? substr($sessionToken, 0, 10).'...' : 'MISSING ❌',
        'session_cookie_name' => $cookieName,
        'session_cookie_received' => $request->hasCookie($cookieName) ? 'YES ✅' : 'NO ❌',
        'all_cookies_received' => array_keys($request->cookies->all()),
        'x_forwarded_for' => $request->header('X-Forwarded-For') ?? 'not set',
        'x_forwarded_proto' => $request->header('X-Forwarded-Proto') ?? 'not set',
        'app_env' => config('app.env'),
        'app_debug' => config('app.debug') ? 'true' : 'false',
        'hint' => 'If session_cookie_received is NO ❌, check SESSION_DOMAIN in .env',
    ]);
})->name('csrf.debug');
// ============================================================

Route::get('/enquire', [PublicEnquiryController::class, 'create'])->name('enquiry.public.create');
Route::post('/enquire', [PublicEnquiryController::class, 'store'])->name('enquiry.public.store');
Route::get('/enquiry-success', fn () => view('public.enquiry_success'))->name('enquiry.success');
Route::get('receipts/{receipt_number}', [ComponentPaymentController::class, 'showPublicReceipt'])
    ->name('public.receipt.show');

Route::get('receipts/{receipt_number}/pdf', [ComponentPaymentController::class, 'downloadPublicReceipt'])
    ->name('public.receipt.pdf');

// --- 2. Authenticated User Routes (Generic) ---
Route::middleware(['auth'])->group(function () {
    // Laravel's default logout route
    Route::match(['get', 'post'], 'logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Standard verified user routes
    Route::middleware(['verified'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

    // College Admin Dashboard Routes
    Route::middleware(['auth', 'role:college-admin|super-admin'])->group(function () {
        Route::get('/admin/dashboard/college-admin', [App\Http\Controllers\Api\CollegeAdminDashboardController::class, 'index'])
            ->name('admin.dashboard.college-admin');
        Route::get('/api/dashboard/payment-modes', [DashboardController::class, 'getPaymentModes']);
    });
});

/*
|--------------------------------------------------------------------------
|                              ADMIN PANEL ROUTES
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware(['auth', 'permission:view backend'])->group(function () {

    // --- Dashboard & Core ---
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/calendar', [UnifiedCalendarController::class, 'index'])->name('calendar.index');
    Route::post('follow-up-calendar/update', [UnifiedCalendarController::class, 'updateDate'])->name('follow-ups.update');
    Route::resource('academic-years', AcademicYearController::class);
    Route::post('academic-year/switch', [AcademicYearController::class, 'switch'])->name('academic-years.switch');
    Route::post('academic-years/{academicYear}/set-current', [AcademicYearController::class, 'setCurrent'])->name('academic-years.set-current');
    Route::get('follow-up-calendar', [FollowUpCalendarController::class, 'index'])
        ->name('follow-ups.calendar');

    // Global search route - Enhanced with Ctrl+K support
    Route::get('global-search', [GlobalSearchController::class, 'search'])->name('global-search');

    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\NotificationController::class, 'index'])->name('index');
        Route::get('/recent', [App\Http\Controllers\Admin\NotificationController::class, 'recent'])->name('recent');
        Route::post('/mark-read', [App\Http\Controllers\Admin\NotificationController::class, 'markAsRead'])->name('mark-read');
        Route::post('/mark-all-read', [App\Http\Controllers\Admin\NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::get('/dashboard', [App\Http\Controllers\Admin\NotificationController::class, 'dashboard'])->name('user-dashboard');
        Route::get('/settings', [App\Http\Controllers\Admin\NotificationController::class, 'settings'])->name('settings');
    });

    // --- Staff Activity Tracking (Super Admin Only) ---
    Route::middleware(['role:super-admin'])->group(function () {
        Route::get('/staff-activity', [StaffActivityController::class, 'index'])->name('staff-activity.index');
        Route::get('/staff-activity/export', [StaffActivityController::class, 'export'])->name('staff-activity.export');
        Route::get('/staff-activity/{user}', [StaffActivityController::class, 'show'])->name('staff-activity.show');
    });
    // --- Admissions & Enquiries ---
    Route::middleware(['permission:manage admissions'])->group(function () {
        // IMPORTANT: All non-parameterised specific routes MUST appear before Route::resource()
        // to prevent the resource's {enquiry} wildcard from swallowing them.

        // --- Specific GET routes (no {enquiry} segment) ---
        Route::get('enquiries/check-mobile', [EnquiryController::class, 'checkMobile'])
            ->name('enquiries.check-mobile');
        Route::get('enquiries/facebook-leads', [EnquiryController::class, 'facebookLeads'])->name('enquiries.facebook-leads');
        Route::get('enquiries/ajax-search', [EnquiryController::class, 'ajaxSearch'])->name('enquiries.ajax-search');
        Route::get('enquiries/export', [EnquiryController::class, 'export'])->name('enquiries.export');
        Route::get('enquiries/export-selected', [EnquiryController::class, 'exportSelected'])->name('enquiries.export-selected');
        Route::get('enquiries/import/sample', [EnquiryController::class, 'downloadSample'])->name('enquiries.import.sample');

        // --- Specific POST routes (no {enquiry} segment) ---
        Route::post('enquiries/bulk-assign', [EnquiryController::class, 'bulkAssign'])->name('enquiries.bulk-assign');
        Route::post('enquiries/bulk-delete', [EnquiryController::class, 'bulkDelete'])->name('enquiries.bulk-delete');
        Route::post('enquiries/bulk-update', [EnquiryController::class, 'bulkUpdate'])->name('enquiries.bulk-update');
        Route::post('enquiries/import', [EnquiryController::class, 'import'])->name('enquiries.import');

        // --- Resource route (registers {enquiry} wildcard — must come AFTER specific routes) ---
        Route::resource('enquiries', EnquiryController::class);

        // --- Parameterised routes (must come AFTER resource) ---
        Route::get('/enquiries/{enquiry}/convert', [EnquiryController::class, 'convertToAdmission'])->name('enquiries.convertToAdmission');
        Route::post('enquiries/{enquiry}/quick-update', [EnquiryController::class, 'quickUpdate'])->name('enquiries.quick-update');
        Route::post('/enquiries/{enquiry}/follow-ups', [EnquiryController::class, 'addFollowUp'])->name('enquiries.follow-ups.store');

        Route::resource('visitors', VisitorController::class);
        Route::get('admissions', [AdminAdmissionController::class, 'index'])->name('admissions.index');
        Route::get('admissions/{admission}', [AdminAdmissionController::class, 'show'])->name('admissions.show');
        Route::post('admissions/{admission}/approve', [AdminAdmissionController::class, 'approve'])->name('admissions.approve');
        Route::post('admissions/{admission}/reject', [AdminAdmissionController::class, 'reject'])->name('admissions.reject');
        Route::post('admissions/{admission}/follow-ups', [AdminAdmissionController::class, 'addFollowUp'])->name('admissions.follow-ups.store');
        Route::get('/admissions/create/{enquiry}', [AdminAdmissionController::class, 'create'])->name('admissions.create');
        Route::post('/admissions/finalize', [AdminAdmissionController::class, 'finalizeAndApprove'])->name('admissions.finalize');
    });

    // Fee Category Analysis Routes
    Route::prefix('fee-category-analysis')->name('fee-category-analysis.')->group(function () {
        // Main routes (specific routes FIRST)
        Route::get('/', [FeeCategoryAnalysisController::class, 'index'])->name('index');
        Route::get('/critical-defaulters', [FeeCategoryAnalysisController::class, 'criticalDefaulters'])->name('critical-defaulters');
        Route::get('/recovery-tracking', [FeeCategoryAnalysisController::class, 'recoveryTracking'])->name('recovery-tracking');
        Route::get('/export/{type}', [FeeCategoryAnalysisController::class, 'export'])->name('export');

        // Category-specific routes (parameterized routes LAST)
        Route::get('/{feeCategory}', [FeeCategoryAnalysisController::class, 'show'])
            ->name('show')
            ->where('feeCategory', '[0-9]+');
        Route::get('/{feeCategory}', [FeeCategoryAnalysisController::class, 'show'])
            ->name('show')
            ->where('feeCategory', '[0-9]+');
        Route::get('/{feeCategory}/pending-students', [FeeCategoryAnalysisController::class, 'pendingStudents'])
            ->name('pending-students')
            ->where('feeCategory', '[0-9]+');

    });


    // Student API routes
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/students/{student}/unpaid-fees', [StudentController::class, 'getUnpaidFees'])
            ->name('students.unpaid-fees')
            ->where('student', '[0-9]+');
    });

    // End of Student/API section


    // --- Attendance Management ---
    Route::middleware(['permission:view attendance'])->group(function () {
        Route::prefix('attendance/single')->name('attendance.single.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\Attendance\SingleAttendanceController::class, 'index'])->name('index');
            Route::get('/students', [\App\Http\Controllers\Admin\Attendance\SingleAttendanceController::class, 'getStudents'])->name('students');
            Route::get('/calendar', [\App\Http\Controllers\Admin\Attendance\SingleAttendanceController::class, 'getCalendar'])->name('calendar');
            Route::post('/store', [\App\Http\Controllers\Admin\Attendance\SingleAttendanceController::class, 'store'])->name('store');
        });

    });

    Route::get('/attendance/leaderboard', [AttendanceSettingsController::class, 'getAttendanceLeaderboard'])
        ->name('attendance.leaderboard')
        ->middleware('permission:view attendance|manage attendance');

    // ================================
    // ACADEMICS SECTION
    // ================================

    Route::middleware(['permission:manage courses'])->group(function () {
        // Standard Resource Routes
        Route::resource('courses', CourseController::class);
        Route::resource('subjects', SubjectController::class);
        Route::resource('batches', BatchController::class);

        // Batch Management
        Route::get('batches/{batch}/manage-students', [BatchController::class, 'manageStudents'])
            ->name('batches.manageStudents')
            ->where('batch', '[0-9]+');
        Route::post('batches/{batch}/sync-students', [BatchController::class, 'syncStudents'])
            ->name('batches.syncStudents')
            ->where('batch', '[0-9]+');
        Route::post('batches/{batch}/graduate', [BatchController::class, 'graduate'])
            ->name('batches.graduate')
            ->where('batch', '[0-9]+');

        Route::post('batches/{batch}/toggle-internship', [BatchController::class, 'toggleInternship'])
            ->name('batches.toggleInternship')
            ->where('batch', '[0-9]+');

        // Course Structure & Terms
        Route::get('courses/{course}/structure', [CourseStructureController::class, 'show'])
            ->name('courses.structure.show')
            ->where('course', '[0-9]+');
        Route::post('courses/{course}/structure', [CourseStructureController::class, 'store'])
            ->name('courses.structure.store')
            ->where('course', '[0-9]+');
        Route::delete('course-terms/{term}', [CourseStructureController::class, 'destroy'])
            ->name('courses.structure.destroy')
            ->where('term', '[0-9]+');

        // Course-Subject Management
        Route::get('courses/{course}/subjects/edit', [CourseSubjectController::class, 'edit'])
            ->name('courses.subjects.edit')
            ->where('course', '[0-9]+');
        Route::put('courses/{course}/subjects', [CourseSubjectController::class, 'update'])
            ->name('courses.subjects.update')
            ->where('course', '[0-9]+');

        // Subject Faculty Management
        Route::get('subjects/{subject}/faculty-data', [SubjectController::class, 'getFacultyData'])
            ->name('subjects.faculty-data')
            ->where('subject', '[0-9]+');
        Route::post('subjects/{subject}/assign-faculty', [SubjectController::class, 'assignFaculty'])
            ->name('subjects.assign-faculty')
            ->where('subject', '[0-9]+');
        Route::post('subjects/{subject}/remove-faculty', [SubjectController::class, 'removeFaculty'])
            ->name('subjects.remove-faculty')
            ->where('subject', '[0-9]+');

        // AJAX Endpoints for Timetable
        Route::get('courses/{course}/terms', [TimetableController::class, 'getCourseTerms'])
            ->name('courses.terms')
            ->where('course', '[0-9]+');
        Route::get('courses/{course}/lab-prerequisites', [TimetableController::class, 'getLabPrerequisites'])
            ->name('courses.lab-prerequisites')
            ->where('course', '[0-9]+');
        Route::get('batches/{batch}/practical-groups', [TimetableController::class, 'getBatchPracticalGroups'])
            ->name('batches.practical-groups')
            ->where('batch', '[0-9]+');
    });

    // ================================
    // STUDENT MANAGEMENT
    // ================================
    Route::middleware(['permission:manage students'])->group(function () {
        // ===== SPECIFIC ROUTES MUST COME FIRST =====

        // Course-related routes
        Route::get('get-batches-for-course/{course}', [StudentController::class, 'getBatchesForCourse'])
            ->name('students.get-batches-for-course')
            ->where('course', '[0-9]+');

        // Student Import Routes
        Route::get('students/import', [StudentImportController::class, 'create'])->name('students.import.create');
        Route::post('students/import', [StudentImportController::class, 'store'])->name('students.import.store');
        Route::get('students/import/sample', [StudentImportController::class, 'downloadSample'])->name('students.import.sample');
        Route::get('alumni', [AlumniController::class, 'index'])->name('alumni.index');

        // Import Log Routes
        Route::get('students/import-logs', [StudentImportController::class, 'importLogs'])->name('students.import-logs');
        Route::get('students/import-logs/{importLog}', [StudentImportController::class, 'showImportLog'])
            ->name('students.import-log.show')
            ->where('importLog', '[0-9]+');
        Route::get('students/import-logs/{importLog}/export', [StudentImportController::class, 'exportImportLog'])
            ->name('students.import-log.export')
            ->where('importLog', '[0-9]+');

        Route::get('students/{student}/unassigned-fee-components', [StudentController::class, 'getUnassignedFeeComponents'])
            ->name('students.unassigned-fee-components')
            ->where('student', '[0-9]+');

        Route::post('students/{student}/assign-fee-component', [StudentController::class, 'assignFeeComponent'])
            ->name('students.assign-fee-component')
            ->where('student', '[0-9]+');

        // Student Suggestion Route
        Route::get('students/suggestions', [StudentController::class, 'getSuggestions'])
            ->name('students.suggestions');

        // **ADD ATTENDANCE-DATA ROUTE HERE - BEFORE RESOURCE**
        Route::get('students/{student}/attendance-data', [StudentController::class, 'getAttendanceData'])
            ->name('students.attendance-data')
            ->where('student', '[0-9]+');

        Route::post('students/{student}/attendance-export/{format}', [StudentController::class, 'exportAttendanceData'])
            ->name('students.attendance-export')
            ->where(['student' => '[0-9]+', 'format' => 'pdf|excel']);

        // Biometric Mapping Routes (CRITICAL: Must come before resource route)
        Route::get('students/biometric-mapping', [StudentController::class, 'biometricMapping'])->name('students.biometric-mapping');
        Route::get('students/biometric-mapping/export', [StudentController::class, 'exportBiometricMapping'])->name('students.biometric-mapping.export');
        Route::post('students/biometric-mapping/import', [StudentController::class, 'importBiometricMapping'])->name('students.biometric-mapping.import');
        Route::get('students/biometric-mapping/sample', [StudentController::class, 'downloadBiometricMappingSample'])->name('students.biometric-mapping.sample');
        Route::post('students/biometric-mapping/bulk', [StudentController::class, 'bulkUpdateBiometricMapping'])->name('students.biometric-mapping.bulk');
        Route::post('students/biometric-mapping/auto-generate', [StudentController::class, 'autoGenerateBiometricMapping'])->name('students.biometric-mapping.auto-generate');

        // ... rest of biometric routes ...

        // Dropout management routes
        Route::get('students/{student}/confirm-dropout', [DropoutController::class, 'confirmDropout'])
            ->name('students.confirm-dropout');

        Route::post('students/{student}/process-dropout', [DropoutController::class, 'processDropout'])
            ->name('students.process-dropout');

        // ... rest of dropout routes ...

        // Other specific students routes
        Route::get('students/export', [StudentController::class, 'export'])->name('students.export');
        Route::post('students/bulk-actions', [StudentController::class, 'bulkActions'])->name('students.bulk-actions');

        // ===== RESOURCE ROUTE MUST COME AFTER SPECIFIC ROUTES =====
        Route::resource('students', StudentController::class);

        // ===== PARAMETERIZED ROUTES LAST =====
        Route::patch('students/{student}/status', [StudentController::class, 'updateStatus'])
            ->name('students.updateStatus')
            ->where('student', '[0-9]+');

        // Activity Log Routes
        Route::get('students/{student}/activity-logs', [StudentController::class, 'getActivityLogs'])
            ->name('students.activity-logs')
            ->where('student', '[0-9]+');
        Route::get('students/{student}/activity-logs/count', [StudentController::class, 'getActivityLogsCount'])
            ->name('students.activity-logs.count')
            ->where('student', '[0-9]+');
    });

    // ================================
    // HR MANAGEMENT
    // ================================

    Route::middleware(['permission:manage hr'])->group(function () {
        Route::resource('faculty', FacultyController::class)->except(['show']);
        Route::resource('leave-types', LeaveTypeController::class);
        Route::get('leave-applications', [LeaveApplicationController::class, 'adminIndex'])->name('leave-applications.index');
        Route::post('leave-applications/{application}/update-status', [LeaveApplicationController::class, 'updateStatus'])
            ->name('leave-applications.updateStatus')
            ->where('application', '[0-9]+');
        Route::resource('salary-components', SalaryComponentController::class);
        Route::get('faculty/{user}/salary', [UserSalaryController::class, 'show'])
            ->name('faculty.salary.show')
            ->where('user', '[0-9]+');
        Route::post('faculty/{user}/salary', [UserSalaryController::class, 'store'])
            ->name('faculty.salary.store')
            ->where('user', '[0-9]+');
        Route::resource('payslips', PayslipController::class)->only(['index', 'create', 'store', 'show']);

        // Faculty-Subject Management Routes
        Route::get('faculty/{user}/subjects', [FacultySubjectController::class, 'edit'])
            ->name('faculty.subjects.edit')
            ->where('user', '[0-9]+');
        Route::put('faculty/{user}/subjects', [FacultySubjectController::class, 'update'])
            ->name('faculty.subjects.update')
            ->where('user', '[0-9]+');
    });

    // --- Financial Management ---
    Route::middleware(['permission:manage financials'])->group(function () {
        // Component-Based Payments
        Route::resource('component-payments', ComponentPaymentController::class);

        // Dashboard
        Route::get('payments/component-dashboard/{student}', [ComponentPaymentController::class, 'studentComponentDashboard'])
            ->name('payments.component-dashboard')
            ->where('student', '[0-9]+');

        // Component Payment Forms & Recording
        Route::get('component-payments/{student}/form', [ComponentPaymentController::class, 'componentPaymentForm'])
            ->name('component-payments.form')
            ->where('student', '[0-9]+');
        Route::post('component-payments/{student}/record', [ComponentPaymentController::class, 'recordComponentPayment'])
            ->name('component-payments.record')
            ->where('student', '[0-9]+');
        Route::post('component-payments/store-quick', [ComponentPaymentController::class, 'storeQuickPayment'])
            ->name('component-payments.store-quick');

        // Manual Webhook Trigger
        Route::post('payments/{payment}/webhook', [PaymentController::class, 'resendWebhook'])
            ->name('payments.webhook');

        // Receipt Routes
        Route::get('payments/{student}/{payment}/receipt', [ComponentPaymentController::class, 'showReceipt'])
            ->name('payments.receipt')
            ->where(['student' => '[0-9]+', 'payment' => '[0-9]+']);

        Route::get('payments/{student}/{payment}/receipt/pdf', [ComponentPaymentController::class, 'downloadReceipt'])
            ->name('payments.receipt.pdf')
            ->where(['student' => '[0-9]+', 'payment' => '[0-9]+']);

        Route::get('payments/{student}/{payment}/receipt/preview', [ComponentPaymentController::class, 'showPdfReceipt'])
            ->name('payments.receipt.preview')
            ->where(['student' => '[0-9]+', 'payment' => '[0-9]+']);

        Route::get('payments/{payment}/receipt', [ComponentPaymentController::class, 'showReceiptById'])
            ->name('payments.receipt.show')
            ->where('payment', '[0-9]+');

        // Component Data & Export
        Route::get('component-payments/component-data', [ComponentPaymentController::class, 'getComponentData'])
            ->name('component-payments.component-data');
        Route::get('component-payments/export', [ComponentPaymentController::class, 'export'])
            ->name('component-payments.export');

        // Bulk Operations
        Route::get('component-payments/bulk/create', [ComponentPaymentController::class, 'bulkCreate'])
            ->name('component-payments.bulk.create');
        Route::post('component-payments/bulk/store', [ComponentPaymentController::class, 'bulkStore'])
            ->name('component-payments.bulk.store');
        Route::post('component-payments/bulk-action', [ComponentPaymentController::class, 'bulkAction'])
            ->name('component-payments.bulk-action');

        // Concession Routes
        Route::post('students/{student}/apply-concession', [ComponentPaymentController::class, 'applyConcession'])
            ->name('students.apply-concession')
            ->where('student', '[0-9]+');
        Route::post('students/{student}/apply-auto-gender-concession', [ComponentPaymentController::class, 'applyGenderBasedConcession'])
            ->name('students.apply-auto-gender-concession')
            ->where('student', '[0-9]+');

        // Fee Structures & Categories
        Route::resource('fee-categories', FeeCategoryController::class);
        Route::resource('fee-structures', FeeStructureController::class);
        Route::resource('student-fees', StudentFeeController::class);
        Route::post('student-fees/generate-for-batch', [StudentFeeController::class, 'generateForBatch'])
            ->name('student-fees.generate-for-batch');

        // Expenses
        Route::resource('expense-categories', ExpenseCategoryController::class);
        Route::resource('expenses', ExpenseController::class);
    });

    // Payment Reminder Management Routes
    Route::middleware(['permission:view backend'])->group(function () {
        Route::prefix('payment-reminders')->name('payment-reminders.')->group(function () {
            Route::get('/', [PaymentReminderController::class, 'index'])->name('index');
            Route::get('/dashboard', [PaymentReminderController::class, 'dashboard'])->name('dashboard');

            Route::get('/create', [PaymentReminderController::class, 'create'])->name('create');
            Route::post('/', [PaymentReminderController::class, 'store'])->name('store');
            Route::get('/{reminder}', [PaymentReminderController::class, 'show'])
                ->name('show')
                ->where('reminder', '[0-9]+');
            Route::get('/{reminder}/edit', [PaymentReminderController::class, 'edit'])
                ->name('edit')
                ->where('reminder', '[0-9]+');
            Route::put('/{reminder}', [PaymentReminderController::class, 'update'])
                ->name('update')
                ->where('reminder', '[0-9]+');
            Route::delete('/{reminder}', [PaymentReminderController::class, 'destroy'])
                ->name('destroy')
                ->where('reminder', '[0-9]+');

            // Actions
            Route::post('/test', [PaymentReminderController::class, 'sendTestReminder'])->name('test');
            Route::post('/{paymentReminder}/queue', [PaymentReminderController::class, 'queueReminder'])
                ->name('queue')
                ->where('paymentReminder', '[0-9]+');
            Route::post('/{paymentReminder}/send', [PaymentReminderController::class, 'send'])
                ->name('send')
                ->where('paymentReminder', '[0-9]+');
            Route::post('/{paymentReminder}/cancel', [PaymentReminderController::class, 'cancel'])
                ->name('cancel')
                ->where('paymentReminder', '[0-9]+');
            Route::post('/{paymentReminder}/reschedule', [PaymentReminderController::class, 'reschedule'])
                ->name('reschedule')
                ->where('paymentReminder', '[0-9]+');

            // Category & Student Specific
            Route::post('/send-category-reminders/{feeCategory}', [PaymentReminderController::class, 'sendCategoryReminders'])
                ->name('send-category-reminders')
                ->where('feeCategory', '[0-9]+');
            Route::post('/send-student-reminder/{student}', [PaymentReminderController::class, 'sendStudentReminder'])
                ->name('send-student-reminder')
                ->where('student', '[0-9]+');

            // Bulk operations
            Route::post('/bulk/action', [PaymentReminderController::class, 'bulkAction'])->name('bulk.action');
            Route::post('/bulk/send', [PaymentReminderController::class, 'bulkSend'])->name('bulk.send');
            Route::post('/bulk/cancel', [PaymentReminderController::class, 'bulkCancel'])->name('bulk.cancel');
            Route::post('/bulk/reschedule', [PaymentReminderController::class, 'bulkReschedule'])->name('bulk.reschedule');

            // Export and reporting
            Route::get('/export/reminders', [PaymentReminderController::class, 'export'])->name('export');
            Route::get('/reports/summary', [PaymentReminderController::class, 'summaryReport'])->name('reports.summary');
            Route::get('/reports/analytics', [PaymentReminderController::class, 'analytics'])->name('reports.analytics');
            
            // Settings
            Route::get('/settings', [PaymentReminderSettingsController::class, 'index'])->name('settings.index');
            Route::put('/settings', [PaymentReminderSettingsController::class, 'update'])->name('settings.update');

            // System operations
            Route::post('/process-pending', [PaymentReminderController::class, 'processPending'])->name('process-pending');
            Route::get('/health-check', [PaymentReminderController::class, 'healthCheck'])->name('health-check');
        });

        // Restore missing payment-defaulters route - Point to centralized analysis
        Route::get('payment-defaulters', [FeeCategoryAnalysisController::class, 'criticalDefaulters'])->name('payment-defaulters.index');
    });



    // ================================
    // TIMETABLE MANAGEMENT
    // ================================

    Route::middleware(['permission:manage timetable'])->group(function () {

        // MAIN TIMETABLE ROUTES (Specific routes FIRST)
        Route::get('timetable/hub', [TimetableController::class, 'hub'])->name('timetable.hub');
        Route::get('timetable/create', [TimetableController::class, 'create'])->name('timetable.create');
        Route::get('timetable/events', [TimetableController::class, 'events'])->name('timetable.events');
        Route::get('timetable/conflicts', [TimetableController::class, 'conflicts'])->name('timetable.conflicts');
        Route::get('timetable/today', [TimetableController::class, 'today'])->name('timetable.today');

        // POST/PUT routes
        Route::post('timetable', [TimetableController::class, 'store'])->name('timetable.store');
        Route::post('timetable/generate', [TimetableController::class, 'generate'])->name('timetable.generate');
        Route::post('timetable/generate-lab', [TimetableController::class, 'generateLab'])->name('timetable.generate-lab');
        Route::post('timetable/quick-schedule', [TimetableController::class, 'quickSchedule'])->name('timetable.quick-schedule');
        Route::post('timetable/move', [TimetableController::class, 'move'])->name('timetable.move');
        Route::post('timetable/bulk-delete', [TimetableController::class, 'bulkDelete'])->name('timetable.bulk-delete');
        Route::post('timetable/bulk-move', [TimetableController::class, 'bulkMove'])->name('timetable.bulk-move');
        Route::post('timetable/manual-update', [TimetableController::class, 'manualUpdate'])->name('timetable.manualUpdate');

        // Export & Reporting routes
        Route::get('timetable/hub/pdf', [TimetableController::class, 'exportPdf'])->name('timetable.hub.pdf');
        Route::get('timetable/pdf', [TimetableController::class, 'generatePdf'])->name('timetable.pdf');
        Route::get('timetable/export/{format}', [TimetableController::class, 'export'])->name('timetable.export');
        Route::get('timetable/reports/utilization', [TimetableController::class, 'utilizationReport'])->name('timetable.reports.utilization');
        Route::get('timetable/reports/conflicts', [TimetableController::class, 'conflictReport'])->name('timetable.reports.conflicts');

        // Parameterized routes LAST
        Route::get('timetable/{timetable}/edit', [TimetableController::class, 'edit'])
            ->name('timetable.edit')
            ->where('timetable', '[0-9]+');
        Route::put('timetable/{timetable}', [TimetableController::class, 'update'])
            ->name('timetable.update')
            ->where('timetable', '[0-9]+');
        Route::delete('timetable/{timetable}', [TimetableController::class, 'destroy'])
            ->name('timetable.destroy')
            ->where('timetable', '[0-9]+');
        Route::get('timetable/{timetable}', [TimetableController::class, 'show'])
            ->name('timetable.show')
            ->where('timetable', '[0-9]+');

        // Legacy route support
        Route::delete('timetable/{id}', [TimetableController::class, 'deleteClass'])
            ->name('timetable.delete')
            ->where('id', '[0-9]+');
        Route::delete('timetable/class/{id}', [TimetableController::class, 'deleteClass'])
            ->name('timetable.deleteClass')
            ->where('id', '[0-9]+');

        // Infrastructure Management
        Route::resource('classrooms', ClassroomController::class);
        Route::resource('holidays', HolidayController::class);
        Route::resource('events', EventController::class);
    });

    // Time Slots Management (separate group to avoid conflicts)
    Route::middleware(['permission:manage timetable'])->group(function () {
        Route::resource('time-slots', TimeSlotController::class);

        // Bulk generator routes
        Route::get('time-slots/generate/form', [TimeSlotController::class, 'showGenerateForm'])
            ->name('time-slots.generate.form');
        Route::post('time-slots/generate/bulk', [TimeSlotController::class, 'generateSlots'])
            ->name('time-slots.generate.bulk');

        // Enhanced functionality routes
        Route::post('time-slots/bulk-action', [TimeSlotController::class, 'bulkAction'])
            ->name('time-slots.bulk-action');
        Route::get('time-slots/export/csv', [TimeSlotController::class, 'export'])
            ->name('time-slots.export');
        Route::get('time-slots/api/dropdown', [TimeSlotController::class, 'getForDropdown'])
            ->name('time-slots.dropdown');
    });

    // --- Inventory Management ---
    Route::middleware(['permission:manage inventory'])->group(function () {
        Route::resource('asset-categories', AssetCategoryController::class);
        Route::resource('assets', AssetController::class);
        Route::resource('audits', AuditController::class)->only(['index', 'store', 'show']);
        Route::post('assets/import', [AssetController::class, 'import'])->name('assets.import.store');
        Route::get('assets/import/sample', [AssetController::class, 'downloadSample'])
            ->name('assets.import.sample')
            ->middleware('permission:manage inventory');
        Route::post('assets/bulk-destroy', [AssetController::class, 'bulkDestroy'])
            ->name('assets.bulkDestroy')
            ->middleware('permission:manage inventory');
    });

    // --- Document Management ---
    Route::middleware(['permission:manage documents'])->group(function () {
        Route::resource('id-card-templates', IdCardTemplateController::class);
        Route::get('id-card-generator', [IdCardController::class, 'show'])->name('id-cards.show');
        Route::resource('certificate-templates', CertificateTemplateController::class);
        Route::get('certificate-generator', [CertificateGeneratorController::class, 'showForm'])->name('certificate.generator.show');
        Route::post('certificate-generator/generate', [CertificateGeneratorController::class, 'generate'])->name('certificate.generator.generate');
        Route::get('certificate-generator/bulk', [CertificateGeneratorController::class, 'showBulkForm'])->name('certificate-generator.bulk');
        Route::post('certificate-generator/bulk', [CertificateGeneratorController::class, 'bulkGenerate']);
    });

    // --- Reports ---
    Route::middleware(['permission:manage reports'])->group(function () {
        Route::get('reports/attendance', [AttendanceReportController::class, 'index'])->name('reports.attendance.index');
        Route::get('reports/attendance/export', [AttendanceReportController::class, 'export'])->name('reports.attendance.export');
        Route::get('reports/financial', [FinancialReportController::class, 'show'])->name('reports.financial.show');
        Route::get('reports/assets', [AssetReportController::class, 'index'])->name('reports.assets.index');
        Route::get('reports/admissions', [AdmissionReportController::class, 'index'])->name('reports.admissions.index');
        Route::get('reports/referrals', [ReferralReportController::class, 'index'])->name('reports.referrals.index');
        Route::get('reports/age', [AgeReportController::class, 'index'])->name('reports.age.index');
        Route::get('reports/address', [AddressReportController::class, 'index'])->name('reports.address.index');
        Route::get('reports/address/export', [AddressReportController::class, 'export'])->name('reports.address.export');
        Route::get('reports/students', [\App\Http\Controllers\Admin\StudentReportController::class, 'index'])->name('reports.students.index');
        Route::get('reports/students/export', [\App\Http\Controllers\Admin\StudentReportController::class, 'export'])->name('reports.students.export');
        Route::post('reports/referrals/{student}/mark-commission-paid', [ReferralReportController::class, 'markCommissionPaid'])
            ->name('reports.referrals.mark-commission-paid')
            ->where('student', '[0-9]+');
        Route::get('reports/certificates', [App\Http\Controllers\Admin\CertificateReportController::class, 'index'])->name('reports.certificates.index');
        Route::post('reports/certificates/{student}/update-status', [App\Http\Controllers\Admin\CertificateReportController::class, 'updateStatus'])->name('reports.certificates.update-status');
    });

    // Advanced Student Analytics (Accessible via view reports or view analytics)
    Route::middleware(['role_or_permission:super-admin|view reports|view analytics|manage reports'])->prefix('analytics')->name('analytics.')->group(function () {
        Route::prefix('student')->name('student.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\StudentAnalyticsController::class, 'index'])->name('index');
            Route::get('/lifecycle', [App\Http\Controllers\Admin\StudentAnalyticsController::class, 'lifecycle'])->name('lifecycle');
            Route::get('/engagement', [App\Http\Controllers\Admin\StudentAnalyticsController::class, 'engagement'])->name('engagement');
        });
    });

    // --- System & Settings ---
    Route::middleware(['permission:manage settings'])->group(function () {
        // User & Access Control
        Route::patch('users/{user}/status', [UserController::class, 'updateStatus'])
            ->name('users.update-status')
            ->middleware('permission:manage users');
        Route::resource('users', UserController::class)->middleware('permission:manage users');
        Route::get('users/export', [UserController::class, 'export'])->name('users.export');
        Route::post('users/bulk-actions', [UserController::class, 'bulkActions'])->name('users.bulk-actions');
        Route::post('users/bulk-destroy', [UserController::class, 'bulkDestroy'])->name('users.bulk-destroy');

        Route::resource('roles', RoleController::class)->middleware('permission:manage roles');
        Route::resource('permissions', PermissionController::class)->middleware('permission:manage permissions');
        Route::get('permission-management', [PermissionManagementController::class, 'index'])->name('permission-management.index')->middleware('permission:manage permissions');

        // Permission management routes
        Route::post('permissions/sync', [PermissionController::class, 'sync'])->name('permissions.sync');
        Route::get('permissions/analytics', [PermissionController::class, 'analytics'])->name('permissions.analytics');

        // General Settings
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index')->middleware('permission:manage settings');
        Route::post('/settings/update', [SettingController::class, 'update'])
            ->name('settings.update')
            ->middleware('permission:manage settings');

        Route::get('settings/export', [SettingController::class, 'export'])->name('settings.export')->middleware('permission:manage settings');
        Route::post('/settings/import', [SettingController::class, 'import'])->name('settings.import')->middleware('permission:manage settings');
        Route::get('/settings/system-info', [SettingController::class, 'systemInfo'])->name('settings.system-info')->middleware('permission:manage settings');
        Route::match(['get', 'post'], '/settings/health-check', [SettingController::class, 'healthCheck'])->name('settings.health-check')->middleware('permission:manage settings');
        Route::post('/settings/test-email', [SettingController::class, 'testEmail'])->name('settings.test-email')->middleware('permission:manage settings');
        Route::post('/settings/clear-cache', [SettingController::class, 'clearCache'])->name('settings.clear-cache')->middleware('permission:manage settings');
        Route::post('/settings/reset-defaults', [SettingController::class, 'resetDefaults'])->name('settings.reset-defaults')->middleware('permission:manage settings');
        Route::post('/settings/toggle-maintenance', [SettingController::class, 'toggleMaintenance'])->name('settings.toggle-maintenance')->middleware('permission:manage settings');
        Route::post('/settings/seed-defaults', [SettingController::class, 'seedDefaults'])->name('settings.seed-defaults')->middleware('permission:manage settings');
        Route::post('/settings/optimize', [SettingController::class, 'optimizeDatabase'])->name('settings.optimize')->middleware('permission:manage settings');
        Route::post('/settings/create-backup', [SettingController::class, 'createBackup'])->name('settings.create-backup')->middleware('permission:manage settings');

        // Notification Settings
        Route::get('notifications/settings', [NotificationSettingsController::class, 'index'])->name('notifications.settings')->middleware('permission:manage settings');
        Route::post('notifications/settings', [NotificationSettingsController::class, 'update'])->name('notifications.settings.update')->middleware('permission:manage settings');

        // Backups & System Health
        Route::get('system/health', [SystemHealthController::class, 'performHealthCheck'])->name('system.health')->middleware('permission:manage settings');

        Route::prefix('backups')->name('backups.')->middleware('permission:manage settings')->group(function () {
            Route::get('/', [BackupController::class, 'index'])->name('index');
            Route::post('/', [BackupController::class, 'store'])->name('store');
            Route::get('/create', [BackupController::class, 'index'])->name('create');
            Route::delete('/{id}', [BackupController::class, 'destroy'])->name('destroy');
            Route::get('/download/{fileName}', [BackupController::class, 'download'])->name('download');

            // Manual Backup / Actions
            Route::post('/manual', [BackupController::class, 'createManualBackup'])->name('manual');
            Route::post('/test', [BackupController::class, 'createManualBackup'])->name('test');
            Route::post('/cleanup', [BackupController::class, 'cleanupBackups'])->name('cleanup');
            Route::put('/settings', [BackupController::class, 'updateSettings'])->name('settings.update');

            // Restore Routes
            Route::post('/restore/database', [BackupController::class, 'restoreDatabase'])->name('restore.database');
            Route::post('/restore/settings', [BackupController::class, 'restoreSettings'])->name('restore.settings');

            // Google Drive
            Route::post('/gdrive/authorize', [BackupController::class, 'authorizeGoogleDrive'])->name('gdrive.authorize');
            Route::get('/gdrive/callback', [BackupController::class, 'handleGoogleDriveCallback'])->name('gdrive.callback');
            Route::get('/gdrive/test', [BackupController::class, 'testGoogleDriveConnection'])->name('gdrive.test');
            Route::get('/gdrive/list', [BackupController::class, 'listGoogleDriveBackups'])->name('gdrive.list');
        });

        // API & Webhooks
        Route::get('api-tokens/usage', [ApiTokenController::class, 'usage'])->name('api-tokens.usage');
        Route::get('api-tokens/export', [ApiTokenController::class, 'export'])->name('api-tokens.export');
        Route::resource('api-tokens', ApiTokenController::class)->middleware('permission:manage api tokens');
        Route::get('api-tokens/{token}/test', [ApiTokenController::class, 'test'])
            ->name('api-tokens.test')
            ->where('token', '[0-9]+');
        Route::post('api-tokens/{token}/regenerate', [ApiTokenController::class, 'regenerate'])
            ->name('api-tokens.regenerate')
            ->where('token', '[0-9]+');
        Route::delete('api-tokens/cleanup-expired', [ApiTokenController::class, 'cleanupExpired'])->name('api-tokens.cleanup-expired');
        Route::post('api-tokens/bulk-action', [ApiTokenController::class, 'bulkAction'])->name('api-tokens.bulk-action');
        Route::delete('api-tokens/users/{user}/revoke-all', [ApiTokenController::class, 'revokeUserTokens'])
            ->name('api-tokens.revoke-user-tokens')
            ->where('user', '[0-9]+');
        Route::delete('api-tokens/cleanup', [ApiTokenController::class, 'cleanupExpired'])->name('api-tokens.cleanup');

        Route::get('api-documentation', fn () => view('admin.api_documentation.index'))->name('api-documentation.index');
        Route::get('api-docs/json', [App\Http\Controllers\Api\ApiDocumentationController::class, 'json'])->name('api-documentation.json');

        Route::prefix('webhooks')->name('webhooks.')->group(function () {
            Route::get('/', [WebhookController::class, 'index'])->name('index');
            Route::get('/create', [WebhookController::class, 'create'])->name('create');
            Route::post('/', [WebhookController::class, 'store'])->name('store');
            Route::get('/{webhook}', [WebhookController::class, 'show'])
                ->name('show')
                ->where('webhook', '[0-9]+');
            Route::get('/{webhook}/edit', [WebhookController::class, 'edit'])
                ->name('edit')
                ->where('webhook', '[0-9]+');
            Route::put('/{webhook}', [WebhookController::class, 'update'])
                ->name('update')
                ->where('webhook', '[0-9]+');
            Route::delete('/{webhook}', [WebhookController::class, 'destroy'])
                ->name('destroy')
                ->where('webhook', '[0-9]+');
            Route::post('/{webhook}/test', [WebhookController::class, 'test'])
                ->name('test')
                ->where('webhook', '[0-9]+');
            Route::get('/{webhook}/logs', [WebhookController::class, 'showLogs'])
                ->name('logs')
                ->where('webhook', '[0-9]+');
            Route::post('/{webhook}/toggle', [WebhookController::class, 'toggle'])
                ->name('toggle')
                ->where('webhook', '[0-9]+');

            Route::post('/logs/{call}/replay', [WebhookController::class, 'replay'])
                ->name('logs.replay')
                ->where('call', '[0-9]+');

            Route::post('/admin/webhooks/test-daily-summary', [WebhookController::class, 'testDailySummary'])
                ->name('admin.webhooks.test-daily-summary');

            Route::post('/admin/webhooks/send-daily-summary', [WebhookController::class, 'sendDailySummary'])
                ->name('admin.webhooks.send-daily-summary');

        });

        // Dynamic Inbound Webhooks
        Route::resource('inbound-webhooks', App\Http\Controllers\Admin\InboundWebhookController::class);
        Route::get('inbound-webhooks/{inboundWebhook}/logs', [App\Http\Controllers\Admin\InboundWebhookController::class, 'logs'])->name('inbound-webhooks.logs');
        Route::post('inbound-webhooks/{inboundWebhook}/mapping', [App\Http\Controllers\Admin\InboundWebhookController::class, 'updateMapping'])->name('inbound-webhooks.update-mapping');
        Route::post('inbound-webhooks/{inboundWebhook}/toggle', [App\Http\Controllers\Admin\InboundWebhookController::class, 'toggle'])->name('inbound-webhooks.toggle');
        Route::post('inbound-webhooks/bulk-action', [App\Http\Controllers\Admin\InboundWebhookController::class, 'bulkAction'])->name('inbound-webhooks.bulk-action');

        // Activity Log
        // Activity Log - REMOVE the 'admin.' prefix from ->name()
        Route::get('activity-log', [ActivityLogController::class, 'index'])->name('activity-log.index');
        Route::delete('activity-log/cleanup', [ActivityLogController::class, 'destroy'])->name('activity-log.cleanup');
    });

    // Debug Subject-Faculty Route
    Route::get('/debug/subject-faculty/{subject}', function (\App\Models\Subject $subject) {
        $assigned = $subject->users;
        $available = \App\Models\User::role('staff')->get();

        return response()->json([
            'subject' => $subject->name,
            'assigned_count' => $assigned->count(),
            'assigned' => $assigned,
            'available_count' => $available->count(),
            'available' => $available,
        ]);
    })->where('subject', '[0-9]+');

    // Webhook Status Route (Admin/Super-Admin Only)
    Route::middleware(['auth', 'role:admin|super-admin'])->get('/webhooks/status', function () {
        try {
            $status = [
                'database_tables' => [
                    'webhooks' => Schema::hasTable('webhooks'),
                    'webhook_calls' => Schema::hasTable('webhook_calls'),
                ],
                'models' => [
                    'webhook_model' => class_exists('\App\Models\Webhook'),
                    'webhook_call_model' => class_exists('\App\Models\WebhookCall'),
                ],
                'listeners' => [
                    'universal_webhook_listener' => class_exists('\App\Listeners\UniversalWebhookListener'),
                ],
                'routes' => [
                    'admin_webhooks_index' => app('router')->getRoutes()->getByName('admin.webhooks.index') !== null,
                    'admin_webhooks_test' => app('router')->getRoutes()->getByName('admin.webhooks.test') !== null,
                ],
            ];

            if (class_exists('\App\Models\Webhook') && Schema::hasTable('webhooks')) {
                $webhookStats = [
                    'total_webhooks' => \App\Models\Webhook::count(),
                    'active_webhooks' => \App\Models\Webhook::where('is_active', true)->count(),
                    'recent_calls_24h' => \App\Models\WebhookCall::where('created_at', '>=', now()->subDay())->count(),
                    'successful_calls_24h' => \App\Models\WebhookCall::where('created_at', '>=', now()->subDay())->where('success', true)->count(),
                    'failed_webhooks' => \App\Models\Webhook::where('consecutive_failures', '>=', 3)->count(),
                ];
                $status['statistics'] = $webhookStats;
            }

            $allGood = true;
            foreach ($status as $category => $checks) {
                if ($category !== 'statistics') {
                    foreach ($checks as $check => $result) {
                        if (! $result) {
                            $allGood = false;
                            break 2;
                        }
                    }
                }
            }

            $status['overall_health'] = $allGood ? 'healthy' : 'issues_detected';

            return response()->json([
                'webhook_system_status' => $status,
                'health' => $allGood ? '✅ Healthy' : '❌ Issues Detected',
                'timestamp' => now()->toIso8601String(),
            ], 200, [], JSON_PRETTY_PRINT);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to check webhook status',
                'message' => $e->getMessage(),
                'health' => '❌ Error',
            ], 500);
        }
    })->middleware(['auth', 'role:super-admin']);
});

// Payment Edit Routes (with admin prefix in route names to match your views)
Route::middleware(['auth', 'permission:edit payments'])->group(function () {
    Route::get('admin/payments/{payment}/edit', [PaymentEditController::class, 'edit'])
        ->name('admin.payment-edit.edit')
        ->where('payment', '[0-9]+');

    Route::put('admin/payments/{payment}/update', [PaymentEditController::class, 'update'])
        ->name('admin.payment-edit.update')
        ->where('payment', '[0-9]+');
});

Route::middleware(['auth', 'permission:view payment history'])->group(function () {
    Route::get('admin/payments/{payment}/history', [PaymentEditController::class, 'history'])
        ->name('admin.payment-edit.history')
        ->where('payment', '[0-9]+');
});

// Payment revert routes (require additional permission)
Route::middleware(['auth', 'permission:revert payments'])->group(function () {
    Route::post('admin/payments/{payment}/revert', [PaymentEditController::class, 'revert'])
        ->name('admin.payment-edit.revert')
        ->where('payment', '[0-9]+');
});

// Alternative routes without admin prefix (for compatibility)
Route::middleware(['auth', 'permission:edit payments'])->group(function () {
    Route::get('payments/{payment}/edit', [PaymentEditController::class, 'edit'])
        ->name('payment-edit.edit')
        ->where('payment', '[0-9]+');

    Route::put('payments/{payment}/update', [PaymentEditController::class, 'update'])
        ->name('payment-edit.update')
        ->where('payment', '[0-9]+');
});

Route::middleware(['auth', 'permission:view payment history'])->group(function () {
    Route::get('payments/{payment}/history', [PaymentEditController::class, 'history'])
        ->name('payment-edit.history')
        ->where('payment', '[0-9]+');
});

Route::middleware(['auth', 'permission:revert payments'])->group(function () {
    Route::post('payments/{payment}/revert', [PaymentEditController::class, 'revert'])
        ->name('payment-edit.revert')
        ->where('payment', '[0-9]+');
});

Route::prefix('admin/attendance')->name('admin.attendance.')->middleware(['auth', 'role:super-admin|college-admin|staff'])->group(function () {

    // AJAX endpoints for live updates (add these to your existing attendance routes)
    Route::get('/dashboard/absent-students', [AttendanceSettingsController::class, 'getAbsentStudentsAjax'])
        ->name('dashboard.absent.ajax');

    Route::get('/dashboard/recent-activity', [AttendanceSettingsController::class, 'getRecentActivityAjax'])
        ->name('dashboard.activity.ajax');

    Route::get('/dashboard/stats', [AttendanceSettingsController::class, 'getTodayStatsAjax'])
        ->name('dashboard.stats.ajax');

    // Quick actions for marking attendance
    Route::post('/dashboard/mark-present', [AttendanceSettingsController::class, 'markStudentPresent'])
        ->name('dashboard.mark.present');

    Route::post('/dashboard/bulk-mark-present', [AttendanceSettingsController::class, 'bulkMarkPresent'])
        ->name('dashboard.bulk.mark.present');
});

/*
|--------------------------------------------------------------------------
| ATTENDANCE MANAGEMENT ROUTES
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin|super-admin'])->group(function () {

    Route::middleware(['permission:manage attendance'])->group(function () {

        // Daily Attendance Routes
        Route::get('daily-attendance', [DailyAttendanceController::class, 'index'])->name('daily-attendance.index');
        Route::get('daily-attendance/create', [DailyAttendanceController::class, 'create'])->name('daily-attendance.create');
        Route::post('daily-attendance', [DailyAttendanceController::class, 'store'])->name('daily-attendance.store');
        Route::get('daily-attendance/show', [DailyAttendanceController::class, 'show'])->name('daily-attendance.show');
        Route::get('daily-attendance/{id}/edit', [DailyAttendanceController::class, 'edit'])
            ->name('daily-attendance.edit')
            ->where('id', '[0-9]+');
        Route::put('daily-attendance/{id}', [DailyAttendanceController::class, 'update'])
            ->name('daily-attendance.update')
            ->where('id', '[0-9]+');
        Route::delete('daily-attendance/{id}', [DailyAttendanceController::class, 'destroy'])
            ->name('daily-attendance.destroy')
            ->where('id', '[0-9]+');
        Route::get('daily-attendance/batches/{batch}/students', [DailyAttendanceController::class, 'getBatchStudents'])
            ->name('daily-attendance.batch-students')
            ->where('batch', '[0-9]+');

        // Student Month & Bulk Attendance Routes
        Route::get('daily-attendance/student-month/{student}', [DailyAttendanceController::class, 'getStudentMonthAttendance'])
            ->name('daily-attendance.student-month')
            ->where('student', '[0-9]+');
        Route::post('daily-attendance/student-bulk-store', [DailyAttendanceController::class, 'storeStudentBulkAttendance'])
            ->name('daily-attendance.student-bulk-store');

        // Attendance Dashboard Routes
        Route::get('attendance/dashboard', [AttendanceSettingsController::class, 'dashboard'])->name('attendance.dashboard');
        Route::get('attendance/dashboard/today', [AttendanceSettingsController::class, 'getTodayDashboard'])->name('attendance.dashboard.today');
        Route::get('attendance/dashboard/weekly', [AttendanceSettingsController::class, 'getWeeklyStats'])->name('attendance.dashboard.weekly');

        // Attendance Import Routes
        Route::get('attendance/import', [AttendanceImportController::class, 'show'])->name('attendance.import.show');
        Route::post('attendance/import', [AttendanceImportController::class, 'store'])->name('attendance.import.store');
        Route::get('attendance/import/sample', [AttendanceImportController::class, 'downloadSample'])->name('attendance.import.sample');

        // Export functionality
        Route::get('attendance/export/today', [AttendanceSettingsController::class, 'exportTodayAttendance'])
            ->name('attendance.export.today')
            ->middleware('permission:export attendance');

        // Testing endpoints
        Route::post('attendance/test-rules', [AttendanceSettingsController::class, 'testRules'])->name('attendance.test.rules');
        Route::post('/attendance/settings/test-sync', [AttendanceSettingsController::class, 'testSync'])->name('admin.attendance.test-sync');
        Route::post('/attendance/settings/trigger-sync', [AttendanceSettingsController::class, 'triggerManualSync'])->name('admin.attendance.trigger-sync');

    });

    Route::middleware(['permission:manage lab allocation'])->group(function () {
        // Lab Allocation Routes
        Route::get('lab-allocation', [LabAllocationController::class, 'index'])->name('lab-allocation.index');
        Route::post('lab-allocation/automate', [LabAllocationController::class, 'automate'])->name('lab-allocation.automate');

        // Group Management Routes
        Route::get('lab-allocation/group/{group}/manage', [LabAllocationController::class, 'manageGroup'])
            ->name('lab-allocation.group.manage')
            ->where('group', '[0-9]+');
        Route::post('lab-allocation/group/{group}/add', [LabAllocationController::class, 'addStudentToGroup'])
            ->name('lab-allocation.group.add')
            ->where('group', '[0-9]+');
        Route::delete('lab-allocation/group/{group}/remove/{student}', [LabAllocationController::class, 'removeStudentFromGroup'])
            ->name('lab-allocation.group.remove')
            ->where(['group' => '[0-9]+', 'student' => '[0-9]+']);

        // Export Routes for Lab Allocation
        Route::get('lab-allocation/pdf/{batch}', [LabAllocationController::class, 'generatePDF'])
            ->name('lab-allocation.pdf.batch')
            ->where('batch', '[0-9]+');
        Route::get('lab-allocation/pdf/students/{batch}', [LabAllocationController::class, 'generateStudentsPDF'])
            ->name('lab-allocation.students-pdf.batch')
            ->where('batch', '[0-9]+');
        Route::get('lab-allocation/excel/{batch}', [LabAllocationController::class, 'generateExcel'])
            ->name('lab-allocation.excel.batch')
            ->where('batch', '[0-9]+');
        Route::get('lab-allocation/pdf/all', [LabAllocationController::class, 'generateAllBatchesPDF'])
            ->name('lab-allocation.pdf.all');
        Route::get('lab-allocation/pdf/students/all', [LabAllocationController::class, 'generateAllBatchesStudentsPDF'])
            ->name('lab-allocation.students-pdf.all');
    });
});

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {

    // Attendance Settings Routes
    Route::prefix('attendance')->name('attendance.')->middleware(['role:super-admin|admin|college-admin|staff'])->group(function () {
        Route::get('/settings', [AttendanceSettingsController::class, 'index'])->name('settings');
        Route::post('/settings/update', [App\Http\Controllers\Admin\AttendanceSettingsController::class, 'update'])->name('settings.update');
        Route::get('/dashboard', [AttendanceSettingsController::class, 'dashboard'])->name('dashboard');

        // ETimeOffice Integration Routes
        Route::prefix('settings/etimeoffice')->name('settings.etimeoffice.')->group(function () {
            // Basic CRUD
            Route::get('/', [AttendanceSettingsController::class, 'getETimeOfficeSettings'])->name('get');
            Route::post('/', [AttendanceSettingsController::class, 'updateETimeOfficeSettings'])->name('update');

            // Connection and Testing
            Route::post('/test-connection', [AttendanceSettingsController::class, 'testETimeOfficeConnection'])->name('test');
            Route::post('/test-auth-formats', [AttendanceSettingsController::class, 'testAuthFormats'])->name('test-auth-formats');
            Route::get('/test-data-format', [AttendanceSettingsController::class, 'testETimeOfficeDataFormat'])->name('test-data-format');

            // FIXED ROUTES - These were causing 404 errors
            Route::get('/validate-config', [AttendanceSettingsController::class, 'validateConfiguration'])->name('validate-config');
            Route::get('/setup-recommendations', [AttendanceSettingsController::class, 'getSetupRecommendations'])->name('setup-recommendations');

            // Data Pulling
            Route::post('/pull-data', [AttendanceSettingsController::class, 'pullETimeOfficeData'])->name('pull-data');
            Route::post('/sync', [AttendanceSettingsController::class, 'triggerManualSync'])->name('sync');

            // Status and Monitoring - FIXED ROUTES
            Route::get('/sync-status', [AttendanceSettingsController::class, 'getSyncStatus'])->name('sync-status');
            Route::get('/sync-history', [AttendanceSettingsController::class, 'getSyncHistory'])->name('sync-history');
            Route::get('/stats', [AttendanceSettingsController::class, 'getBiometricStats'])->name('biometric.stats');

        });

        // Export Routes
        Route::prefix('export')->name('export.')->group(function () {
            Route::get('/today', [AttendanceSettingsController::class, 'exportTodayAttendance'])->name('today');
            Route::post('/custom', [AttendanceSettingsController::class, 'exportAttendanceData'])->name('custom');
            Route::get('/sync-logs', [AttendanceSettingsController::class, 'exportSyncLogs'])->name('sync-logs');
        });

        // Dashboard Data Routes
        Route::get('/dashboard/today', [AttendanceSettingsController::class, 'getTodayDashboard'])->name('dashboard.today');
        Route::get('/dashboard/weekly', [AttendanceSettingsController::class, 'getWeeklyStats'])->name('dashboard.weekly');

        // Testing and Rules
        Route::post('/test-rules', [AttendanceSettingsController::class, 'testRules'])->name('test-rules');

    });
});

// Main Attendance Routes

Route::get('students/biometric-mapping-test', [StudentController::class, 'biometricMapping'])
    ->name('students.biometric-mapping.test')
    ->middleware(['auth', 'permission:view backend']);

/*
|--------------------------------------------------------------------------
| Notification Routes
|--------------------------------------------------------------------------
*/

// Authenticated User Notification Routes (Common for All Users)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [AdminNotificationController::class, 'index'])->name('index');
        Route::get('/{notification}', [AdminNotificationController::class, 'show'])
            ->name('show')
            ->where('notification', '[0-9]+');
        Route::get('/unread-count', [AdminNotificationController::class, 'getUnreadCount'])->name('unread-count');
        Route::get('/recent', [AdminNotificationController::class, 'recent'])->name('recent');
        Route::post('/{notification}/read', [AdminNotificationController::class, 'markAsRead'])
            ->name('mark-as-read')
            ->where('notification', '[0-9]+');
        Route::post('/mark-all-read', [AdminNotificationController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::get('/preferences', [AdminNotificationController::class, 'preferences'])->name('preferences');
        Route::post('/preferences', [AdminNotificationController::class, 'updatePreferences'])->name('preferences.update');
    });
});

// Admin Notification Management Routes
Route::prefix('admin/notifications')->name('admin.notifications.')->middleware(['auth', 'permission:view backend'])->group(function () {

    // Notification Dashboard & Viewing
    Route::get('/', [NotificationManagementController::class, 'dashboard'])->name('dashboard');
    Route::get('/list', [NotificationManagementController::class, 'index'])->name('index');
    Route::get('/{notification}', [NotificationManagementController::class, 'show'])
        ->name('show')
        ->where('notification', '[0-9]+');

    Route::get('/admin-unread-count', function () {
        $count = \App\Models\SystemNotification::getUnreadCountForUser(auth()->id());

        return response()->json(['count' => $count]);
    })->name('admin-unread-count');

    // [FIXED ROUTE]
    Route::post('/test', [NotificationManagementController::class, 'sendTestNotification'])
        ->name('test')
        ->middleware('permission:manage settings');

    // Attendance settings test route
    Route::post('attendance/settings/test-auth-formats', [AttendanceSettingsController::class, 'testAuthFormats'])
        ->name('attendance.settings.test-auth-formats');

    // Notification Settings
    Route::middleware(['permission:manage settings'])->group(function () {
        Route::get('/settings', [NotificationSettingsController::class, 'index'])->name('settings');
        Route::post('notifications/settings/update', [NotificationSettingsController::class, 'update'])
            ->name('notifications.settings.update')
            ->middleware('permission:manage settings');
        Route::post('/settings/preferences', [NotificationSettingsController::class, 'updateUserPreferences'])->name('settings.preferences');
        Route::post('/settings/reset', [NotificationSettingsController::class, 'resetPreferences'])->name('settings.reset');
    });
});
/*
|--------------------------------------------------------------------------
|                            ROLE-SPECIFIC WEB ROUTES
|--------------------------------------------------------------------------
*/

// Faculty Routes
Route::prefix('faculty')->name('faculty.')->middleware(['auth', 'role:faculty|staff'])->group(function () {
    Route::get('/dashboard', [FacultyDashboardController::class, 'index'])->name('dashboard.main');

    Route::get('my-leave', [LeaveApplicationController::class, 'facultyIndex'])->name('my-leave.index');
    Route::post('my-leave', [LeaveApplicationController::class, 'store'])->name('my-leave.store');

});

// Student Routes
Route::prefix('student')->name('student.')->middleware(['auth', 'role:student'])->group(function () {
    Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard.main');
    Route::get('/my-attendance', function () {
        $student = auth()->user()->student;

    })->name('my.analytics');
    Route::get('/my-report', function () {
        $student = auth()->user()->student;

    })->name('my.report');
});

/*
|--------------------------------------------------------------------------
|                                 API ROUTES
|--------------------------------------------------------------------------
*/
Route::prefix('api')->name('api.')->group(function () {

    // V1 Public API
    Route::prefix('v1')->name('v1.')->middleware(['auth:sanctum'])->group(function () {
        Route::get('/test', [TestController::class, 'index'])->name('test');
        Route::get('/profile', [TestController::class, 'profile'])->name('profile');

        // Student API
        Route::get('/students/search', [ApiStudentController::class, 'search'])->name('students.search');
        Route::get('/students/{student}', [ApiStudentController::class, 'show'])
            ->name('students.show')
            ->where('student', '[0-9]+');
        Route::get('/students/{student}/dashboard', [StudentApiController::class, 'dashboard'])
            ->name('students.dashboard')
            ->where('student', '[0-9]+');
        Route::get('students/export', [StudentController::class, 'export'])->name('students.export');

        // Attendance API

        // Admin-only API routes
        Route::prefix('admin')->name('admin.')->middleware(['permission:manage students'])->group(function () {
            Route::post('/students', [StudentApiController::class, 'store'])->name('students.store');
            Route::get('/batches', [BatchController::class, 'apiGetStudents'])->name('batches.index');
        });

    });

    // Dashboard API
    Route::get('/dashboard/stats', [ApiDashboardController::class, 'stats'])->name('dashboard.stats');
    Route::post('/dashboard/metrics', [ApiDashboardController::class, 'storeMetrics'])
        ->name('dashboard.metrics')
        ->middleware(['auth']);

    // Admin Panel AJAX API
    Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
        Route::get('permissions/search', function (Request $request) {
            $query = $request->get('q', '');
            $permissions = Permission::where('name', 'like', "%{$query}%")->limit(20)->get();

            return response()->json(['success' => true, 'permissions' => $permissions]);
        })->name('permissions.search');

        Route::get('roles/search', function (Request $request) {
            $query = $request->get('q', '');
            $roles = Role::where('name', 'like', "%{$query}%")->limit(20)->get();

            return response()->json(['success' => true, 'roles' => $roles]);
        })->name('roles.search');
    });

    // Batch Students API
    Route::get('batches/{batch}/students', function (\App\Models\Batch $batch, Request $request) {
        try {
            $date = $request->get('date', now()->format('Y-m-d'));

            // Load students with their attendance for the given date
            $students = $batch->students()
                ->select('id', 'name', 'enrollment_number', 'email')
                ->orderBy('name')
                ->get();

            // Get existing attendance for this date and batch
            $existingAttendance = \App\Models\Attendance\Attendance::where('batch_id', $batch->id)
                ->whereDate('attendance_date', $date)
                ->pluck('status', 'student_id')
                ->toArray();

            return response()->json([
                'success' => true,
                'data' => [
                    'students' => $students,
                    'existing_attendance' => $existingAttendance,
                    'batch' => $batch,
                    'date' => $date,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load students: '.$e->getMessage(),
            ], 500);
        }
    })->name('admin.batches.students')->middleware(['auth', 'permission:manage attendance'])->where('batch', '[0-9]+');

    // Legacy API (Redirects)
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/test', fn () => redirect()->route('api.v1.test'));
        Route::get('/students/search', fn () => redirect()->route('api.v1.students.search'));
    });

    // Server time for real-time sync
    Route::get('/server-time', [App\Http\Controllers\Api\CollegeAdminDashboardController::class, 'getServerTime'])
        ->name('server-time');

    // Dashboard data endpoints
    Route::get('/college-admin/academic-metrics', [App\Http\Controllers\Api\CollegeAdminDashboardController::class, 'academicMetrics'])
        ->name('college-admin.academic-metrics');

    Route::get('/college-admin/enrollment-trends', [App\Http\Controllers\Api\CollegeAdminDashboardController::class, 'enrollmentTrends'])
        ->name('college-admin.enrollment-trends');

    Route::get('/dashboard/my-payment-data', [App\Http\Controllers\Api\CollegeAdminDashboardController::class, 'getMyPaymentData'])
        ->name('dashboard.my-payment-data');

    Route::get('/dashboard/my-activities', [App\Http\Controllers\Api\CollegeAdminDashboardController::class, 'getMyActivitiesApi'])
        ->name('dashboard.my-activities');

    Route::get('/dashboard/attendance-data', [App\Http\Controllers\Api\CollegeAdminDashboardController::class, 'getAttendanceData'])
        ->name('dashboard.attendance-data');
});

// Payment History API
Route::middleware(['auth', 'permission:manage financials'])->group(function () {
    Route::get('api/students/{student}/payment-history', function (Student $student) {
        $paymentHistory = Payment::where('student_id', $student->id)
            ->with(['createdBy:id,name', 'componentItems.studentFee.feeCategory'])
            ->orderBy('payment_date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'count' => $paymentHistory->count(),
            'total_amount' => $paymentHistory->sum('amount'),
            'payments' => $paymentHistory->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'receipt_number' => $payment->receipt_number,
                    'amount' => $payment->amount,
                    'payment_date' => \Carbon\Carbon::parse($payment->payment_date)->format('d M Y'),
                    'payment_method' => $payment->payment_method,
                    'created_by' => $payment->createdBy ? $payment->createdBy->name : 'System',
                    'status' => $payment->status ?? 'completed',
                    'components' => $payment->componentItems->map(function ($item) {
                        return [
                            'fee_category' => $item->studentFee->feeCategory->name,
                            'amount_paid' => $item->amount_paid,
                        ];
                    }),
                ];
            }),
        ]);
    })->name('api.students.payment-history')->where('student', '[0-9]+');
});

// API Documentation Route
Route::get('api/documentation', function () {
    return view('admin.api_documentation.index');
})->name('api.documentation');

/*
|--------------------------------------------------------------------------
|                            DEVELOPMENT & TESTING ROUTES
|--------------------------------------------------------------------------
*/
if (app()->environment(['local', 'testing'])) {
    Route::prefix('dev')->name('dev.')->group(function () {
        // Test notification
        Route::post('test-notification', function () {
            return response()->json(['success' => true, 'message' => 'Test notification sent!']);
        })->middleware('auth')->name('test-notification');

        // Generate test data
        Route::post('/attendance/generate-test-data', function () {
            return response()->json(['success' => true, 'message' => 'Test data generated']);
        })->name('attendance.generate.test.data');
    });
}

// Test route conflict debugging route
Route::get('/test-route-conflict', function () {
    try {
        $request = \Illuminate\Http\Request::create('/attendance/analytics', 'GET');
        $route = \Route::getRoutes()->match($request);

        return response()->json([
            'url_tested' => '/attendance/analytics',
            'matched_route' => $route->uri(),
            'matched_action' => $route->getActionName(),
            'route_parameters' => $route->parameters(),
            'is_conflict' => $route->uri() === 'attendance/{attendance}' ? 'YES - CONFLICT!' : 'No conflict',
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()]);
    }
});
Route::get('/admin/test-direct-sync', [AttendanceSettingsController::class, 'testDirectSync']);
// Test lab generation route
Route::get('/test-lab-generation', [TimetableController::class, 'testLabGeneration']);

// Student Self-Service Portal Routes
Route::get('students/login', function () {
    return redirect()->away('https://uvchm.com');
});

Route::prefix('student')->name('student.')->group(function () {
    // Public/Guest
    Route::get('/login', [App\Http\Controllers\StudentPortalController::class, 'loginPage'])->name('login');
    Route::post('/authenticate', [App\Http\Controllers\StudentPortalController::class, 'authenticate'])->name('authenticate');

    // Auth Required
    Route::match(['get', 'post'], '/logout', [App\Http\Controllers\StudentPortalController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [App\Http\Controllers\StudentPortalController::class, 'dashboard'])->name('dashboard');
    Route::post('/request-update', [App\Http\Controllers\StudentPortalController::class, 'requestUpdate'])->name('request.update');

    // AJAX Data endpoints
    Route::get('/data/payments', [App\Http\Controllers\StudentPortalController::class, 'getPaymentData'])->name('data.payments');
    Route::get('/data/attendance', [App\Http\Controllers\StudentPortalController::class, 'getAttendanceData'])->name('data.attendance');
    Route::get('/refresh-csrf', function () {
        return response()->json(['token' => csrf_token()]);
    })->name('refresh-csrf');
});

// Admin Student Request Moderation Routes
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/student-requests', [App\Http\Controllers\Admin\StudentRequestController::class, 'index'])->name('student-requests.index');
    Route::post('/student-requests/{id}/action', [App\Http\Controllers\Admin\StudentRequestController::class, 'action'])->name('student-requests.action');
    // Preview Image Route
    Route::get('/student-requests/{id}/preview', [App\Http\Controllers\Admin\StudentRequestController::class, 'preview'])->name('student-requests.preview');

    // Student Portal Activity Logs
    Route::get('/student-portal-logs', [App\Http\Controllers\Admin\StudentPortalLogsController::class, 'index'])->name('student-portal-logs.index');
    Route::get('/student-portal-logs/dashboard', [App\Http\Controllers\Admin\StudentPortalLogsController::class, 'dashboard'])->name('student-portal-logs.dashboard');
    Route::get('/student-portal-logs/export', [App\Http\Controllers\Admin\StudentPortalLogsController::class, 'export'])->name('student-portal-logs.export');
    Route::get('/student-portal-logs/stats', [App\Http\Controllers\Admin\StudentPortalLogsController::class, 'getStats'])->name('student-portal-logs.stats');
    Route::get('/student-portal-logs/{id}', [App\Http\Controllers\Admin\StudentPortalLogsController::class, 'show'])->name('student-portal-logs.show')->where('id', '[0-9]+');
});

/*
|--------------------------------------------------------------------------
|                                 FALLBACK ROUTE
|--------------------------------------------------------------------------
*/
Route::fallback(function () {
    abort(404);
});

require __DIR__.'/auth.php';
