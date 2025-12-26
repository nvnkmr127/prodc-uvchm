<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DashboardSecurityMiddleware
{
    /**
     * Handle dashboard security measures
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        
        if (!$user) {
            return $next($request);
        }
        
        // Check for suspicious activity
        if ($this->isSuspiciousActivity($request, $user)) {
            $this->logSuspiciousActivity($request, $user);
            
            return response()->json([
                'error' => 'Suspicious activity detected',
                'message' => 'Your session has been flagged for review'
            ], 429);
        }
        
        // Check session security
        if ($this->isSessionCompromised($request, $user)) {
            auth()->logout();
            
            return response()->json([
                'error' => 'Session security compromised',
                'message' => 'Please log in again'
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
        // Check for rapid requests
        $requestKey = "dashboard_requests_{$user->id}";
        $requestCount = Cache::get($requestKey, 0);
        
        if ($requestCount > 100) { // More than 100 requests per minute
            return true;
        }
        
        Cache::