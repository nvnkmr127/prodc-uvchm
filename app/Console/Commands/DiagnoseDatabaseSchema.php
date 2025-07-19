<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Invoice;

class DiagnoseDatabaseSchema extends Command
{
    protected $signature = 'db:diagnose-status';
    protected $description = 'Diagnose invoice status column issues';

    public function handle()
    {
        $this->info('🔍 Diagnosing Invoice Status Column...');
        $this->newLine();

        // Check table exists
        if (!Schema::hasTable('invoices')) {
            $this->error('❌ Invoices table does not exist!');
            return;
        }

        // Get column information
        $this->info('📋 Getting column details for invoices.status:');
        
        try {
            $columns = DB::select("SHOW COLUMNS FROM invoices WHERE Field = 'status'");
            
            if (empty($columns)) {
                $this->error('❌ Status column not found in invoices table!');
                return;
            }

            $statusColumn = $columns[0];
            $this->line("   Column Type: {$statusColumn->Type}");
            $this->line("   Null: {$statusColumn->Null}");
            $this->line("   Default: {$statusColumn->Default}");
            $this->line("   Extra: {$statusColumn->Extra}");
            
        } catch (\Exception $e) {
            $this->error('Error getting column info: ' . $e->getMessage());
        }

        $this->newLine();

        // Test ENUM values
        $this->info('🧪 Testing ENUM values:');
        $testValues = ['unpaid', 'partially_paid', 'paid', 'cancelled'];
        
        foreach ($testValues as $value) {
            try {
                // Test if we can insert this value
                $testQuery = "SELECT '{$value}' as test_value WHERE '{$value}' IN ('unpaid', 'partially_paid', 'paid', 'cancelled')";
                $result = DB::select($testQuery);
                
                if (!empty($result)) {
                    $this->line("   ✅ '{$value}' - Valid");
                } else {
                    $this->line("   ❌ '{$value}' - Invalid");
                }
                
            } catch (\Exception $e) {
                $this->line("   ❌ '{$value}' - Error: " . $e->getMessage());
            }
        }

        $this->newLine();

        // Test actual update
        $this->info('🔧 Testing actual invoice update:');
        
        // Find an invoice to test with
        $invoice = Invoice::first();
        if (!$invoice) {
            $this->warn('No invoices found to test with');
            return;
        }

        $this->line("Testing with Invoice #{$invoice->id}");
        $this->line("Current status: {$invoice->status}");
        
        // Test direct SQL update
        try {
            $this->line('Testing direct SQL update...');
            DB::statement("UPDATE invoices SET status = 'partially_paid' WHERE id = ? LIMIT 1", [$invoice->id]);
            $this->line('   ✅ Direct SQL update successful');
            
            // Revert back
            DB::statement("UPDATE invoices SET status = ? WHERE id = ? LIMIT 1", [$invoice->status, $invoice->id]);
            
        } catch (\Exception $e) {
            $this->line('   ❌ Direct SQL update failed: ' . $e->getMessage());
        }

        // Test Eloquent update
        try {
            $this->line('Testing Eloquent update...');
            $originalStatus = $invoice->status;
            
            $invoice->status = 'partially_paid';
            $invoice->save();
            
            $this->line('   ✅ Eloquent update successful');
            
            // Revert back
            $invoice->status = $originalStatus;
            $invoice->save();
            
        } catch (\Exception $e) {
            $this->line('   ❌ Eloquent update failed: ' . $e->getMessage());
        }

        $this->newLine();
        $this->info('💡 Recommendations:');
        $this->line('If direct SQL works but Eloquent fails, the issue is in the model or observers.');
        $this->line('If both fail, the database schema needs to be fixed.');
    }
}