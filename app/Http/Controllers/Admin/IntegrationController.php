<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class IntegrationController extends Controller
{
    /**
     * Show payment gateways configuration
     */
    public function paymentGateways()
    {
        try {
            // Get current payment gateway settings
            $gateways = [
                'razorpay' => [
                    'name' => 'Razorpay',
                    'enabled' => $this->getSetting('razorpay_enabled', false),
                    'key_id' => $this->getSetting('razorpay_key_id', ''),
                    'key_secret' => $this->getSetting('razorpay_key_secret', ''),
                    'webhook_secret' => $this->getSetting('razorpay_webhook_secret', ''),
                    'test_mode' => $this->getSetting('razorpay_test_mode', true),
                    'logo' => '/images/razorpay-logo.png',
                    'supported_methods' => ['card', 'netbanking', 'wallet', 'upi'],
                ],
                'payu' => [
                    'name' => 'PayU',
                    'enabled' => $this->getSetting('payu_enabled', false),
                    'merchant_key' => $this->getSetting('payu_merchant_key', ''),
                    'merchant_salt' => $this->getSetting('payu_merchant_salt', ''),
                    'test_mode' => $this->getSetting('payu_test_mode', true),
                    'logo' => '/images/payu-logo.png',
                    'supported_methods' => ['card', 'netbanking', 'wallet'],
                ],
                'phonepe' => [
                    'name' => 'PhonePe',
                    'enabled' => $this->getSetting('phonepe_enabled', false),
                    'merchant_id' => $this->getSetting('phonepe_merchant_id', ''),
                    'api_key' => $this->getSetting('phonepe_api_key', ''),
                    'test_mode' => $this->getSetting('phonepe_test_mode', true),
                    'logo' => '/images/phonepe-logo.png',
                    'supported_methods' => ['upi', 'wallet'],
                ],
                'stripe' => [
                    'name' => 'Stripe',
                    'enabled' => $this->getSetting('stripe_enabled', false),
                    'publishable_key' => $this->getSetting('stripe_publishable_key', ''),
                    'secret_key' => $this->getSetting('stripe_secret_key', ''),
                    'webhook_secret' => $this->getSetting('stripe_webhook_secret', ''),
                    'test_mode' => $this->getSetting('stripe_test_mode', true),
                    'logo' => '/images/stripe-logo.png',
                    'supported_methods' => ['card'],
                ],
            ];

            // Get gateway statistics
            $stats = $this->getGatewayStats();

            return view('admin.integrations.payment-gateways', compact('gateways', 'stats'));

        } catch (\Exception $e) {
            Log::error('Error loading payment gateways: ' . $e->getMessage());
            
            return back()->with('error', 'Error loading payment gateway settings.');
        }
    }

    /**
     * Test payment gateway connection
     */
    public function testGateway(Request $request, string $gateway)
    {
        try {
            $result = [];
            
            switch ($gateway) {
                case 'razorpay':
                    $result = $this->testRazorpay($request);
                    break;
                    
                case 'payu':
                    $result = $this->testPayU($request);
                    break;
                    
                case 'phonepe':
                    $result = $this->testPhonePe($request);
                    break;
                    
                case 'stripe':
                    $result = $this->testStripe($request);
                    break;
                    
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Unsupported gateway: ' . $gateway
                    ]);
            }

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error("Gateway test failed for {$gateway}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Test failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Show webhook configuration
     */
    public function webhooks()
    {
        try {
            $webhooks = [
                'payment_webhook' => [
                    'name' => 'Payment Webhook',
                    'url' => url('/webhook/payment'),
                    'secret' => $this->getSetting('webhook_secret', Str::random(32)),
                    'enabled' => $this->getSetting('webhook_enabled', true),
                    'events' => ['payment.created', 'payment.failed', 'payment.captured'],
                    'last_received' => $this->getSetting('webhook_last_received', null),
                    'total_received' => $this->getSetting('webhook_total_received', 0),
                ],
            ];

            // Get recent webhook calls (if you have WebhookCall model)
            $recentWebhooks = [];
            if (class_exists('\App\Models\WebhookCall')) {
                $recentWebhooks = \App\Models\WebhookCall::latest()->take(10)->get();
            }

            return view('admin.integrations.webhooks', compact('webhooks', 'recentWebhooks'));

        } catch (\Exception $e) {
            Log::error('Error loading webhook settings: ' . $e->getMessage());
            
            return back()->with('error', 'Error loading webhook settings.');
        }
    }

    /**
     * Regenerate webhook secret
     */
    public function regenerateWebhook(Request $request)
    {
        try {
            $webhookType = $request->input('type', 'payment_webhook');
            $newSecret = Str::random(32);
            
            switch ($webhookType) {
                case 'payment_webhook':
                    $this->setSetting('webhook_secret', $newSecret);
                    break;
                    
                case 'razorpay_webhook':
                    $this->setSetting('razorpay_webhook_secret', $newSecret);
                    break;
                    
                case 'stripe_webhook':
                    $this->setSetting('stripe_webhook_secret', $newSecret);
                    break;
                    
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid webhook type'
                    ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Webhook secret regenerated successfully',
                'secret' => $newSecret
            ]);

        } catch (\Exception $e) {
            Log::error('Error regenerating webhook secret: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error regenerating webhook secret'
            ]);
        }
    }

    /**
     * Test Razorpay connection
     */
    private function testRazorpay(Request $request): array
    {
        $keyId = $request->input('key_id') ?: $this->getSetting('razorpay_key_id');
        $keySecret = $request->input('key_secret') ?: $this->getSetting('razorpay_key_secret');
        
        if (!$keyId || !$keySecret) {
            return [
                'success' => false,
                'message' => 'Razorpay credentials not configured'
            ];
        }

        try {
            $response = Http::withBasicAuth($keyId, $keySecret)
                ->get('https://api.razorpay.com/v1/payments', [
                    'count' => 1
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Razorpay connection successful',
                    'data' => [
                        'api_status' => 'Connected',
                        'test_mode' => $this->getSetting('razorpay_test_mode', true),
                        'response_code' => $response->status()
                    ]
                ];
            }

            return [
                'success' => false,
                'message' => 'Razorpay API error: ' . $response->body()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Razorpay connection failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Test PayU connection
     */
    private function testPayU(Request $request): array
    {
        $merchantKey = $request->input('merchant_key') ?: $this->getSetting('payu_merchant_key');
        $merchantSalt = $request->input('merchant_salt') ?: $this->getSetting('payu_merchant_salt');
        
        if (!$merchantKey || !$merchantSalt) {
            return [
                'success' => false,
                'message' => 'PayU credentials not configured'
            ];
        }

        try {
            $testData = [
                'key' => $merchantKey,
                'command' => 'verify_payment',
                'var1' => 'test_transaction_' . time(),
                'hash' => hash('sha512', $merchantKey . '|verify_payment|test_transaction_' . time() . '|' . $merchantSalt)
            ];

            $response = Http::asForm()->post('https://info.payu.in/merchant/postservice?form=2', $testData);

            return [
                'success' => true,
                'message' => 'PayU connection test completed',
                'data' => [
                    'api_status' => $response->successful() ? 'Connected' : 'Error',
                    'test_mode' => $this->getSetting('payu_test_mode', true),
                    'response_code' => $response->status()
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'PayU connection failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Test PhonePe connection
     */
    private function testPhonePe(Request $request): array
    {
        $merchantId = $request->input('merchant_id') ?: $this->getSetting('phonepe_merchant_id');
        $apiKey = $request->input('api_key') ?: $this->getSetting('phonepe_api_key');
        
        if (!$merchantId || !$apiKey) {
            return [
                'success' => false,
                'message' => 'PhonePe credentials not configured'
            ];
        }

        try {
            $testUrl = $this->getSetting('phonepe_test_mode', true) 
                ? 'https://api-preprod.phonepe.com/apis/merchant-simulator'
                : 'https://api.phonepe.com/apis/hermes';

            $response = Http::withHeaders([
                'X-VERIFY' => hash('sha256', 'test' . $apiKey) . '###1',
                'Content-Type' => 'application/json'
            ])->get($testUrl . '/status');

            return [
                'success' => true,
                'message' => 'PhonePe connection test completed',
                'data' => [
                    'api_status' => $response->successful() ? 'Connected' : 'Error',
                    'test_mode' => $this->getSetting('phonepe_test_mode', true),
                    'response_code' => $response->status()
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'PhonePe connection failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Test Stripe connection
     */
    private function testStripe(Request $request): array
    {
        $secretKey = $request->input('secret_key') ?: $this->getSetting('stripe_secret_key');
        
        if (!$secretKey) {
            return [
                'success' => false,
                'message' => 'Stripe secret key not configured'
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $secretKey,
                'Content-Type' => 'application/x-www-form-urlencoded'
            ])->get('https://api.stripe.com/v1/balance');

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'message' => 'Stripe connection successful',
                    'data' => [
                        'api_status' => 'Connected',
                        'test_mode' => $this->getSetting('stripe_test_mode', true),
                        'account_type' => ($data['livemode'] ?? false) ? 'Live' : 'Test'
                    ]
                ];
            }

            return [
                'success' => false,
                'message' => 'Stripe API error: ' . $response->body()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Stripe connection failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get payment gateway statistics
     */
    private function getGatewayStats(): array
    {
        try {
            if (class_exists('\App\Models\Payment')) {
                return [
                    'total_transactions' => \App\Models\Payment::count(),
                    'total_amount' => \App\Models\Payment::sum('amount'),
                    'this_month_transactions' => \App\Models\Payment::whereMonth('created_at', now()->month)->count(),
                    'this_month_amount' => \App\Models\Payment::whereMonth('created_at', now()->month)->sum('amount'),
                ];
            }

            return [
                'total_transactions' => 0,
                'total_amount' => 0,
                'this_month_transactions' => 0,
                'this_month_amount' => 0,
            ];

        } catch (\Exception $e) {
            Log::error('Error getting gateway stats: ' . $e->getMessage());
            return [
                'total_transactions' => 0,
                'total_amount' => 0,
                'this_month_transactions' => 0,
                'this_month_amount' => 0,
            ];
        }
    }

    /**
     * Get setting value
     */
    private function getSetting($key, $default = null)
    {
        try {
            // Try Settings model first
            if (class_exists('\App\Models\Setting')) {
                $setting = \App\Models\Setting::where('key', $key)->first();
                return $setting ? $setting->value : $default;
            }
            
            // Fall back to config
            return config('settings.' . $key, $default);
        } catch (\Exception $e) {
            return $default;
        }
    }

    /**
     * Set setting value
     */
    private function setSetting($key, $value)
    {
        try {
            if (class_exists('\App\Models\Setting')) {
                return \App\Models\Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value]
                );
            }
            
            // Log that we couldn't save the setting
            Log::warning("Could not save setting {$key}: Settings model not available");
            return false;
        } catch (\Exception $e) {
            Log::error("Error saving setting {$key}: " . $e->getMessage());
            return false;
        }
    }
}