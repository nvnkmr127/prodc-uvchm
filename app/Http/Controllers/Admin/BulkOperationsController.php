<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class BulkOperationsController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle bulk student operations
     */
    public function handleBulkStudentOperation(Request $request)
    {
        $request->validate([
            'operation' => 'required|string|in:activate,deactivate,delete,export',
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
        ]);

        $studentCount = count($request->student_ids);
        $operation = $request->operation;

        try {
            // Perform the bulk operation
            switch ($operation) {
                case 'activate':
                    \App\Models\Student::whereIn('id', $request->student_ids)->update(['status' => 'active']);
                    break;
                case 'deactivate':
                    \App\Models\Student::whereIn('id', $request->student_ids)->update(['status' => 'inactive']);
                    break;
                case 'delete':
                    \App\Models\Student::whereIn('id', $request->student_ids)->delete();
                    break;
                case 'export':
                    // Handle export logic
                    break;
            }

            // 🔔 NOTIFY ADMINS OF BULK OPERATION
            $this->notificationService->send([
                'title' => 'Bulk Student Operation Completed',
                'message' => "Bulk {$operation} operation completed for {$studentCount} students",
                'type' => 'success',
                'category' => 'system',
                'priority' => $operation === 'delete' ? 'high' : 'normal',
                'roles' => ['super-admin', 'college-admin'],
                'data' => [
                    'operation' => $operation,
                    'student_count' => $studentCount,
                    'performed_by' => auth()->user()->name,
                    'timestamp' => now()->toISOString(),
                ]
            ]);

            return response()->json(['success' => true, 'message' => "Bulk {$operation} completed successfully"]);

        } catch (\Exception $e) {
            // 🔔 NOTIFY OF BULK OPERATION FAILURE
            $this->notificationService->sendSystemAlert(
                "Bulk student operation failed: {$e->getMessage()}",
                'high',
                [
                    'operation' => $operation,
                    'student_count' => $studentCount,
                    'error' => $e->getMessage(),
                    'performed_by' => auth()->user()->name,
                ]
            );

            return response()->json(['success' => false, 'message' => 'Bulk operation failed: ' . $e->getMessage()], 500);
        }
    }
}