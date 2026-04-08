<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class MockWhatsAppService
{
    protected $mockResponses;

    protected $failureRate;

    protected $delaySimulation;

    protected $templateValidation;

    public function __construct()
    {
        $this->mockResponses = config('services.mock_whatsapp.responses', []);
        $this->failureRate = config('services.mock_whatsapp.failure_rate', 0.05); // 5% failure rate
        $this->delaySimulation = config('services.mock_whatsapp.delay_simulation', true);
        $this->templateValidation = config('services.mock_whatsapp.template_validation', true);
    }

    /**
     * Send payment reminder via WhatsApp (Mock Implementation)
     */
    public function sendPaymentReminder(string $phoneNumber, string $message, array $data = []): bool
    {
        try {
            // Simulate network delay
            if ($this->delaySimulation) {
                usleep(rand(200000, 800000)); // 0.2 to 0.8 seconds
            }

            // Validate phone number format
            $formattedPhone = $this->formatPhoneNumber($phoneNumber);
            if (! $formattedPhone) {
                Log::error('Mock WhatsApp: Invalid phone number format', [
                    'phone' => $phoneNumber,
                ]);

                return false;
            }

            // Simulate random failures
            if (rand(1, 100) <= ($this->failureRate * 100)) {
                Log::warning('Mock WhatsApp failure simulation', [
                    'phone' => $phoneNumber,
                    'reason' => 'Simulated API error',
                ]);

                return false;
            }

            // Validate template data if enabled
            if ($this->templateValidation && ! $this->validateTemplateData($data)) {
                Log::error('Mock WhatsApp: Invalid template data', [
                    'phone' => $phoneNumber,
                    'data' => $data,
                ]);

                return false;
            }

            // Log successful mock WhatsApp message
            Log::info('Mock WhatsApp sent successfully', [
                'phone' => $formattedPhone,
                'template' => 'payment_reminder',
                'data' => $data,
                'mock_message_id' => 'MOCK_WA_'.uniqid(),
                'timestamp' => now()->toISOString(),
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Mock WhatsApp service error', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send template message (Mock Implementation)
     */
    public function sendTemplateMessage(string $phoneNumber, string $templateName, array $parameters = []): bool
    {
        try {
            // Simulate network delay
            if ($this->delaySimulation) {
                usleep(rand(200000, 600000));
            }

            $formattedPhone = $this->formatPhoneNumber($phoneNumber);
            if (! $formattedPhone) {
                return false;
            }

            // Simulate template validation
            if (! $this->isValidTemplate($templateName)) {
                Log::error('Mock WhatsApp: Invalid template', [
                    'phone' => $phoneNumber,
                    'template' => $templateName,
                ]);

                return false;
            }

            Log::info('Mock WhatsApp template message sent', [
                'phone' => $formattedPhone,
                'template' => $templateName,
                'parameters' => $parameters,
                'mock_message_id' => 'MOCK_WA_TPL_'.uniqid(),
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Mock WhatsApp template error', [
                'phone' => $phoneNumber,
                'template' => $templateName,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send bulk WhatsApp messages (Mock Implementation)
     */
    public function sendBulkMessages(array $recipients, string $templateName, array $commonData = []): array
    {
        $results = [];

        foreach ($recipients as $recipient) {
            $phoneNumber = is_array($recipient) ? $recipient['phone'] : $recipient;
            $data = is_array($recipient) ? array_merge($commonData, $recipient['data'] ?? []) : $commonData;

            $results[$phoneNumber] = $this->sendTemplateMessage($phoneNumber, $templateName, $data);
        }

        Log::info('Mock WhatsApp bulk messages completed', [
            'template' => $templateName,
            'total_recipients' => count($recipients),
            'successful' => count(array_filter($results)),
            'failed' => count($recipients) - count(array_filter($results)),
        ]);

        return $results;
    }

    /**
     * Check message delivery status (Mock Implementation)
     */
    public function checkDeliveryStatus(string $messageId): array
    {
        // Simulate different delivery statuses
        $statuses = ['sent', 'delivered', 'read', 'failed'];
        $status = $statuses[array_rand($statuses)];

        $response = [
            'message_id' => $messageId,
            'status' => $status,
            'timestamp' => now()->subMinutes(rand(1, 30))->toISOString(),
        ];

        if ($status === 'failed') {
            $response['error'] = [
                'code' => rand(100, 999),
                'message' => 'Mock delivery failure',
            ];
        }

        return $response;
    }

    /**
     * Get WhatsApp Business account info (Mock Implementation)
     */
    public function getAccountInfo(): array
    {
        return [
            'account_id' => 'MOCK_ACCOUNT_'.uniqid(),
            'business_name' => 'Mock College Management System',
            'phone_number' => '+91'.rand(7000000000, 9999999999),
            'verified' => true,
            'tier' => 'standard',
            'messaging_limit' => rand(1000, 10000),
            'last_updated' => now()->toISOString(),
        ];
    }

    /**
     * Validate template data structure
     */
    private function validateTemplateData(array $data): bool
    {
        $requiredFields = ['student_name', 'amount', 'due_date', 'fee_type'];

        foreach ($requiredFields as $field) {
            if (! isset($data[$field]) || empty($data[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if template name is valid
     */
    private function isValidTemplate(string $templateName): bool
    {
        $validTemplates = [
            'payment_reminder',
            'fee_due_notice',
            'payment_confirmation',
            'overdue_notice',
            'welcome_message',
        ];

        return in_array($templateName, $validTemplates);
    }

    /**
     * Format phone number for WhatsApp (with country code)
     */
    private function formatPhoneNumber(string $phone): ?string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Add country code if not present (assuming India +91)
        if (strlen($phone) === 10) {
            $phone = '91'.$phone;
        } elseif (strlen($phone) === 11 && substr($phone, 0, 1) === '0') {
            $phone = '91'.substr($phone, 1);
        } elseif (strlen($phone) === 12 && substr($phone, 0, 2) === '91') {
            // Already has country code
        } else {
            return null; // Invalid format
        }

        return strlen($phone) === 12 ? $phone : null;
    }

    /**
     * Get mock service statistics
     */
    public function getServiceStats(): array
    {
        return [
            'service_type' => 'mock',
            'total_sent' => rand(500, 2500),
            'success_rate' => (1 - $this->failureRate) * 100,
            'average_delivery_time' => rand(10, 60).' seconds',
            'templates_used' => rand(3, 8),
            'last_activity' => now()->subMinutes(rand(1, 120))->toISOString(),
        ];
    }

    /**
     * Validate phone number format
     */
    public function validatePhoneNumber(string $phone): bool
    {
        $formatted = $this->formatPhoneNumber($phone);

        return ! is_null($formatted) && strlen($formatted) === 12;
    }
}
