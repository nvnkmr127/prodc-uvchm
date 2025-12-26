<?php
// app/Http/Middleware/DashboardAccessMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DashboardAccessMiddleware
{
    /**
     * Handle an incoming request for dashboard access
     */
    public function handle(Request $request, Closure $next, $dashboard = null): Response
    {
        $user = auth()->user();
        
        if (!$user) {
            return redirect()->route('login');
        }
        
        // Check if user has basic dashboard access
        if (!$user->hasPermissionTo('view dashboard')) {
            abort(403, 'Dashboard access denied. Contact administrator for permissions.');
        }
        
        // Check role-specific dashboard access if specified
        if ($dashboard && !$this->canAccessDashboard($user, $dashboard)) {
            abort(403, 'Insufficient permissions for this dashboard section.');
        }
        
        // Log dashboard access for analytics
        $this->logDashboardAccess($user, $dashboard);
        
        return $next($request);
    }
    
    /**
     * Check if user can access specific dashboard type
     */
    private function canAccessDashboard($user, $dashboardType): bool
    {
        $allowedDashboards = [
            'super-admin' => ['super-admin'],
            'college-admin' => ['college-admin'],
            'accountant' => ['accountant', 'super-admin', 'college-admin'],
            'staff' => ['staff'],
            'student' => ['student']
        ];
        
        foreach ($user->roles as $role) {
            if (in_array($dashboardType, $allowedDashboards[$role->name] ?? [])) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Log dashboard access for analytics
     */
    private function logDashboardAccess($user, $dashboard): void
    {
        // Log dashboard access - implement based on your logging requirements
        \Log::channel('dashboard')->info('Dashboard accessed', [
            'user_id' => $user->id,
            'user_role' => $user->getRoleNames()->first(),
            'dashboard_type' => $dashboard,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()
        ]);
    }
}