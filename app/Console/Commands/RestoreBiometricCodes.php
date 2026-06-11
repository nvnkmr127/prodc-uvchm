<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;

class RestoreBiometricCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:restore-biometric-codes {--file= : Path to the biometric_mapping.json file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restores biometric codes for students from a JSON mapping file.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $file = $this->option('file') ?: base_path('biometric_mapping.json');

        if (!file_exists($file)) {
            $this->error("Mapping file not found at: {$file}");
            return;
        }

        $mapping = json_decode(file_get_contents($file), true);

        if (!$mapping) {
            $this->error("Invalid JSON format in the mapping file.");
            return;
        }

        $this->info("Restoring " . count($mapping) . " biometric codes...");
        $updated = 0;

        foreach ($mapping as $studentId => $biometricCode) {
            $student = Student::find($studentId);
            if ($student && $student->biometric_employee_code !== $biometricCode) {
                $student->biometric_employee_code = $biometricCode;
                $student->save();
                $this->info("Restored Student #{$studentId} -> {$biometricCode}");
                $updated++;
            }
        }

        $this->info("Done! Successfully restored {$updated} biometric codes.");
    }
}
