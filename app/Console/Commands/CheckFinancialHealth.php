<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\Admin\InvoiceController;

class CheckFinancialHealth extends Command
{
    protected $signature = 'finance:health-check';
    protected $description = 'Check financial health and send alerts for high overdue amounts';

    public function handle()
    {
        $this->info('🏥 Checking Financial Health...');
        
        $controller = app(InvoiceController::class);
        $result = $controller->checkFinancialHealth();
        $data = $result->getData();

        $this->line("💰 Total Overdue: ₹" . number_format($data->total_overdue));
        $this->line("📄 Overdue Invoices: " . $data->overdue_count);
        $this->line("🩺 Health Status: " . strtoupper($data->health_status));

        if ($data->health_status === 'critical') {
            $this->error("⚠️  CRITICAL: High overdue amount detected!");
        } else {
            $this->info("✅ Financial health is good");
        }

        return 0;
    }
}