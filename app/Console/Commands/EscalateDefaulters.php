<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PaymentDefaulter;
use App\Models\PaymentReminder;
use App\Services\PaymentReminderService;
use App\Models\Setting;
use Carbon\Carbon;

class EscalateDefaulters extends Command
{
    protected $signature = 'defaulters:escalate 
                            {--category= : Escalate specific category (mild, moderate, severe, chronic)}
                            {--auto-assign : Automatically assign to collection staff}
                            {--dry-run : Show what would be escalated without taking action}';
    
    protected $description = 'Escalate payment defaulters based on their category and overdue duration';

    protected $reminderService;

    public function __construct(PaymentReminderService $reminderService)
    {
        parent::__construct();
        $this->reminderService = $reminderService;
    }

    public function handle()
    {
        $this->info('🚨 Starting Defaulter Escalation Process...');
        
        $category = $this->option('category');
        $autoAssign = $this->option('auto-assign');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('🧪 DRY RUN MODE - No escalations will actually be processed');
        }

        // Get escalation criteria from settings
        $escalationCriteria = [
            'mild' => [
                'days_threshold' => Setting::get('mild_defaulter_days', 15),
                'escalation_days' => 20,
                'next_level' => 'moderate'
            ],
            'moderate' => [
                'days_threshold' => Setting::get('moderate_defaulter_days', 30),
                'escalation_days' => 35,
                'next_level' => 'severe'
            ],
            'severe' => [
                'days_threshold' => Setting::get('severe_defaulter_days', 60),
                'escalation_days' => 70,
                'next_level' => 'chronic'
            ],
            'chronic' => [
                'days_threshold' => Setting::get('chronic_defaulter_days', 90),
                'escalation_days' => 100,
                'next_level' => 'legal_action'
            ]
        ];

        // Build query for defaulters eligible for escalation
        $defaultersQuery = PaymentDefaulter::where('current_status', '!=', 'resolved')
            ->where('current_status', '!=', 'suspended')
            ->with(['student.batch.course']);

        if ($category) {
            $defaultersQuery->where('defaulter_category', $category);
        }

        $defaulters = $defaultersQuery->get();

        if ($defaulters->isEmpty()) {
            $this->info('✅ No defaulters found for escalation.');
            return 0;
        }

        $this->info("📋 Found {$defaulters->count()} defaulters to evaluate");

        $escalated = 0;
        $contacted = 0;
        $assigned = 0;
        $skipped = 0;

        $progressBar = $this->output->createProgressBar($defaulters->count());
        $progressBar->start();

        foreach ($defaulters as $defaulter) {
            try {
                $student = $defaulter->student;
                $currentCategory = $defaulter->defaulter_category;
                $criteria = $escalationCriteria[$currentCategory] ?? null;

                if (!$criteria) {
                    $skipped++;
                    $progressBar->advance();
                    continue;
                }

                // Check if defaulter meets escalation criteria
                $shouldEscalate = $this->shouldEscalate($defaulter, $criteria);
                
                if (!$shouldEscalate) {
                    $skipped++;
                    $progressBar->advance();
                    continue;
                }

                if ($dryRun) {
                    $this->newLine();
                    $this->line("🚨 Would escalate: {$student->name}");
                    $this->line("   📊 From: {$currentCategory} → {$criteria['next_level']}");
                    $this->line("   💰 Amount: ₹{$defaulter->total_overdue_amount}");
                    $this->line("   📅 Overdue: {$defaulter->overdue_days} days");
                    $escalated++;
                } else {
                    // Perform actual escalation
                    $this->escalateDefaulter($defaulter, $criteria, $autoAssign);
                    $escalated++;

                    // Send escalation notification
                    $this->sendEscalationNotification($defaulter);
                    $contacted++;

                    // Auto-assign if requested and enabled
                    if ($autoAssign && Setting::get('auto_assignment_enabled', false)) {
                        $this->autoAssignDefaulter($defaulter);
                        $assigned++;
                    }
                }

            } catch (\Exception $e) {
                $this->newLine();
                $this->error("❌ Error escalating defaulter {$defaulter->student->name}: " . $e->getMessage());
                $skipped++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info('📊 Defaulter Escalation Summary:');
        $this->table(
            ['Action', 'Count'],
            [
                ['Evaluated', $defaulters->count()],
                ['Escalated', $escalated],
                ['Notifications Sent', $contacted],
                ['Auto-assigned', $assigned],
                ['Skipped', $skipped]
            ]
        );

        if ($category) {
            $this->line("🎯 Category Filter: " . ucfirst($category));
        }

        return 0;
    }

    /**
     * Check if defaulter should be escalated
     */
    private function shouldEscalate(PaymentDefaulter $defaulter, array $criteria): bool
    {
        // Check if enough days have passed since last escalation
        $daysSinceLastEscalation = $defaulter->updated_at->diffInDays(now());
        
        // Check if overdue days meet threshold
        $meetsTimeThreshold = $defaulter->overdue_days >= $criteria['escalation_days'];
        
        // Check if no recent contact attempts
        $lastContactDays = $defaulter->last_contact_date 
            ? Carbon::parse($defaulter->last_contact_date)->diffInDays(now())
            : 999;
        
        $noRecentContact = $lastContactDays >= 7; // No contact in last 7 days
        
        return $meetsTimeThreshold && $noRecentContact && $daysSinceLastEscalation >= 3;
    }

    /**
     * Escalate the defaulter to next level
     */
    private function escalateDefaulter(PaymentDefaulter $defaulter, array $criteria, bool $autoAssign): void
    {
        $nextLevel = $criteria['next_level'];
        $oldCategory = $defaulter->defaulter_category;
        
        $defaulter->update([
            'defaulter_category' => $nextLevel === 'legal_action' ? 'chronic' : $nextLevel,
            'escalation_level' => $defaulter->escalation_level + 1,
            'current_status' => $nextLevel === 'legal_action' ? 'legal_action' : 'escalated',
            'last_contact_date' => now(),
            'next_action_date' => now()->addDays(7), // Schedule follow-up in 7 days
        ]);

        // Add escalation note
        $notes = $defaulter->notes ? json_decode($defaulter->notes, true) : [];
        $notes[] = [
            'date' => now()->toDateTimeString(),
            'action' => 'escalation',
            'details' => "Escalated from {$oldCategory} to {$nextLevel}",
            'system_generated' => true
        ];
        
        $defaulter->update(['notes' => json_encode($notes)]);

        $this->line("🚨 Escalated: {$defaulter->student->name} ({$oldCategory} → {$nextLevel})");
    }

    /**
     * Send escalation notification
     */
    private function sendEscalationNotification(PaymentDefaulter $defaulter): void
    {
        try {
            $student = $defaulter->student;
            $feeCategory = $student->invoices()
                ->where('status', '!=', 'paid')
                ->with('items.feeCategory')
                ->first()
                ->items
                ->first()
                ->feeCategory ?? null;

            if ($feeCategory) {
                $this->reminderService->scheduleReminder(
                    $student,
                    $feeCategory,
                    'escalation',
                    now(),
                    null,
                    ['escalation_level' => $defaulter->escalation_level]
                );
            }
        } catch (\Exception $e) {
            $this->error("Failed to send escalation notification: " . $e->getMessage());
        }
    }

    /**
     * Auto-assign defaulter to collection staff
     */
    private function autoAssignDefaulter(PaymentDefaulter $defaulter): void
    {
        // Get available collection staff (users with 'manage financials' permission)
        $collectionStaff = \App\Models\User::permission('manage financials')
            ->where('is_active', true)
            ->get();

        if ($collectionStaff->isNotEmpty()) {
            // Simple round-robin assignment
            $assignedStaff = $collectionStaff->random();
            
            $defaulter->update([
                'assigned_to' => $assignedStaff->id
            ]);

            $this->line("👤 Assigned to: {$assignedStaff->name}");
        }
    }
}