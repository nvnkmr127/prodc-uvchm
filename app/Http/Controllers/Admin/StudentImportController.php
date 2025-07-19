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

class StudentImportController extends Controller
{
    /**
     * Show the import form with invoice creation options
     */
    public function create(): View
    {
        $batches = Batch::with('course')->orderBy('name')->get();
        $recentImports = ImportLog::with(['batch', 'user'])
                                 ->latest()
                                 ->limit(10)
                                 ->get();
        
        return view('admin.students.import', compact('batches', 'recentImports'));
    }

    /**
     * Process the import with invoice creation options
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'import_file' => ['required', 'mimes:xlsx,xls,csv', 'max:2048'],
            'batch_id' => ['required', 'exists:batches,id'],
            'auto_create_invoices' => ['nullable', 'boolean'], // ✅ NEW: Invoice creation option
            'import_settings' => ['nullable', 'array'], // ✅ NEW: Additional settings
        ]);

        $batch = Batch::with('course')->findOrFail($request->batch_id);
        $autoCreateInvoices = $request->boolean('auto_create_invoices', true); // ✅ Default to true
        
        Log::info('Import started with invoice options:', [
            'batch_id' => $request->batch_id,
            'batch_name' => $batch->name,
            'auto_create_invoices' => $autoCreateInvoices,
            'file_original_name' => $request->file('import_file')->getClientOriginalName(),
            'file_size' => $request->file('import_file')->getSize(),
            'user_id' => auth()->id()
        ]);

        // Start database transaction
        DB::beginTransaction();

        try {
            // ✅ Create import with invoice creation option
            $import = new StudentsImport($batch, $autoCreateInvoices);
            
            // Import the file
            Excel::import($import, $request->file('import_file'));
            
            // ✅ Complete the import log
            $import->completeImportLog();
            
            // Commit the transaction
            DB::commit();
            
            // ✅ Get comprehensive import statistics
            $summary = $import->getImportSummary();
            
            // ✅ Build detailed success message with invoice information
            $message = $this->buildImportSuccessMessage($summary, $batch, $autoCreateInvoices);
            
            Log::info('Import completed successfully:', [
                'import_summary' => $summary,
                'batch_name' => $batch->name,
                'auto_create_invoices' => $autoCreateInvoices
            ]);
            
            // ✅ Store detailed results in session for display
            session()->flash('import_summary', $summary);
            if ($summary['rejected'] > 0) {
                session()->flash('rejected_students', $summary['rejected_details']);
            }
            if ($summary['invoice_errors'] > 0) {
                session()->flash('invoice_errors', $summary['invoice_error_details']);
            }
            
            // ✅ Determine message type based on results
            $messageType = $this->determineMessageType($summary);
            
            return redirect()->route('admin.students.index')
                ->with($messageType, $message)
                ->with('import_log_id', $summary['import_log_id']); // ✅ For detailed view
                
        } catch (ValidationException $e) {
            DB::rollBack();
            
            // ✅ Log validation failures with import log
            if (isset($import)) {
                $import->completeImportLog();
                ImportLog::where('id', $import->getImportSummary()['import_log_id'])
                         ->update(['status' => 'failed']);
            }
            
            Log::error('Import validation failed:', [
                'failures' => $e->failures(),
                'batch_id' => $request->batch_id,
                'auto_create_invoices' => $autoCreateInvoices
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
            
            // ✅ Mark import as failed
            if (isset($import)) {
                $import->completeImportLog();
                ImportLog::where('id', $import->getImportSummary()['import_log_id'])
                         ->update(['status' => 'failed']);
            }
            
            Log::error('Import failed with exception:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'batch_id' => $request->batch_id,
                'auto_create_invoices' => $autoCreateInvoices,
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
     * ✅ NEW: Show detailed import log
     */
    public function showImportLog(ImportLog $importLog): View
    {
        $importLog->load(['batch.course', 'user', 'details.student']);
        
        return view('admin.students.import-log', compact('importLog'));
    }

    /**
     * ✅ NEW: List all import logs
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
     * ✅ NEW: Retry invoice creation for failed invoices
     */
    public function retryInvoiceCreation(ImportLog $importLog): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $invoiceService = app(\App\Services\InvoiceService::class);
            $successCount = 0;
            $errorCount = 0;
            $errors = [];

            // Get students that were imported but don't have invoices
            $importedStudents = $importLog->getImportedStudents();
            
            foreach ($importedStudents as $logDetail) {
                if ($logDetail->student && $logDetail->student->invoices()->count() === 0) {
                    try {
                        $invoiceService->generateTermInvoicesForStudent($logDetail->student);
                        $successCount++;
                    } catch (\Exception $e) {
                        $errorCount++;
                        $errors[] = "Failed for {$logDetail->student->name}: " . $e->getMessage();
                    }
                }
            }

            // Update import log
            $importLog->update([
                'invoices_created' => $importLog->invoices_created + $successCount,
                'invoice_errors_count' => $errorCount,
            ]);

            DB::commit();

            $message = "Invoice retry completed. Created: {$successCount} invoices.";
            if ($errorCount > 0) {
                $message .= " Errors: {$errorCount}.";
            }

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to retry invoice creation: ' . $e->getMessage());
        }
    }

    /**
     * ✅ NEW: Export import log details
     */
    public function exportImportLog(ImportLog $importLog)
    {
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
     * ✅ NEW: Build comprehensive import success message
     */
    private function buildImportSuccessMessage(array $summary, Batch $batch, bool $autoCreateInvoices): string
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

        // ✅ Invoice creation summary
        if ($autoCreateInvoices) {
            $message .= "\n💰 **Invoice Creation:**\n";
            $message .= "📄 **Invoices Created:** {$summary['invoices_created']}\n";
            
            if ($summary['invoice_errors'] > 0) {
                $message .= "⚠️ **Invoice Errors:** {$summary['invoice_errors']} (can be retried)\n";
            }
            
            if ($summary['imported'] > 0) {
                $invoiceSuccessRate = round(($summary['invoices_created'] / $summary['imported']) * 100, 1);
                $message .= "📈 **Invoice Success Rate:** {$invoiceSuccessRate}%\n";
            }
        } else {
            $message .= "\n💰 **Invoices:** Not created (disabled in import settings)\n";
        }
        
        $message .= "\n📁 **Batch:** {$batch->name}\n";
        $message .= "📈 **Total Processed:** {$summary['total_processed']} rows\n";
        $message .= "🕒 **Import Log ID:** #{$summary['import_log_id']}";
        
        return $message;
    }

    /**
     * ✅ NEW: Determine message type based on results
     */
    private function determineMessageType(array $summary): string
    {
        if ($summary['rejected'] > 0 && $summary['imported'] === 0) {
            return 'error';
        } elseif ($summary['rejected'] > 0 || $summary['invoice_errors'] > 0) {
            return 'warning';
        } else {
            return 'success';
        }
    }
}