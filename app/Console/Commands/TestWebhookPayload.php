<?php

// Create this file: app/Console/Commands/TestWebhookPayload.php

namespace App\Console\Commands;

use App\Events\EloquentWebhookEvent;
use App\Models\Payment;
use App\Services\WebhookPayloadBuilder;
use Illuminate\Console\Command;

class TestWebhookPayload extends Command
{
    protected $signature = 'webhook:test-payload {payment_id?}';

    protected $description = 'Test webhook payload generation for a specific payment';

    public function handle()
    {
        $paymentId = $this->argument('payment_id');

        if (! $paymentId) {
            // Get the latest component payment
            $payment = Payment::where('payment_type', 'component')
                ->with(['componentItems.studentFee.feeCategory', 'student'])
                ->latest()
                ->first();

            if (! $payment) {
                $this->error('No component payments found in the database');

                return;
            }

            $this->info("Using latest payment ID: {$payment->id}");
        } else {
            $payment = Payment::with(['componentItems.studentFee.feeCategory', 'student'])
                ->find($paymentId);

            if (! $payment) {
                $this->error("Payment with ID {$paymentId} not found");

                return;
            }
        }

        $this->info("Testing webhook payload for Payment ID: {$payment->id}");
        $this->info("Payment Type: {$payment->payment_type}");
        $this->info("Payment Amount: ₹{$payment->amount}");
        $this->info('Component Items Count: '.$payment->componentItems->count());

        // Create a mock webhook event
        $event = new EloquentWebhookEvent($payment, 'created', 'Payment');

        // Generate the payload
        $payload = WebhookPayloadBuilder::buildOptimizedPayload($event);

        $this->info("\n🎯 Generated Webhook Payload:");
        $this->line('==========================================');

        // Pretty print the JSON
        $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->line('==========================================');

        // Validate components_paid array
        $componentsPaid = $payload['data']['components_paid'] ?? [];

        if (empty($componentsPaid)) {
            $this->error("\n❌ ISSUE: components_paid array is empty!");

            // Debug information
            $this->warn("\n🔍 Debug Information:");
            $this->warn('Payment Type: '.($payment->payment_type ?? 'null'));
            $this->warn('Is Component Payment: '.($payment->isComponentPayment() ? 'Yes' : 'No'));
            $this->warn('Component Items Loaded: '.($payment->relationLoaded('componentItems') ? 'Yes' : 'No'));
            $this->warn('Component Items Count: '.$payment->componentItems->count());

            if ($payment->componentItems->count() > 0) {
                $this->warn("\n📋 Component Items Details:");
                foreach ($payment->componentItems as $item) {
                    $this->warn("- Item ID: {$item->id}, Amount: {$item->amount_paid}");
                    $this->warn("  Student Fee ID: {$item->student_fee_id}");
                    $this->warn('  Category: '.($item->studentFee?->feeCategory?->name ?? 'Not loaded'));
                }
            }
        } else {
            $this->info("\n✅ SUCCESS: components_paid array contains ".count($componentsPaid).' items');

            foreach ($componentsPaid as $index => $component) {
                $this->info('Component '.($index + 1).": {$component['category_name']} - ₹{$component['amount_paid']}");
            }
        }

        $this->info("\n✅ Webhook payload test completed!");
    }
}
