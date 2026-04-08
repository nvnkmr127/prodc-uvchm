<?php

namespace App\Console\Commands;

use App\Services\ETimeOfficeApiPuller;
use Carbon\Carbon;
use Illuminate\Console\Command;

class PullETimeOfficeData extends Command
{
    protected $signature = 'etimeoffice:pull 
                           {--hours=2 : Hours of data to pull from now}
                           {--from= : Start date (Y-m-d H:i format)}
                           {--to= : End date (Y-m-d H:i format)}
                           {--empcode=ALL : Employee code (ALL for all employees)}
                           {--test : Test mode - show data without saving}';

    protected $description = 'Pull attendance data from ETimeOffice API';

    public function handle()
    {
        $this->info('🔄 ETimeOffice Data Puller');
        $this->info('==========================');

        // Determine date range
        if ($this->option('from') && $this->option('to')) {
            $fromDate = Carbon::parse($this->option('from'));
            $toDate = Carbon::parse($this->option('to'));
        } else {
            $hours = $this->option('hours');
            $fromDate = now()->subHours($hours);
            $toDate = now();
        }

        $empcode = $this->option('empcode');

        $this->info("📅 Pulling data from: {$fromDate->format('Y-m-d H:i')}");
        $this->info("📅 Pulling data to: {$toDate->format('Y-m-d H:i')}");
        $this->info("👤 Employee code: {$empcode}");
        $this->info('');

        if ($this->option('test')) {
            $this->info('🧪 TEST MODE - Data will not be saved');
            $this->info('');
        }

        try {
            $puller = new ETimeOfficeApiPuller;

            // Pull data from API
            $this->info('📡 Connecting to ETimeOffice API...');
            $apiResult = $puller->pullAttendanceData($fromDate, $toDate, $empcode);

            if (! $apiResult['success']) {
                $this->error('❌ Failed to pull data from ETimeOffice API');
                $this->error('Error: '.$apiResult['error']);

                return 1;
            }

            $this->info("✅ API Success: {$apiResult['api_message']}");
            $this->info("📊 Found {$apiResult['count']} punch records");

            if ($apiResult['count'] === 0) {
                $this->warn('⚠️  No punch data found in the specified time range');
                $this->info('');
                $this->info('💡 Possible reasons:');
                $this->info('  - No employee punched during this time');
                $this->info('  - Different time zone settings');
                $this->info('  - Try a larger time range with --hours=24');

                return 0;
            }

            // Show sample data structure if we have records
            $punchData = $apiResult['data'];
            if (is_array($punchData) && count($punchData) > 0) {
                $this->info('');
                $this->info('📋 Sample punch record structure:');
                $sampleRecord = $punchData[0];

                if (is_array($sampleRecord)) {
                    $this->info('  Record type: Array');
                    $this->info('  Available fields: '.implode(', ', array_keys($sampleRecord)));
                    $this->info('  Sample data:');
                    foreach ($sampleRecord as $key => $value) {
                        $displayValue = is_string($value) ? $value : json_encode($value);
                        $this->info("    {$key}: {$displayValue}");
                    }
                } else {
                    $this->info('  Record type: '.gettype($sampleRecord));
                    $this->info('  Content: '.json_encode($sampleRecord));
                }
            }

            if ($this->option('test')) {
                $this->info('');
                $this->info('🧪 Test mode complete - data analysis finished');
                $this->info('');
                $this->info('💡 Next steps:');
                $this->info('  1. Verify employee codes match your student records');
                $this->info('  2. Run without --test to process and save attendance');
                $this->info('  3. Check student biometric code mappings if no matches found');

                return 0;
            }

            // Process the data
            $this->info('');
            $this->info('⚙️  Processing punch records into attendance...');

            $processResult = $puller->processAttendanceRecords($punchData);

            // Show results
            $this->info('');
            $this->info('📊 PROCESSING RESULTS:');
            $this->info("  API Records: {$apiResult['count']}");
            $this->info('  Employee Codes Found: '.count($processResult['employee_codes_found']));
            $this->info("  Successfully Processed: {$processResult['processed']}");
            $this->info('  Errors: '.count($processResult['errors']));

            if (! empty($processResult['employee_codes_found'])) {
                $codes = $processResult['employee_codes_found'];
                $this->info('  Employee Codes: '.implode(', ', array_slice($codes, 0, 10)).
                           (count($codes) > 10 ? ' (and '.(count($codes) - 10).' more)' : ''));
            }

            if (! empty($processResult['errors'])) {
                $this->info('');
                $this->warn('⚠️  Processing Issues ('.count($processResult['errors']).' total):');
                foreach (array_slice($processResult['errors'], 0, 5) as $error) {
                    $this->warn("  - {$error}");
                }
                if (count($processResult['errors']) > 5) {
                    $this->warn('  ... and '.(count($processResult['errors']) - 5).' more errors');
                }
            }

            // Update last sync timestamp
            \App\Models\Setting::updateOrCreate(
                ['key' => 'etimeoffice_last_sync'],
                ['value' => now()->toISOString()]
            );

            $this->info('');
            if ($processResult['processed'] > 0) {
                $this->info("✅ Successfully processed {$processResult['processed']} attendance records!");
            } else {
                $this->warn('⚠️  No attendance records were created');
                $this->info('');
                $this->info('🔍 Common issues and solutions:');
                $this->info('  1. Employee codes don\'t match student records');
                $this->info('     → Check: php artisan tinker --execute="\\App\\Models\\Student::pluck(\'enrollment_number\', \'biometric_employee_code\')"');
                $this->info('  2. Students need biometric codes assigned');
                $this->info('     → Visit: /admin/students/biometric-mapping');
                $this->info('  3. Date format issues in API data');
                $this->info('     → Check the sample record structure above');
            }

            // Show quick stats
            $todayCount = \App\Models\Attendance::whereDate('created_at', today())->count();
            $this->info('');
            $this->info("📈 Total attendance records today: {$todayCount}");

        } catch (\Exception $e) {
            $this->error('❌ Error: '.$e->getMessage());

            return 1;
        }

        return 0;
    }
}
