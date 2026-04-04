<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * CsrfDebugMiddleware
 *
 * Logs detailed CSRF/Session state for every POST/PUT/DELETE request.
 * This helps diagnose 419 Page Expired errors.
 *
 * USAGE: Add 'csrf.debug' before VerifyCsrfToken in the 'web' middleware group in Kernel.php
 * REMOVE after root cause is confirmed.
 */
class CsrfDebugMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Only log for state-changing HTTP methods (the ones that require CSRF)
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {

            $sessionToken   = $request->session()->token();
            $requestToken   = $request->input('_token') ?? $request->header('X-CSRF-TOKEN') ?? $request->header('X-XSRF-TOKEN');
            $sessionId      = $request->session()->getId();
            $sessionDriver  = config('session.driver');
            $sessionDomain  = config('session.domain');
            $sessionSecure  = config('session.secure');
            $cookieName     = config('session.cookie');
            $hasCookie      = $request->hasCookie($cookieName);

            $tokensMatch    = $sessionToken && $requestToken && hash_equals($sessionToken, $requestToken);

            Log::channel('single')->warning('=== CSRF DEBUG ===', [
                'url'              => $request->fullUrl(),
                'method'           => $request->method(),
                'ip'               => $request->ip(),
                'user_agent'       => $request->userAgent(),

                // Session state
                'session_driver'   => $sessionDriver,
                'session_domain'   => $sessionDomain,
                'session_secure'   => $sessionSecure,
                'session_id'       => $sessionId,
                'session_started'  => $request->session()->isStarted(),

                // Cookie state
                'session_cookie_name'    => $cookieName,
                'session_cookie_exists'  => $hasCookie,
                'all_cookie_names'       => array_keys($request->cookies->all()),

                // Token comparison
                'session_token'       => $sessionToken ? substr($sessionToken, 0, 8) . '...' : 'MISSING',
                'request_token'       => $requestToken ? substr($requestToken, 0, 8) . '...' : 'MISSING',
                'token_source'        => $request->input('_token') ? 'form_field'
                    : ($request->header('X-CSRF-TOKEN') ? 'X-CSRF-TOKEN header'
                    : ($request->header('X-XSRF-TOKEN') ? 'X-XSRF-TOKEN header' : 'NONE')),
                'tokens_match'     => $tokensMatch ? 'YES ✅' : 'NO ❌ — WILL CAUSE 419',

                // Headers that affect session/proxy behavior
                'x_forwarded_for'  => $request->header('X-Forwarded-For'),
                'x_forwarded_proto'=> $request->header('X-Forwarded-Proto'),
                'host'             => $request->header('Host'),
                'origin'           => $request->header('Origin'),
                'referer'          => $request->header('Referer'),
            ]);
        }

        return $next($request);
    }
}
