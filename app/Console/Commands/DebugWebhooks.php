<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Payment;
use App\Models\Webhook;
use App\Models\Invoice;
use App\Models\Student;

class DebugWebhooks extends Command
{
    protected $signature = 'webhooks:debug {--test-payment : Create a test payment to trigger webhooks}';
    protected $description = 'Debug webhook configuration and test webhook events';

    public function handle()
    {
        $this->info('🔍 Debugging Webhook Configuration...');
        $this->newLine();

        // Check if webhooks exist
        $webhooks = Webhook::where('is_active', true)->get();
        if ($webhooks->isEmpty()) {
            $this->error('❌ No active webhooks found!');
            $this->info('💡 Create a webhook first in the admin panel.');
            return;
        }

        $this->info('✅ Found ' . $webhooks->count() . ' active webhook(s):');
        foreach ($webhooks as $webhook) {
            // Handle both event_name (single) and events (array) structures
            $eventDisplay = '';
            if (isset($webhook->event_name)) {
                $eventDisplay = $webhook->event_name;
            } elseif (isset($webhook->events)) {
                $events = is_array($webhook->events) ? $webhook->events : json_decode($webhook->events, true) ?? [$webhook->events];
                $eventDisplay = implode(', ', $events);
            } else {
                $eventDisplay = 'Unknown event';
            }
            
            $this->line("   • Event: {$eventDisplay} - URL: {$webhook->url}");
            $this->line("     Active: " . ($webhook->is_active ? '✅ Yes' : '❌ No'));
            if ($webhook->description) {
                $this->line("     Description: {$webhook->description}");
            }
        }
        $this->newLine();

        // Check Payment model configuration
        $this->info('🔧 Checking Payment Model Configuration...');
        $payment = new Payment();
        
        if (method_exists($payment, 'areWebhooksEnabled')) {
            $this->info('✅ Payment model has WebhookEnabled trait');
            $this->line('   Webhook Events: ' . implode(', ', $payment->getWebhookEvents()));
            $this->line('   Webhooks Enabled: ' . ($payment->areWebhooksEnabled() ? '✅ Yes' : '❌ No'));
        } else {
            $this->error('❌ Payment model missing WebhookEnabled trait');
        }
        $this->newLine();

        // Check UniversalWebhookListener
        $listenerClass = '\App\Listeners\UniversalWebhookListener';
        if (class_exists($listenerClass)) {
            $this->info('✅ UniversalWebhookListener exists');
        } else {
            $this->error('❌ UniversalWebhookListener missing');
        }
        $this->newLine();

        // Check Event Service Provider
        $this->info('🔧 Checking Event Registration...');
        $eventServiceProvider = app()->getProviders(\App\Providers\EventServiceProvider::class);
        if (!empty($eventServiceProvider)) {
            $this->info('✅ EventServiceProvider found');
        } else {
            $this->warn('⚠️  EventServiceProvider not found or not registered');
        }
        $this->newLine();

        // Test payment creation if requested
        if ($this->option('test-payment')) {
            $this->testPaymentWebhook();
        }

        $this->info('💡 To test webhooks:');
        $this->line('   1. Run: php artisan webhooks:debug --test-payment');
        $this->line('   2. Check webhook logs in the admin panel');
        $this->line('   3. Check Laravel logs: tail -f storage/logs/laravel.log');
    }

    private function testPaymentWebhook()
    {
        $this->info('🧪 Creating test payment to trigger webhooks...');

        try {
            // Find a student and invoice for testing
            $invoice = Invoice::with('student')->first();
            if (!$invoice) {
                $this->error('❌ No invoices found. Create an invoice first.');
                return;
            }

            // Create test payment
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'amount' => 100.00,
                'payment_date' => now(),
                'payment_method' => 'Test',
                'notes' => 'Test payment for webhook debugging',
                'receipt_number' => 'TEST-' . time(),
            ]);

            $this->info('✅ Test payment created with ID: ' . $payment->id);
            $this->line('   Invoice: ' . $invoice->invoice_number);
            $this->line('   Student: ' . $invoice->student->name);
            $this->line('   Amount: ₹100.00');

            // Wait a moment for webhook processing
            sleep(2);

            // Check webhook calls
            $webhookCalls = \App\Models\WebhookCall::latest()->take(5)->get();
            if ($webhookCalls->count() > 0) {
                $this->info('📡 Recent webhook calls:');
                foreach ($webhookCalls as $call) {
                    $status = $call->success ? '✅' : '❌';
                    $this->line("   {$status} {$call->created_at} - Status: {$call->status_code}");
                }
            } else {
                $this->warn('⚠️  No webhook calls found. Check configuration.');
            }

        } catch (\Exception $e) {
            $this->error('❌ Failed to create test payment: ' . $e->getMessage());
        }
    }
}