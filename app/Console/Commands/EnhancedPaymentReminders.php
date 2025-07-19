<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PaymentReminderService;
use App\Models\Student;
use App\Models\FeeCategory;
use App\Models\Invoice;
use Carbon\Carbon;

class EnhancedPaymentReminders extends Command
{
    protected $signature = 'payments:enhanced-reminders 
                            {--fee-type= : Specific fee type to process (tuition_fee, library_fee, etc.)}
                            {--student-id= : Process reminders for specific student}
                            {--dry-run : Show what would be sent without actually sending}
                            {--force : Force send even if reminders were recently sent}';
    
    protected $description = 'Send enhanced payment reminders with advanced filtering and targeting';

    protected $reminderService;

    public function __construct(PaymentReminderService $reminderService)
    {
        parent::__construct();
        $this->reminderService = $reminderService;
    }

    public function handle()
    {
        $this->info('🔔 Starting Enhanced Payment Reminders...');
        
        $feeType = $this->option('fee-type');
        $studentId = $this->option('student-id');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        if ($dryRun) {
            $this->warn('🧪 DRY RUN MODE - No reminders will actually be sent');
        }

        // Build query for students with overdue payments
        $studentsQuery = Student::whereHas('invoices', function($query) use ($feeType) {
            $query->where('status', '!=', 'paid')
                  ->where('due_date', '<=', now());
            
            if ($feeType) {
                $query->whereHas('items.feeCategory', function($subQuery) use ($feeType) {
                    $subQuery->where('name', 'like', '%' . str_replace('_', ' ', $feeType) . '%');
                });
            }
        });

        // Filter by specific student if provided
        if ($studentId) {
            $studentsQuery->where('id', $studentId);
        }

        $students = $studentsQuery->with([
            'batch.course',
            'invoices' => function($query) use ($feeType) {
                $query->where('status', '!=', 'paid')
                      ->where('due_date', '<=', now())
                      ->with('items.feeCategory');
                
                if ($feeType) {
                    $query->whereHas('items.feeCategory', function($subQuery) use ($feeType) {
                        $subQuery->where('name', 'like', '%' . str_replace('_', ' ', $feeType) . '%');
                    });
                }
            }
        ])->get();

        if ($students->isEmpty()) {
            $this->info('✅ No students found with overdue payments for the specified criteria.');
            return 0;
        }

        $this->info("📋 Found {$students->count()} students with overdue payments");

        if ($feeType) {
            $this->line("🎯 Filtering by fee type: " . str_replace('_', ' ', ucwords($feeType)));
        }

        // Process each student
        $processed = 0;
        $sent = 0;
        $failed = 0;
        $skipped = 0;

        $progressBar = $this->output->createProgressBar($students->count());
        $progressBar->start();

        foreach ($students as $student) {
            $processed++;
            
            try {
                // Check if student has valid contact information
                if (empty($student->email) && empty($student->phone)) {
                    $this->newLine();
                    $this->warn("⚠️  Skipping {$student->name} - No contact information");
                    $skipped++;
                    $progressBar->advance();
                    continue;
                }

                // Get the most urgent overdue invoice
                $urgentInvoice = $student->invoices
                    ->sortBy('due_date')
                    ->first();

                if (!$urgentInvoice) {
                    $skipped++;
                    $progressBar->advance();
                    continue;
                }

                // Determine reminder type based on overdue days
                $daysOverdue = Carbon::parse($urgentInvoice->due_date)->diffInDays(now());
                $reminderType = $this->determineReminderType($daysOverdue);

                // Check if we should skip due to recent reminders (unless forced)
                if (!$force && $this->wasRecentlyReminded($student, $reminderType)) {
                    $skipped++;
                    $progressBar->advance();
                    continue;
                }

                if ($dryRun) {
                    $this->newLine();
                    $this->line("📧 Would send {$reminderType} reminder to: {$student->name}");
                    $this->line("   💰 Amount: ₹{$urgentInvoice->total_amount}");
                    $this->line("   📅 Due: {$urgentInvoice->due_date} ({$daysOverdue} days overdue)");
                    $sent++;
                } else {
                    // Actually send the reminder
                    $result = $this->reminderService->scheduleReminder(
                        $student,
                        $urgentInvoice->items->first()->feeCategory,
                        $reminderType,
                        now(),
                        $urgentInvoice
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

        // Summary
        $this->info('📊 Enhanced Payment Reminders Summary:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Students Processed', $processed],
                ['Reminders Sent', $sent],
                ['Failed', $failed],
                ['Skipped', $skipped],
                ['Success Rate', $processed > 0 ? round(($sent / $processed) * 100, 1) . '%' : '0%']
            ]
        );

        if ($feeType) {
            $this->line("🎯 Fee Type Filter: " . str_replace('_', ' ', ucwords($feeType)));
        }

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