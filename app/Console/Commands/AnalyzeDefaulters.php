<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PaymentReminderService;
use App\Models\PaymentDefaulter;
use App\Models\Student;

class AnalyzeDefaulters extends Command
{
    protected $signature = 'defaulters:analyze {--update-db : Update defaulter records in database}';
    protected $description = 'Analyze and categorize payment defaulters';

    public function handle()
    {
        $this->info('🔍 Analyzing Payment Defaulters...');
        
        try {
            $reminderService = app(PaymentReminderService::class);
            $defaulters = $reminderService->generateDefaultersList();
            
            if (empty($defaulters)) {
                $this->info('✅ No payment defaulters found!');
                return 0;
            }
            
            $this->info("Found " . count($defaulters) . " defaulters");
            
            // Group by category
            $categories = [];
            foreach ($defaulters as $defaulter) {
                $category = $defaulter['defaulter_category'];
                if (!isset($categories[$category])) {
                    $categories[$category] = [];
                }
                $categories[$category][] = $defaulter;
            }
            
            // Display summary
            $this->newLine();
            $this->info('📊 Defaulter Analysis Summary:');
            $this->table(
                ['Category', 'Count', 'Total Amount', 'Avg Days Overdue'],
                collect($categories)->map(function($defaulters, $category) {
                    return [
                        ucfirst($category),
                        count($defaulters),
                        '₹' . number_format(array_sum(array_column($defaulters, 'total_overdue_amount'))),
                        round(array_sum(array_column($defaulters, 'overdue_days')) / count($defaulters))
                    ];
                })->toArray()
            );
            
            // Show top 10 defaulters
            $this->newLine();
            $this->info('🔥 Top 10 Defaulters by Amount:');
            $topDefaulters = array_slice($defaulters, 0, 10);
            
            $this->table(
                ['Student', 'Enrollment', 'Course', 'Amount', 'Days', 'Category'],
                collect($topDefaulters)->map(function($defaulter) {
                    return [
                        $defaulter['student_name'],
                        $defaulter['enrollment_number'],
                        $defaulter['course'],
                        '₹' . number_format($defaulter['total_overdue_amount']),
                        $defaulter['overdue_days'],
                        $defaulter['defaulter_category']
                    ];
                })->toArray()
            );
            
            if ($this->option('update-db')) {
                $this->updateDefaulterDatabase($defaulters);
            } else {
                $this->newLine();
                $this->comment('💡 Use --update-db flag to update the defaulter database');
            }
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('❌ Error analyzing defaulters: ' . $e->getMessage());
            if ($this->option('verbose')) {
                $this->line($e->getTraceAsString());
            }
            return 1;
        }
    }
    
    private function updateDefaulterDatabase($defaulters)
    {
        $this->info('📝 Updating defaulter database...');
        
        try {
            // Don't truncate - instead update/create records
            $bar = $this->output->createProgressBar(count($defaulters));
            $updated = 0;
            $created = 0;
            
            foreach ($defaulters as $defaulter) {
                $record = PaymentDefaulter::updateOrCreate(
                    ['student_id' => $defaulter['student_id']],
                    [
                        'defaulter_category' => $defaulter['defaulter_category'],
                        'total_overdue_amount' => $defaulter['total_overdue_amount'],
                        'overdue_days' => $defaulter['overdue_days'],
                        'total_overdue_invoices' => $defaulter['overdue_invoice_count'],
                        'first_overdue_date' => $defaulter['oldest_due_date'],
                        'overdue_fee_types' => json_encode($defaulter['overdue_fee_types']),
                        'current_status' => 'active'
                    ]
                );
                
                if ($record->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }
                
                $bar->advance();
            }
            
            $bar->finish();
            $this->newLine(2);
            $this->info("✅ Database updated successfully!");
            $this->line("📊 Created: {$created} records");
            $this->line("📊 Updated: {$updated} records");
            
            // Clean up resolved defaulters
            $resolvedCount = PaymentDefaulter::whereNotIn('student_id', 
                collect($defaulters)->pluck('student_id')
            )->update(['current_status' => 'resolved', 'resolution_date' => now()]);
            
            if ($resolvedCount > 0) {
                $this->line("📊 Marked as resolved: {$resolvedCount} records");
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Failed to update database: ' . $e->getMessage());
        }
    }
}