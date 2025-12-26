<?php
// File: app/Http/Controllers/Attendance/AttendanceController.php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Services\Attendance\AttendanceService;
use App\Services\Attendance\ValidationService;
use App\Models\Attendance\Attendance;
use App\Models\Student;
use App\Models\Batch;
use App\Models\Subject;
use App\Http\Requests\Attendance\AttendanceRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{
    protected AttendanceService $attendanceService;
    protected ValidationService $validationService;

    // ✅ FIX 1: Proper constructor with service injection
    public function __construct(
        AttendanceService $attendanceService,
        ValidationService $validationService
    ) {
        $this->attendanceService = $attendanceService;
        $this->validationService = $validationService;
        
        // Apply permissions
        $this->middleware('permission:view attendance')->only(['index', 'show', 'getData', 'getStudentsByBatch', 'getAttendanceByDateAndBatch']);
        $this->middleware('permission:take attendance')->only(['create', 'store', 'quickMark']);
        $this->middleware('permission:edit attendance')->only(['edit', 'update']);
        $this->middleware('permission:delete attendance')->only(['destroy']);
        $this->middleware('permission:manage attendance')->only(['bulk', 'import', 'export', 'bulkDelete', 'bulkUpdateStatus', 'autoMarkAbsent']);
    }

    /**
     * Display attendance listing
     */
    public function index(Request $request): View
    {
        $filters = $this->getFilters($request);
        
        $query = Attendance::with(['student', 'batch', 'subject', 'faculty'])
            ->orderBy('attendance_date', 'desc');

        // Apply filters
        $this->applyFilters($query, $filters);

        $attendances = $query->paginate(50);
        
        // Get filter options
        $batches = Batch::with('course')->orderBy('name')->get();
        $subjects = Subject::orderBy('name')->get();
        
        return view('attendance.index', compact('attendances', 'batches', 'subjects', 'filters'));
    }

    /**
     * Show attendance taking form
     */
    public function create(Request $request): View
    {
        $batchId = $request->get('batch_id');
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));
        
        $batches = Batch::with('course')->orderBy('name')->get();
        $batch = $batchId ? Batch::with(['students' => function($query) {
            $query->orderBy('name');
        }])->find($batchId) : null;
        
        $subjects = Subject::orderBy('name')->get();
        
        // Get existing attendance for the selected batch and date
        $existingAttendance = [];
        if ($batch && $date) {
            $existingAttendance = Attendance::where('batch_id', $batchId)
                ->whereDate('attendance_date', $date)
                ->pluck('status', 'student_id')
                ->toArray();
        }
        
        return view('attendance.create', compact(
            'batches', 
            'batch', 
            'subjects', 
            'date', 
            'existingAttendance'
        ));
    }

    /**
     * Store new attendance
     */
    public function store(AttendanceRequest $request): JsonResponse
    {
        try {
            $result = $this->attendanceService->markBulkAttendance($request->validated());
            
            return response()->json([
                'success' => true,
                'message' => 'Attendance saved successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to store attendance', [
                'error' => $e->getMessage(),
                'request_data' => $request->validated()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to save attendance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show specific attendance record
     */
    public function show(Attendance $attendance): View
    {
        $attendance->load(['student', 'batch', 'subject', 'faculty']);
        
        return view('attendance.show', compact('attendance'));
    }

    /**
     * Show edit form for attendance
     */
    public function edit(Attendance $attendance): View
    {
        $attendance->load(['student', 'batch', 'subject']);
        $batches = Batch::with('course')->orderBy('name')->get();
        $subjects = Subject::orderBy('name')->get();
        
        return view('attendance.edit', compact('attendance', 'batches', 'subjects'));
    }

    /**
     * Update attendance record
     */
    public function update(AttendanceRequest $request, Attendance $attendance): JsonResponse
    {
        try {
            $updated = $this->attendanceService->updateAttendance($attendance, $request->validated());
            
            return response()->json([
                'success' => true,
                'message' => 'Attendance updated successfully',
                'data' => $updated
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update attendance', [
                'attendance_id' => $attendance->id,
                'error' => $e->getMessage(),
                'request_data' => $request->validated()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update attendance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete attendance record
     */
    public function destroy(Attendance $attendance): JsonResponse
    {
        try {
            $this->attendanceService->deleteAttendance($attendance);
            
            return response()->json([
                'success' => true,
                'message' => 'Attendance deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete attendance', [
                'attendance_id' => $attendance->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete attendance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export attendance records
     */
    public function export(Request $request)
    {
        $filters = $this->getFilters($request);
        $selectedIds = $request->get('ids', []);
        
        try {
            if (!empty($selectedIds)) {
                // Export selected records
                $attendances = Attendance::with(['student', 'batch', 'subject', 'faculty'])
                    ->whereIn('id', $selectedIds)
                    ->get();
            } else {
                // Export with filters
                $query = Attendance::with(['student', 'batch', 'subject', 'faculty'])
                    ->orderBy('attendance_date', 'desc');
                
                $this->applyFilters($query, $filters);
                $attendances = $query->get();
            }
            
            // Check if Excel class exists
            if (class_exists('\Maatwebsite\Excel\Facades\Excel') && class_exists('\App\Exports\AttendanceExport')) {
                return \Maatwebsite\Excel\Facades\Excel::download(
                    new \App\Exports\AttendanceExport($attendances), 
                    'attendance_records_' . now()->format('Y_m_d_H_i_s') . '.xlsx'
                );
            } else {
                // Fallback to CSV export
                return $this->exportToCsv($attendances);
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to export attendance', [
                'error' => $e->getMessage(),
                'filters' => $filters,
                'selected_ids' => $selectedIds
            ]);
            
            return back()->with('error', 'Failed to export attendance: ' . $e->getMessage());
        }
    }

    /**
     * Get students by batch (AJAX endpoint)
     */
    public function getStudentsByBatch(Batch $batch): JsonResponse
    {
        try {
            $students = $batch->students()->orderBy('name')->get(['id', 'name', 'enrollment_number']);
            
            return response()->json([
                'success' => true,
                'data' => $students
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch students: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get attendance by date and batch (AJAX endpoint)
     */
    public function getAttendanceByDateAndBatch(string $date, Batch $batch): JsonResponse
    {
        try {
            $attendances = Attendance::with(['student'])
                ->where('batch_id', $batch->id)
                ->whereDate('attendance_date', $date)
                ->get()
                ->keyBy('student_id');
            
            return response()->json([
                'success' => true,
                'data' => $attendances
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch attendance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Quick mark attendance (AJAX endpoint)
     */
    public function quickMark(Request $request): JsonResponse
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'status' => 'required|in:present,absent,late,excused',
            'date' => 'required|date',
            'batch_id' => 'required|exists:batches,id'
        ]);
        
        try {
            $attendance = $this->attendanceService->markAttendance([
                'student_id' => $request->student_id,
                'batch_id' => $request->batch_id,
                'status' => $request->status,
                'attendance_date' => $request->date,
                'marked_by' => auth()->id()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Attendance marked successfully',
                'data' => $attendance
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to quick mark attendance', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark attendance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk operations
     */
    public function bulk(Request $request): JsonResponse
    {
        $action = $request->get('action');
        $attendanceIds = $request->get('attendance_ids', []);

        if (empty($attendanceIds)) {
            return response()->json([
                'success' => false,
                'message' => 'No attendance records selected'
            ], 422);
        }

        try {
            switch ($action) {
                case 'delete':
                    return $this->performBulkDelete($attendanceIds, $request->get('reason'));
                case 'mark_present':
                    return $this->performBulkUpdateStatus($attendanceIds, 'present');
                case 'mark_absent':
                    return $this->performBulkUpdateStatus($attendanceIds, 'absent');
                case 'mark_late':
                    return $this->performBulkUpdateStatus($attendanceIds, 'late');
                case 'mark_excused':
                    return $this->performBulkUpdateStatus($attendanceIds, 'excused');
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid bulk action'
                    ], 422);
            }
        } catch (\Exception $e) {
            Log::error('Bulk operation failed', [
                'action' => $action,
                'attendance_ids' => $attendanceIds,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Bulk operation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk delete attendance records (separate method to avoid conflict)
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'attendance_ids' => 'required|array',
            'attendance_ids.*' => 'exists:attendances,id',
            'reason' => 'nullable|string|max:255'
        ]);
        
        return $this->performBulkDelete($request->attendance_ids, $request->reason);
    }

    /**
     * Bulk update attendance status (separate method to avoid conflict)
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $request->validate([
            'attendance_ids' => 'required|array',
            'attendance_ids.*' => 'exists:attendances,id',
            'status' => 'required|in:present,absent,late,excused'
        ]);
        
        return $this->performBulkUpdateStatus($request->attendance_ids, $request->status);
    }

    /**
     * Auto-mark absent students
     */
    public function autoMarkAbsent(Request $request): JsonResponse
    {
        try {
            $date = $request->get('date') ? Carbon::parse($request->get('date')) : Carbon::today();
            $results = $this->attendanceService->autoMarkAbsentStudents($date);

            return response()->json([
                'success' => true,
                'message' => "Auto-marked {$results['marked']} students as absent",
                'data' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('Auto-mark absent failed', [
                'date' => $request->get('date'),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Auto-mark failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get attendance data for AJAX requests
     */
    public function getData(Request $request): JsonResponse
    {
        $batchId = $request->get('batch_id');
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));

        if (!$batchId) {
            return response()->json([
                'success' => false,
                'message' => 'Batch ID is required'
            ], 422);
        }

        try {
            $batch = Batch::with('students')->findOrFail($batchId);
            $summary = $this->attendanceService->getBatchSummary($batchId, Carbon::parse($date));

            return response()->json([
                'success' => true,
                'data' => [
                    'batch' => $batch,
                    'summary' => $summary,
                    'students' => $batch->students->map(function ($student) use ($date) {
                        $attendance = Attendance::where('student_id', $student->id)
                            ->whereDate('attendance_date', $date)
                            ->first();
                        
                        return [
                            'id' => $student->id,
                            'name' => $student->name,
                            'enrollment_number' => $student->enrollment_number,
                            'status' => $attendance->status ?? null,
                            'attendance_id' => $attendance->id ?? null
                        ];
                    })
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch attendance data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get filters from request
     */
    private function getFilters(Request $request): array
    {
        return [
            'batch_id' => $request->get('batch_id'),
            'subject_id' => $request->get('subject_id'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'status' => $request->get('status'),
            'student_name' => $request->get('student_name'),
        ];
    }

    /**
     * Apply filters to query
     */
    private function applyFilters($query, array $filters): void
    {
        if (!empty($filters['batch_id'])) {
            $query->where('batch_id', $filters['batch_id']);
        }

        if (!empty($filters['subject_id'])) {
            $query->where('subject_id', $filters['subject_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('attendance_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('attendance_date', '<=', $filters['date_to']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['student_name'])) {
            $query->whereHas('student', function($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['student_name'] . '%');
            });
        }
    }

    /**
     * ✅ FIX: Perform bulk delete operation (renamed to avoid method name conflict)
     */
    private function performBulkDelete(array $attendanceIds, ?string $reason): JsonResponse
    {
        try {
            $deletedCount = Attendance::whereIn('id', $attendanceIds)->count();
            
            // Log the deletion with reason
            Log::info('Bulk attendance deletion', [
                'attendance_ids' => $attendanceIds,
                'reason' => $reason,
                'deleted_by' => auth()->id(),
                'count' => $deletedCount
            ]);
            
            // Perform the deletion
            Attendance::whereIn('id', $attendanceIds)->delete();
            
            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$deletedCount} attendance records",
                'data' => [
                    'deleted_count' => $deletedCount,
                    'reason' => $reason
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk delete failed', [
                'attendance_ids' => $attendanceIds,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete attendance records: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ FIX: Perform bulk status update operation (renamed to avoid method name conflict)
     */
    private function performBulkUpdateStatus(array $attendanceIds, string $status): JsonResponse
    {
        try {
            $updatedCount = Attendance::whereIn('id', $attendanceIds)
                ->update([
                    'status' => $status,
                    'updated_by' => auth()->id(),
                    'updated_at' => now()
                ]);
            
            Log::info('Bulk attendance status update', [
                'attendance_ids' => $attendanceIds,
                'new_status' => $status,
                'updated_by' => auth()->id(),
                'count' => $updatedCount
            ]);
            
            return response()->json([
                'success' => true,
                'message' => "Successfully updated {$updatedCount} attendance records to {$status}",
                'data' => [
                    'updated_count' => $updatedCount,
                    'new_status' => $status
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk status update failed', [
                'attendance_ids' => $attendanceIds,
                'status' => $status,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update attendance status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ FIX: Export to CSV fallback when Excel package is not available
     */
    private function exportToCsv($attendances)
    {
        $filename = 'attendance_records_' . now()->format('Y_m_d_H_i_s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($attendances) {
            $file = fopen('php://output', 'w');
            
            // Add CSV headers
            fputcsv($file, [
                'Date',
                'Student Name',
                'Enrollment Number',
                'Batch',
                'Subject',
                'Status',
                'Check-in Time',
                'Marked By'
            ]);

            // Add data rows
            foreach ($attendances as $attendance) {
                fputcsv($file, [
                    $attendance->attendance_date,
                    $attendance->student->name ?? 'N/A',
                    $attendance->student->enrollment_number ?? 'N/A',
                    $attendance->batch->name ?? 'N/A',
                    $attendance->subject->name ?? 'N/A',
                    ucfirst($attendance->status),
                    $attendance->check_in_time ?? 'N/A',
                    $attendance->markedBy->name ?? 'System'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}