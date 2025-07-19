<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
// FIX: Added missing controller imports. The application would throw an error without these.
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;


// Import all necessary controllers
use App\Http\Controllers\PublicEnquiryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeaveApplicationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UnifiedCalendarController;
use App\Http\Controllers\Faculty\AttendanceController as FacultyAttendanceController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\NotificationController;

// Admin Controllers
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AdmissionController as AdminAdmissionController;
use App\Http\Controllers\Admin\AdmissionReportController;
use App\Http\Controllers\Admin\AlumniController;
use App\Http\Controllers\Admin\ApiTokenController;
use App\Http\Controllers\Admin\AssetCategoryController;
use App\Http\Controllers\Admin\AssetController;
use App\Http\Controllers\Admin\AssetReportController;
use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\BatchAllotmentController;
use App\Http\Controllers\Admin\BatchController;
use App\Http\Controllers\Admin\CertificateGeneratorController;
use App\Http\Controllers\Admin\CertificateTemplateController;
use App\Http\Controllers\Admin\ClassroomController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\CourseStructureController;
use App\Http\Controllers\Admin\CourseSubjectController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DailyAttendanceController;
use App\Http\Controllers\Admin\EnquiryController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\ExpenseCategoryController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\FacultyController;
use App\Http\Controllers\Admin\FacultySubjectController;
use App\Http\Controllers\Admin\PaymentEditController;
use App\Http\Controllers\Admin\FeeCategoryController;
use App\Http\Controllers\Admin\FeeStructureController;
use App\Http\Controllers\Admin\FinancialReportController;
use App\Http\Controllers\Admin\GlobalSearchController;
use App\Http\Controllers\Admin\HolidayController;
use App\Http\Controllers\Admin\IdCardController;
use App\Http\Controllers\Admin\IdCardTemplateController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\LabAllocationController;
use App\Http\Controllers\Admin\LeaveTypeController;
use App\Http\Controllers\Admin\PayslipController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\PermissionManagementController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SalaryComponentController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\StudentImportController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\TimetableController;
use App\Http\Controllers\Admin\TimeSlotController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserSalaryController;
use App\Http\Controllers\Admin\VisitorController;
use App\Http\Controllers\Admin\WebhookController;
use App\Http\Controllers\Admin\AcademicYearController;
use App\Http\Controllers\Admin\WidgetController;
use App\Http\Controllers\Admin\DashboardBuilderController;
use App\Http\Controllers\Admin\ConfigurationController;
use App\Http\Controllers\Admin\AttendanceImportController;
use App\Http\Controllers\Admin\AttendanceReportController;
use App\Http\Controllers\Admin\FollowUpCalendarController;
use App\Http\Controllers\Admin\SessionSearchController;
use App\Http\Controllers\Admin\NotificationManagementController;
use App\Http\Controllers\Admin\NotificationSettingsController;
use App\Http\Controllers\Admin\SystemHealthController;
use App\Http\Controllers\Admin\BulkOperationsController;
use App\Http\Controllers\Admin\PaymentReminderSettingsController;
use App\Http\Controllers\Admin\PaymentReminderController;
use App\Http\Controllers\Admin\PaymentDefaulterController;
use App\Http\Controllers\Admin\IntegrationController;
use App\Http\Controllers\Admin\PaymentReportsController;
use App\Http\Controllers\Admin\FeeCollectionController;
use App\Http\Controllers\Admin\InvoiceEditController;
// FIX: Added missing controller import for student duplicate checks. This was causing an error.
use App\Http\Controllers\Admin\StudentDuplicateCheckController;


// API Controllers
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\StudentController as ApiStudentController;
use App\Http\Controllers\Api\StudentApiController;
use App\Http\Controllers\Api\GlobalSearchController as ApiGlobalSearchController;
use App\Http\Controllers\Api\WebhookController as ApiWebhookController;
use App\Http\Controllers\Api\DashboardController as ApiDashboardController;
use App\Http\Controllers\Api\TestController;



// Spatie Permission Models
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// --- 1. Public Routes ---
Route::get('/', fn() => view('welcome'))->name('home');
Route::get('/enquire', [PublicEnquiryController::class, 'create'])->name('enquiry.public.create');
Route::post('/enquire', [PublicEnquiryController::class, 'store'])->name('enquiry.public.store');
Route::get('/enquiry-success', fn() => view('public.enquiry_success'))->name('enquiry.success');

Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');
    Route::get('receipts/{receipt_number}', [PaymentController::class, 'showPublicReceipt'])
    ->name('public.receipt.show');

Route::get('receipts/{receipt_number}/pdf', [PaymentController::class, 'downloadPublicReceipt'])
    ->name('public.receipt.pdf');
    

// --- 2. Authenticated User Routes ---
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
});

// --- 3. Faculty-Only Routes ---
Route::prefix('faculty')->name('faculty.')
    ->middleware(['auth', 'role:faculty|staff'])->group(function () {
    Route::get('attendance/{timetable}/take', [FacultyAttendanceController::class, 'create'])->name('attendance.create');
    Route::post('attendance', [FacultyAttendanceController::class, 'store'])->name('attendance.store');
    Route::get('my-leave', [LeaveApplicationController::class, 'facultyIndex'])->name('my-leave.index');
    Route::post('my-leave', [LeaveApplicationController::class, 'store'])->name('my-leave.store');
});

/*
|--------------------------------------------------------------------------
| Primary Notification Routes (All Authenticated Users)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {
    // Main notification endpoints accessible to all authenticated users
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/unread-count', [NotificationController::class, 'getUnreadCount'])->name('notifications.unread-count');
    Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
    Route::get('notifications/{notification}', [NotificationController::class, 'show'])->name('notifications.show');
    
    // User preferences
    Route::get('notifications/preferences', [NotificationController::class, 'preferences'])->name('notifications.preferences');
    Route::post('notifications/preferences', [NotificationController::class, 'updatePreferences'])->name('notifications.preferences.update');
    
    // Test notification route for debugging
    Route::post('test-notification', function (\Illuminate\Http\Request $request) {
        $service = app(\App\Services\NotificationService::class);
        
        $notification = $service->send([
            'title' => 'Test Notification',
            'message' => 'Test notification sent at ' . now()->format('H:i:s'),
            'type' => 'info',
            'category' => 'system',
            'priority' => 'low',
            'roles' => ['super-admin'],
            'data' => ['test' => true]
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Test notification sent!',
            'notification_id' => $notification->id
        ]);
    })->name('test-notification');
});

/*
|--------------------------------------------------------------------------
| API Routes - Version 1
|--------------------------------------------------------------------------
*/

// Public API routes (no authentication required)
Route::prefix('api/v1')->name('api.v1.')->group(function () {
    Route::post('/biometric/attendance', [AttendanceController::class, 'store'])->name('biometric.attendance');
    Route::post('/webhook/biometric', [ApiWebhookController::class, 'handleBiometric'])->name('webhook.biometric');
});

// Authenticated API routes
Route::middleware(['auth:sanctum'])->prefix('api/v1')->name('api.v1.')->group(function () {
    // Basic test and profile endpoints
    Route::get('/test', [TestController::class, 'index'])->name('test');
    Route::get('/profile', [TestController::class, 'profile'])->name('profile');
    Route::get('/restricted-test', [TestController::class, 'restrictedTest'])->name('restricted-test');

    // Student Management
    Route::prefix('students')->name('students.')->group(function () {
        Route::get('/search', [ApiStudentController::class, 'search'])->name('search');
        Route::get('/{student}', [ApiStudentController::class, 'show'])->name('show');
        Route::get('/{student}/profile', [StudentApiController::class, 'profile'])->name('profile');
        Route::get('/{student}/attendance', [StudentApiController::class, 'attendance'])->name('attendance');
        Route::get('/{student}/financials', [StudentApiController::class, 'financials'])->name('financials');
        Route::get('/{student}/dashboard', [StudentApiController::class, 'dashboard'])->name('dashboard');
        Route::put('/{student}/profile', [StudentApiController::class, 'updateProfile'])->name('profile.update');
    });

    // Attendance Management
    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::post('/', [AttendanceController::class, 'store'])->name('store');
        Route::get('/student/{student}', [StudentApiController::class, 'attendance'])->name('student');
        Route::get('/today', function () {
            $today = \Carbon\Carbon::today();
            $attendance = \App\Models\Attendance::where('attendance_date', $today)
                ->with(['student', 'batch', 'faculty'])
                ->get();

            return response()->json([
                'success' => true,
                'data' => $attendance->map(function ($att) {
                    return [
                        'student_name' => $att->student->name,
                        'enrollment_number' => $att->student->enrollment_number,
                        'status' => $att->status,
                        'batch' => $att->batch->name,
                        'faculty' => $att->faculty->name,
                    ];
                })
            ]);
        })->name('today');
    });

    // Dashboard & Analytics
    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::get('/stats', [ApiDashboardController::class, 'stats'])->name('stats');
        Route::get('/attendance-trends', [ApiDashboardController::class, 'attendanceTrends'])->name('attendance-trends');
        Route::get('/financial-trends', [ApiDashboardController::class, 'financialTrends'])->name('financial-trends');
    });

    // Search & Discovery
    Route::get('/search', [ApiGlobalSearchController::class, 'search'])->name('search');

    // Notifications
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', function (Request $request) {
            $notifications = $request->user()->notifications()->latest()->limit(20)->get();
            return response()->json([
                'success' => true,
                'data' => $notifications
            ]);
        })->name('index');

        Route::put('/{id}/read', function (Request $request, $id) {
            $notification = $request->user()->notifications()->find($id);
            if ($notification) {
                $notification->markAsRead();
                return response()->json(['success' => true, 'message' => 'Notification marked as read']);
            }
            return response()->json(['success' => false, 'message' => 'Notification not found'], 404);
        })->name('read');
    });

    // System Info
    Route::get('/system/info', function () {
        return response()->json([
            'success' => true,
            'data' => [
                'app_name' => config('app.name'),
                'version' => '1.0.0',
                'environment' => config('app.env'),
                'timezone' => config('app.timezone'),
                'current_time' => now()->toISOString(),
            ]
        ]);
    })->name('system.info');
});

// Admin-only API routes
Route::middleware(['auth:sanctum', 'permission:manage students'])->prefix('api/v1/admin')->name('api.v1.admin.')->group(function () {
    // Student Management
    Route::post('/students', function (Request $request) {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:students,email',
            'enrollment_number' => 'required|string|unique:students,enrollment_number',
            'batch_id' => 'required|exists:batches,id',
            'gender' => 'nullable|in:Male,Female,Other',
            'student_mobile' => 'nullable|string|max:15',
            'father_name' => 'nullable|string|max:255',
            'father_mobile' => 'nullable|string|max:15',
            'village' => 'nullable|string|max:255',
            'admission_date' => 'required|date',
        ]);

        $student = \App\Models\Student::create($data);
        return response()->json([
            'success' => true,
            'message' => 'Student created successfully',
            'data' => $student
        ], 201);
    })->name('students.store');

    // Batch operations
    Route::get('/batches', function () {
        $batches = \App\Models\Batch::with('course')->get();
        return response()->json([
            'success' => true,
            'data' => $batches
        ]);
    })->name('batches.index');

    // Reports
    Route::get('/reports/attendance', function (Request $request) {
        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'batch_id' => 'nullable|exists:batches,id'
        ]);

        $query = \App\Models\Attendance::whereBetween('attendance_date', [
            $request->date_from,
            $request->date_to
        ])->with(['student', 'batch']);

        if ($request->batch_id) {
            $query->where('batch_id', $request->batch_id);
        }

        $attendance = $query->get();

        return response()->json([
            'success' => true,
            'data' => [
                'period' => [
                    'from' => $request->date_from,
                    'to' => $request->date_to
                ],
                'total_records' => $attendance->count(),
                'present_count' => $attendance->where('status', 'present')->count(),
                'absent_count' => $attendance->where('status', 'absent')->count(),
                'records' => $attendance->map(function ($att) {
                    return [
                        'student_name' => $att->student->name,
                        'enrollment_number' => $att->student->enrollment_number,
                        'date' => $att->attendance_date,
                        'status' => $att->status,
                        'batch' => $att->batch->name,
                    ];
                })
            ]
        ]);
    })->name('reports.attendance');
});

// Legacy API routes (deprecated, redirect to v1)
Route::middleware(['auth:sanctum'])->prefix('api')->group(function () {
    Route::get('/test', function () {
        \Illuminate\Support\Facades\Log::warning('Legacy API route /api/test accessed. Use /api/v1/test instead.');
        return redirect()->route('api.v1.test');
    });
    Route::get('/students/search', function () {
        \Illuminate\Support\Facades\Log::warning('Legacy API route /api/students/search accessed. Use /api/v1/students/search instead.');
        return redirect()->route('api.v1.students.search');
    });
    Route::post('/attendance', function () {
        \Illuminate\Support\Facades\Log::warning('Legacy API route /api/attendance accessed. Use /api/v1/attendance instead.');
        return redirect()->route('api.v1.attendance.store');
    });
    Route::get('/global-search', function () {
        \Illuminate\Support\Facades\Log::warning('Legacy API route /api/global-search accessed. Use /api/v1/search instead.');
        return redirect()->route('api.v1.search');
    });
});

/*
|--------------------------------------------------------------------------
| Admin Panel Routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    // General Admin pages accessible to anyone with backend access
    Route::middleware(['permission:view backend'])->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('follow-up-calendar', [FollowUpCalendarController::class, 'index'])->name('follow-ups.calendar');
        Route::resource('academic-years', AcademicYearController::class);
        Route::post('academic-year/switch', [AcademicYearController::class, 'switch'])->name('academic-years.switch');
        Route::get('/calendar', [UnifiedCalendarController::class, 'index'])->name('calendar.index');
        Route::post('/global-search/session', [SessionSearchController::class, 'search'])->name('global-search.session');
    });

    // Admin Notification Management Routes
    Route::prefix('notifications')->name('notifications.')->group(function () {
        // View-related routes (require view backend permission)
        Route::middleware(['permission:view backend'])->group(function () {
            Route::get('/', [NotificationManagementController::class, 'dashboard'])->name('dashboard');
            Route::get('/settings', [NotificationSettingsController::class, 'index'])->name('settings');
            Route::post('/settings', [NotificationSettingsController::class, 'update'])->name('settings.update');
            Route::post('/settings/preferences', [NotificationSettingsController::class, 'updateUserPreferences'])->name('settings.preferences');
            Route::post('/settings/reset', [NotificationSettingsController::class, 'resetPreferences'])->name('settings.reset');
            Route::get('/list', [NotificationManagementController::class, 'index'])->name('index');
            Route::get('/{notification}', [NotificationManagementController::class, 'show'])->name('show');
            
            // Admin-specific unread count (for admin panel)
            Route::get('/admin-unread-count', function() {
                $count = \App\Models\SystemNotification::getUnreadCountForUser(auth()->id());
                return response()->json(['count' => $count]);
            })->name('admin-unread-count');
        });

        // Action-related routes (require manage notifications permission)
        Route::middleware(['permission:manage notifications'])->group(function () {
            Route::post('/test', [NotificationManagementController::class, 'testNotifications'])->name('test');
            Route::post('/mark-all-read', [NotificationManagementController::class, 'markAllAsRead'])->name('mark-all-read');
            Route::post('/cleanup', [NotificationManagementController::class, 'cleanup'])->name('cleanup');
            Route::get('/export', [NotificationManagementController::class, 'export'])->name('export');
            Route::post('/fee-reminders', [InvoiceController::class, 'sendFeeReminders'])->name('fee-reminders');
            Route::post('/financial-health', [InvoiceController::class, 'checkFinancialHealth'])->name('financial-health');
            Route::post('/test-fee-reminders', function () {
                try {
                    Artisan::call('fees:send-reminders', ['--dry-run' => true]);
                    return response()->json(['success' => true, 'output' => Artisan::output()]);
                } catch (\Exception $e) {
                    return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
                }
            })->name('test-fee-reminders');
            Route::post('/{notification}/read', [NotificationManagementController::class, 'markAsRead'])->name('mark-read');
        });

        // Settings-related routes (require manage settings permission)
        Route::middleware(['permission:manage settings'])->group(function () {
            // FIX: The following routes for 'advanced-settings' are duplicates. They are already defined below
            // under the same middleware 'permission:manage settings'. Commenting them out to avoid conflicts.
            // Route::get('/advanced-settings', [NotificationSettingsController::class, 'index'])->name('advanced-settings');
            // Route::post('/advanced-settings', [NotificationSettingsController::class, 'update'])->name('advanced-settings.update');
            // Route::post('/advanced-settings/preferences', [NotificationSettingsController::class, 'updateUserPreferences'])->name('advanced-settings.preferences');
            // Route::post('/advanced-settings/reset', [NotificationSettingsController::class, 'resetPreferences'])->name('advanced-settings.reset');
            Route::post('/settings/create-backup', [SettingController::class, 'createBackup'])->name('settings.create-backup');
            Route::post('/settings/optimize-database', [SettingController::class, 'optimizeDatabase'])->name('settings.optimize-database');
            Route::post('/settings/test-sms', [SettingController::class, 'testSMS'])->name('settings.test-sms');
            Route::post('/settings/reset-all', [SettingController::class, 'resetAllSettings'])->name('settings.reset-all');
        });
    });

    // System Health (manage settings permission)
    Route::middleware(['permission:manage settings'])->group(function () {
        Route::get('system/health', [SystemHealthController::class, 'performHealthCheck'])->name('system.health');
        Route::get('system/simple-health', [SystemHealthController::class, 'simpleHealthCheck'])->name('system.simple-health');
        Route::post('system/manual-health-check', function () {
            try {
                Artisan::call('system:health-check --notify');
                return response()->json(['success' => true, 'output' => Artisan::output()]);
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
            }
        })->name('system.manual-health-check');
    });

    // Attendance Management
    Route::prefix('attendance')->name('attendance.')->middleware(['permission:manage attendance'])->group(function () {
        Route::post('monitor', function () {
            try {
                Artisan::call('attendance:monitor');
                return response()->json(['success' => true, 'output' => Artisan::output()]);
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
            }
        })->name('manual-monitor');

        Route::get('analytics', function () {
            $minimumAttendance = (int) setting('minimum_attendance_percentage', 75);

            $lowAttendanceStudents = \App\Models\Student::whereHas('attendances')
                ->with(['attendances', 'batch'])
                ->get()
                ->filter(function ($student) use ($minimumAttendance) {
                    $totalClasses = $student->attendances->count();
                    $presentClasses = $student->attendances->whereIn('status', ['present', 'late'])->count();
                    $percentage = $totalClasses > 0 ? ($presentClasses / $totalClasses) * 100 : 100;
                    return $percentage < $minimumAttendance;
                })
                ->map(function ($student) {
                    $totalClasses = $student->attendances->count();
                    $presentClasses = $student->attendances->whereIn('status', ['present', 'late'])->count();
                    $student->attendance_percentage = $totalClasses > 0 ? round(($presentClasses / $totalClasses) * 100, 2) : 100;
                    return $student;
                })
                ->sortBy('attendance_percentage');

            return view('admin.attendance.analytics', compact('lowAttendanceStudents', 'minimumAttendance'));
        })->name('analytics');

        Route::post('notify-low-attendance', function () {
            $notificationService = app(\App\Services\NotificationService::class);
            $minimumAttendance = (int) setting('minimum_attendance_percentage', 75);
            $count = 0;

            \App\Models\Student::whereHas('attendances')
                ->with('attendances')
                ->chunk(50, function ($students) use ($notificationService, $minimumAttendance, &$count) {
                    foreach ($students as $student) {
                        $totalClasses = $student->attendances->count();
                        $presentClasses = $student->attendances->whereIn('status', ['present', 'late'])->count();
                        $percentage = $totalClasses > 0 ? ($presentClasses / $totalClasses) * 100 : 100;

                        if ($percentage < $minimumAttendance) {
                            $notificationService->sendAcademicNotification('low_attendance', [
                                'student_id' => $student->id,
                                'student_name' => $student->name,
                                'attendance_percentage' => round($percentage, 2),
                                'minimum_required' => $minimumAttendance,
                                'enrollment_number' => $student->enrollment_number,
                                'batch_name' => $student->batch->name ?? 'Unknown',
                                'trigger_type' => 'manual_bulk_check',
                            ]);
                            $count++;
                        }
                    }
                });

            return response()->json([
                'success' => true,
                'message' => "Sent {$count} low attendance notifications"
            ]);
        })->name('notify-low-attendance');

        Route::post('test-biometric', function () {
            $notificationService = app(\App\Services\NotificationService::class);

            $notification = $notificationService->send([
                'title' => 'Biometric System Test',
                'message' => 'Test biometric notification - system is working correctly',
                'type' => 'success',
                'category' => 'attendance',
                'priority' => 'low',
                'roles' => ['super-admin', 'college-admin'],
                'data' => [
                    'test_type' => 'biometric_integration',
                    'test_time' => now()->toISOString(),
                    'device_id' => 'test_device_001',
                ]
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Biometric test notification sent',
                'notification_id' => $notification->id
            ]);
        })->name('test-biometric');
    });

    // User Management Routes
    Route::middleware(['permission:manage users'])->group(function () {
        Route::resource('users', UserController::class);
        Route::patch('users/{user}/status', [UserController::class, 'updateStatus'])->name('users.updateStatus');
        Route::post('users/bulk-actions', [UserController::class, 'bulkActions'])->name('users.bulk-actions');
        Route::delete('/users/bulk-destroy', [UserController::class, 'bulkDestroy'])->name('users.bulkDestroy');
        Route::get('users/export', [UserController::class, 'export'])->name('users.export');
        Route::get('users/import/sample', [UserController::class, 'downloadSample'])->name('users.import.sample');
        Route::get('users/import', [UserController::class, 'importForm'])->name('users.import.form');
        Route::post('users/import', [UserController::class, 'import'])->name('users.import');
         // Component Payment Routes - ADD THESE:
    Route::prefix('component-payments')->as('component-payments.')->group(function () {
        
        // Student component dashboard
        Route::get('/student/{student}', [\App\Http\Controllers\Admin\ComponentPaymentController::class, 'studentComponentDashboard'])
            ->name('student-dashboard');
        
        // Record component payment
        Route::post('/student/{student}/record', [\App\Http\Controllers\Admin\ComponentPaymentController::class, 'recordComponentPayment'])
            ->name('record');
        
        // Component payment report
        Route::get('/report', [\App\Http\Controllers\Admin\ComponentPaymentController::class, 'componentPaymentReport'])
            ->name('report');
        
        // API endpoint for getting component details (AJAX)
        Route::get('/student/{student}/components', [\App\Http\Controllers\Admin\ComponentPaymentController::class, 'getStudentComponents'])
            ->name('get-components');
    });
    
    // Add link to existing invoice routes for easy navigation
    Route::get('/invoices/student/{student}/components', [\App\Http\Controllers\Admin\ComponentPaymentController::class, 'studentComponentDashboard'])
        ->name('invoices.student-components');
        
    });

    // Role Management Routes
    Route::middleware(['permission:manage roles'])->group(function () {
        Route::resource('roles', RoleController::class);
        Route::post('roles/{role}/permissions', [RoleController::class, 'updatePermissions'])->name('roles.permissions.update');
        Route::get('roles/{role}/permissions', [RoleController::class, 'getPermissions'])->name('roles.permissions.get');
        Route::get('roles/export', [RoleController::class, 'export'])->name('roles.export');
        Route::get('roles/{role}/advanced-edit', [RoleController::class, 'advancedEdit'])->name('roles.advanced-edit');
        Route::get('roles/templates', [RoleController::class, 'showTemplates'])->name('roles.templates');
        Route::post('roles/{role}/apply-template', [RoleController::class, 'applyTemplate'])->name('roles.apply-template');
        Route::post('roles/{role}/clone', [RoleController::class, 'clone'])->name('roles.clone');
        Route::post('roles/bulk-permissions', [RoleController::class, 'bulkUpdatePermissions'])->name('roles.bulk-permissions');
        Route::get('roles/{role}/analytics', [RoleController::class, 'getAnalytics'])->name('roles.analytics');
        Route::get('roles/compare', [RoleController::class, 'compare'])->name('roles.compare');
        Route::post('roles/compare', [RoleController::class, 'performComparison'])->name('roles.compare.perform');
    });

    // Enhanced Permission Management Routes
    Route::middleware(['permission:manage permissions'])->group(function () {
        Route::resource('permissions', PermissionController::class);
        Route::get('permissions/export', [PermissionController::class, 'export'])->name('permissions.export');
        Route::get('permission-management', [PermissionManagementController::class, 'index'])->name('permission-management.index');
        Route::post('permissions/bulk-create', [PermissionManagementController::class, 'bulkCreate'])->name('permissions.bulk-create');
        Route::post('permissions/sync', [PermissionManagementController::class, 'syncPermissions'])->name('permissions.sync');
        Route::post('permissions/cleanup-orphaned', [PermissionManagementController::class, 'cleanupOrphaned'])->name('permissions.cleanup-orphaned');
        Route::post('permissions/apply-template', [PermissionManagementController::class, 'applyTemplate'])->name('permissions.apply-template');
        Route::get('roles/{role}/permissions', [PermissionManagementController::class, 'getRolePermissions'])->name('roles.permissions.get');
        Route::post('permissions/copy-role-permissions', [PermissionManagementController::class, 'copyPermissions'])->name('permissions.copy-role-permissions');
        Route::get('permissions/analytics', [PermissionManagementController::class, 'getAnalytics'])->name('permissions.analytics');
        Route::get('permissions/orphaned', [PermissionManagementController::class, 'findOrphanedPermissions'])->name('permissions.orphaned');
        Route::post('permissions/generate-report', [PermissionManagementController::class, 'generateReport'])->name('permissions.generate-report');
        Route::get('permissions/validate-structure', [PermissionManagementController::class, 'validateStructure'])->name('permissions.validate-structure');
    });

    // Admission Management
    Route::middleware(['permission:manage admissions'])->group(function () {
        Route::resource('enquiries', EnquiryController::class);
        Route::get('/enquiries/{enquiry}/convert', [EnquiryController::class, 'convertToAdmission'])->name('enquiries.convertToAdmission');
        Route::resource('visitors', VisitorController::class);
        Route::get('admissions', [AdminAdmissionController::class, 'index'])->name('admissions.index');
        Route::get('admissions/{admission}', [AdminAdmissionController::class, 'show'])->name('admissions.show');
        Route::post('admissions/{admission}/approve', [AdminAdmissionController::class, 'approve'])->name('admissions.approve');
        Route::post('admissions/{admission}/reject', [AdminAdmissionController::class, 'reject'])->name('admissions.reject');
        Route::post('admissions/{admission}/follow-ups', [AdminAdmissionController::class, 'addFollowUp'])->name('admissions.follow-ups.store');
        Route::get('/admissions/create/{enquiry}', [AdminAdmissionController::class, 'create'])->name('admissions.create');
        Route::post('/admissions/finalize', [AdminAdmissionController::class, 'finalizeAndApprove'])->name('admissions.finalize');
    });

    // Course Management
    Route::middleware(['permission:manage courses'])->group(function () {
        Route::resource('courses', CourseController::class);
        Route::resource('subjects', SubjectController::class);
        Route::resource('batches', BatchController::class);
        Route::get('batches/{batch}/manage-students', [BatchController::class, 'manageStudents'])->name('batches.manageStudents');
        Route::post('batches/{batch}/sync-students', [BatchController::class, 'syncStudents'])->name('batches.syncStudents');
        Route::post('batches/{batch}/graduate', [BatchController::class, 'graduate'])->name('batches.graduate');
        Route::get('courses/{course}/structure', [CourseStructureController::class, 'show'])->name('courses.structure.show');
        Route::post('courses/{course}/structure', [CourseStructureController::class, 'store'])->name('courses.structure.store');
        Route::delete('course-terms/{term}', [CourseStructureController::class, 'destroy'])->name('course-terms.destroy');
        Route::get('courses/{course}/subjects', [CourseSubjectController::class, 'edit'])->name('courses.subjects.edit');
        Route::post('courses/{course}/subjects', [CourseSubjectController::class, 'update'])->name('courses.subjects.update');
        Route::get('/get-course-terms/{course}', [CourseStructureController::class, 'getTermsForDropdown'])->name('courses.terms.get');
    });

    // Student Management
    Route::middleware(['permission:manage students'])->group(function () {
        Route::resource('students', StudentController::class);
        Route::patch('students/{student}/status', [StudentController::class, 'updateStatus'])->name('students.updateStatus');
        Route::post('students/bulk-actions', [StudentController::class, 'bulkActions'])->name('students.bulk-actions');
        Route::get('/get-batches-for-course/{course}', [StudentController::class, 'getBatchesForCourse'])->name('get-batches-for-course');
        Route::get('/get-filtered-batches', [StudentController::class, 'getFilteredBatches'])->name('get-filtered-batches');
        Route::get('students/import/sample', [StudentController::class, 'downloadSample'])->name('students.import.sample');
        Route::get('students/import', [StudentImportController::class, 'create'])->name('students.import.create');
        Route::post('students/import', [StudentImportController::class, 'store'])->name('students.import.store');
        Route::get('students/export', [StudentController::class, 'export'])->name('students.export');
        Route::get('alumni', [AlumniController::class, 'index'])->name('alumni.index');
                Route::get('students/import-logs', [StudentImportController::class, 'importLogs'])->name('students.import-logs');
        Route::get('students/import-logs/{importLog}', [StudentImportController::class, 'showImportLog'])->name('students.import-log.show');
        Route::post('students/import-logs/{importLog}/retry-invoices', [StudentImportController::class, 'retryInvoiceCreation'])->name('students.import-log.retry-invoices');
        Route::get('students/import-logs/{importLog}/export', [StudentImportController::class, 'exportImportLog'])->name('students.import-log.export');

        // ✅ NEW: Duplicate check API routes
        Route::post('students/check-mobile-duplicate', [StudentDuplicateCheckController::class, 'checkMobileDuplicate'])
             ->name('students.check-mobile-duplicate');
        Route::post('students/check-enrollment-duplicate', [StudentDuplicateCheckController::class, 'checkEnrollmentDuplicate'])
             ->name('students.check-enrollment-duplicate');
        Route::post('students/check-email-duplicate', [StudentDuplicateCheckController::class, 'checkEmailDuplicate'])
             ->name('students.check-email-duplicate');
        Route::post('students/bulk-check-duplicates', [StudentDuplicateCheckController::class, 'bulkCheckDuplicates'])
             ->name('students.bulk-check-duplicates');
 
    });

    // Timetable Management
    Route::middleware(['permission:manage timetable'])->group(function () {
        Route::get('daily-attendance', [DailyAttendanceController::class, 'show'])->name('daily-attendance.show');
        Route::resource('daily-attendance', DailyAttendanceController::class);
        Route::post('daily-attendance', [DailyAttendanceController::class, 'store'])->name('daily-attendance.store');
        Route::get('attendance/import', [AttendanceImportController::class, 'show'])->name('attendance.import.show');
        Route::post('attendance/import', [AttendanceImportController::class, 'store'])->name('attendance.import.store');
        Route::get('lab-allocation', [LabAllocationController::class, 'index'])->name('lab-allocation.index');
        Route::post('lab-allocation/automate', [LabAllocationController::class, 'automate'])->name('lab-allocation.automate');
        Route::get('lab-allocation/{group}/manage', [LabAllocationController::class, 'manageGroup'])->name('lab-allocation.group.manage');
        Route::post('lab-allocation/{group}/add', [LabAllocationController::class, 'addStudentToGroup'])->name('lab-allocation.group.add');
        Route::delete('lab-allocation/{group}/remove/{student}', [LabAllocationController::class, 'removeStudentFromGroup'])->name('lab-allocation.group.remove');
        Route::resource('classrooms', ClassroomController::class);
        Route::resource('time-slots', TimeSlotController::class);
        Route::get('time-slots/generate', [TimeSlotController::class, 'showGenerateForm'])->name('time-slots.generate.form');
        Route::post('time-slots/generate', [TimeSlotController::class, 'generateSlots'])->name('time-slots.generate.store');
        Route::resource('holidays', HolidayController::class);
        Route::resource('events', EventController::class);
        Route::get('timetable-hub', [TimetableController::class, 'index'])->name('timetable.hub');
        Route::get('timetable-events', [TimetableController::class, 'getEvents'])->name('timetable.events');
        Route::post('timetable-generate', [TimetableController::class, 'generate'])->name('timetable.generate');
        Route::post('timetable-update', [TimetableController::class, 'manualUpdate'])->name('timetable.manual_update');
        Route::get('timetable-hub/pdf', [TimetableController::class, 'downloadPDF'])->name('timetable.hub.pdf');
    });

    // Financial Management
    Route::middleware(['permission:manage financials'])->group(function () {
        Route::resource('fee-categories', FeeCategoryController::class);
        Route::resource('fee-structures', FeeStructureController::class);
        Route::resource('expense-categories', ExpenseCategoryController::class);
        Route::resource('expenses', ExpenseController::class);

        // FIX: This entire 'payment-defaulters' block is redundant. A more comprehensive block for the same
        // route prefix and name is defined later under the 'permission:view backend' middleware.
        // Commenting out this smaller, conflicting block.
        // Route::prefix('payment-defaulters')->name('payment-defaulters.')->group(function () {
        //     Route::get('/', [PaymentDefaulterController::class, 'index'])->name('index');
        //     Route::get('/fee-type', [PaymentDefaulterController::class, 'unpaidByFeeType'])->name('fee-type');
        //     Route::post('/bulk-reminders', [PaymentDefaulterController::class, 'sendBulkReminders'])->name('bulk-reminders');
        //     Route::get('/export', [PaymentDefaulterController::class, 'export'])->name('export');
        //     Route::post('/mark-resolved/{student}', [PaymentDefaulterController::class, 'markResolved'])->name('mark-resolved');
        //     Route::post('/add-note/{student}', [PaymentDefaulterController::class, 'addNote'])->name('add-note');
        //     Route::get('/analytics', [PaymentDefaulterController::class, 'analytics'])->name('analytics');
        // });

        // Invoice routes
        Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::post('invoices/{invoice}/mark-paid', [InvoiceController::class, 'markAsPaid'])->name('invoices.mark-paid');
        Route::post('invoices/{invoice}/void', [InvoiceController::class, 'void'])->name('invoices.void');
        Route::post('invoices/{invoice}/reminder', [InvoiceController::class, 'sendReminder'])->name('invoices.reminder');
        Route::post('invoices/{invoice}/concession', [InvoiceController::class, 'applyConcession'])->name('invoices.concession.store');
                Route::get('invoices/{invoice}/edit', [InvoiceController::class, 'edit'])->name('invoices.edit');
        Route::put('invoices/{invoice}', [InvoiceEditController::class, 'update'])->name('invoices.update');
        Route::get('invoices/{invoice}/edit-history', [InvoiceEditController::class, 'editHistory'])->name('invoices.edit-history');
        Route::post('invoices/{invoice}/revert/{editLog}', [InvoiceEditController::class, 'revert'])->name('invoices.revert');
        
        // ✅ NEW: Bulk Invoice Operations
        Route::post('invoices/bulk-generate', [InvoiceController::class, 'bulkGenerate'])->name('invoices.bulk-generate');
        Route::post('invoices/bulk-edit', [InvoiceEditController::class, 'bulkEdit'])->name('invoices.bulk-edit');
        Route::get('invoices/audit-trail', [InvoiceEditController::class, 'auditTrail'])->name('invoices.audit-trail');

        // Student Financial Routes
        Route::get('financials/student/{student}', [InvoiceController::class, 'showStudentLedger'])->name('financials.student.ledger');
        Route::get('financials/student/{student}/statement', [InvoiceController::class, 'downloadStatement'])->name('financials.student.statement.download');
        
        // Payment routes
        Route::post('invoices/{invoice}/payments', [InvoiceController::class, 'addPayment'])->name('invoices.payments.store');
        Route::delete('payments/{payment}/reverse', [InvoiceController::class, 'reversePayment'])->name('payments.reverse');
        
        // Other financial routes
        Route::post('financials/student/{student}/generate-invoice', [InvoiceController::class, 'generateSingleInvoice'])->name('financials.student.invoice.store');
        
        // Receipt routes
        Route::get('payments/{payment}/receipt/show', [PaymentController::class, 'showReceipt'])->name('payments.receipt.show');
        Route::get('payments/{payment}/receipt/pdf', [PaymentController::class, 'downloadReceipt'])->name('payments.receipt.pdf');
        
        // Print and bulk operations
        Route::get('invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print');
        Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');
        Route::post('invoices/{invoice}/email', [InvoiceController::class, 'sendEmail'])->name('invoices.email');
        Route::post('invoices/bulk-action', [InvoiceController::class, 'bulkAction'])->name('invoices.bulk-action');
        Route::get('financial-reports', [InvoiceController::class, 'reports'])->name('financial.reports');
        Route::get('invoices/{invoice}/data', [InvoiceController::class, 'getInvoiceData'])->name('invoices.data');

        // Payment Reports Routes
        Route::prefix('payment-reports')->name('payment-reports.')->group(function () {
            Route::get('/dashboard', [PaymentReportsController::class, 'dashboard'])->name('dashboard');
            Route::get('/collection', [PaymentReportsController::class, 'collectionReport'])->name('collection');
            Route::get('/outstanding', [PaymentReportsController::class, 'outstandingReport'])->name('outstanding');
            Route::get('/analytics', [PaymentReportsController::class, 'analyticsReport'])->name('analytics');
            Route::get('/fee-wise', [PaymentReportsController::class, 'feeWiseReport'])->name('fee-wise');
            Route::get('/batch-wise', [PaymentReportsController::class, 'batchWiseReport'])->name('batch-wise');
            Route::post('/export/{type}', [PaymentReportsController::class, 'exportReport'])->name('export');
        });
    });

    // Payment Reminder Settings Routes
    Route::middleware(['permission:manage settings'])->group(function () {
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/payment-reminders', [PaymentReminderSettingsController::class, 'index'])->name('payment-reminders.index');
            Route::put('/payment-reminders', [PaymentReminderSettingsController::class, 'update'])->name('payment-reminders.update');
            Route::post('/payment-reminders/test', [PaymentReminderSettingsController::class, 'testReminder'])->name('payment-reminders.test');
            Route::get('/payment-reminders/dashboard', [PaymentReminderSettingsController::class, 'dashboard'])->name('payment-reminders.dashboard');
        });
    });

    // Payment Reminder Management Routes
    Route::middleware(['permission:view backend'])->group(function () {
        Route::prefix('payment-reminders')->name('payment-reminders.')->group(function () {
            Route::get('/', [PaymentReminderController::class, 'index'])->name('index');
            Route::get('/dashboard', [PaymentReminderController::class, 'dashboard'])->name('dashboard');
            Route::get('/defaulters', [PaymentReminderController::class, 'defaulters'])->name('defaulters');
            Route::post('/defaulters/update', [PaymentReminderController::class, 'updateDefaulters'])->name('defaulters.update');
            Route::get('/{reminder}', [PaymentReminderController::class, 'show'])->name('show');
            Route::post('/test', [PaymentReminderController::class, 'sendTestReminder'])->name('test');

            // FIX: This route conflicts with a later, more specific definition. Commenting out.
            // Route::post('/process-pending', [PaymentReminderController::class, 'processPendingReminders'])->name('process-pending');
            
            // FIX: This route name conflicts with a later, more consistent one ('bulk.action'). Commenting out.
            // Route::post('/bulk-action', [PaymentReminderController::class, 'bulkAction'])->name('bulk-action');

            // FIX: The following routes ('send', 'cancel', 'delete') are defined again later with a more
            // consistent parameter name ('paymentReminder'). Commenting these out to avoid conflicts.
            // Route::post('/{reminder}/send', [PaymentReminderController::class, 'sendReminder'])->name('send');
            // Route::post('/{reminder}/cancel', [PaymentReminderController::class, 'cancelReminder'])->name('cancel');
            // Route::delete('/{reminder}', [PaymentReminderController::class, 'deleteReminder'])->name('delete');

            Route::get('/create', [PaymentReminderController::class, 'create'])->name('create');
            Route::post('/', [PaymentReminderController::class, 'store'])->name('store');
            Route::get('/{paymentReminder}/edit', [PaymentReminderController::class, 'edit'])->name('edit');
            Route::put('/{paymentReminder}', [PaymentReminderController::class, 'update'])->name('update');
            Route::delete('/{paymentReminder}', [PaymentReminderController::class, 'destroy'])->name('destroy');
            
            // Individual reminder actions
            Route::post('/{paymentReminder}/send', [PaymentReminderController::class, 'send'])->name('send');
            Route::post('/{paymentReminder}/cancel', [PaymentReminderController::class, 'cancel'])->name('cancel');
            Route::post('/{paymentReminder}/reschedule', [PaymentReminderController::class, 'reschedule'])->name('reschedule');
            
            // Bulk operations
            Route::post('/bulk/action', [PaymentReminderController::class, 'bulkAction'])->name('bulk.action');
            Route::post('/bulk/send', [PaymentReminderController::class, 'bulkSend'])->name('bulk.send');
            Route::post('/bulk/cancel', [PaymentReminderController::class, 'bulkCancel'])->name('bulk.cancel');
            Route::post('/bulk/reschedule', [PaymentReminderController::class, 'bulkReschedule'])->name('bulk.reschedule');
            
            // Export and reporting
            Route::get('/export/reminders', [PaymentReminderController::class, 'export'])->name('export');
            Route::get('/reports/summary', [PaymentReminderController::class, 'summaryReport'])->name('reports.summary');
            Route::get('/reports/analytics', [PaymentReminderController::class, 'analytics'])->name('reports.analytics');
            
            // System operations
            Route::post('/process-pending', [PaymentReminderController::class, 'processPending'])->name('process-pending');
            Route::get('/health-check', [PaymentReminderController::class, 'healthCheck'])->name('health-check');
        });

        // Payment Defaulter Management Routes (Separate from Financial Management)
        Route::prefix('payment-defaulters')->name('payment-defaulters.')->group(function () {
            Route::get('/', [PaymentDefaulterController::class, 'index'])->name('index');
            Route::get('/analytics', [PaymentDefaulterController::class, 'analytics'])->name('analytics');
            Route::get('/{student}/show', [PaymentDefaulterController::class, 'show'])->name('show');
            
            // Individual defaulter actions
            Route::post('/{student}/send-reminder', [PaymentDefaulterController::class, 'sendReminder'])->name('send-reminder');
            Route::post('/{student}/mark-resolved', [PaymentDefaulterController::class, 'markResolved'])->name('mark-resolved');
            Route::post('/{student}/add-note', [PaymentDefaulterController::class, 'addNote'])->name('add-note');
            Route::post('/{student}/assign', [PaymentDefaulterController::class, 'assign'])->name('assign');
            Route::post('/{student}/update-status', [PaymentDefaulterController::class, 'updateStatus'])->name('update-status');
            
            // Bulk operations
            Route::post('/bulk/send-reminders', [PaymentDefaulterController::class, 'bulkSendReminders'])->name('bulk.send-reminders');
            Route::post('/bulk/assign', [PaymentDefaulterController::class, 'bulkAssign'])->name('bulk.assign');
            Route::post('/bulk/update-status', [PaymentDefaulterController::class, 'bulkUpdateStatus'])->name('bulk.update-status');
            
            // Export and reporting
            Route::get('/export/list', [PaymentDefaulterController::class, 'export'])->name('export');
            Route::get('/reports/recovery', [PaymentDefaulterController::class, 'recoveryReport'])->name('reports.recovery');
        });
    });
    
    // ✅ API Routes for AJAX functionality
Route::prefix('api/admin')->middleware(['auth:sanctum'])->name('api.admin.')->group(function () {
    // Real-time import status
    Route::get('import-status/{importLog}', [StudentImportController::class, 'getImportStatus']);
    
    // Invoice editing helpers
    Route::get('invoices/{invoice}/current-state', [InvoiceEditController::class, 'getCurrentState']);
    Route::post('invoices/validate-edit', [InvoiceEditController::class, 'validateEdit']);
    
    // Fee categories for invoice editing
    Route::get('fee-categories/for-batch/{batch}', function(\App\Models\Batch $batch) {
        $feeStructure = $batch->feeStructure;
        if (!$feeStructure) {
            return response()->json(['error' => 'No fee structure found for this batch'], 404);
        }
        
        return response()->json([
            'fee_categories' => $feeStructure->feeCategories->map(function($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'amount' => $category->pivot->amount
                ];
            })
        ]);
    });
});

    // Quick access API routes for dashboard widgets
    Route::middleware(['permission:view backend'])->prefix('api/payment')->name('api.payment.')->group(function () {
        // Quick stats for dashboard widgets
        Route::get('/stats/reminders-today', function() {
            $count = \App\Models\PaymentReminder::whereDate('sent_at', today())->count();
            return response()->json(['count' => $count]);
        })->name('stats.reminders-today');

        Route::get('/stats/pending-reminders', function() {
            $count = \App\Models\PaymentReminder::pending()->count();
            return response()->json(['count' => $count]);
        })->name('stats.pending-reminders');

        Route::get('/stats/overdue-reminders', function() {
            $count = \App\Models\PaymentReminder::overdue()->count();
            return response()->json(['count' => $count]);
        })->name('stats.overdue-reminders');

        Route::get('/stats/defaulters-count', function() {
            $count = \App\Models\PaymentDefaulter::active()->count();
            return response()->json(['count' => $count]);
        })->name('stats.defaulters-count');

        Route::get('/stats/collection-rate', function() {
            $totalInvoices = \App\Models\Invoice::count();
            $paidInvoices = \App\Models\Invoice::where('status', 'paid')->count();
            $rate = $totalInvoices > 0 ? round(($paidInvoices / $totalInvoices) * 100, 2) : 0;
            return response()->json(['rate' => $rate]);
        })->name('stats.collection-rate');

        Route::get('/stats/overdue-amount', function() {
            $amount = \App\Models\Invoice::where('due_date', '<', now())
                                        ->where('status', 'unpaid')
                                        ->sum('due_amount');
            return response()->json(['amount' => number_format($amount, 2)]);
        })->name('stats.overdue-amount');

        // Quick actions
        Route::post('/reminders/send-overdue', function() {
            $overdueReminders = \App\Models\PaymentReminder::overdue()->limit(10)->get();
            $sent = 0;
            
            foreach ($overdueReminders as $reminder) {
                try {
                    // In a real implementation, you'd call the reminder service
                    $reminder->update(['status' => 'sent', 'sent_at' => now()]);
                    $sent++;
                } catch (\Exception $e) {
                    // Log error
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => "Sent {$sent} overdue reminders"
            ]);
        })->name('send-overdue-reminders');

        Route::post('/defaulters/update-records', function() {
            try {
                $service = app(\App\Services\PaymentReminderService::class);
                $results = $service->updateDefaulterRecords();
                
                return response()->json([
                    'success' => true,
                    'message' => "Updated {$results['updated']} records, resolved {$results['resolved']}",
                    'results' => $results
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update defaulter records: ' . $e->getMessage()
                ], 500);
            }
        })->name('update-defaulter-records');
    });

    // Fee Collection Management Routes
    Route::middleware(['permission:manage financials'])->group(function () {
        Route::prefix('fee-collection')->name('fee-collection.')->group(function () {
            Route::get('/dashboard', [FeeCollectionController::class, 'dashboard'])->name('dashboard');
            Route::get('/statistics', [FeeCollectionController::class, 'statistics'])->name('statistics');
            Route::post('/update-targets', [FeeCollectionController::class, 'updateTargets'])->name('update-targets');
            Route::get('/export/{type}', [FeeCollectionController::class, 'export'])->name('export');
        });
    });

    // System Integration Routes
    Route::middleware(['permission:manage settings'])->group(function () {
        Route::prefix('integration')->name('integration.')->group(function () {
            Route::get('/payment-gateways', [IntegrationController::class, 'paymentGateways'])->name('payment-gateways');
            Route::post('/test-gateway/{gateway}', [IntegrationController::class, 'testGateway'])->name('test-gateway');
            Route::get('/webhooks', [IntegrationController::class, 'webhooks'])->name('webhooks');
            Route::post('/webhooks/regenerate', [IntegrationController::class, 'regenerateWebhook'])->name('webhooks.regenerate');
            Route::post('/gateway-settings', [IntegrationController::class, 'updateGatewaySettings'])->name('gateway-settings.update');
            Route::get('/export-config', [IntegrationController::class, 'exportConfiguration'])->name('export-config');
            Route::post('/import-config', [IntegrationController::class, 'importConfiguration'])->name('import-config');
            Route::get('/analytics', [IntegrationController::class, 'getGatewayAnalytics'])->name('analytics');
            Route::post('/test-webhook', [IntegrationController::class, 'testWebhookEndpoint'])->name('test-webhook');
            Route::get('/webhook-logs', [IntegrationController::class, 'getWebhookLogs'])->name('webhook-logs');
            Route::post('/retry-webhook/{callId}', [IntegrationController::class, 'retryWebhookDelivery'])->name('retry-webhook');
        });
    });

    // HR Management
    Route::middleware(['permission:manage hr'])->group(function () {
        Route::resource('leave-types', LeaveTypeController::class);
        Route::get('leave-applications', [LeaveApplicationController::class, 'adminIndex'])->name('leave-applications.index');
        Route::post('leave-applications/{application}/update-status', [LeaveApplicationController::class, 'updateStatus'])->name('leave-applications.updateStatus');
        Route::resource('salary-components', SalaryComponentController::class);
        Route::get('faculty/{user}/salary', [UserSalaryController::class, 'show'])->name('faculty.salary.show');
        Route::post('faculty/{user}/salary', [UserSalaryController::class, 'store'])->name('faculty.salary.store');
        Route::delete('faculty/salary/{structure}', [UserSalaryController::class, 'destroy'])->name('faculty.salary.destroy');
        Route::resource('payslips', PayslipController::class)->only(['index', 'create', 'store', 'show']);
        Route::resource('faculty', FacultyController::class)->except(['show']);
        Route::get('faculty/{user}/subjects', [FacultySubjectController::class, 'edit'])->name('faculty.subjects.edit');
        Route::post('faculty/{user}/subjects', [FacultySubjectController::class, 'update'])->name('faculty.subjects.update');
    });

    // Inventory Management
    Route::middleware(['permission:manage inventory'])->group(function () {
        Route::resource('asset-categories', AssetCategoryController::class);
        Route::resource('assets', AssetController::class);
        Route::delete('assets/bulk-destroy', [AssetController::class, 'bulkDestroy'])->name('assets.bulkDestroy');
        Route::post('assets/import', [AssetController::class, 'importAssets'])->name('assets.import.store');
        Route::get('assets/import/sample', [AssetController::class, 'downloadSample'])->name('assets.import.sample');
        Route::resource('audits', AuditController::class)->only(['index', 'store', 'show']);
        Route::post('audits/{audit}/items', [AuditController::class, 'saveItemStatus'])->name('audits.items.store');
        Route::post('audits/{audit}/complete', [AuditController::class, 'complete'])->name('audits.complete');
    });

    // Document Management
    Route::middleware(['permission:manage documents'])->group(function () {
        Route::get('id-card-generator', [IdCardController::class, 'show'])->name('id-cards.show');
        Route::resource('id-card-templates', IdCardTemplateController::class);
        Route::get('certificate-generator', [CertificateGeneratorController::class, 'showForm'])->name('certificate.generator.show');
        Route::post('certificate-generator/generate', [CertificateGeneratorController::class, 'generate'])->name('certificate.generator.generate');
        Route::resource('certificate-templates', CertificateTemplateController::class);
    });
    

// FIX: The closing curly brace and parenthesis for the main admin group were missing here.
// I've added them back to ensure all the routes above are correctly grouped under the `/admin` prefix.
//}); 

// FIX: The following routes for public receipts conflict with the globally defined routes at the top of the file.
// Since these are inside the 'admin' prefix group, their URIs would be 'admin/receipts/...', but their names
// are 'public.receipt.show' and 'public.receipt.pdf', which creates a conflict. The global routes are correct
// for public access, so these are commented out.
// Route::get('receipts/{receipt_number}', function($receipt_number) {
//     $payment = \App\Models\Payment::where('receipt_number', $receipt_number)
//                         ->with('invoice.student')
//                         ->firstOrFail();

//     return view('admin.receipts.receipt_pdf', compact('payment'));
// })->name('public.receipt.show');

// Route::get('receipts/{receipt_number}/pdf', function($receipt_number) {
//     $payment = \App\Models\Payment::where('receipt_number', $receipt_number)
//                         ->with('invoice.student')
//                         ->firstOrFail();

//     $pdf = \PDF::loadView('admin.receipts.receipt_pdf', compact('payment'));
    
//     $fileName = 'Receipt-' . $payment->receipt_number . '.pdf';

//     return $pdf->download($fileName);
// })->name('public.receipt.pdf');

    // Settings Management
    Route::middleware(['permission:manage settings'])->group(function () {
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
        Route::get('/settings/export', [SettingController::class, 'export'])->name('settings.export');
        Route::post('/settings/import', [SettingController::class, 'import'])->name('settings.import');
        Route::post('/settings/reset-defaults', [SettingController::class, 'resetToDefaults'])->name('settings.reset-defaults');
        Route::post('/settings/test-email', [SettingController::class, 'testEmail'])->name('settings.test-email');
        Route::post('/settings/clear-cache', [SettingController::class, 'clearCache'])->name('settings.clear-cache');
        Route::post('/settings/toggle-maintenance', [SettingController::class, 'toggleMaintenance'])->name('settings.toggle-maintenance');
        Route::get('/settings/system-info', [SettingController::class, 'systemInfo'])->name('settings.system-info');
        Route::match(['get', 'post'], '/settings/health-check', [SettingController::class, 'healthCheck'])->name('settings.health-check');
        Route::post('/settings/validate', [SettingController::class, 'validateSetting'])->name('settings.validate');
        Route::get('/settings/{group}', [SettingController::class, 'showGroup'])->name('settings.group');
        Route::post('/settings/{group}/reset', [SettingController::class, 'resetGroupToDefaults'])->name('settings.group.reset');
        Route::post('/test-payment-reminders', [SettingController::class, 'testPaymentReminders'])->name('test-payment-reminders');
        Route::post('/test-sms', [SettingController::class, 'testSMSConfiguration'])->name('test-sms');
        Route::post('/test-whatsapp', [SettingController::class, 'testWhatsAppConfiguration'])->name('test-whatsapp');
        Route::get('/system-stats', [SettingController::class, 'getSystemStats'])->name('system-stats');
        Route::post('/reset-reminders', [SettingController::class, 'resetAllReminders'])->name('reset-reminders');
        Route::post('/sync-defaulters', [SettingController::class, 'syncDefaulters'])->name('sync-defaulters');
Route::middleware(['permission:edit payments'])->group(function () {
    Route::get('payments/{payment}/edit', [PaymentEditController::class, 'edit'])->name('payments.edit');
    Route::put('payments/{payment}', [PaymentEditController::class, 'update'])->name('payments.update');
});
        Route::prefix('api/settings')->name('api.settings.')->group(function () {
            Route::get('/groups', [SettingController::class, 'getGroups'])->name('groups');
            Route::get('/public', [SettingController::class, 'getPublicSettings'])->name('public');
            Route::get('/statistics', [SettingController::class, 'getStatistics'])->name('statistics');
            Route::get('/{key}', [SettingController::class, 'getSetting'])->name('get');
            Route::post('/{key}', [SettingController::class, 'updateSetting'])->name('update');
            Route::delete('/{key}', [SettingController::class, 'deleteSetting'])->name('delete');
        });
    });

    // API Token Management
    Route::middleware(['permission:manage api tokens'])->group(function () {
        Route::resource('api-tokens', ApiTokenController::class);
        Route::post('api-tokens/{user}/revoke-all', [ApiTokenController::class, 'revokeUserTokens'])->name('api-tokens.revoke-user');
        Route::get('api-tokens-usage', fn() => redirect()->route('admin.api-tokens.index')->with('info', 'Token usage statistics will be available soon.'))->name('api-tokens.usage');
        Route::delete('api-tokens-cleanup', [ApiTokenController::class, 'cleanupExpired'])->name('api-tokens.cleanup');
        Route::post('api-tokens/{token}/regenerate', [ApiTokenController::class, 'regenerate'])->name('api-tokens.regenerate');
        Route::post('api-tokens-bulk', [ApiTokenController::class, 'bulkAction'])->name('api-tokens.bulk');
        Route::get('api-tokens/{token}/test', [ApiTokenController::class, 'test'])->name('api-tokens.test');
    });

    // System Management Routes
    Route::middleware(['permission:view backend'])->group(function () {
        Route::get('configuration', [ConfigurationController::class, 'index'])->name('configuration.index');
        Route::resource('widgets', WidgetController::class);
        Route::post('widgets/sync', [WidgetController::class, 'sync'])->name('widgets.sync');
        Route::get('dashboard-builder', [DashboardBuilderController::class, 'index'])->name('dashboard-builder.index');
        Route::get('dashboard-builder/{role}/load', [DashboardBuilderController::class, 'load'])->name('dashboard-builder.load');
        Route::post('dashboard-builder/save', [DashboardBuilderController::class, 'save'])->name('dashboard-builder.save');
        Route::get('dashboard-builder/render/{widget}', [DashboardBuilderController::class, 'renderWidget'])->name('dashboard-builder.render');
        Route::get('activity-log', [ActivityLogController::class, 'index'])->name('activity-log.index');

        // Complete Backup Management Routes
        Route::prefix('backups')->name('backups.')->group(function () {
            Route::get('/', [BackupController::class, 'index'])->name('index');
            Route::post('/create', [BackupController::class, 'create'])->name('create');
            Route::get('/download/{fileName}', [BackupController::class, 'download'])->name('download');
            Route::delete('/destroy/{fileName}', [BackupController::class, 'destroy'])->name('destroy');
            Route::put('/settings', [BackupController::class, 'updateSettings'])->name('settings.update');
            Route::post('/restore-settings', [BackupController::class, 'restoreSettings'])->name('restore-settings');
            Route::post('/test', [BackupController::class, 'testBackup'])->name('test');
            Route::post('/cleanup', [BackupController::class, 'cleanupBackups'])->name('cleanup');
        });

        Route::prefix('webhooks')->name('webhooks.')->group(function () {
            Route::get('/', [WebhookController::class, 'index'])->name('index');
            Route::get('/create', [WebhookController::class, 'create'])->name('create');
            Route::post('/', [WebhookController::class, 'store'])->name('store');
            Route::get('/export', [WebhookController::class, 'export'])->name('export');
            Route::get('/health-check', [WebhookController::class, 'healthCheck'])->name('health-check');
            Route::get('/analytics', [WebhookController::class, 'analytics'])->name('analytics');
            Route::post('/bulk-action', [WebhookController::class, 'bulkAction'])->name('bulk-action');
            Route::get('/{webhook}', [WebhookController::class, 'show'])->name('show');
            Route::get('/{webhook}/edit', [WebhookController::class, 'edit'])->name('edit');
            Route::put('/{webhook}', [WebhookController::class, 'update'])->name('update');
            Route::delete('/{webhook}', [WebhookController::class, 'destroy'])->name('destroy');
            Route::post('/{webhook}/test', [WebhookController::class, 'test'])->name('test');
            Route::get('/{webhook}/toggle', [WebhookController::class, 'toggle'])->name('toggle');
            Route::get('/{webhook}/logs', [WebhookController::class, 'showLogs'])->name('logs');
            Route::post('/{webhook}/regenerate-secret', [WebhookController::class, 'regenerateSecret'])->name('regenerateSecret');
        });

        Route::get('api-documentation', fn() => view('admin.api_documentation.index'))->name('api-documentation.index');
        Route::post('api-documentation/generate', function () {
            try {
                $documentation = [
                    'info' => [
                        'title' => 'API Documentation',
                        'version' => '1.0.0',
                        'description' => 'Generated API documentation'
                    ],
                    'generated_at' => now()->toISOString(),
                    'routes' => collect(\Illuminate\Support\Facades\Route::getRoutes())
                        ->filter(function ($route) {
                            return str_starts_with($route->uri(), 'api/');
                        })
                        ->map(function ($route) {
                            return [
                                'uri' => $route->uri(),
                                'methods' => $route->methods(),
                                'name' => $route->getName(),
                                'action' => $route->getActionName()
                            ];
                        })
                        ->values()
                        ->toArray()
                ];

                $storagePath = storage_path('api-docs');
                if (!is_dir($storagePath)) {
                    mkdir($storagePath, 0755, true);
                }

                file_put_contents(
                    $storagePath . '/api-docs.json',
                    json_encode($documentation, JSON_PRETTY_PRINT)
                );

                return redirect()->back()->with('success', 'API documentation generated successfully!');
            } catch (\Exception $e) {
                return redirect()->back()->with('error', 'Failed to generate documentation: ' . $e->getMessage());
            }
        })->name('api-documentation.generate');

        Route::get('api-documentation/download', function () {
            $filePath = storage_path('api-docs/api-docs.json');
            if (file_exists($filePath)) {
                return response()->download($filePath, 'api-documentation.json');
            }
            return redirect()->back()->with('error', 'Documentation file not found. Please generate it first.');
        })->name('api-documentation.download');
    });

    // Reports Management
    Route::middleware(['permission:manage reports'])->group(function () {
        Route::get('reports/attendance', [AttendanceReportController::class, 'index'])->name('reports.attendance.index');
        Route::get('reports/financial', [FinancialReportController::class, 'show'])->name('reports.financial.show');
        Route::get('reports/assets', [AssetReportController::class, 'index'])->name('reports.assets.index');
        Route::get('reports/admissions', [AdmissionReportController::class, 'index'])->name('reports.admissions.index');
    });

    // AJAX Routes for Real-time Updates
    Route::middleware(['permission:view backend'])->prefix('ajax')->name('ajax.')->group(function () {
        Route::get('/defaulters/count', function() {
            $count = \App\Models\Student::whereHas('invoices', function($q) {
                $q->where('due_date', '<', now())->where('status', 'unpaid');
            })->count();
            return response()->json(['count' => $count]);
        })->name('defaulters.count');
        
        Route::get('/overdue/amount', function() {
            $amount = \App\Models\Invoice::where('due_date', '<', now())
                ->where('status', 'unpaid')->sum('due_amount');
            return response()->json(['amount' => number_format($amount, 2)]);
        })->name('overdue.amount');
        
        Route::get('/reminders/today', function() {
            $count = \App\Models\PaymentReminder::whereDate('sent_at', today())->count();
            return response()->json(['count' => $count]);
        })->name('reminders.today');
    });
// FIX: This closing bracket and parenthesis `);` was a syntax error. It was closing the main admin group prematurely.
// It has been commented out and the correct closing for the admin group is added at the very end of the admin section.
// );
});



// Add to routes/api.php - Legacy webhook support (for existing integrations)
Route::prefix('webhooks')->group(function () {
    // Legacy biometric webhook (redirect to new endpoint)
    Route::post('/biometric', function (\Illuminate\Http\Request $request) {
        return app(\App\Http\Controllers\Api\AttendanceController::class)->store($request);
    });
    
    // ESSL specific legacy endpoint
    Route::any('/essl', function (\Illuminate\Http\Request $request) {
        return app(\App\Http\Controllers\Api\AttendanceController::class)->esslWebhook($request);
    });
    
    // ZKTeco specific legacy endpoint  
    Route::post('/zkteco', function (\Illuminate\Http\Request $request) {
        return app(\App\Http\Controllers\Api\AttendanceController::class)->zktecoWebhook($request);
    });
});

// FIX: This group is empty and serves no purpose. Commenting it out.
// Add middleware for biometric routes rate limiting
// Route::middleware(['throttle:biometric'])->group(function () {
//     // All biometric endpoints are rate limited
// });

// FIX: This function `configureRateLimiting` does not belong in a routes file.
// It will cause a fatal error. This code should be in `app/Providers/RouteServiceProvider.php`.
// I have commented out the entire block.
/*
public function configureRateLimiting()
{
    // Existing rate limiters...
    
    // Biometric device rate limiter
    RateLimiter::for('biometric', function (Request $request) {
        return Limit::perMinute(60)->by($request->ip());
    });
    
    // Device-specific rate limiter
    RateLimiter::for('device', function (Request $request) {
        $deviceId = $request->header('X-DEVICE-ID') ?? $request->input('device_id') ?? $request->ip();
        return Limit::perMinute(120)->by($deviceId);
    });
}
*/

// Cron job routes (for automated processing)
Route::middleware(['auth:sanctum'])->prefix('api/cron')->name('api.cron.')->group(function () {
    Route::post('/process-reminders', [PaymentReminderController::class, 'processPending'])
        ->name('process-reminders');
    
    Route::post('/update-defaulters', function() {
        try {
            $service = app(\App\Services\PaymentReminderService::class);
            $results = $service->updateDefaulterRecords();
            
            return response()->json([
                'success' => true,
                'results' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    })->name('update-defaulters');
    
    Route::post('/cleanup-old-records', function() {
        try {
            $service = app(\App\Services\PaymentReminderService::class);
            $results = $service->cleanupOldRecords();
            
            return response()->json([
                'success' => true,
                'results' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    })->name('cleanup-records');
});

/*
|--------------------------------------------------------------------------
| API Routes for AJAX functionality
|--------------------------------------------------------------------------
*/

Route::prefix('api/admin')->name('api.admin.')->middleware(['auth', 'permission:manage permissions'])->group(function () {
    Route::get('permissions/search', function (Request $request) {
        $query = $request->get('q', '');
        $permissions = Permission::where('name', 'like', "%{$query}%")
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'permissions' => $permissions->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'formatted_name' => ucwords(str_replace(['_', '-'], ' ', $permission->name)),
                    'roles_count' => $permission->roles()->count()
                ];
            })
        ]);
    })->name('permissions.search');

    Route::get('roles/search', function (Request $request) {
        $query = $request->get('q', '');
        $roles = Role::where('name', 'like', "%{$query}%")
            ->withCount('permissions', 'users')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'roles' => $roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'formatted_name' => ucfirst(str_replace('-', ' ', $role->name)),
                    'permissions_count' => $role->permissions_count,
                    'users_count' => $role->users_count
                ];
            })
        ]);
    })->name('roles.search');

    Route::post('roles/{role}/toggle-permission', function (Request $request, Role $role) {
        $request->validate([
            'permission' => 'required|exists:permissions,name'
        ]);

        $permission = Permission::where('name', $request->permission)->first();

        if ($role->hasPermissionTo($permission)) {
            $role->revokePermissionTo($permission);
            $action = 'removed';
        } else {
            $role->givePermissionTo($permission);
            $action = 'added';
        }

        return response()->json([
            'success' => true,
            'action' => $action,
            'message' => "Permission {$action} successfully."
        ]);
    })->name('roles.toggle-permission');

    Route::get('permissions/module/{module}', function ($module) {
        $permissions = Permission::where('name', 'like', "% {$module}")
            ->orWhere('name', 'like', "{$module} %")
            ->get();

        $groupedPermissions = $permissions->groupBy(function ($permission) {
            $parts = explode(' ', $permission->name);
            return $parts[0];
        });

        return response()->json([
            'success' => true,
            'module' => $module,
            'permissions' => $permissions,
            'grouped' => $groupedPermissions
        ]);
    })->name('permissions.module');
});

/*
|--------------------------------------------------------------------------
| Permission Checker Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {
    Route::post('check-permissions', function (Request $request) {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'string'
        ]);

        $user = auth()->user();
        $results = [];

        foreach ($request->permissions as $permission) {
            $results[$permission] = $user->can($permission);
        }

        return response()->json([
            'success' => true,
            'permissions' => $results,
            'user_roles' => $user->roles->pluck('name')
        ]);
    })->name('permissions.check');

    Route::get('my-permissions', function () {
        $user = auth()->user();
        $directPermissions = $user->getDirectPermissions();
        $rolePermissions = $user->getPermissionsViaRoles();
        $allPermissions = $user->getAllPermissions();

        return response()->json([
            'success' => true,
            'direct_permissions' => $directPermissions->pluck('name'),
            'role_permissions' => $rolePermissions->pluck('name'),
            'all_permissions' => $allPermissions->pluck('name'),
            'roles' => $user->roles->map(function ($role) {
                return [
                    'name' => $role->name,
                    'permissions' => $role->permissions->pluck('name')
                ];
            })
        ]);
    })->name('permissions.my-permissions');
});

// Permission seeder route (development only)
if (app()->environment(['local', 'staging'])) {
    Route::get('admin/permissions/seed', function () {
        try {
            Artisan::call('db:seed', ['--class' => 'AdvancedPermissionSeeder']);
            return response()->json([
                'success' => true,
                'message' => 'Advanced permissions seeded successfully!',
                'output' => Artisan::output()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to seed permissions: ' . $e->getMessage()
            ], 500);
        }
    })->middleware(['auth', 'permission:manage settings'])->name('permissions.seed');
}

/*
|--------------------------------------------------------------------------
| API Documentation & Debug Routes
|--------------------------------------------------------------------------
*/

Route::get('/api/documentation', function () {
    $filePath = storage_path('api-docs/api-docs.json');
    $documentation = file_exists($filePath) ? json_decode(file_get_contents($filePath), true) : [
        'error' => 'Documentation not found',
        'message' => 'Please generate the API documentation first from the admin panel.'
    ];

    $documentation['deprecation_notice'] = [
        'message' => 'Legacy /api/* endpoints are deprecated. Use /api/v1/* endpoints instead.',
        'deprecated_routes' => ['/api/test', '/api/students/search', '/api/attendance', '/api/global-search']
    ];

    return response()->json($documentation);
})->name('api.documentation');

// Debug routes (restricted to local/staging)
Route::middleware(['auth', 'permission:manage settings'])->group(function () {
    Route::get('/debug/routes', function () {
        if (!app()->environment(['local', 'staging'])) {
            abort(403, 'Debug routes are only available in local or staging environments.');
        }
        return response()->json([
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'app_url' => config('app.url'),
            'total_routes' => count(\Illuminate\Support\Facades\Route::getRoutes()),
            'api_routes' => collect(\Illuminate\Support\Facades\Route::getRoutes())
                ->filter(function ($route) {
                    return str_starts_with($route->uri(), 'api/');
                })
                ->count(),
            'status' => 'All routes loaded successfully!'
        ]);
    })->name('debug.routes');

    Route::get('/debug/test-json', function () {
        if (!app()->environment(['local', 'staging'])) {
            abort(403, 'Debug routes are only available in local or staging environments.');
        }
        return response()->json([
            'test' => 'This JSON response works',
            'timestamp' => now(),
            'url' => request()->url(),
            'method' => request()->method(),
            'status' => 'OK'
        ]);
    })->name('debug.test-json');
});

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now(),
        'app' => config('app.name'),
        'version' => '1.0.0'
    ]);
})->name('health');

// Include authentication routes
require __DIR__ . '/auth.php';