<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Exceptions\ThrottleRequestsException;

class DashboardThrottleMiddleware
{
    protected $limiter;

    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Handle dashboard-specific rate limiting
     */
    public function handle(Request $request, Closure $next, $maxAttempts = 60, $decayMinutes = 1): Response
    {
        $user = auth()->user();
        
        if (!$user) {
            return $next($request);
        }
        
        $key = $this->resolveRequestSignature($request, $user);
        $maxAttempts = $this->resolveMaxAttempts($request, $user, $maxAttempts);
        
        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            throw $this->buildException($request, $key, $maxAttempts);
        }
        
        $this->limiter->hit($key, $decayMinutes * 60);
        
        $response = $next($request);
        
        return $this->addHeaders(
            $response,
            $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts)
        );
    }
    
    /**
     * Resolve request signature for rate limiting
     */
    protected function resolveRequestSignature(Request $request, $user): string
    {
        $route = $request->route();
        $routeName = $route ? $route->getName() : 'unknown';
        
        return sha1(implode('|', [
            'dashboard',
            $user->id,
            $user->getRoleNames()->first(),
            $routeName,
            $request->method(),
            $request->ip()
        ]));
    }
    
    /**
     * Resolve max attempts based on user role
     */
    protected function resolveMaxAttempts(Request $request, $user, $default): int
    {
        $roleLimits = [
            'super-admin' => $default * 2,     // Double limit for admins
            'college-admin' => $default * 1.5, // 50% more for college admins
            'accountant' => $default * 1.2,    // 20% more for accountants
            'staff' => $default,               // Default limit
            'student' => $default * 0.8        // 20% less for students
        ];
        
        $userRole = $user->getRoleNames()->first();
        
        return (int)($roleLimits[$userRole] ?? $default);
    }
    
    /**
     * Create throttle exception
     */
    protected function buildException(Request $request, string $key, int $maxAttempts): ThrottleRequestsException
    {
        $retryAfter = $this->getTimeUntilNextRetry($key);
        
        $headers = $this->getHeaders(
            $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts),
            $retryAfter
        );
        
        return new ThrottleRequestsException('Too Many Dashboard Requests', null, $headers);
    }
    
    /**
     * Add rate limit headers to response
     */
    protected function addHeaders(Response $response, int $maxAttempts, int $remainingAttempts, $retryAfter = null): Response
    {
        $headers = $this->getHeaders($maxAttempts, $remainingAttempts, $retryAfter);
        
        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }
        
        return $response;
    }
    
    /**
     * Get rate limit headers
     */
    protected function getHeaders(int $maxAttempts, int $remainingAttempts, $retryAfter = null): array
    {
        $headers = [
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remainingAttempts,
        ];
        
        if (!is_null($retryAfter)) {
            $headers['Retry-After'] = $retryAfter;
            $headers['X-RateLimit-Reset'] = now()->addSeconds($retryAfter)->getTimestamp();
        }
        
        return $headers;
    }
    
    /**
     * Calculate remaining attempts
     */
    protected function calculateRemainingAttempts(string $key, int $maxAttempts): int
    {
        return $this->limiter->retriesLeft($key, $maxAttempts);
    }
    
    /**
     * Get time until next retry
     */
    protected function getTimeUntilNextRetry(string $key): int
    {
        return $this->limiter->availableIn($key);
    }
}