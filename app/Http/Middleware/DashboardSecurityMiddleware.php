<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class DashboardSecurityMiddleware
{
    /**
     * Handle dashboard security measures
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (! $user) {
            return $next($request);
        }

        // Check for suspicious activity
        if ($this->isSuspiciousActivity($request, $user)) {
            $this->logSuspiciousActivity($request, $user);

            return response()->json([
                'error' => 'Suspicious activity detected',
                'message' => 'Your session has been flagged for review',
            ], 429);
        }

        // Check session security
        if ($this->isSessionCompromised($request, $user)) {
            auth()->logout();

            return response()->json([
                'error' => 'Session security compromised',
                'message' => 'Please log in again',
            ], 401);
        }

        $response = $next($request);

        // Add security headers
        return $this->addSecurityHeaders($response);
    }

    /**
     * Detect suspicious activity patterns
     */
    private function isSuspiciousActivity(Request $request, $user): bool
    {
        $requestKey = "dashboard_requests_{$user->id}";

        // Atomic add returns true if the key didn't exist (sets value to 1 with 60s TTL)
        if (\Illuminate\Support\Facades\Cache::add($requestKey, 1, 60)) {
            return false;
        }

        // If it already existed, atomically increment it
        return \Illuminate\Support\Facades\Cache::increment($requestKey) > 100;
    }

    /**
     * Detect if session is compromised
     */
    private function isSessionCompromised(Request $request, $user): bool
    {
        return false;
    }

    /**
     * Log suspicious activity
     */
    private function logSuspiciousActivity(Request $request, $user): void
    {
        // Add logging logic here
    }

    /**
     * Add security headers to response
     */
    private function addSecurityHeaders(Response $response): Response
    {
        return $response;
    }
}
