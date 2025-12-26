<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ComponentPaymentReminderService;
use App\Models\Student;
use App\Models\FeeCategory;
// ✅ CHANGED: Replaced Invoice model with StudentFee
use App\Models\StudentFee;
use Carbon\Carbon;

class EnhancedPaymentReminders extends Command
{
    protected $signature = 'payments:enhanced-reminders 
                            {--fee-type= : Specific fee type to process (tuition_fee, library_fee, etc.)}
                            {--student-id= : Process reminders for specific student}
                            {--dry-run : Show what would be sent without actually sending}
                            {--force : Force send even if reminders were recently sent}';
    
    protected $description = 'Send enhanced payment reminders for fee components with advanced filtering';

    protected $reminderService;

    public function __construct(ComponentPaymentReminderService $reminderService)
    {
        parent::__construct();
        $this->reminderService = $reminderService;
    }

    public function handle()
    {
        $this->info('🔔 Starting Enhanced Payment Reminders for Fee Components...');
        
        $feeType = $this->option('fee-type');
        $studentId = $this->option('student-id');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        if ($dryRun) {
            $this->warn('🧪 DRY RUN MODE - No reminders will actually be sent');
        }

        // ✅ CHANGED: Build query for students with overdue fee components
        $studentsQuery = Student::whereHas('studentFees', function($query) use ($feeType) {
            $query->whereIn('status', ['unpaid', 'partial', 'overdue'])
                  ->where('due_date', '<=', now())
                  ->whereRaw('amount - concession_amount - paid_amount > 0');
            
            if ($feeType) {
                $query->whereHas('feeCategory', function($subQuery) use ($feeType) {
                    $subQuery->where('name', 'like', '%' . str_replace('_', ' ', $feeType) . '%');
                });
            }
        });

        // Filter by specific student if provided
        if ($studentId) {
            $studentsQuery->where('id', $studentId);
        }

        // ✅ CHANGED: Eager load studentFees instead of invoices
        $students = $studentsQuery->with([
            'batch.course',
            'studentFees' => function($query) use ($feeType) {
                $query->whereIn('status', ['unpaid', 'partial', 'overdue'])
                      ->where('due_date', '<=', now())
                      ->whereRaw('amount - concession_amount - paid_amount > 0')
                      ->with('feeCategory');
                
                if ($feeType) {
                    $query->whereHas('feeCategory', function($subQuery) use ($feeType) {
                        $subQuery->where('name', 'like', '%' . str_replace('_', ' ', $feeType) . '%');
                    });
                }
            }
        ])->get();

        if ($students->isEmpty()) {
            $this->info('✅ No students found with overdue fee components for the specified criteria.');
            return 0;
        }

        $this->info("📋 Found {$students->count()} students with overdue payments");

        if ($feeType) {
            $this->line("🎯 Filtering by fee type: " . str_replace('_', ' ', ucwords($feeType)));
        }

        $processed = 0;
        $sent = 0;
        $failed = 0;
        $skipped = 0;

        $progressBar = $this->output->createProgressBar($students->count());
        $progressBar->start();

        foreach ($students as $student) {
            $processed++;
            
            try {
                if (empty($student->email) && empty($student->phone)) {
                    $this->newLine();
                    $this->warn("⚠️  Skipping {$student->name} - No contact information");
                    $skipped++;
                    $progressBar->advance();
                    continue;
                }

                // ✅ CHANGED: Get the most urgent overdue fee component
                $urgentFee = $student->studentFees
                    ->sortBy('due_date')
                    ->first();

                if (!$urgentFee) {
                    $skipped++;
                    $progressBar->advance();
                    continue;
                }

                $daysOverdue = Carbon::parse($urgentFee->due_date)->diffInDays(now());
                $reminderType = $this->determineReminderType($daysOverdue);

                if (!$force && $this->wasRecentlyReminded($student, $reminderType)) {
                    $skipped++;
                    $progressBar->advance();
                    continue;
                }

                if ($dryRun) {
                    $this->newLine();
                    $this->line("📧 Would send {$reminderType} reminder to: {$student->name}");
                    $this->line("    Fee Component: {$urgentFee->feeCategory->name}");
                    $this->line("   💰 Amount: ₹" . $urgentFee->getRemainingAmount());
                    $this->line("   📅 Due: {$urgentFee->due_date} ({$daysOverdue} days overdue)");
                    $sent++;
                } else {
                    // ✅ CHANGED: Schedule reminder using StudentFee model
                    $result = $this->reminderService->scheduleReminder(
                        $student,
                        $urgentFee->feeCategory,
                        $reminderType,
                        now(),
                        $urgentFee // Pass the StudentFee object instead of Invoice
                    );

                    if ($result['success']) {
                        $sent++;
                    } else {
                        $failed++;
                        $this->newLine();
                        $this->error("❌ Failed to send reminder to {$student->name}: {$result['message']}");
                    }
                }

            } catch (\Exception $e) {
                $failed++;
                $this->newLine();
                $this->error("❌ Error processing {$student->name}: " . $e->getMessage());
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info('📊 Enhanced Payment Reminders Summary:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Students Processed', $processed],
                ['Reminders Sent/Scheduled', $sent],
                ['Failed', $failed],
                ['Skipped', $skipped],
                ['Success Rate', $processed > 0 ? round(($sent / $processed) * 100, 1) . '%' : '0%']
            ]
        );

        return $failed > 0 ? 1 : 0;
    }

    /**
     * Determine reminder type based on days overdue
     */
    private function determineReminderType(int $daysOverdue): string
    {
        if ($daysOverdue <= 7) {
            return 'overdue';
        } elseif ($daysOverdue <= 30) {
            return 'escalation';
        } else {
            return 'final_notice';
        }
    }

    /**
     * Check if student was recently reminded
     */
    private function wasRecentlyReminded(Student $student, string $reminderType): bool
    {
        $recentReminder = $student->paymentReminders()
            ->where('reminder_type', $reminderType)
            ->where('created_at', '>', now()->subHours(24))
            ->exists();

        return $recentReminder;
    }
}