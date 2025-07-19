<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     * 
     * Now compatible with Spatie Permission package
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            abort(401, 'Authentication required.');
        }

        $user = auth()->user();

        // ✅ FIXED - Use Spatie's hasRole method instead of direct property access
        if (!$user->hasRole($role)) {
            abort(403, 'Insufficient permissions. Required role: ' . $role);
        }

        return $next($request);
    }

    /**
     * Handle multiple roles (pipe-separated)
     * Usage: middleware(['role:admin|manager'])
     */
    public function handleMultiple(Request $request, Closure $next, string $roles): Response
    {
        if (!auth()->check()) {
            abort(401, 'Authentication required.');
        }

        $user = auth()->user();
        $allowedRoles = explode('|', $roles);

        // Check if user has any of the allowed roles
        foreach ($allowedRoles as $role) {
            if ($user->hasRole(trim($role))) {
                return $next($request);
            }
        }

        abort(403, 'Insufficient permissions. Required roles: ' . $roles);
    }
}