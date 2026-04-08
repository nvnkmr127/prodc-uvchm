<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class DashboardMaintenanceMiddleware
{
    /**
     * Handle dashboard maintenance mode
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if dashboard is in maintenance mode
        if ($this->isDashboardInMaintenance()) {
            $user = auth()->user();

            // Allow super admins to bypass maintenance
            if (! $user || ! $user->hasRole('super-admin')) {
                return $this->getMaintenanceResponse($request);
            }
        }

        return $next($request);
    }

    /**
     * Check if dashboard is in maintenance mode
     */
    private function isDashboardInMaintenance(): bool
    {
        return Cache::get('dashboard_maintenance_mode', false) ||
               config('dashboard.maintenance_mode', false);
    }

    /**
     * Get maintenance mode response
     */
    private function getMaintenanceResponse(Request $request): Response
    {
        $maintenanceInfo = Cache::get('dashboard_maintenance_info', [
            'message' => 'Dashboard is currently under maintenance. Please try again later.',
            'estimated_completion' => null,
            'contact_info' => 'Contact your administrator for more information.',
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Dashboard Maintenance',
                'message' => $maintenanceInfo['message'],
                'maintenance_mode' => true,
                'estimated_completion' => $maintenanceInfo['estimated_completion'],
                'contact_info' => $maintenanceInfo['contact_info'],
            ], 503);
        }

        return response()->view('dashboard.maintenance', $maintenanceInfo, 503);
    }
}
