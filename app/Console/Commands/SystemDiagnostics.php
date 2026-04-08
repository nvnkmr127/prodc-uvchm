<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SystemDiagnostics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:diagnose {--full : Run detailed diagnostic checks}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform a comprehensive system diagnostic check';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting System Diagnostics...');
        $this->line('---------------------------------');

        $results = [];

        // 1. Execute Health Check
        $this->info('🏥 Running health:check...');
        $healthExitCode = Artisan::call('health:check');
        $healthOutput = Artisan::output();
        $results['Health Check'] = [
            'status' => $healthExitCode === Command::SUCCESS ? '✅ Passed' : '❌ Failed',
            'details' => $this->option('full') ? $healthOutput : 'Use --full to see details',
        ];

        // 2. Execute System Final Test
        $this->info('🧪 Running system:final-test...');
        $finalTestExitCode = Artisan::call('system:final-test');
        $finalTestOutput = Artisan::output();
        $results['Final System Test'] = [
            'status' => $finalTestExitCode === Command::SUCCESS ? '✅ Passed' : '❌ Failed',
            'details' => $this->option('full') ? $finalTestOutput : 'Use --full to see details',
        ];

        // 3. Execute Backup Monitor
        $this->info('💾 Running backup:monitor...');
        $backupExitCode = Artisan::call('backup:monitor');
        $backupOutput = Artisan::output();
        $results['Backup Monitor'] = [
            'status' => $backupExitCode === Command::SUCCESS ? '✅ Healthy' : '⚠️ Issues',
            'details' => $this->option('full') ? $backupOutput : 'Use --full to see details',
        ];

        // 4. Analyze Laravel Log
        $this->info('📝 Analyzing laravel.log...');
        $logAnalysis = $this->analyzeLog();
        $results['Log Analysis'] = [
            'status' => $logAnalysis['errors_found'] > 0 ? '⚠️ '.$logAnalysis['errors_found'].' Errors Found' : '✅ No Recent Errors',
            'details' => $logAnalysis['summary'],
        ];

        // 5. Validate .env Keys
        $this->info('🔑 Validating .env keys...');
        $envValidation = $this->validateEnv();
        $results['Env Validation'] = [
            'status' => $envValidation['missing_count'] === 0 ? '✅ Valid' : '❌ '.$envValidation['missing_count'].' Keys Missing/Invalid',
            'details' => $envValidation['summary'],
        ];

        // Display Summary Table
        $this->line('');
        $this->info('📊 Diagnostic Summary:');
        $this->table(['Check', 'Status', 'Summary/Details'], array_map(function ($name, $res) {
            return [$name, $res['status'], $res['details']];
        }, array_keys($results), $results));

        if ($this->option('full')) {
            $this->line('');
            $this->info('📄 Detailed Command Outputs:');
            $this->line('-------------------------');
            $this->warn('--- Health Check ---');
            $this->line($healthOutput);
            $this->warn('--- Final Test ---');
            $this->line($finalTestOutput);
            $this->warn('--- Backup Monitor ---');
            $this->line($backupOutput);
        }

        return 0;
    }

    /**
     * Analyze the last 200 lines of laravel.log
     */
    private function analyzeLog()
    {
        $logPath = storage_path('logs/laravel.log');
        if (! File::exists($logPath)) {
            return ['errors_found' => 0, 'summary' => 'Log file not found'];
        }

        $lines = explode("\n", File::get($logPath));
        $lastLines = array_slice($lines, -200);
        $errorCount = 0;
        $recentErrors = [];

        foreach ($lastLines as $line) {
            if (preg_match('/(ERROR|CRITICAL|ALERT|EMERGENCY)/i', $line)) {
                $errorCount++;
                if (count($recentErrors) < 5) {
                    $recentErrors[] = Str::limit($line, 80);
                }
            }
        }

        return [
            'errors_found' => $errorCount,
            'summary' => $errorCount > 0
                ? 'Found '.$errorCount.' errors. Last 5: '.implode(', ', $recentErrors)
                : 'Checked last 200 lines.',
        ];
    }

    /**
     * Validate important .env keys
     */
    private function validateEnv()
    {
        $requiredKeys = [
            'APP_KEY',
            'APP_URL',
            'DB_CONNECTION',
            'DB_HOST',
            'DB_DATABASE',
            'DB_USERNAME',
            'MAIL_HOST',
        ];

        $missing = [];
        foreach ($requiredKeys as $key) {
            $value = env($key);
            if (empty($value)) {
                $missing[] = $key;
            }

            // Special check for APP_KEY default
            if ($key === 'APP_KEY' && $value === 'base64:bTR4N2czamV5OWJyYzVnM3Y0Y21ib2VzdWxpa3VjNmc=') {
                $missing[] = 'APP_KEY (Default detected)';
            }
        }

        return [
            'missing_count' => count($missing),
            'summary' => count($missing) > 0
                ? 'Missing/Invalid: '.implode(', ', $missing)
                : 'All critical keys present.',
        ];
    }
}
