<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Events\DashboardDataUpdated;

class DashboardWebhookController extends Controller
{
    public function __construct()
    {
        $this->middleware('throttle:100,1'); // 100 requests per minute
    }

    /**
     * Handle attendance update webhook
     */
    public function attendanceUpdate(Request $request)
    {
        try {
            $request->validate([
                'student_id' => 'required|integer',
                'class_id' => 'required|integer',
                'status' => 'required|in:present,absent,late',
                'date' => 'required|date',
                'timestamp' => 'required|string'
            ]);

            $data = $request->all();
            
            Log::info('Attendance webhook received', $data);

            // Process attendance update
            $this->processAttendanceUpdate($data);

            // Clear related cache
            $this->clearAttendanceCache($data['student_id'], $data['class_id']);

            // Broadcast update to relevant dashboards
            $this->broadcastAttendanceUpdate($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Attendance update processed',
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Attendance webhook failed: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'error' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process attendance update'
            ], 500);
        }
    }

    /**
     * Handle payment received webhook
     */
    public function paymentReceived(Request $request)
    {
        try {
            $request->validate([
                'student_id' => 'required|integer',
                'amount' => 'required|numeric',
                'payment_method' => 'required|string',
                'transaction_id' => 'required|string',
                'fee_type' => 'required|string',
                'timestamp' => 'required|string'
            ]);

            $data = $request->all();
            
            Log::info('Payment webhook received', $data);

            // Process payment
            $this->processPaymentReceived($data);

            // Clear financial cache
            $this->clearFinancialCache($data['student_id']);

            // Broadcast update
            $this->broadcastPaymentUpdate($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Payment processed',
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Payment webhook failed: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'error' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process payment'
            ], 500);
        }
    }

    /**
     * Handle enrollment update webhook
     */
    public function enrollmentUpdate(Request $request)
    {
        try {
            $request->validate([
                'student_id' => 'required|integer',
                'course_id' => 'required|integer',
                'status' => 'required|in:enrolled,withdrawn,transferred',
                'effective_date' => 'required|date',
                'timestamp' => 'required|string'
            ]);

            $data = $request->all();
            
            Log::info('Enrollment webhook received', $data);

            // Process enrollment update
            $this->processEnrollmentUpdate($data);

            // Clear enrollment cache
            $this->clearEnrollmentCache($data['student_id'], $data['course_id']);

            // Broadcast update
            $this->broadcastEnrollmentUpdate($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Enrollment update processed',
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Enrollment webhook failed: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'error' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process enrollment update'
            ], 500);
        }
    }

    /**
     * Handle system alert webhook
     */
    public function systemAlert(Request $request)
    {
        try {
            $request->validate([
                'alert_type' => 'required|in:info,warning,error,critical',
                'title' => 'required|string|max:255',
                'message' => 'required|string',
                'source' => 'required|string',
                'timestamp' => 'required|string'
            ]);

            $data = $request->all();
            
            Log::info('System alert webhook received', $data);

            // Process system alert
            $this->processSystemAlert($data);

            // Broadcast alert to relevant users
            $this->broadcastSystemAlert($data);

            return response()->json([
                'status' => 'success',
                'message' => 'System alert processed',
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('System alert webhook failed: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'error' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process system alert'
            ], 500);
        }
    }

    /**
     * Process attendance update
     */
    private function processAttendanceUpdate(array $data): void
    {
        // Update attendance record in database
        \App\Models\Attendance::updateOrCreate(
            [
                'student_id' => $data['student_id'],
                'class_id' => $data['class_id'],
                'date' => $data['date']
            ],
            [
                'status' => $data['status'],
                'updated_at' => now()
            ]
        );

        // Update attendance statistics
        $this->updateAttendanceStatistics($data['student_id'], $data['class_id']);
    }

    /**
     * Process payment received
     */
    private function processPaymentReceived(array $data): void
    {
        // Create payment record
        \App\Models\Payment::create([
            'student_id' => $data['student_id'],
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'],
            'transaction_id' => $data['transaction_id'],
            'fee_type' => $data['fee_type'],
            'status' => 'completed',
            'paid_at' => now()
        ]);

        // Update fee collection statistics
        $this->updateFeeCollectionStatistics($data['student_id']);
    }

    /**
     * Process enrollment update
     */
    private function processEnrollmentUpdate(array $data): void
    {
        // Update enrollment record
        \App\Models\Enrollment::updateOrCreate(
            [
                'student_id' => $data['student_id'],
                'course_id' => $data['course_id']
            ],
            [
                'status' => $data['status'],
                'effective_date' => $data['effective_date'],
                'updated_at' => now()
            ]
        );

        // Update enrollment statistics
        $this->updateEnrollmentStatistics($data['course_id']);
    }

    /**
     * Process system alert
     */
    private function processSystemAlert(array $data): void
    {
        // Create system alert record
        \App\Models\SystemAlert::create([
            'type' => $data['alert_type'],
            'title' => $data['title'],
            'message' => $data['message'],
            'source' => $data['source'],
            'status' => 'active',
            'created_at' => now()
        ]);

        // If critical, send immediate notifications
        if ($data['alert_type'] === 'critical') {
            $this->sendCriticalAlertNotifications($data);
        }
    }

    /**
     * Clear attendance cache
     */
    private function clearAttendanceCache(int $studentId, int $classId): void
    {
        $cacheKeys = [
            "attendance_student_{$studentId}",
            "attendance_class_{$classId}",
            "attendance_statistics_{$studentId}",
            "widget_attendance_analytics",
            "dashboard_attendance_data"
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Clear financial cache
     */
    private function clearFinancialCache(int $studentId): void
    {
        $cacheKeys = [
            "payments_student_{$studentId}",
            "fee_collection_statistics",
            "widget_revenue_chart",
            "widget_fee_collection_status",
            "dashboard_financial_data"
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Clear enrollment cache
     */
    private function clearEnrollmentCache(int $studentId, int $courseId): void
    {
        $cacheKeys = [
            "enrollment_student_{$studentId}",
            "enrollment_course_{$courseId}",
            "enrollment_statistics",
            "widget_total_students",
            "dashboard_academic_data"
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Broadcast attendance update
     */
    private function broadcastAttendanceUpdate(array $data): void
    {
        event(new DashboardDataUpdated('attendance', $data));
    }

    /**
     * Broadcast payment update
     */
    private function broadcastPaymentUpdate(array $data): void
    {
        event(new DashboardDataUpdated('payment', $data));
    }

    /**
     * Broadcast enrollment update
     */
    private function broadcastEnrollmentUpdate(array $data): void
    {
        event(new DashboardDataUpdated('enrollment', $data));
    }

    /**
     * Broadcast system alert
     */
    private function broadcastSystemAlert(array $data): void
    {
        event(new DashboardDataUpdated('system_alert', $data));
    }

    /**
     * Update attendance statistics
     */
    private function updateAttendanceStatistics(int $studentId, int $classId): void
    {
        // Recalculate attendance percentage for student
        $totalClasses = \App\Models\Attendance::where('student_id', $studentId)->count();
        $presentClasses = \App\Models\Attendance::where('student_id', $studentId)
            ->where('status', 'present')->count();
        
        $percentage = $totalClasses > 0 ? ($presentClasses / $totalClasses) * 100 : 0;
        
        // Update student record or cache
        Cache::put("attendance_percentage_{$studentId}", $percentage, 3600);
    }

    /**
     * Update fee collection statistics
     */
    private function updateFeeCollectionStatistics(int $studentId): void
    {
        // Recalculate fee collection statistics
        $totalDue = \App\Models\FeeStructure::sum('amount');
        $totalPaid = \App\Models\Payment::where('status', 'completed')->sum('amount');
        
        $percentage = $totalDue > 0 ? ($totalPaid / $totalDue) * 100 : 0;
        
        Cache::put('fee_collection_percentage', $percentage, 3600);
    }

    /**
     * Update enrollment statistics
     */
    private function updateEnrollmentStatistics(int $courseId): void
    {
        // Update course enrollment counts
        $activeEnrollments = \App\Models\Enrollment::where('course_id', $courseId)
            ->where('status', 'enrolled')->count();
        
        Cache::put("enrollment_count_course_{$courseId}", $activeEnrollments, 3600);
    }

    /**
     * Send critical alert notifications
     */
    private function sendCriticalAlertNotifications(array $data): void
    {
        // Get super admins
        $superAdmins = \App\Models\User::role('super-admin')->get();
        
        foreach ($superAdmins as $admin) {
            // Create notification
            $admin->notifications()->create([
                'type' => 'critical_alert',
                'title' => $data['title'],
                'message' => $data['message'],
                'data' => json_encode($data),
                'is_read' => false
            ]);
        }
    }
}