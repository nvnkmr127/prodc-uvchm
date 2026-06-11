<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Services\UnifiedIdentifierService;
use Illuminate\Support\Facades\DB;

class FixBiometricCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-biometric-codes 
                            {--force : Force regenerate all biometric codes}
                            {--batch-name= : Filter by batch name (e.g. 2026-27)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix existing student biometric codes based on new logic (Year 26).';

    /**
     * Execute the console command.
     */
    public function handle(UnifiedIdentifierService $identifierService)
    {
        $this->info('Starting Biometric ID Fix...');

        $query = Student::with(['course', 'batch']);
        
        if ($this->option('batch-name')) {
            $batchName = $this->option('batch-name');
            $query->whereHas('batch', function($q) use ($batchName) {
                $q->where('name', 'like', "%{$batchName}%");
            });
            $this->info("Filtering students by batch name containing: {$batchName}");
        }

        $students = $query->get();
        $count = 0;
        $skipped = 0;

        foreach ($students as $student) {
            $oldBiometricId = $student->biometric_employee_code;
            
            // Re-generate ID
            // Check if we need to regenerate
            // Let's see what the expected prefix should be
            $courseMapping = [
                'ADHM' => '2',
                'MDHM' => '3',
                'PDHM' => '4',
                'DHM' => '1',
            ];
    
            $coursePrefix = '';
            if ($student->course) {
                $coursePrefix = $student->course->code ?? $student->course->enrollment_prefix ?? strtoupper(substr($student->course->name, 0, 4));
            }
            $coursePrefix = strtoupper(trim($coursePrefix));
    
            $courseCode = '9';
            foreach ($courseMapping as $mappedCode => $number) {
                if (strpos($coursePrefix, $mappedCode) !== false) {
                    $courseCode = $number;
                    break;
                }
            }
            
            if ($courseCode === '9' && $student->enrollment_number) {
                 $enrollUpper = strtoupper($student->enrollment_number);
                 foreach ($courseMapping as $mappedCode => $number) {
                    if (strpos($enrollUpper, $mappedCode) !== false) {
                        $courseCode = $number;
                        break;
                    }
                 }
            }

            if ($student->batch && $student->batch->created_at) {
                $year = \Carbon\Carbon::parse($student->batch->created_at)->format('y');
            } else {
                $year = date('y');
            }
    
            $expectedPrefix = "{$courseCode}{$year}";

            // If it doesn't match the expected prefix OR force flag is used
            if ($this->option('force') || empty($oldBiometricId) || substr($oldBiometricId, 0, strlen($expectedPrefix)) !== $expectedPrefix) {
                
                $newBiometricId = $identifierService->generateStudentBiometricId($student);
                
                $student->biometric_employee_code = $newBiometricId;
                $student->save();
                
                $this->info("Updated Student #{$student->id} ({$student->name}): {$oldBiometricId} -> {$newBiometricId}");
                $count++;
            } else {
                $skipped++;
            }
        }

        $this->info("Completed. Fixed {$count} biometric codes. Skipped {$skipped} valid codes.");
    }
}
