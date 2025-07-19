<?php
// In app/Services/WebhookSecurityService.php

namespace App\Services;

use Illuminate\Http\Request;

class WebhookSecurityService
{
    /**
     * Verify the HMAC-SHA256 signature of an incoming webhook request.
     *
     * @param Request $request The incoming HTTP request.
     * @param string $secret The signing secret used to generate the signature.
     * @return bool True if the signature is valid, false otherwise.
     */
    public function verify(Request $request, string $secret): bool
    {
        // Get the signature from the request header.
        $sentSignature = $request->header('X-App-Signature');

        if (!$sentSignature) {
            // Abort if no signature is present.
            return false;
        }

        // Get the raw JSON body of the request.
        $payload = $request->getContent();

        // Calculate the expected signature.
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        // Compare the sent signature with the expected one in a way that
        // prevents timing attacks.
        return hash_equals($expectedSignature, $sentSignature);
    }
}