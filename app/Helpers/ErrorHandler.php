<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Throwable;

class ErrorHandler
{
    /**
     * Handle exceptions safely without exposing sensitive information
     * 
     * @param Throwable $exception
     * @param string $context
     * @param string $userMessage
     * @return array
     */
    public static function handleException(Throwable $exception, string $context = '', string $userMessage = 'An error occurred'): array
    {
        // Log the full exception details for debugging
        Log::error($context ?: 'Application Error', [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'user_id' => auth()->id(),
            'url' => request()->fullUrl(),
            'ip' => request()->ip(),
        ]);

        // Return safe error response
        return [
            'success' => false,
            'error' => $userMessage,
            'debug_info' => config('app.debug') ? [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ] : null
        ];
    }

    /**
     * Handle exceptions for API responses
     * 
     * @param Throwable $exception
     * @param string $context
     * @param string $userMessage
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    public static function handleApiException(Throwable $exception, string $context = '', string $userMessage = 'Internal server error', int $statusCode = 500)
    {
        $errorData = self::handleException($exception, $context, $userMessage);
        
        return response()->json($errorData, $statusCode);
    }

    /**
     * Handle exceptions for web responses
     * 
     * @param Throwable $exception
     * @param string $context
     * @param string $userMessage
     * @param string $redirectRoute
     * @return \Illuminate\Http\RedirectResponse
     */
    public static function handleWebException(Throwable $exception, string $context = '', string $userMessage = 'An error occurred', string $redirectRoute = null)
    {
        self::handleException($exception, $context, $userMessage);
        
        $redirect = $redirectRoute ? redirect()->route($redirectRoute) : redirect()->back();
        
        return $redirect->with('error', $userMessage);
    }

    /**
     * Get safe error message based on environment
     * 
     * @param Throwable $exception
     * @param string $fallbackMessage
     * @return string
     */
    public static function getSafeErrorMessage(Throwable $exception, string $fallbackMessage = 'An error occurred'): string
    {
        return config('app.debug') ? $exception->getMessage() : $fallbackMessage;
    }
}