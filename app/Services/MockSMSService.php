<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class MockSMSService
{
    protected $mockResponses;

    protected $failureRate;

    protected $delaySimulation;

    public function __construct()
    {
        $this->mockResponses = config('services.mock_sms.responses', []);
        $this->failureRate = config('services.mock_sms.failure_rate', 0.1); // 10% failure rate
        $this->delaySimulation = config('services.mock_sms.delay_simulation', true);
    }

    /**
     * Send payment reminder SMS (Mock Implementation)
     */
    public function sendPaymentReminder(string $phoneNumber, string $message): bool
    {
        try {
            // Simulate network delay
            if ($this->delaySimulation) {
                usleep(rand(100000, 500000)); // 0.1 to 0.5 seconds
            }

            // Simulate random failures
            if (rand(1, 100) <= ($this->failureRate * 100)) {
                Log::warning('Mock SMS failure simulation', [
                    'phone' => $phoneNumber,
                    'reason' => 'Simulated network error',
                ]);

                return false;
            }

            // Log successful mock SMS
            Log::info('Mock SMS sent successfully', [
                'phone' => $this->formatPhoneNumber($phoneNumber),
                'message_length' => strlen($message),
                'message_preview' => substr($message, 0, 50).'...',
                'mock_message_id' => 'MOCK_'.uniqid(),
                'timestamp' => now()->toISOString(),
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Mock SMS service error', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send bulk SMS (Mock Implementation)
     */
    public function sendBulkSMS(array $recipients, string $message): array
    {
        $results = [];

        foreach ($recipients as $recipient) {
            $phoneNumber = is_array($recipient) ? $recipient['phone'] : $recipient;
            $results[$phoneNumber] = $this->sendPaymentReminder($phoneNumber, $message);
        }

        Log::info('Mock bulk SMS completed', [
            'total_recipients' => count($recipients),
            'successful' => count(array_filter($results)),
            'failed' => count($recipients) - count(array_filter($results)),
        ]);

        return $results;
    }

    /**
     * Check SMS delivery status (Mock Implementation)
     */
    public function checkDeliveryStatus(string $messageId): array
    {
        // Simulate different delivery statuses
        $statuses = ['delivered', 'pending', 'failed', 'expired'];
        $status = $statuses[array_rand($statuses)];

        return [
            'message_id' => $messageId,
            'status' => $status,
            'delivered_at' => $status === 'delivered' ? now()->subMinutes(rand(1, 30))->toISOString() : null,
            'error_message' => $status === 'failed' ? 'Mock delivery failure' : null,
        ];
    }

    /**
     * Get SMS balance (Mock Implementation)
     */
    public function getBalance(): array
    {
        return [
            'balance' => rand(100, 1000),
            'currency' => 'INR',
            'last_updated' => now()->toISOString(),
        ];
    }

    /**
     * Validate phone number format
     */
    public function validatePhoneNumber(string $phone): bool
    {
        $formatted = $this->formatPhoneNumber($phone);

        return ! is_null($formatted) && strlen($formatted) === 10;
    }

    /**
     * Format phone number for Indian mobile numbers
     */
    private function formatPhoneNumber(string $phone): ?string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (strlen($phone) === 11 && substr($phone, 0, 1) === '0') {
            $phone = substr($phone, 1); // Remove leading 0
        }

        return strlen($phone) === 10 ? $phone : null;
    }

    /**
     * Get mock service statistics
     */
    public function getServiceStats(): array
    {
        return [
            'service_type' => 'mock',
            'total_sent' => rand(1000, 5000),
            'success_rate' => (1 - $this->failureRate) * 100,
            'average_delivery_time' => rand(30, 120).' seconds',
            'last_activity' => now()->subMinutes(rand(1, 60))->toISOString(),
        ];
    }
}
