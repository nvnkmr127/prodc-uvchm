<?php

namespace App\Services\Attendance;

use App\Models\Attendance\Attendance;
use App\Models\Student;
use App\Models\Batch;
use App\Services\Attendance\ValidationService;
use App\Services\Attendance\NotificationService;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AttendanceExport;
use App\Imports\AttendancesImport;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ImportExportService
{
    protected ValidationService $validationService;
    protected NotificationService $notificationService;
    
    public function __construct(
        ValidationService $validationService,
        NotificationService $notificationService
    ) {
        $this->validationService = $validationService;
        $this->notificationService = $notificationService;
    }

    /**
     * Export attendance data to Excel format
     */
    public function exportAttendanceToExcel(array $filters = []): array
    {
        try {
            $filename = $this->generateExportFilename('attendance_export', 'xlsx', $filters);
            $filepath = 'exports/' . $filename;
            
            // Get attendance data with filters
            $attendanceData = $this->getAttendanceData($filters);
            
            // Create Excel export
            Excel::store(new AttendanceExport($attendanceData, $filters), $filepath, 'public');
            
            Log::info('Attendance exported to Excel', [
                'filename' => $filename,
                'records_count' => count($attendanceData),
                'filters' => $filters
            ]);
            
            return [
                'success' => true,
                'filename' => $filename,
                'path' => $filepath,
                'download_url' => Storage::url($filepath),
                'records_count' => count($attendanceData),
                'file_size' => Storage::size('public/' . $filepath)
            ];
            
        } catch (\Exception $e) {
            Log::error('Excel export failed', [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);
            
            return [
                'success' => false,
                'error' => 'Failed to export to Excel: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Export attendance data to CSV format
     */
    public function exportAttendanceToCsv(array $filters = []): array
    {
        try {
            $filename = $this->generateExportFilename('attendance_export', 'csv', $filters);
            $filepath = 'exports/' . $filename;
            
            // Get attendance data
            $attendanceData = $this->getAttendanceData($filters);
            
            // Generate CSV content
            $csvContent = $this->generateCsvContent($attendanceData);
            
            // Store CSV file
            Storage::put('public/' . $filepath, $csvContent);
            
            Log::info('Attendance exported to CSV', [
                'filename' => $filename,
                'records_count' => count($attendanceData)
            ]);
            
            return [
                'success' => true,
                'filename' => $filename,
                'path' => $filepath,
                'download_url' => Storage::url($filepath),
                'records_count' => count($attendanceData),
                'file_size' => Storage::size('public/' . $filepath)
            ];
            
        } catch (\Exception $e) {
            Log::error('CSV export failed', [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);
            
            return [
                'success' => false,
                'error' => 'Failed to export to CSV: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Export attendance data to PDF format
     */
    public function exportAttendanceToPdf(array $filters = []): array
    {
        try {
            $filename = $this->generateExportFilename('attendance_report', 'pdf', $filters);
            $filepath = 'exports/' . $filename;
            
            // Get attendance data
            $attendanceData = $this->getAttendanceData($filters);
            
            // Generate PDF content
            $pdf = Pdf::loadView('attendance.exports.pdf', [
                'attendances' => $attendanceData,
                'filters' => $filters,
                'generated_at' => now(),
                'summary' => $this->generateSummaryStats($attendanceData)
            ]);
            
            // Store PDF file
            Storage::put('public/' . $filepath, $pdf->output());
            
            Log::info('Attendance exported to PDF', [
                'filename' => $filename,
                'records_count' => count($attendanceData)
            ]);
            
            return [
                'success' => true,
                'filename' => $filename,
                'path' => $filepath,
                'download_url' => Storage::url($filepath),
                'records_count' => count($attendanceData),
                'file_size' => Storage::size('public/' . $filepath)
            ];
            
        } catch (\Exception $e) {
            Log::error('PDF export failed', [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);
            
            return [
                'success' => false,
                'error' => 'Failed to export to PDF: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Import attendance data from uploaded file
     */
    public function importAttendanceFromFile(UploadedFile $file, array $options = []): array
    {
        try {
            // Validate file
            $fileValidation = $this->validateImportFile($file);
            if (!$fileValidation['valid']) {
                return [
                    'success' => false,
                    'error' => $fileValidation['error']
                ];
            }
            
            // Store file temporarily
            $tempPath = $file->store('temp', 'local');
            
            // Create import instance
            $import = new AttendancesImport();
            
            // Perform import
            Excel::import($import, storage_path('app/' . $tempPath));
            
            // Get import results
            $results = $import->getImportSummary();
            
            // Clean up temp file
            Storage::disk('local')->delete($tempPath);
            
            // Send notification about import completion
            $this->notifyImportCompletion($results, $options);
            
            Log::info('Attendance import completed', [
                'filename' => $file->getClientOriginalName(),
                'results' => $results
            ]);
            
            return [
                'success' => true,
                'results' => $results,
                'message' => $this->generateImportMessage($results)
            ];
            
        } catch (\Exception $e) {
            Log::error('Import failed', [
                'filename' => $file->getClientOriginalName(),
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Import failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validate import file
     */
    public function validateImportFile(UploadedFile $file): array
    {
        $maxSize = config('attendance.import_export.max_import_size', 10000) * 1024; // Convert to bytes
        $allowedFormats = config('attendance.import_export.allowed_import_formats', ['csv', 'xlsx']);
        
        // Check file size
        if ($file->getSize() > $maxSize) {
            return [
                'valid' => false,
                'error' => 'File size exceeds maximum allowed size of ' . ($maxSize / 1024 / 1024) . 'MB'
            ];
        }
        
        // Check file extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $allowedFormats)) {
            return [
                'valid' => false,
                'error' => 'Invalid file format. Allowed formats: ' . implode(', ', $allowedFormats)
            ];
        }
        
        // Check MIME type
        $allowedMimes = [
            'text/csv',
            'application/csv',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];
        
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            return [
                'valid' => false,
                'error' => 'Invalid file type detected'
            ];
        }
        
        return ['valid' => true];
    }

    /**
     * Get sample import template
     */
    public function generateImportTemplate(): array
    {
        try {
            $filename = 'attendance_import_template.xlsx';
            $filepath = 'templates/' . $filename;
            
            // Sample data for template
            $sampleData = [
                [
                    'enrollment_number' => 'STU001',
                    'attendance_date' => '2024-01-15',
                    'status' => 'present',
                    'notes' => 'On time',
                    'late_minutes' => ''
                ],
                [
                    'enrollment_number' => 'STU002',
                    'attendance_date' => '2024-01-15',
                    'status' => 'late',
                    'notes' => 'Traffic delay',
                    'late_minutes' => '10'
                ],
                [
                    'enrollment_number' => 'STU003',
                    'attendance_date' => '2024-01-15',
                    'status' => 'absent',
                    'notes' => 'Sick leave',
                    'late_minutes' => ''
                ]
            ];
            
            // Create template with instructions
            $templateData = [
                'instructions' => [
                    'Required Columns: enrollment_number, attendance_date, status',
                    'Status Options: present, absent, late, excused',
                    'Date Format: YYYY-MM-DD (e.g., 2024-01-15)',
                    'Notes: Optional field for additional information',
                    'Late Minutes: Required only when status is "late"'
                ],
                'sample_data' => $sampleData
            ];
            
            Excel::store(new \App\Exports\AttendanceTemplateExport($templateData), $filepath, 'public');
            
            return [
                'success' => true,
                'filename' => $filename,
                'path' => $filepath,
                'download_url' => Storage::url($filepath)
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to generate template: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Bulk export for multiple batches
     */
    public function bulkExportByBatches(array $batchIds, string $format = 'excel'): array
    {
        try {
            $exports = [];
            
            foreach ($batchIds as $batchId) {
                $batch = Batch::find($batchId);
                if (!$batch) continue;
                
                $filters = ['batch_id' => $batchId];
                
                switch ($format) {
                    case 'excel':
                        $result = $this->exportAttendanceToExcel($filters);
                        break;
                    case 'csv':
                        $result = $this->exportAttendanceToCsv($filters);
                        break;
                    case 'pdf':
                        $result = $this->exportAttendanceToPdf($filters);
                        break;
                    default:
                        throw new \InvalidArgumentException("Unsupported format: {$format}");
                }
                
                if ($result['success']) {
                    $exports[] = [
                        'batch_id' => $batchId,
                        'batch_name' => $batch->name,
                        'result' => $result
                    ];
                }
            }
            
            return [
                'success' => true,
                'exports' => $exports,
                'total_files' => count($exports)
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Bulk export failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Private helper methods
     */
    private function getAttendanceData(array $filters): array
    {
        $query = Attendance::with(['student.user', 'batch', 'subject', 'faculty'])
            ->orderBy('attendance_date', 'desc')
            ->orderBy('created_at', 'desc');
        
        // Apply filters
        if (isset($filters['date_from'])) {
            $query->where('attendance_date', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->where('attendance_date', '<=', $filters['date_to']);
        }
        
        if (isset($filters['batch_id'])) {
            $query->where('batch_id', $filters['batch_id']);
        }
        
        if (isset($filters['student_id'])) {
            $query->where('student_id', $filters['student_id']);
        }
        
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        // Limit records for performance
        $limit = $filters['limit'] ?? config('attendance.import_export.export_chunk_size', 1000);
        
        return $query->limit($limit)->get()->toArray();
    }

    private function generateCsvContent(array $data): string
    {
        if (empty($data)) {
            return "No data available\n";
        }
        
        $csvContent = '';
        
        // Headers
        $headers = [
            'Date',
            'Student Name',
            'Enrollment Number',
            'Batch',
            'Status',
            'Marked At',
            'Marked By',
            'Notes'
        ];
        $csvContent .= '"' . implode('","', $headers) . '"' . "\n";
        
        // Data rows
        foreach ($data as $attendance) {
            $row = [
                $attendance['attendance_date'],
                $attendance['student']['name'] ?? 'Unknown',
                $attendance['student']['enrollment_number'] ?? 'N/A',
                $attendance['batch']['name'] ?? 'Unknown',
                ucfirst($attendance['status']),
                $attendance['marked_at'] ? Carbon::parse($attendance['marked_at'])->format('Y-m-d H:i:s') : 'N/A',
                $attendance['faculty']['name'] ?? 'System',
                $attendance['notes'] ?? ''
            ];
            
            $csvContent .= '"' . implode('","', array_map('str_replace', array_fill(0, count($row), '"'), array_fill(0, count($row), '""'), $row)) . '"' . "\n";
        }
        
        return $csvContent;
    }

    private function generateSummaryStats(array $data): array
    {
        $total = count($data);
        $present = count(array_filter($data, fn($a) => in_array($a['status'], ['present', 'late'])));
        $absent = count(array_filter($data, fn($a) => $a['status'] === 'absent'));
        $late = count(array_filter($data, fn($a) => $a['status'] === 'late'));
        
        return [
            'total_records' => $total,
            'present_count' => $present,
            'absent_count' => $absent,
            'late_count' => $late,
            'attendance_percentage' => $total > 0 ? round(($present / $total) * 100, 2) : 0
        ];
    }

    private function generateExportFilename(string $prefix, string $extension, array $filters): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filterString = '';
        
        if (isset($filters['batch_id'])) {
            $batch = Batch::find($filters['batch_id']);
            $filterString .= '_' . ($batch ? str_replace(' ', '-', $batch->name) : 'batch-' . $filters['batch_id']);
        }
        
        if (isset($filters['date_from']) && isset($filters['date_to'])) {
            $from = Carbon::parse($filters['date_from'])->format('Y-m-d');
            $to = Carbon::parse($filters['date_to'])->format('Y-m-d');
            $filterString .= '_' . $from . '_to_' . $to;
        }
        
        return $prefix . $filterString . '_' . $timestamp . '.' . $extension;
    }

    private function generateImportMessage(array $results): string
    {
        $message = "Import completed successfully!\n";
        $message .= "• Imported: {$results['imported']} records\n";
        
        if ($results['skipped'] > 0) {
            $message .= "• Skipped: {$results['skipped']} records\n";
        }
        
        if ($results['rejected'] > 0) {
            $message .= "• Rejected: {$results['rejected']} records (validation errors)\n";
        }
        
        return $message;
    }

    private function notifyImportCompletion(array $results, array $options): void
    {
        $this->notificationService->send([
            'title' => 'Attendance Import Completed',
            'message' => $this->generateImportMessage($results),
            'type' => 'success',
            'category' => 'attendance',
            'priority' => 'normal',
            'roles' => ['super-admin', 'college-admin'],
            'data' => [
                'import_results' => $results,
                'imported_by' => auth()->user()->name ?? 'System',
                'timestamp' => now()->toISOString()
            ]
        ]);
    }
}