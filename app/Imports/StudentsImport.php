<?php

namespace App\Imports;

use App\Models\Student;
use App\Models\Batch;
use App\Models\Setting;
use App\Models\ImportLog;
use App\Models\Payment;
use App\Models\StudentFee;
use App\Models\FeeCategory;
use App\Services\ComponentPaymentService;

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
    protected $autoCreateFeeComponents;
    protected $componentPaymentService;
    protected $collegePrefix;
    protected $processedEmails = [];
    protected $processedStudentMobiles = []; 
    protected $processedFatherMobiles = [];  
    
    protected $importedCount = 0;
    protected $skippedCount = 0;
    protected $rejectedCount = 0;
    protected $rejectedRows = [];
    protected $feeComponentsCreated = 0;
    protected $feeComponentErrors = [];
    
    // 🆕 NEW: Financial data tracking
    protected $paymentsCreated = 0;
    protected $paymentErrors = [];
    
    protected $importLogId; 

    public function __construct(Batch $batch, $autoCreateFeeComponents = true)
    {
        $this->batch = $batch;
        $this->autoCreateFeeComponents = $autoCreateFeeComponents;
        $this->componentPaymentService = app(ComponentPaymentService::class);
        
        $settings = Setting::all()->keyBy('key');
        $this->collegePrefix = $settings['enrollment_prefix']->value ?? 'UV';
        
        $this->createImportLog();
    }

    public function model(array $row)
    {
        try {
            // 🔧 DEBUG: Log the incoming row data
            Log::info('=== PROCESSING ROW ===', [
                'row_data' => $row,
                'has_paid_amount' => isset($row['paid_amount']),
                'paid_amount_value' => $row['paid_amount'] ?? 'NOT_SET',
                'has_concession_amount' => isset($row['concession_amount']),
                'concession_amount_value' => $row['concession_amount'] ?? 'NOT_SET'
            ]);

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

            Log::info('Student created successfully:', [
                'student_id' => $student->id,
                'enrollment_number' => $student->enrollment_number,
                'name' => $student->name
            ]);

            // 🆕 ENHANCED: Auto-create fee components with financial data processing
            if ($this->autoCreateFeeComponents && $student->id) {
                // 🔧 DEBUG: Check if we're going to process financial data
                $hasPaidAmount = isset($row['paid_amount']) && floatval($row['paid_amount']) > 0;
                $hasConcessionAmount = isset($row['concession_amount']) && floatval($row['concession_amount']) > 0;
                
                Log::info('About to create fee components with financial data:', [
                    'student_id' => $student->id,
                    'has_paid_amount' => $hasPaidAmount,
                    'paid_amount' => $row['paid_amount'] ?? 'NOT_SET',
                    'has_concession_amount' => $hasConcessionAmount,
                    'concession_amount' => $row['concession_amount'] ?? 'NOT_SET',
                    'autoCreateFeeComponents' => $this->autoCreateFeeComponents
                ]);
                
                $this->createFeeComponentsWithFinancialData($student, $row);
            }

            $this->importedCount++;
            $this->logRowProcessing($row, 'imported', 'Successfully imported with financial data', $student->id);
            
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

    // 🆕 ENHANCED: Create fee components with financial data processing
private function createFeeComponentsWithFinancialData(Student $student, array $rowData)
{
    try {
        Log::info('=== STARTING FEE COMPONENT CREATION ===', [
            'student_id' => $student->id,
            'student_name' => $student->name,
            'batch_id' => $this->batch->id
        ]);

        // Get the batch's fee structure with fee categories
        $feeStructure = $student->batch->feeStructure;
        
        if (!$feeStructure) {
            Log::warning('No fee structure found, falling back to original method', [
                'student_id' => $student->id,
                'batch_id' => $this->batch->id
            ]);
            $this->createFeeComponentsForStudent($student, $rowData);
            return;
        }

        $feeStructure->load('feeCategories');
        
        Log::info('Fee structure loaded:', [
            'fee_structure_id' => $feeStructure->id,
            'fee_structure_name' => $feeStructure->name,
            'fee_categories_count' => $feeStructure->feeCategories->count()
        ]);

        $academicYear = date('Y') . '-' . (date('Y') + 1);
        $createdCount = 0;
        
        // 🆕 Extract financial data from Excel row
        $totalPaidAmount = floatval($rowData['paid_amount'] ?? 0);
        $totalConcessionAmount = floatval($rowData['concession_amount'] ?? 0);
        $uniformFeeFromExcel = $rowData['uniform_fee'] ?? null; // Keep as null if empty
        $paymentDate = $this->parsePaymentDate($rowData['payment_date'] ?? null);
        $paymentMethod = $rowData['payment_method'] ?? 'Cash';
        $paymentRemarks = $rowData['payment_remarks'] ?? 'Bulk import payment';

        Log::info('Financial data extracted from Excel:', [
            'total_paid_amount' => $totalPaidAmount,
            'total_concession_amount' => $totalConcessionAmount,
            'uniform_fee_from_excel' => $uniformFeeFromExcel,
            'uniform_fee_interpretation' => $this->interpretUniformFeeStatus($uniformFeeFromExcel),
            'payment_date' => $paymentDate ? $paymentDate->format('Y-m-d') : 'null',
            'payment_method' => $paymentMethod,
            'payment_remarks' => $paymentRemarks
        ]);

        // 1. CREATE FEE COMPONENTS WITH SMART UNIFORM HANDLING
        $studentFees = [];
        $tuitionFees = [];
        $uniformFees = [];
        $otherFees = [];
        $totalFeeAmount = 0;

        foreach ($feeStructure->feeCategories as $category) {
            Log::info('Processing fee category:', [
                'category_id' => $category->id,
                'category_name' => $category->name,
                'pivot_amount' => $category->pivot->amount ?? 'NO_PIVOT_AMOUNT'
            ]);

            // Check if component already exists
            $existingFee = \App\Models\StudentFee::where([
                'student_id' => $student->id,
                'fee_category_id' => $category->id,
                'academic_year' => $academicYear
            ])->first();

            if ($existingFee) {
                Log::info('Fee component already exists, skipping:', [
                    'existing_fee_id' => $existingFee->id,
                    'category_name' => $category->name
                ]);
                continue;
            }

            // Determine base fee amount
            $feeAmount = $category->pivot->amount ?? 0;
            
            // 🆕 ENHANCED: Smart uniform fee handling
            $isUniformFee = $this->isUniformFeeCategory($category->name);
            
            if ($isUniformFee) {
                // Handle uniform fee logic
                $uniformStatus = $this->interpretUniformFeeStatus($uniformFeeFromExcel);
                
                if ($uniformStatus['is_paid']) {
                    $feeAmount = $uniformStatus['amount'];
                    Log::info('Uniform fee set as PAID from Excel:', [
                        'category_name' => $category->name,
                        'amount' => $feeAmount,
                        'excel_value' => $uniformFeeFromExcel
                    ]);
                } else {
                    // Keep the original amount from fee structure but mark as unpaid
                    Log::info('Uniform fee set as UNPAID from Excel:', [
                        'category_name' => $category->name,
                        'amount' => $feeAmount,
                        'excel_value' => $uniformFeeFromExcel
                    ]);
                }
            }

            Log::info('Creating fee component:', [
                'student_id' => $student->id,
                'fee_structure_id' => $feeStructure->id,
                'fee_category_id' => $category->id,
                'category_name' => $category->name,
                'amount' => $feeAmount,
                'academic_year' => $academicYear,
                'is_uniform_fee' => $isUniformFee
            ]);

            $studentFee = \App\Models\StudentFee::create([
                'student_id' => $student->id,
                'fee_structure_id' => $feeStructure->id,
                'fee_category_id' => $category->id,
                'academic_year' => $academicYear,
                'amount' => $feeAmount,
                'due_date' => $paymentDate ? $paymentDate->copy()->addDays(30) : now()->addDays(30),
                'status' => 'unpaid',
                'installment_number' => 1,
                'total_installments' => 1,
                'paid_amount' => 0,
                'concession_amount' => 0
            ]);

            $studentFee->load('feeCategory');
            $studentFees[] = $studentFee;
            $totalFeeAmount += $feeAmount;
            $createdCount++;
            
            // 🆕 CATEGORIZE FEES FOR SMART PROCESSING
            if ($this->isTuitionFeeCategory($category->name)) {
                $tuitionFees[] = $studentFee;
            } elseif ($this->isUniformFeeCategory($category->name)) {
                $uniformFees[] = $studentFee;
            } else {
                $otherFees[] = $studentFee;
            }
            
            Log::info('Fee component created and categorized:', [
                'fee_id' => $studentFee->id,
                'category_name' => $category->name,
                'amount' => $feeAmount,
                'fee_type' => $this->categorizeFeeType($category->name)
            ]);
        }

        Log::info('Fee components categorization completed:', [
            'student_id' => $student->id,
            'total_components_created' => $createdCount,
            'tuition_fees_count' => count($tuitionFees),
            'uniform_fees_count' => count($uniformFees),
            'other_fees_count' => count($otherFees),
            'total_fee_amount' => $totalFeeAmount
        ]);

        // 2. 🆕 PROCESS FINANCIAL DATA WITH SMART LOGIC
        if (!empty($studentFees)) {
            $this->processFinancialDataWithCategories($student, [
                'tuition_fees' => $tuitionFees,
                'uniform_fees' => $uniformFees,
                'other_fees' => $otherFees,
                'all_fees' => $studentFees
            ], [
                'total_paid' => $totalPaidAmount,
                'total_concession' => $totalConcessionAmount,
                'uniform_fee_data' => [
                    'excel_value' => $uniformFeeFromExcel,
                    'status' => $this->interpretUniformFeeStatus($uniformFeeFromExcel)
                ],
                'payment_date' => $paymentDate,
                'payment_method' => $paymentMethod,
                'payment_remarks' => $paymentRemarks,
                'total_fee_amount' => $totalFeeAmount
            ]);
        }

        $this->feeComponentsCreated += $createdCount;
        
        Log::info('Fee components with financial data creation completed:', [
            'student_id' => $student->id,
            'student_name' => $student->name,
            'enrollment_number' => $student->enrollment_number,
            'components_created' => $createdCount,
            'total_paid' => $totalPaidAmount,
            'total_concession' => $totalConcessionAmount
        ]);
        
    } catch (\Exception $e) {
        $this->feeComponentErrors[] = [
            'student_id' => $student->id,
            'student_name' => $student->name,
            'enrollment_number' => $student->enrollment_number,
            'error' => $e->getMessage()
        ];
        
        Log::error('Failed to create fee components with financial data:', [
            'student_id' => $student->id,
            'error' => $e->getMessage(),
            'row_data' => $rowData,
            'trace' => $e->getTraceAsString()
        ]);
        
        // Fall back to original method
        Log::info('Falling back to original fee component creation method');
        $this->createFeeComponentsForStudent($student, $rowData);
    }
}

/**
 * Check if fee category is tuition-related
 */
private function isTuitionFeeCategory($categoryName)
{
    $tuitionKeywords = ['tuition', 'tution', 'course', 'academic', 'semester', 'study', 'education'];
    $categoryLower = strtolower($categoryName);
    
    foreach ($tuitionKeywords as $keyword) {
        if (stripos($categoryLower, $keyword) !== false) {
            return true;
        }
    }
    
    return false;
}

/**
 * Check if fee category is uniform-related
 */
private function isUniformFeeCategory($categoryName)
{
    $uniformKeywords = ['uniform', 'dress', 'clothing', 'attire'];
    $categoryLower = strtolower($categoryName);
    
    foreach ($uniformKeywords as $keyword) {
        if (stripos($categoryLower, $keyword) !== false) {
            return true;
        }
    }
    
    return false;
}

/**
 * Categorize fee type for logging
 */
private function categorizeFeeType($categoryName)
{
    if ($this->isTuitionFeeCategory($categoryName)) {
        return 'tuition';
    } elseif ($this->isUniformFeeCategory($categoryName)) {
        return 'uniform';
    } else {
        return 'other';
    }
}

/**
 * Interpret uniform fee status from Excel data
 * Logic: 12000 = paid, empty/null = unpaid
 */
private function interpretUniformFeeStatus($uniformFeeValue)
{
    if ($uniformFeeValue === null || $uniformFeeValue === '' || $uniformFeeValue === 0) {
        return [
            'is_paid' => false,
            'amount' => 0,
            'status' => 'unpaid'
        ];
    } else {
        return [
            'is_paid' => true,
            'amount' => floatval($uniformFeeValue),
            'status' => 'paid'
        ];
    }
}

/**
 * Process uniform fees based on Excel logic
 */
private function processUniformFees(Student $student, array $uniformFees, array $uniformFeeData)
{
    foreach ($uniformFees as $uniformFee) {
        if ($uniformFeeData['status']['is_paid']) {
            // Mark uniform as paid
            $uniformFee->update([
                'paid_amount' => $uniformFee->amount,
                'status' => 'paid',
                'paid_date' => now(),
                'payment_method' => 'Bulk Import'
            ]);
            
            Log::info('Uniform fee marked as PAID:', [
                'student_id' => $student->id,
                'fee_id' => $uniformFee->id,
                'amount' => $uniformFee->amount,
                'excel_value' => $uniformFeeData['excel_value']
            ]);
        } else {
            // Keep uniform as unpaid
            Log::info('Uniform fee kept as UNPAID:', [
                'student_id' => $student->id,
                'fee_id' => $uniformFee->id,
                'amount' => $uniformFee->amount,
                'excel_value' => $uniformFeeData['excel_value']
            ]);
        }
    }
}

private function processFinancialDataWithCategories(Student $student, array $categorizedFees, array $paymentData)
{
    try {
        $totalPaid = $paymentData['total_paid'];
        $totalConcession = $paymentData['total_concession'];
        $uniformFeeData = $paymentData['uniform_fee_data'];
        $paymentDate = $paymentData['payment_date'] ?? now();
        $paymentMethod = $paymentData['payment_method'];
        $paymentRemarks = $paymentData['payment_remarks'];

        Log::info('=== STARTING ENHANCED FINANCIAL DATA PROCESSING ===', [
            'student_id' => $student->id,
            'total_paid' => $totalPaid,
            'total_concession' => $totalConcession,
            'tuition_fees_count' => count($categorizedFees['tuition_fees']),
            'uniform_fees_count' => count($categorizedFees['uniform_fees']),
            'other_fees_count' => count($categorizedFees['other_fees']),
            'uniform_fee_status' => $uniformFeeData['status']
        ]);

        // STEP 1: 🆕 HANDLE UNIFORM FEES FIRST (Based on Excel logic)
        if (!empty($categorizedFees['uniform_fees'])) {
            $this->processUniformFees($student, $categorizedFees['uniform_fees'], $uniformFeeData);
        }

        // STEP 2: 🆕 APPLY CONCESSIONS ONLY TO TUITION FEES
        if ($totalConcession > 0 && !empty($categorizedFees['tuition_fees'])) {
            $this->applyConcessionToTuitionFees($student, $categorizedFees['tuition_fees'], $totalConcession);
        } elseif ($totalConcession > 0) {
            Log::warning('Concession specified but no tuition fees found:', [
                'student_id' => $student->id,
                'concession_amount' => $totalConcession,
                'available_fee_categories' => array_map(function($fee) {
                    return $fee->feeCategory->name ?? 'Unknown';
                }, $categorizedFees['all_fees'])
            ]);
        }

        // STEP 3: 🆕 DISTRIBUTE REGULAR PAYMENTS (Excluding uniform if already paid)
        if ($totalPaid > 0) {
            $this->distributeRegularPayments($student, $categorizedFees, $totalPaid, $uniformFeeData);
        }

        // STEP 4: CREATE PAYMENT RECORD (if applicable)
        if ($totalPaid > 0) {
            $this->createPaymentRecord($student, $categorizedFees['all_fees'], $totalPaid, $paymentDate, $paymentMethod, $paymentRemarks);
        }

        Log::info('=== ENHANCED FINANCIAL DATA PROCESSING COMPLETED ===', [
            'student_id' => $student->id,
            'concessions_applied' => $totalConcession,
            'payments_applied' => $totalPaid,
            'uniform_fees_processed' => count($categorizedFees['uniform_fees'])
        ]);

    } catch (\Exception $e) {
        $this->paymentErrors[] = [
            'student_id' => $student->id,
            'student_name' => $student->name,
            'error' => $e->getMessage()
        ];
        
        Log::error('Failed to process enhanced financial data:', [
            'student_id' => $student->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
}

    // 🆕 NEW: Process financial data and update fee components
private function processFinancialData(Student $student, array $studentFees, array $paymentData)
{
    try {
        $totalPaid = $paymentData['total_paid'];
        $totalConcession = $paymentData['total_concession'];
        $paymentDate = $paymentData['payment_date'] ?? now();
        $paymentMethod = $paymentData['payment_method'];
        $paymentRemarks = $paymentData['payment_remarks'];
        $totalFeeAmount = $paymentData['total_fee_amount'];

        Log::info('=== STARTING FINANCIAL DATA PROCESSING ===', [
            'student_id' => $student->id,
            'total_paid' => $totalPaid,
            'total_concession' => $totalConcession,
            'total_fee_amount' => $totalFeeAmount,
            'fee_components_count' => count($studentFees)
        ]);

        // 🔧 FIXED: Apply concessions to ALL fee components proportionally (not just tuition)
        if ($totalConcession > 0 && $totalFeeAmount > 0) {
            Log::info('Applying concessions proportionally to all fee components');
            
            foreach ($studentFees as $studentFee) {
                $proportion = $studentFee->amount / $totalFeeAmount;
                $feeConcession = round($totalConcession * $proportion, 2);
                
                if ($feeConcession > 0) {
                    $studentFee->update([
                        'concession_amount' => $feeConcession
                    ]);
                    
                    Log::info('Applied concession to fee component:', [
                        'student_id' => $student->id,
                        'fee_category' => $studentFee->feeCategory->name ?? 'Unknown',
                        'fee_amount' => $studentFee->amount,
                        'proportion' => $proportion,
                        'concession_applied' => $feeConcession
                    ]);
                }
            }
        } else if ($totalConcession > 0) {
            Log::warning('Concession specified but no valid fee structure found:', [
                'student_id' => $student->id,
                'concession_amount' => $totalConcession,
                'total_fee_amount' => $totalFeeAmount
            ]);
        }

        // 🔧 FIXED: Apply payments to ALL fee components proportionally
        if ($totalPaid > 0 && $totalFeeAmount > 0) {
            Log::info('Applying payments proportionally to all fee components');
            
            foreach ($studentFees as $studentFee) {
                $proportion = $studentFee->amount / $totalFeeAmount;
                $feePayment = round($totalPaid * $proportion, 2);
                
                // Ensure we don't overpay (payment can't exceed net amount)
                $maxPayable = $studentFee->amount - $studentFee->concession_amount;
                $feePayment = min($feePayment, $maxPayable);
                
                if ($feePayment > 0) {
                    $newStatus = $this->calculateFeeStatus($studentFee->amount, $feePayment, $studentFee->concession_amount);
                    
                    $studentFee->update([
                        'paid_amount' => $feePayment,
                        'status' => $newStatus
                    ]);
                    
                    Log::info('Applied payment to fee component:', [
                        'student_id' => $student->id,
                        'fee_category' => $studentFee->feeCategory->name ?? 'Unknown',
                        'fee_amount' => $studentFee->amount,
                        'payment_applied' => $feePayment,
                        'concession_amount' => $studentFee->concession_amount,
                        'proportion' => $proportion,
                        'new_status' => $newStatus
                    ]);
                }
            }
        }

        // Create payment record if there's an actual payment
        if ($totalPaid > 0) {
            $payment = Payment::create([
                'student_id' => $student->id,
                'amount' => $totalPaid,
                'payment_date' => $paymentDate,
                'payment_method' => $paymentMethod,
                'payment_type' => 'component',
                'receipt_number' => $this->generateReceiptNumber($student),
                'academic_year' => date('Y') . '-' . (date('Y') + 1),
                'notes' => $paymentRemarks,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Create component payment items to link payment to specific fee components
            foreach ($studentFees as $studentFee) {
                if ($studentFee->paid_amount > 0) {
                    DB::table('component_payment_items')->insert([
                        'payment_id' => $payment->id,
                        'student_fee_id' => $studentFee->id,
                        'amount_paid' => $studentFee->paid_amount,
                        'notes' => 'Bulk import payment',
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }

            $this->paymentsCreated++;
            
            Log::info('Created payment record:', [
                'student_id' => $student->id,
                'payment_id' => $payment->id,
                'amount' => $totalPaid,
                'receipt_number' => $payment->receipt_number,
                'component_items_created' => count($studentFees)
            ]);
        }

        Log::info('=== FINANCIAL DATA PROCESSING COMPLETED ===', [
            'student_id' => $student->id,
            'concessions_applied' => $totalConcession,
            'payments_applied' => $totalPaid,
            'payment_record_created' => $totalPaid > 0
        ]);

    } catch (\Exception $e) {
        $this->paymentErrors[] = [
            'student_id' => $student->id,
            'student_name' => $student->name,
            'error' => $e->getMessage()
        ];
        
        Log::error('Failed to process financial data:', [
            'student_id' => $student->id,
            'error' => $e->getMessage(),
            'payment_data' => $paymentData,
            'trace' => $e->getTraceAsString()
        ]);
    }
}

/**
 * Apply concessions ONLY to tuition fees
 */
private function applyConcessionToTuitionFees(Student $student, array $tuitionFees, $totalConcession)
{
    if (empty($tuitionFees)) {
        return;
    }
    
    $tuitionFeeAmount = array_sum(array_map(function($fee) { return $fee->amount; }, $tuitionFees));
    
    Log::info('Applying concessions to tuition fees only:', [
        'student_id' => $student->id,
        'total_concession' => $totalConcession,
        'tuition_fees_count' => count($tuitionFees),
        'tuition_fee_total' => $tuitionFeeAmount
    ]);
    
    foreach ($tuitionFees as $tuitionFee) {
        $proportion = $tuitionFeeAmount > 0 ? ($tuitionFee->amount / $tuitionFeeAmount) : 0;
        $feeConcession = round($totalConcession * $proportion, 2);
        
        if ($feeConcession > 0) {
            $tuitionFee->update([
                'concession_amount' => $feeConcession
            ]);
            
            Log::info('Applied concession to tuition fee:', [
                'student_id' => $student->id,
                'fee_category' => $tuitionFee->feeCategory->name ?? 'Unknown',
                'fee_amount' => $tuitionFee->amount,
                'proportion' => $proportion,
                'concession_applied' => $feeConcession
            ]);
        }
    }
}

/**
 * Distribute regular payments (excluding already-paid uniform fees)
 */
private function distributeRegularPayments(Student $student, array $categorizedFees, $totalPaid, array $uniformFeeData)
{
    // Calculate which fees need payment
    $feesNeedingPayment = [];
    $totalAmountNeedingPayment = 0;
    
    foreach ($categorizedFees['all_fees'] as $fee) {
        // Skip uniform fees that are already marked as paid
        if ($this->isUniformFeeCategory($fee->feeCategory->name ?? '') && $uniformFeeData['status']['is_paid']) {
            Log::info('Skipping uniform fee from payment distribution (already paid):', [
                'fee_id' => $fee->id,
                'category' => $fee->feeCategory->name ?? 'Unknown'
            ]);
            continue;
        }
        
        $netAmount = $fee->amount - $fee->concession_amount;
        if ($netAmount > 0) {
            $feesNeedingPayment[] = $fee;
            $totalAmountNeedingPayment += $netAmount;
        }
    }
    
    Log::info('Payment distribution calculation:', [
        'student_id' => $student->id,
        'total_paid_amount' => $totalPaid,
        'fees_needing_payment' => count($feesNeedingPayment),
        'total_amount_needing_payment' => $totalAmountNeedingPayment
    ]);
    
    if (empty($feesNeedingPayment) || $totalAmountNeedingPayment <= 0) {
        Log::warning('No fees need payment or all fees are covered by concessions');
        return;
    }
    
    // Distribute payment proportionally
    foreach ($feesNeedingPayment as $fee) {
        $netAmount = $fee->amount - $fee->concession_amount;
        $proportion = $netAmount / $totalAmountNeedingPayment;
        $feePayment = round($totalPaid * $proportion, 2);
        
        // Ensure we don't overpay
        $feePayment = min($feePayment, $netAmount);
        
        if ($feePayment > 0) {
            $newStatus = $this->calculateFeeStatus($fee->amount, $feePayment, $fee->concession_amount);
            
            $fee->update([
                'paid_amount' => $feePayment,
                'status' => $newStatus
            ]);
            
            Log::info('Applied payment to fee component:', [
                'student_id' => $student->id,
                'fee_category' => $fee->feeCategory->name ?? 'Unknown',
                'fee_amount' => $fee->amount,
                'concession_amount' => $fee->concession_amount,
                'payment_applied' => $feePayment,
                'proportion' => $proportion,
                'new_status' => $newStatus
            ]);
        }
    }
}


    // 🆕 NEW: Calculate fee status based on amounts
  private function calculateFeeStatus($totalAmount, $paidAmount, $concessionAmount)
{
    $netAmount = $totalAmount - $concessionAmount;
    
    if ($paidAmount >= $netAmount) {
        return 'paid';
    } elseif ($paidAmount > 0) {
        return 'partial';
    } else {
        return 'unpaid';  // 🔧 FIXED: Use 'unpaid' instead of 'pending'
    }
}

    // 🆕 NEW: Generate receipt number
    private function generateReceiptNumber(Student $student)
    {
        $prefix = 'RCP';
        $year = date('y');
        $sequence = str_pad($this->paymentsCreated + 1, 4, '0', STR_PAD_LEFT);
        
        return $prefix . '-' . $student->enrollment_number . '-' . $year . $sequence;
    }

    // 🆕 NEW: Parse payment date
    private function parsePaymentDate($dateString)
    {
        if (!$dateString) {
            return now();
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $dateString);
        } catch (\Exception $e) {
            try {
                return Carbon::parse($dateString);
            } catch (\Exception $e2) {
                return now();
            }
        }
    }

    // 🆕 NEW: Debug method to check fee categories and concession logic
    private function debugFinancialProcessing(Student $student, array $studentFees, array $paymentData)
    {
        Log::info('=== DEBUGGING FINANCIAL PROCESSING ===', [
            'student_id' => $student->id,
            'student_name' => $student->name,
            'payment_data' => $paymentData
        ]);

        foreach ($studentFees as $index => $studentFee) {
            $categoryName = $studentFee->feeCategory->name ?? 'Unknown';
            $isTuitionFee = stripos($categoryName, 'tuition') !== false || 
                           stripos($categoryName, 'course') !== false ||
                           stripos($categoryName, 'academic') !== false ||
                           stripos($categoryName, 'semester') !== false;

            Log::info("Fee Component #{$index}:", [
                'fee_id' => $studentFee->id,
                'category_name' => $categoryName,
                'amount' => $studentFee->amount,
                'is_tuition_fee' => $isTuitionFee,
                'paid_amount_before' => $studentFee->paid_amount,
                'concession_amount_before' => $studentFee->concession_amount
            ]);
        }
    }

    // EXISTING METHOD: Original fee component creation (fallback)
private function createFeeComponentsForStudent(Student $student, array $rowData)
{
    try {
        // Get the batch's fee structure
        $feeStructure = $student->batch->feeStructure;
        
        if (!$feeStructure) {
            throw new \Exception('No fee structure found for batch: ' . $student->batch->name);
        }

        $academicYear = date('Y') . '-' . (date('Y') + 1);
        $createdCount = 0;

        // Create fee components based on fee structure
        foreach ($feeStructure->feeCategories as $category) {
            // Check if component already exists
            $existingFee = \App\Models\StudentFee::where([
                'student_id' => $student->id,
                'fee_category_id' => $category->id,
                'academic_year' => $academicYear
            ])->first();

            if (!$existingFee) {
                \App\Models\StudentFee::create([
                    'student_id' => $student->id,
                    'fee_structure_id' => $feeStructure->id,
                    'fee_category_id' => $category->id,
                    'academic_year' => $academicYear,
                    'amount' => $category->pivot->amount ?? 0,
                    'due_date' => now()->addDays(30),
                    'status' => 'unpaid', // 🔧 FIXED: Use 'unpaid' instead of 'pending'
                    'installment_number' => 1,
                    'total_installments' => 1,
                    'paid_amount' => 0,
                    'concession_amount' => 0
                ]);
                $createdCount++;
            }
        }

        $this->feeComponentsCreated += $createdCount;
        
        Log::info('Fee components created for imported student:', [
            'student_id' => $student->id,
            'student_name' => $student->name,
            'enrollment_number' => $student->enrollment_number,
            'batch_id' => $this->batch->id,
            'components_created' => $createdCount
        ]);
        
    } catch (\Exception $e) {
        $this->feeComponentErrors[] = [
            'student_id' => $student->id,
            'student_name' => $student->name,
            'enrollment_number' => $student->enrollment_number,
            'error' => $e->getMessage()
        ];
        
        Log::error('Failed to create fee components for imported student:', [
            'student_id' => $student->id,
            'error' => $e->getMessage(),
            'row_data' => $rowData
        ]);
    }
}


    // EXISTING METHOD: Create import log entry with component flag
    private function createImportLog()
    {
        try {
            $importLog = ImportLog::create([
                'batch_id' => $this->batch->id,
                'batch_name' => $this->batch->name,
                'course_name' => $this->batch->course->name ?? 'Unknown',
                'user_id' => auth()->id(),
                'user_name' => auth()->user()->name ?? 'System',
                'import_type' => 'students_bulk_upload_with_financial_data', // 🆕 Updated type
                'status' => 'processing',
                'auto_create_fee_components' => $this->autoCreateFeeComponents,
                'started_at' => now(),
                'settings' => [
                    'auto_fee_components' => $this->autoCreateFeeComponents,
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

    // EXISTING METHOD: Log individual row processing
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

    // 🆕 ENHANCED: Complete import log with financial data
    public function completeImportLog()
    {
        try {
            if (!$this->importLogId) return;

            $summary = [
                'imported' => $this->importedCount,
                'skipped' => $this->skippedCount,
                'rejected' => $this->rejectedCount,
                'fee_components_created' => $this->feeComponentsCreated,
                'payments_created' => $this->paymentsCreated, // 🆕 Added
                'fee_component_errors' => count($this->feeComponentErrors),
                'payment_errors' => count($this->paymentErrors), // 🆕 Added
                'rejected_details' => $this->rejectedRows,
                'fee_component_error_details' => $this->feeComponentErrors,
                'payment_error_details' => $this->paymentErrors // 🆕 Added
            ];

            // 🔧 FIXED: Use existing column names from import_logs table
            ImportLog::where('id', $this->importLogId)->update([
                'status' => 'completed',
                'completed_at' => now(),
                'total_rows' => $this->importedCount + $this->skippedCount + $this->rejectedCount,
                'imported_count' => $this->importedCount,
                'skipped_count' => $this->skippedCount,
                'rejected_count' => $this->rejectedCount,
                'invoices_created' => $this->feeComponentsCreated, // 🔧 FIXED: Use existing column
                'invoice_errors_count' => count($this->feeComponentErrors) + count($this->paymentErrors),
                'summary' => json_encode($summary)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to complete import log:', [
                'error' => $e->getMessage(),
                'import_log_id' => $this->importLogId
            ]);
        }
    }

    // 🆕 ENHANCED: Get comprehensive import summary with financial data
    public function getImportSummary(): array
    {
        return [
            'imported' => $this->importedCount,
            'skipped' => $this->skippedCount,
            'rejected' => $this->rejectedCount,
            'total_processed' => $this->importedCount + $this->skippedCount + $this->rejectedCount,
            'fee_components_created' => $this->feeComponentsCreated,
            'payments_created' => $this->paymentsCreated, // 🆕 Added
            'fee_component_errors' => count($this->feeComponentErrors),
            'payment_errors' => count($this->paymentErrors), // 🆕 Added
            'rejected_details' => $this->rejectedRows,
            'fee_component_error_details' => $this->feeComponentErrors,
            'payment_error_details' => $this->paymentErrors, // 🆕 Added
            'import_log_id' => $this->importLogId
        ];
    }

    // 🆕 ENHANCED: Getter methods for tracking statistics
    public function getImportedCount(): int { return $this->importedCount; }
    public function getSkippedCount(): int { return $this->skippedCount; }
    public function getRejectedCount(): int { return $this->rejectedCount; }
    public function getRejectedRows(): array { return $this->rejectedRows; }
    public function getFeeComponentsCreated(): int { return $this->feeComponentsCreated; }
    public function getFeeComponentErrors(): array { return $this->feeComponentErrors; }
    public function getPaymentsCreated(): int { return $this->paymentsCreated; } // 🆕 Added
    public function getPaymentErrors(): array { return $this->paymentErrors; } // 🆕 Added

    // EXISTING METHODS: All validation and helper methods remain unchanged
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
    
    
    /**
 * Create payment record with component items
 */
private function createPaymentRecord(Student $student, array $allFees, $totalPaid, $paymentDate, $paymentMethod, $paymentRemarks)
{
    $payment = Payment::create([
        'student_id' => $student->id,
        'amount' => $totalPaid,
        'payment_date' => $paymentDate,
        'payment_method' => $paymentMethod,
        'payment_type' => 'component',
        'receipt_number' => $this->generateReceiptNumber($student),
        'academic_year' => date('Y') . '-' . (date('Y') + 1),
        'notes' => $paymentRemarks,
        'created_at' => now(),
        'updated_at' => now()
    ]);

    // Create component payment items for fees with payments
    $itemsCreated = 0;
    foreach ($allFees as $fee) {
        if ($fee->paid_amount > 0) {
            DB::table('component_payment_items')->insert([
                'payment_id' => $payment->id,
                'student_fee_id' => $fee->id,
                'amount_paid' => $fee->paid_amount,
                'notes' => 'Bulk import payment',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            $itemsCreated++;
        }
    }

    $this->paymentsCreated++;
    
    Log::info('Created payment record with component items:', [
        'student_id' => $student->id,
        'payment_id' => $payment->id,
        'amount' => $totalPaid,
        'receipt_number' => $payment->receipt_number,
        'component_items_created' => $itemsCreated
    ]);
}

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
            // 🆕 NEW: Financial data validation rules
            'paid_amount' => 'nullable|numeric|min:0',
            'concession_amount' => 'nullable|numeric|min:0',
            'uniform_fee' => 'nullable|numeric|min:0',
            'payment_date' => 'nullable|date',
            'payment_method' => 'nullable|in:Cash,Online,Cheque,Bank Transfer,UPI'
        ];
    }

    // ALL EXISTING HELPER METHODS REMAIN UNCHANGED
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