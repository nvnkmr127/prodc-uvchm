<?php

namespace App\Console\Commands;

use App\Listeners\UniversalWebhookListener;
use App\Models\Webhook;
use App\Models\WebhookCall;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class WebhookHealthCheck extends Command
{
    protected $signature = 'webhooks:health-check {--test : Run test webhook calls}';

    protected $description = 'Check webhook system health and configuration';

    public function handle()
    {
        $this->info('🔍 Webhook System Health Check');
        $this->info('================================');

        $issues = [];

        // 1. Check database tables
        $issues = array_merge($issues, $this->checkDatabase());

        // 2. Check models and relationships
        $issues = array_merge($issues, $this->checkModels());

        // 3. Check routes
        $issues = array_merge($issues, $this->checkRoutes());

        // 4. Check listeners
        $issues = array_merge($issues, $this->checkListeners());

        // 5. Check existing webhooks
        $issues = array_merge($issues, $this->checkExistingWebhooks());

        // 6. Test webhook functionality if requested
        if ($this->option('test')) {
            $issues = array_merge($issues, $this->runTests());
        }

        // 7. Show summary
        $this->showSummary($issues);

        return empty($issues) ? 0 : 1;
    }

    protected function checkDatabase(): array
    {
        $this->info("\n📊 Checking Database Tables...");
        $issues = [];

        // Check webhooks table
        if (! Schema::hasTable('webhooks')) {
            $issues[] = '❌ webhooks table does not exist';
            $this->error('   ❌ webhooks table missing');
        } else {
            $this->info('   ✅ webhooks table exists');

            // Check required columns
            $requiredColumns = ['id', 'url', 'event_name', 'is_active', 'secret_key'];
            foreach ($requiredColumns as $column) {
                if (! Schema::hasColumn('webhooks', $column)) {
                    $issues[] = "❌ webhooks table missing column: {$column}";
                    $this->error("   ❌ Missing column: {$column}");
                } else {
                    $this->line("   ✅ Column {$column} exists");
                }
            }
        }

        // Check webhook_calls table
        if (! Schema::hasTable('webhook_calls')) {
            $issues[] = '❌ webhook_calls table does not exist';
            $this->error('   ❌ webhook_calls table missing');
        } else {
            $this->info('   ✅ webhook_calls table exists');
        }

        return $issues;
    }

    protected function checkModels(): array
    {
        $this->info("\n🏗️ Checking Models...");
        $issues = [];

        // Check Webhook model
        if (! class_exists('\App\Models\Webhook')) {
            $issues[] = '❌ Webhook model does not exist';
            $this->error('   ❌ Webhook model missing');
        } else {
            $this->info('   ✅ Webhook model exists');

            try {
                $webhook = new \App\Models\Webhook;
                $this->info('   ✅ Webhook model can be instantiated');
            } catch (\Exception $e) {
                $issues[] = "❌ Webhook model error: {$e->getMessage()}";
                $this->error("   ❌ Webhook model error: {$e->getMessage()}");
            }
        }

        // Check WebhookCall model
        if (! class_exists('\App\Models\WebhookCall')) {
            $issues[] = '❌ WebhookCall model does not exist';
            $this->error('   ❌ WebhookCall model missing');
        } else {
            $this->info('   ✅ WebhookCall model exists');
        }

        return $issues;
    }

    protected function checkRoutes(): array
    {
        $this->info("\n🛣️ Checking Routes...");
        $issues = [];

        $requiredRoutes = [
            'admin.webhooks.index',
            'admin.webhooks.create',
            'admin.webhooks.store',
            'admin.webhooks.show',
            'admin.webhooks.edit',
            'admin.webhooks.update',
            'admin.webhooks.destroy',
            'admin.webhooks.test',
        ];

        foreach ($requiredRoutes as $routeName) {
            try {
                $route = app('router')->getRoutes()->getByName($routeName);
                if ($route) {
                    $this->info("   ✅ Route {$routeName} exists");
                } else {
                    $issues[] = "❌ Route {$routeName} missing";
                    $this->error("   ❌ Route {$routeName} missing");
                }
            } catch (\Exception $e) {
                $issues[] = "❌ Route {$routeName} error: {$e->getMessage()}";
                $this->error("   ❌ Route {$routeName} error: {$e->getMessage()}");
            }
        }

        return $issues;
    }

    protected function checkListeners(): array
    {
        $this->info("\n👂 Checking Event Listeners...");
        $issues = [];

        // Check UniversalWebhookListener
        if (! class_exists('\App\Listeners\UniversalWebhookListener')) {
            $issues[] = '❌ UniversalWebhookListener does not exist';
            $this->error('   ❌ UniversalWebhookListener missing');
        } else {
            $this->info('   ✅ UniversalWebhookListener exists');

            try {
                $listener = new UniversalWebhookListener;
                $this->info('   ✅ UniversalWebhookListener can be instantiated');
            } catch (\Exception $e) {
                $issues[] = "❌ UniversalWebhookListener error: {$e->getMessage()}";
                $this->error("   ❌ UniversalWebhookListener error: {$e->getMessage()}");
            }
        }

        return $issues;
    }

    protected function checkExistingWebhooks(): array
    {
        $this->info("\n🔗 Checking Existing Webhooks...");
        $issues = [];

        try {
            $totalWebhooks = Webhook::count();
            $activeWebhooks = Webhook::where('is_active', true)->count();
            $recentCalls = WebhookCall::where('created_at', '>=', now()->subDay())->count();

            $this->info("   📊 Total webhooks: {$totalWebhooks}");
            $this->info("   ✅ Active webhooks: {$activeWebhooks}");
            $this->info("   📞 Recent calls (24h): {$recentCalls}");

            if ($totalWebhooks === 0) {
                $this->warn('   ⚠️ No webhooks configured');
            }

            // Check webhook health
            $failingWebhooks = Webhook::where('consecutive_failures', '>=', 3)->count();
            if ($failingWebhooks > 0) {
                $issues[] = "⚠️ {$failingWebhooks} webhooks are failing";
                $this->warn("   ⚠️ {$failingWebhooks} webhooks are failing");
            }

        } catch (\Exception $e) {
            $issues[] = "❌ Error checking webhooks: {$e->getMessage()}";
            $this->error("   ❌ Error checking webhooks: {$e->getMessage()}");
        }

        return $issues;
    }

    protected function runTests(): array
    {
        $this->info("\n🧪 Running Webhook Tests...");
        $issues = [];

        // Test creating a webhook
        try {
            $testWebhook = Webhook::create([
                'url' => 'https://httpbin.org/post',
                'event_name' => 'test.webhook',
                'description' => 'Test webhook created by health check',
                'is_active' => true,
                'secret_key' => 'test-secret-'.uniqid(),
            ]);

            $this->info('   ✅ Test webhook created successfully');

            // Test sending to the webhook
            $payload = [
                'event' => 'test.webhook',
                'event_id' => uniqid('test_'),
                'created_at' => now()->toIso8601String(),
                'data' => ['test' => true],
            ];

            $signature = hash_hmac('sha256', json_encode($payload), $testWebhook->secret_key);

            $response = Http::timeout(10)
                ->withHeaders(['X-App-Signature' => $signature])
                ->post($testWebhook->url, $payload);

            if ($response->successful()) {
                $this->info('   ✅ Test webhook call successful');

                // Log the call
                $testWebhook->calls()->create([
                    'success' => true,
                    'status_code' => $response->status(),
                    'payload' => $payload,
                    'response_body' => $response->body(),
                    'execution_time_ms' => 100,
                ]);

            } else {
                $issues[] = "❌ Test webhook call failed: HTTP {$response->status()}";
                $this->error("   ❌ Test webhook call failed: HTTP {$response->status()}");
            }

            // Clean up test webhook
            $testWebhook->calls()->delete();
            $testWebhook->delete();
            $this->info('   🧹 Test webhook cleaned up');

        } catch (\Exception $e) {
            $issues[] = "❌ Webhook test failed: {$e->getMessage()}";
            $this->error("   ❌ Webhook test failed: {$e->getMessage()}");
        }

        return $issues;
    }

    protected function showSummary(array $issues): void
    {
        $this->info("\n📋 Health Check Summary");
        $this->info('========================');

        if (empty($issues)) {
            $this->info('🎉 All webhook system checks passed!');
            $this->info('Your webhook system is healthy and ready to use.');
        } else {
            $this->error('❌ Found '.count($issues).' issue(s):');
            foreach ($issues as $issue) {
                $this->error("   {$issue}");
            }

            $this->info("\n💡 Recommended Actions:");
            if (in_array('❌ webhooks table does not exist', $issues)) {
                $this->info('   • Run: php artisan migrate');
            }
            if (str_contains(implode(' ', $issues), 'Route') && str_contains(implode(' ', $issues), 'missing')) {
                $this->info('   • Check routes/web.php for webhook routes');
                $this->info('   • Run: php artisan route:clear');
            }
            if (str_contains(implode(' ', $issues), 'model')) {
                $this->info('   • Ensure Webhook and WebhookCall models exist');
                $this->info('   • Run: composer dump-autoload');
            }
        }

        $this->info("\n🔧 Next Steps:");
        $this->info('   • Visit /admin/webhooks to manage webhooks');
        $this->info('   • Create test webhooks at https://webhook.site');
        $this->info('   • Check logs for webhook delivery status');
    }
}
