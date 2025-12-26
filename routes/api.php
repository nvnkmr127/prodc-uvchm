<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use App\Http\Controllers\Api\GlobalSearchController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\BiometricWebhookController;
use App\Http\Controllers\NotificationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {
        Route::get('/api/dashboard/my-payment-data', [DashboardController::class, 'getMyPaymentData']);
        Route::get('/api/dashboard/my-activities', [DashboardController::class, 'getMyActivities']);
        Route::get('/api/dashboard/attendance-data', [DashboardController::class, 'getAttendanceData']);
        Route::get('/admin/reports/my-payments/export', [ComponentPaymentController::class, 'exportMyPayments']);

    });

// Health check endpoints
Route::get('/ping', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'PONG',
        'timestamp' => now(),
        'server_time' => date('Y-m-d H:i:s')
    ]);
});

Route::get('/test', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'API is working',
        'timestamp' => now(),
        'server_time' => date('Y-m-d H:i:s')
    ]);
});

// Debug endpoint - SECURED: Only available in local environment with authentication
Route::middleware(['auth:sanctum', 'throttle:10,1'])->group(function () {
    Route::any('/debug-realtime', function(\Illuminate\Http\Request $request) {
        // Only allow in local environment
        if (!app()->environment('local')) {
            abort(404);
        }
        
        // Only allow super-admin users
        if (!$request->user() || !$request->user()->hasRole('super-admin')) {
            abort(403, 'Unauthorized access to debug endpoint');
        }
        
        \Log::info('=== DEBUG REALTIME ENDPOINT ===', [
            'timestamp' => now(),
            'method' => $request->method(),
            'user_id' => $request->user()->id,
            'ip' => $request->ip()
            // Removed sensitive data logging
        ]);
        
        return response()->json([
            'Result' => 'OK',
            'Status' => 'Debug endpoint working (secured)',
            'timestamp' => now(),
            'method' => $request->method(),
            'environment' => app()->environment()
        ]);
    });
});

// ETimeOffice Webhook Routes - No authentication required for webhook endpoints
Route::prefix('etimeoffice')->group(function () {
    // Primary ETimeOffice webhook endpoint
    Route::post('/webhook', [BiometricWebhookController::class, 'handleETimeOffice'])
        ->name('api.etimeoffice.webhook');
    
    // Alternative endpoint names for ETimeOffice compatibility
    Route::post('/attendance', [BiometricWebhookController::class, 'handleETimeOffice'])
        ->name('api.etimeoffice.attendance');
    
    Route::post('/punch-data', [BiometricWebhookController::class, 'handleETimeOffice'])
        ->name('api.etimeoffice.punch-data');
});

// Legacy biometric webhook - redirects to ETimeOffice (for backward compatibility)
Route::post('/biometric/webhook', [BiometricWebhookController::class, 'handleBiometric'])
    ->name('api.biometric.webhook.legacy');

// Deprecated endpoints - return 410 Gone
Route::post('/biometric/realtime', [BiometricWebhookController::class, 'handleRealtime'])
    ->name('api.biometric.realtime.deprecated');


/*
|--------------------------------------------------------------------------
| Public Webhook Routes (No Authentication Required)
|--------------------------------------------------------------------------
*/

Route::prefix('webhooks')->group(function () {
    Route::post('/attendance', [AttendanceController::class, 'store']);
});

// Attendance route without conflicting middleware
Route::post('/attendance', [AttendanceController::class, 'store'])->name('api.attendance.store');

/*
|--------------------------------------------------------------------------
| Protected API Routes (Require Authentication)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    // Global search endpoint
    Route::get('/search', [GlobalSearchController::class, 'search']);
    
    // Course-related endpoints
    Route::get('/courses/{course}/terms', function (App\Models\Course $course) {
        return response()->json($course->terms);
    });
    
    // User profile endpoint
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    // Student search endpoints - more restrictive rate limiting for search
    Route::middleware('throttle:30,1')->group(function () {
        Route::get('/students/search', [\App\Http\Controllers\Api\StudentController::class, 'search']);
    });
    
    Route::get('/students/{student}', [\App\Http\Controllers\Api\StudentController::class, 'show']);
    
    // Attendance API endpoints - sensitive data with tighter rate limiting
    Route::prefix('attendance')->name('api.attendance.')->middleware('throttle:100,1')->group(function () {
        Route::get('/today', [AttendanceController::class, 'getTodayAttendance'])->name('today');
        Route::get('/student/{student}', [AttendanceController::class, 'getStudentAttendance'])->name('student');
        Route::get('/batch/{batch}', [AttendanceController::class, 'getBatchAttendance'])->name('batch');
        
        // Real-time endpoints need more restrictive limits
        Route::middleware('throttle:30,1')->group(function () {
            Route::get('/realtime', [AttendanceController::class, 'getRealTimeData'])->name('realtime');
            Route::get('/live-feed', [AttendanceController::class, 'getLiveFeed'])->name('live-feed');
        });
        
        Route::get('/stats/today', [AttendanceController::class, 'getTodayStats'])->name('stats.today');
        Route::get('/stats/weekly', [AttendanceController::class, 'getWeeklyStats'])->name('stats.weekly');
        Route::get('/stats/monthly', [AttendanceController::class, 'getMonthlyStats'])->name('stats.monthly');
    });
});

/*
|--------------------------------------------------------------------------
| Dashboard Builder API Routes
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| API Routes for Notifications
|--------------------------------------------------------------------------
*/

// API routes with token authentication
Route::middleware(['auth:sanctum'])->prefix('notifications')->name('api.notifications.')->group(function () {
    Route::get('/unread-count', [NotificationController::class, 'getUnreadCount'])->name('unread-count');
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::post('/{notification}/read', [NotificationController::class, 'markAsRead'])->name('mark-read');
    Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
    Route::get('/preferences', [NotificationController::class, 'preferences'])->name('preferences');
    Route::post('/preferences', [NotificationController::class, 'updatePreferences'])->name('preferences.update');
});

// Fallback API routes with session authentication for web interface
Route::middleware(['auth', 'web'])->prefix('api/notifications')->name('api.notifications.web.')->group(function () {
    Route::get('/unread-count', [NotificationController::class, 'getUnreadCount'])->name('unread-count');
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::post('/{notification}/read', [NotificationController::class, 'markAsRead'])->name('mark-read');
    Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
});