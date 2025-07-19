<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPaymentAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        
        if (!$user) {
            return redirect()->route('login');
        }

        // Check if user has any financial permissions
        $requiredPermissions = [
            'view financials',
            'manage financials',
            'view reports',
            'manage settings'
        ];

        $hasAccess = false;
        foreach ($requiredPermissions as $permission) {
            if ($user->can($permission)) {
                $hasAccess = true;
                break;
            }
        }

        if (!$hasAccess) {
            abort(403, 'Access denied to payment management features.');
        }

        return $next($request);
    }
}
