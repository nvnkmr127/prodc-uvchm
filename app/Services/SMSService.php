<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SMSService
{
    protected $apiUrl;

    protected $apiKey;

    protected $senderId;

    public function __construct()
    {
        $this->apiUrl = config('services.sms.api_url');
        $this->apiKey = config('services.sms.api_key');
        $this->senderId = config('services.sms.sender_id');
    }

    /**
     * Send payment reminder SMS
     */
    public function sendPaymentReminder(string $phoneNumber, string $message): bool
    {
        try {
            $response = Http::get($this->apiUrl, [
                'authkey' => $this->apiKey,
                'mobiles' => $this->formatPhoneNumber($phoneNumber),
                'message' => $message,
                'sender' => $this->senderId,
                'route' => 4, // Transactional route
                'country' => 91,
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                if ($responseData['type'] === 'success') {
                    Log::info('SMS reminder sent successfully', [
                        'phone' => $phoneNumber,
                        'message_id' => $responseData['message_id'] ?? null,
                    ]);

                    return true;
                }
            }

            Log::error('SMS reminder failed', [
                'phone' => $phoneNumber,
                'error' => $response->body(),
            ]);

            return false;

        } catch (\Exception $e) {
            Log::error('SMS service error', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function formatPhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters and ensure 10 digits
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (strlen($phone) === 11 && substr($phone, 0, 1) === '0') {
            $phone = substr($phone, 1); // Remove leading 0
        }

        return strlen($phone) === 10 ? $phone : null;
    }
}
