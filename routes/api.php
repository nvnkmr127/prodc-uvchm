<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\GlobalSearchController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Admin\{DashboardBuilderController, WidgetController};
use App\Http\Controllers\NotificationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public webhook routes (no authentication required)
Route::prefix('webhooks')->group(function () {
    Route::post('/biometric', [WebhookController::class, 'handleBiometric']);
    Route::post('/attendance', [AttendanceController::class, 'store']);
});

// Protected API routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
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
    
    // Student search endpoints
    Route::get('/students/search', [\App\Http\Controllers\Api\StudentController::class, 'search']);
    Route::get('/students/{student}', [\App\Http\Controllers\Api\StudentController::class, 'show']);
});

Route::middleware(['auth:sanctum'])->prefix('dashboard-builder')->name('dashboard-builder.')->group(function () {
    // Dashboard Management
    Route::get('roles', [App\Http\Controllers\Admin\DashboardBuilderController::class, 'getRoles']);
    Route::get('widget-categories', [App\Http\Controllers\Admin\DashboardBuilderController::class, 'getWidgetCategories']);
    Route::get('dashboards/{role}', [App\Http\Controllers\Admin\DashboardBuilderController::class, 'loadDashboard']);
    Route::post('dashboards/save', [App\Http\Controllers\Admin\DashboardBuilderController::class, 'saveDashboard']);
    Route::get('templates', [App\Http\Controllers\Admin\DashboardBuilderController::class, 'getTemplates']);
Route::post('templates', [App\Http\Controllers\Admin\DashboardBuilderController::class, 'saveTemplate']);
Route::post('templates/{template}/apply', [App\Http\Controllers\Admin\DashboardBuilderController::class, 'applyTemplate']);
Route::get('widgets/{widget}/config', [App\Http\Controllers\Admin\DashboardBuilderController::class, 'getWidgetConfig']);
Route::post('widgets/config', [App\Http\Controllers\Admin\DashboardBuilderController::class, 'updateWidgetConfig']);
    // Widget Management
    Route::post('widgets/add', [App\Http\Controllers\Admin\DashboardBuilderController::class, 'addWidget']);
    Route::delete('widgets/{instanceId}', [App\Http\Controllers\Admin\DashboardBuilderController::class, 'removeWidget']);
    Route::get('widgets/{widget}/data', [App\Http\Controllers\Admin\DashboardBuilderController::class, 'getWidgetData']);
});
/*
|--------------------------------------------------------------------------
| API Routes for Notifications
|--------------------------------------------------------------------------
*/

// Add these routes to your existing api.php file
Route::middleware(['auth:sanctum'])->prefix('notifications')->name('api.notifications.')->group(function () {
    // Get unread count - this is what your JavaScript is trying to call
   Route::get('/unread-count', [NotificationController::class, 'getUnreadCount']);
    
    // Get all notifications
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    
    // Mark specific notification as read
    Route::post('/{notification}/read', [NotificationController::class, 'markAsRead'])->name('mark-read');
    
    // Mark all notifications as read
    Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
    
    // Get notification preferences
    Route::get('/preferences', [NotificationController::class, 'preferences'])->name('preferences');
    
    // Update notification preferences
    Route::post('/preferences', [NotificationController::class, 'updatePreferences'])->name('preferences.update');
});

// Alternative: Add session-based auth routes if you're not using API tokens
Route::middleware(['auth', 'web'])->prefix('api/notifications')->name('api.notifications.web.')->group(function () {
    Route::get('/unread-count', [NotificationController::class, 'getUnreadCount']);
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::post('/{notification}/read', [NotificationController::class, 'markAsRead'])->name('mark-read');
    Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
});