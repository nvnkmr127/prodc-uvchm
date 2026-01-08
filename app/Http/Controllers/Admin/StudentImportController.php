<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\StudentsImport;
use App\Exports\StudentsSampleExport;
use App\Models\ImportLog;
use App\Models\Batch;
use Maatwebsite\Excel\Validators\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\ComponentPaymentService;
use App\Services\SecureFileValidator;

class StudentImportController extends Controller
{
    protected $componentPaymentService;

    public function __construct(ComponentPaymentService $componentPaymentService)
    {
        $this->middleware('permission:manage students');
        $this->componentPaymentService = $componentPaymentService;
    }
    public function create(): View
    {
        $batches = Batch::with('course')->orderBy('name')->get();
        $recentImports = ImportLog::with(['batch', 'user'])
            ->latest()
            ->limit(10)
            ->get();

        // The view should be updated to show "Auto Create Fee Components"
        return view('admin.students.import', compact('batches', 'recentImports'));
    }

    /**
     * Process the import with fee component creation options
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'import_file' => ['required', 'file', 'max:5120'], // Increased to 5MB to match SecureFileValidator
            'batch_id' => ['required', 'exists:batches,id'],
            // ✅ CHANGED: from auto_create_invoices to auto_create_fee_components
            'auto_create_fee_components' => ['nullable', 'boolean'],
            'import_settings' => ['nullable', 'array'],
        ]);

        // Enhanced file validation using SecureFileValidator
        $fileValidator = new SecureFileValidator();
        $validationResult = $fileValidator->validateFile($request->file('import_file'), ['xlsx', 'xls', 'csv']);

        if (!$validationResult['valid']) {
            return redirect()->back()
                ->withInput()
                ->with('error', $validationResult['error']);
        }

        $batch = Batch::with('course')->findOrFail($request->batch_id);
        // ✅ CHANGED: variable name to reflect component-based system
        $autoCreateFeeComponents = $request->boolean('auto_create_fee_components', true);

        // ✅ CHANGED: Updated logging to be component-specific
        Log::info('Import started with fee component options:', [
            'batch_id' => $request->batch_id,
            'batch_name' => $batch->name,
            'auto_create_fee_components' => $autoCreateFeeComponents,
            'file_original_name' => $request->file('import_file')->getClientOriginalName(),
            'file_size' => $request->file('import_file')->getSize(),
            'user_id' => auth()->id()
        ]);

        // Start database transaction
        DB::beginTransaction();

        try {
            // ✅ CHANGED: Pass the new component flag to the importer
            $import = new StudentsImport($batch, $autoCreateFeeComponents);

            // Import the file
            Excel::import($import, $request->file('import_file'));

            // ✅ Complete the import log (the method inside StudentsImport will be updated)
            $import->completeImportLog();

            // Commit the transaction
            DB::commit();

            // ✅ Get comprehensive import statistics
            $summary = $import->getImportSummary();

            // ✅ CHANGED: Build a success message that mentions fee components
            $message = $this->buildImportSuccessMessage($summary, $batch, $autoCreateFeeComponents);

            Log::info('Import completed successfully:', [
                'import_summary' => $summary,
                'batch_name' => $batch->name,
                'auto_create_fee_components' => $autoCreateFeeComponents
            ]);

            // ✅ Store detailed results in session for display
            session()->flash('import_summary', $summary);
            if ($summary['rejected'] > 0) {
                session()->flash('rejected_students', $summary['rejected_details']);
            }
            // ✅ CHANGED: from invoice_errors to fee_component_errors
            if ($summary['fee_component_errors'] > 0) {
                session()->flash('fee_component_errors', $summary['fee_component_error_details']);
            }

            // ✅ Determine message type based on results
            $messageType = $this->determineMessageType($summary);

            return redirect()->route('admin.students.index')
                ->with($messageType, $message)
                ->with('import_log_id', $summary['import_log_id']); // For detailed view

        } catch (ValidationException $e) {
            DB::rollBack();

            // Log validation failures with import log
            if (isset($import)) {
                $import->completeImportLog();
                ImportLog::where('id', $import->getImportSummary()['import_log_id'])
                    ->update(['status' => 'failed']);
            }

            Log::error('Import validation failed:', [
                'failures' => $e->failures(),
                'batch_id' => $request->batch_id,
                'auto_create_fee_components' => $autoCreateFeeComponents
            ]);

            $errorMessages = [];
            foreach ($e->failures() as $failure) {
                $errorMessages[] = sprintf(
                    "Row %d: %s (Value: %s)",
                    $failure->row(),
                    implode(', ', $failure->errors()),
                    implode(', ', $failure->values())
                );
            }

            return redirect()->back()
                ->withInput()
                ->with('import_errors', array_slice($errorMessages, 0, 10))
                ->with('error', 'Import failed due to validation errors. Please check the format and try again.');

        } catch (\Exception $e) {
            DB::rollBack();

            // Mark import as failed
            if (isset($import)) {
                $import->completeImportLog();
                ImportLog::where('id', $import->getImportSummary()['import_log_id'])
                    ->update(['status' => 'failed']);
            }

            Log::error('Import failed with exception:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'batch_id' => $request->batch_id,
                'auto_create_fee_components' => $autoCreateFeeComponents,
                'file_name' => $request->file('import_file')->getClientOriginalName()
            ]);

            if (strpos($e->getMessage(), 'ZIP') !== false || strpos($e->getMessage(), 'format') !== false) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Invalid file format. Please upload a valid Excel (.xlsx, .xls) or CSV file.');
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Show detailed import log
     */
    public function showImportLog(ImportLog $importLog): View
    {
        $importLog->load(['batch.course', 'user', 'details.student']);

        return view('admin.students.import-log', compact('importLog'));
    }

    /**
     * List all import logs
     */
    public function importLogs(Request $request): View
    {
        $query = ImportLog::with(['batch.course', 'user']);

        // Apply filters
        if ($request->filled('batch_id')) {
            $query->where('batch_id', $request->batch_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $importLogs = $query->latest()->paginate(15);

        // Get filter options
        $batches = Batch::with('course')->orderBy('name')->get();
        $users = \App\Models\User::orderBy('name')->get();

        return view('admin.students.import-logs', compact('importLogs', 'batches', 'users'));
    }


    /**
     * Export import log details
     */
    public function exportImportLog(ImportLog $importLog)
    {
        // This would require a new Export class: App\Exports\ImportLogExport
        // For now, we'll assume it exists.
        $export = new \App\Exports\ImportLogExport($importLog);
        return Excel::download($export, "import_log_{$importLog->id}_{$importLog->created_at->format('Y-m-d')}.xlsx");
    }

    /**
     * Download sample Excel file
     */
    public function downloadSample(): BinaryFileResponse
    {
        try {
            return Excel::download(
                new StudentsSampleExport,
                'student_import_template.xlsx'
            );
        } catch (\Exception $e) {
            Log::error('Failed to download sample file:', [
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to download sample file. Please try again.');
        }
    }

    /**
     * ✅ CHANGED: Build comprehensive import success message with component terminology
     */
    private function buildImportSuccessMessage(array $summary, Batch $batch, bool $autoCreateFeeComponents): string
    {
        $message = "🎉 **Import Completed Successfully!**\n\n";
        $message .= "📊 **Import Summary:**\n";
        $message .= "✅ **Successfully Imported:** {$summary['imported']} students\n";

        if ($summary['skipped'] > 0) {
            $message .= "⚠️ **Skipped:** {$summary['skipped']} empty rows\n";
        }

        if ($summary['rejected'] > 0) {
            $message .= "❌ **Rejected:** {$summary['rejected']} students (validation issues)\n";
        }

        // ✅ Fee component creation summary
        if ($autoCreateFeeComponents) {
            $message .= "\n💰 **Fee Component Creation:**\n";
            $message .= "📄 **Fee Components Created:** {$summary['fee_components_created']}\n"; // updated key

            if ($summary['fee_component_errors'] > 0) { // updated key
                $message .= "⚠️ **Fee Creation Errors:** {$summary['fee_component_errors']} (can be retried)\n";
            }

            if ($summary['imported'] > 0) {
                $successRate = round(($summary['fee_components_created'] / $summary['imported']) * 100, 1);
                $message .= "📈 **Fee Creation Success Rate:** {$successRate}%\n";
            }
        } else {
            $message .= "\n💰 **Fee Components:** Not created (disabled in import settings)\n";
        }

        $message .= "\n📁 **Batch:** {$batch->name}\n";
        $message .= "📈 **Total Processed:** {$summary['total_processed']} rows\n";
        $message .= "🕒 **Import Log ID:** #{$summary['import_log_id']}";

        return $message;
    }

    /**
     * Determine message type based on results
     */
    private function determineMessageType(array $summary): string
    {
        if ($summary['rejected'] > 0 && $summary['imported'] === 0) {
            return 'error';
        } elseif ($summary['rejected'] > 0 || $summary['fee_component_errors'] > 0) { // updated key
            return 'warning';
        } else {
            return 'success';
        }
    }
}