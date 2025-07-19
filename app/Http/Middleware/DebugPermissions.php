<?php

// Create this file: app/Http/Middleware/DebugPermissions.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DebugPermissions
{
    public function handle(Request $request, Closure $next, ...$permissions)
    {
        $user = auth()->user();
        
        if (!$user) {
            Log::info('403 Debug: No authenticated user', [
                'route' => $request->route()->getName(),
                'url' => $request->url(),
            ]);
            return $next($request);
        }

        Log::info('Permission Check Debug', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'route_name' => $request->route()->getName(),
            'url' => $request->url(),
            'required_permissions' => $permissions,
            'user_roles' => $user->roles->pluck('name')->toArray(),
            'user_permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
            'permission_checks' => collect($permissions)->mapWithKeys(function($perm) use ($user) {
                return [$perm => $user->can($perm)];
            })->toArray()
        ]);

        return $next($request);
    }
}