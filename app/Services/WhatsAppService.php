<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected $apiUrl;

    protected $apiToken;

    public function __construct()
    {
        $this->apiUrl = config('services.whatsapp.api_url');
        $this->apiToken = config('services.whatsapp.api_token');
    }

    /**
     * Send payment reminder via WhatsApp
     */
    public function sendPaymentReminder(string $phoneNumber, string $message, array $data = []): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiToken,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl.'/messages', [
                'messaging_product' => 'whatsapp',
                'to' => $this->formatPhoneNumber($phoneNumber),
                'type' => 'template',
                'template' => [
                    'name' => 'payment_reminder',
                    'language' => ['code' => 'en'],
                    'components' => [
                        [
                            'type' => 'body',
                            'parameters' => [
                                ['type' => 'text', 'text' => $data['student_name'] ?? ''],
                                ['type' => 'text', 'text' => $data['amount'] ?? ''],
                                ['type' => 'text', 'text' => $data['due_date'] ?? ''],
                                ['type' => 'text', 'text' => $data['fee_type'] ?? ''],
                            ],
                        ],
                    ],
                ],
            ]);

            if ($response->successful()) {
                Log::info('WhatsApp reminder sent successfully', [
                    'phone' => $phoneNumber,
                    'message_id' => $response->json('messages.0.id'),
                ]);

                return true;
            } else {
                Log::error('WhatsApp reminder failed', [
                    'phone' => $phoneNumber,
                    'error' => $response->body(),
                ]);

                return false;
            }
        } catch (\Exception $e) {
            Log::error('WhatsApp service error', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function formatPhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Add country code if not present (assuming India +91)
        if (strlen($phone) === 10) {
            $phone = '91'.$phone;
        }

        return $phone;
    }
}
