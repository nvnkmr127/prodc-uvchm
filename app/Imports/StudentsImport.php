<?php

namespace App\Imports;

use App\Models\Student;
use App\Models\Batch;
use App\Models\Setting;
use App\Models\ImportLog;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\Importable;
use Carbon\Carbon;

class StudentsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures, Importable;

    protected $batch;
    protected $collegePrefix;
    protected $processedEmails = [];
    protected $processedStudentMobiles = []; 
    protected $processedFatherMobiles = [];  
    
    protected $importedCount = 0;
    protected $skippedCount = 0;
    protected $rejectedCount = 0;
    protected $rejectedRows = [];
    protected $invoicesCreated = 0; // ✅ NEW: Track invoice creation
    protected $invoiceErrors = []; // ✅ NEW: Track invoice errors
    
    protected $importLogId; // ✅ NEW: Track import session
    protected $invoiceService; // ✅ NEW: Invoice service
    protected $autoCreateInvoices = true; // ✅ NEW: Auto invoice creation flag

    public function __construct(Batch $batch, $autoCreateInvoices = true)
    {
        $this->batch = $batch;
        $this->autoCreateInvoices = $autoCreateInvoices;
        $this->invoiceService = app(InvoiceService::class);
        
        $settings = Setting::all()->keyBy('key');
        $this->collegePrefix = $settings['enrollment_prefix']->value ?? 'UV';
        
        // ✅ NEW: Create import log entry
        $this->createImportLog();
    }

    public function model(array $row)
    {
        try {
            // Skip empty rows
            if ($this->isRowEmpty($row)) {
                $this->skippedCount++;
                $this->logRowProcessing($row, 'skipped', 'Empty row');
                return null;
            }

            // Clean and validate mobile numbers BEFORE processing
            $studentMobile = $this->cleanPhoneNumber($row['student_mobile'] ?? null);
            $fatherMobile = $this->cleanPhoneNumber($row['father_mobile'] ?? null);
            
            // Check for mobile number duplicates and REJECT if found
            $rejectionReason = $this->checkMobileDuplicates($studentMobile, $fatherMobile, $row);
            if ($rejectionReason) {
                $this->rejectedCount++;
                $this->rejectedRows[] = [
                    'row_data' => $row,
                    'reason' => $rejectionReason,
                    'student_name' => $row['full_name'] ?? $row['name'] ?? 'Unknown'
                ];
                
                $this->logRowProcessing($row, 'rejected', $rejectionReason);
                return null;
            }

            // Track mobile numbers as processed
            if ($studentMobile) {
                $this->processedStudentMobiles[] = $studentMobile;
            }
            if ($fatherMobile) {
                $this->processedFatherMobiles[] = $fatherMobile;
            }

            // Generate unique enrollment number
            $enrollmentNumber = $this->generateEnrollmentNumber();
            
            // Generate email if column exists
            $email = Schema::hasColumn('students', 'email') 
                ? $this->generateEmailFromName($row['full_name'] ?? $row['name'] ?? 'Student')
                : '';
            
            // Parse admission date
            $admissionDate = $this->parseAdmissionDate($row['admission_date'] ?? null);
            
            // Clean and validate gender
            $gender = $this->cleanGender($row['gender'] ?? 'Male');
            
            // Clean source field
            $source = $this->cleanSource($row['source'] ?? 'Call');
            
            // Check for duplicate enrollment numbers
            $maxAttempts = 100;
            $attempt = 0;
            
            do {
                if (Student::where('enrollment_number', $enrollmentNumber)->exists()) {
                    Log::warning('Duplicate enrollment number found, regenerating:', ['enrollment_number' => $enrollmentNumber]);
                    $enrollmentNumber = $this->generateEnrollmentNumber();
                    $attempt++;
                } else {
                    break;
                }
            } while ($attempt < $maxAttempts);
            
            // Handle email duplicates
            if ($email && Student::where('email', $email)->exists()) {
                Log::warning('Duplicate email found, regenerating:', ['email' => $email]);
                $email = $this->generateEmailFromName($row['full_name'] ?? $row['name'] ?? 'Student');
            }

            // Create student record
            $studentData = [
                'enrollment_number' => $enrollmentNumber,
                'name' => $row['full_name'] ?? $row['name'] ?? 'Unknown',
                'father_name' => $row['father_name'] ?? null,
                'student_mobile' => $studentMobile,
                'father_mobile' => $fatherMobile,
                'village' => $row['village'] ?? null,
                'admission_date' => $admissionDate,
                'gender' => $gender,
                'batch_id' => $this->batch->id,
                'status' => 'active',
                'source' => $source,
                'referral_name' => $row['referral_name'] ?? null,
            ];

            // Only add email if the column exists
            if (Schema::hasColumn('students', 'email')) {
                $studentData['email'] = $email;
            }

            $student = new Student($studentData);
            $student->save(); // Save immediately to get ID

            // ✅ NEW: Auto-create invoices if enabled
            if ($this->autoCreateInvoices && $student->id) {
                $this->createInvoiceForStudent($student, $row);
            }

            $this->importedCount++;
            $this->logRowProcessing($row, 'imported', 'Successfully imported', $student->id);
            
            return $student;

        } catch (\Exception $e) {
            $this->logRowProcessing($row, 'error', $e->getMessage());
            Log::error('Error processing student row:', [
                'row' => $row,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    // ✅ NEW: Create invoice for imported student
    private function createInvoiceForStudent(Student $student, array $rowData)
    {
        try {
            $this->invoiceService->generateTermInvoicesForStudent($student);
            $this->invoicesCreated++;
            
            Log::info('Invoice created for imported student:', [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'enrollment_number' => $student->enrollment_number,
                'batch_id' => $this->batch->id
            ]);
            
        } catch (\Exception $e) {
            $this->invoiceErrors[] = [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'enrollment_number' => $student->enrollment_number,
                'error' => $e->getMessage()
            ];
            
            Log::error('Failed to create invoice for imported student:', [
                'student_id' => $student->id,
                'error' => $e->getMessage(),
                'row_data' => $rowData
            ]);
        }
    }

    // ✅ NEW: Create import log entry
    private function createImportLog()
    {
        try {
            $importLog = ImportLog::create([
                'batch_id' => $this->batch->id,
                'batch_name' => $this->batch->name,
                'course_name' => $this->batch->course->name ?? 'Unknown',
                'user_id' => auth()->id(),
                'user_name' => auth()->user()->name ?? 'System',
                'import_type' => 'students_bulk_upload',
                'status' => 'processing',
                'auto_create_invoices' => $this->autoCreateInvoices,
                'started_at' => now(),
                'settings' => [
                    'auto_invoices' => $this->autoCreateInvoices,
                    'college_prefix' => $this->collegePrefix,
                    'batch_id' => $this->batch->id
                ]
            ]);
            
            $this->importLogId = $importLog->id;
            
        } catch (\Exception $e) {
            Log::error('Failed to create import log:', [
                'error' => $e->getMessage(),
                'batch_id' => $this->batch->id
            ]);
        }
    }

    // ✅ NEW: Log individual row processing
    private function logRowProcessing(array $rowData, string $status, string $message, $studentId = null)
    {
        try {
            if (!$this->importLogId) return;

            DB::table('import_log_details')->insert([
                'import_log_id' => $this->importLogId,
                'row_data' => json_encode($rowData),
                'student_id' => $studentId,
                'student_name' => $rowData['full_name'] ?? $rowData['name'] ?? 'Unknown',
                'status' => $status,
                'message' => $message,
                'processed_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to log row processing:', [
                'error' => $e->getMessage(),
                'status' => $status,
                'message' => $message
            ]);
        }
    }

    // ✅ NEW: Complete import log
    public function completeImportLog()
    {
        try {
            if (!$this->importLogId) return;

            ImportLog::where('id', $this->importLogId)->update([
                'status' => 'completed',
                'completed_at' => now(),
                'total_rows' => $this->importedCount + $this->skippedCount + $this->rejectedCount,
                'imported_count' => $this->importedCount,
                'skipped_count' => $this->skippedCount,
                'rejected_count' => $this->rejectedCount,
                'invoices_created' => $this->invoicesCreated,
                'invoice_errors_count' => count($this->invoiceErrors),
                'summary' => [
                    'imported' => $this->importedCount,
                    'skipped' => $this->skippedCount,
                    'rejected' => $this->rejectedCount,
                    'invoices_created' => $this->invoicesCreated,
                    'invoice_errors' => count($this->invoiceErrors),
                    'rejected_details' => $this->rejectedRows,
                    'invoice_error_details' => $this->invoiceErrors
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to complete import log:', [
                'error' => $e->getMessage(),
                'import_log_id' => $this->importLogId
            ]);
        }
    }

    // ✅ Enhanced: Get comprehensive import summary
    public function getImportSummary(): array
    {
        return [
            'imported' => $this->importedCount,
            'skipped' => $this->skippedCount,
            'rejected' => $this->rejectedCount,
            'total_processed' => $this->importedCount + $this->skippedCount + $this->rejectedCount,
            'invoices_created' => $this->invoicesCreated,
            'invoice_errors' => count($this->invoiceErrors),
            'rejected_details' => $this->rejectedRows,
            'invoice_error_details' => $this->invoiceErrors,
            'import_log_id' => $this->importLogId
        ];
    }

    // Existing methods (checkMobileDuplicates, rules, etc.) remain the same...
    private function checkMobileDuplicates(?string $studentMobile, ?string $fatherMobile, array $row): ?string
    {
        if ($studentMobile && $fatherMobile && $studentMobile === $fatherMobile) {
            return "Student mobile and father mobile cannot be the same ({$studentMobile})";
        }

        if ($studentMobile && Student::where('student_mobile', $studentMobile)->exists()) {
            $existingStudent = Student::where('student_mobile', $studentMobile)->first();
            return "Student mobile {$studentMobile} already exists for {$existingStudent->name} (ID: {$existingStudent->enrollment_number})";
        }

        if ($fatherMobile && Student::where('father_mobile', $fatherMobile)->exists()) {
            $existingStudent = Student::where('father_mobile', $fatherMobile)->first();
            return "Father mobile {$fatherMobile} already exists for {$existingStudent->name} (ID: {$existingStudent->enrollment_number})";
        }

        if ($studentMobile && in_array($studentMobile, $this->processedStudentMobiles)) {
            return "Student mobile {$studentMobile} appears multiple times in this import file";
        }

        if ($fatherMobile && in_array($fatherMobile, $this->processedFatherMobiles)) {
            return "Father mobile {$fatherMobile} appears multiple times in this import file";
        }

        if ($studentMobile && Student::where('father_mobile', $studentMobile)->exists()) {
            $existingStudent = Student::where('father_mobile', $studentMobile)->first();
            return "Student mobile {$studentMobile} is already registered as father mobile for {$existingStudent->name}";
        }

        if ($fatherMobile && Student::where('student_mobile', $fatherMobile)->exists()) {
            $existingStudent = Student::where('student_mobile', $fatherMobile)->first();
            return "Father mobile {$fatherMobile} is already registered as student mobile for {$existingStudent->name}";
        }

        if ($studentMobile && in_array($studentMobile, $this->processedFatherMobiles)) {
            return "Student mobile {$studentMobile} conflicts with father mobile in this import file";
        }

        if ($fatherMobile && in_array($fatherMobile, $this->processedStudentMobiles)) {
            return "Father mobile {$fatherMobile} conflicts with student mobile in this import file";
        }

        return null;
    }

    // ✅ Getter methods for tracking statistics
    public function getImportedCount(): int { return $this->importedCount; }
    public function getSkippedCount(): int { return $this->skippedCount; }
    public function getRejectedCount(): int { return $this->rejectedCount; }
    public function getRejectedRows(): array { return $this->rejectedRows; }
    public function getInvoicesCreated(): int { return $this->invoicesCreated; }
    public function getInvoiceErrors(): array { return $this->invoiceErrors; }

    public function rules(): array
    {
        return [
            'full_name' => 'required|string|max:255',
            'gender' => 'required|in:Male,Female,Other,male,female,other',
            'admission_date' => 'required',
            'student_mobile' => [
                'nullable',
                'max:15',
                'regex:/^[6-9]\d{9}$/'
            ],
            'father_mobile' => [
                'nullable',
                'max:15', 
                'regex:/^[6-9]\d{9}$/'
            ],
            'source' => 'nullable|string',
            'referral_name' => 'nullable|string|max:255',
        ];
    }

    // All other existing methods remain the same (isRowEmpty, cleanGender, cleanPhoneNumber, etc.)
    private function isRowEmpty(array $row): bool
    {
        $requiredFields = ['full_name', 'gender'];
        
        foreach ($requiredFields as $field) {
            if (!empty($row[$field])) {
                return false;
            }
        }
        
        return true;
    }

    private function cleanGender($gender): string
    {
        $gender = strtolower(trim($gender));
        
        switch ($gender) {
            case 'male':
            case 'm':
                return 'Male';
            case 'female':
            case 'f':
                return 'Female';
            case 'other':
            case 'o':
                return 'Other';
            default:
                return 'Male';
        }
    }

    private function cleanPhoneNumber($phone): ?string
    {
        if (empty($phone)) {
            return null;
        }
        
        $phone = (string) $phone;
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        if (strlen($phone) > 10) {
            $phone = substr($phone, -10);
        }
        
        if (strlen($phone) === 10 && preg_match('/^[6-9]\d{9}$/', $phone)) {
            return $phone;
        }
        
        return null;
    }

    private function cleanSource($source): string
    {
        if (empty($source) || strtolower($source) === 'null') {
            return 'Call';
        }
        
        $allowedSources = [
            'Call', 'Referrals', 'Online Ads', 'Social Media', 'School/College',
            'Flyers/Brochures', 'Website', 'Whatsapp', 'PRO', 'List', 
            'Student Referral', 'just dial'
        ];
        
        foreach ($allowedSources as $allowedSource) {
            if (strtolower($source) === strtolower($allowedSource)) {
                return $allowedSource;
            }
        }
        
        Log::warning('Unknown source provided, defaulting to Call:', ['source' => $source]);
        return 'Call';
    }

    private function generateEmailFromName($name): string
    {
        if (!Schema::hasColumn('students', 'email')) {
            return '';
        }
        
        if (empty($name)) {
            $name = 'student' . time() . rand(1000, 9999);
        }
        
        $baseEmail = strtolower(str_replace(' ', '.', $name));
        $baseEmail = preg_replace('/[^a-z0-9.]/', '', $baseEmail);
        $baseEmail = trim($baseEmail, '.');
        
        if (empty($baseEmail)) {
            $baseEmail = 'student' . time() . rand(1000, 9999);
        }
        
        $email = $baseEmail . '@example.com';
        
        $counter = 1;
        while (Student::where('email', $email)->exists() || in_array($email, $this->processedEmails)) {
            $email = $baseEmail . $counter . '@example.com';
            $counter++;
        }
        
        $this->processedEmails[] = $email;
        return $email;
    }

    private function generateEnrollmentNumber(): string
    {
        $currentYear = date('Y');
        $shortYear = substr($currentYear, -2);
        
        $coursePrefix = $this->getCoursePrefix();
        
        $maxAttempts = 100;
        $attempt = 0;
        
        do {
            $studentCount = Student::where('batch_id', $this->batch->id)->count() + $attempt + 1;
            $rollNumber = str_pad($studentCount, 3, '0', STR_PAD_LEFT);
            
            $enrollmentNumber = $this->collegePrefix . '-' . $coursePrefix . '-' . $shortYear . $rollNumber;
            
            $exists = Student::where('enrollment_number', $enrollmentNumber)->exists();
            $attempt++;
            
        } while ($exists && $attempt < $maxAttempts);
        
        if ($attempt >= $maxAttempts) {
            $enrollmentNumber = $this->collegePrefix . '-' . $coursePrefix . '-' . $shortYear . substr(time(), -3);
        }
        
        return $enrollmentNumber;
    }

    private function getCoursePrefix(): string
    {
        if ($this->batch && $this->batch->course) {
            if (!empty($this->batch->course->enrollment_prefix)) {
                return strtoupper($this->batch->course->enrollment_prefix);
            }
            
            if (!empty($this->batch->course->name)) {
                return strtoupper(substr($this->batch->course->name, 0, 4));
            }
        }
        
        return 'UNKN';
    }

    private function parseAdmissionDate($dateValue)
    {
        if (empty($dateValue)) {
            return now()->format('Y-m-d');
        }

        try {
            if (is_numeric($dateValue)) {
                $baseDate = Carbon::create(1900, 1, 1)->addDays($dateValue - 2);
                return $baseDate->format('Y-m-d');
            }

            if (is_string($dateValue)) {
                $formats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'm-d-Y'];
                
                foreach ($formats as $format) {
                    try {
                        $date = Carbon::createFromFormat($format, trim($dateValue));
                        return $date->format('Y-m-d');
                    } catch (\Exception $e) {
                        continue;
                    }
                }
                
                $date = Carbon::parse($dateValue);
                return $date->format('Y-m-d');
            }

            return now()->format('Y-m-d');
        } catch (\Exception $e) {
            Log::warning('Failed to parse admission date, using current date:', [
                'date_value' => $dateValue,
                'error' => $e->getMessage()
            ]);
            return now()->format('Y-m-d');
        }
    }
}