<?php

namespace App\Http\Middleware;

use App\Models\Widget;
use App\Services\DashboardPermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WidgetPermissionMiddleware
{
    protected $permissionService;

    public function __construct(DashboardPermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Handle widget access permissions
     */
    public function handle(Request $request, Closure $next, $action = 'view'): Response
    {
        $user = auth()->user();

        if (! $user) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        // Get widget from route parameter
        $widget = $this->getWidgetFromRequest($request);

        if (! $widget) {
            return response()->json(['error' => 'Widget not found'], 404);
        }

        // Check widget permissions based on action
        $hasPermission = match ($action) {
            'view' => $this->permissionService->canViewWidget($user, $widget),
            'edit' => $this->canEditWidget($user, $widget),
            'delete' => $this->canDeleteWidget($user, $widget),
            default => false
        };

        if (! $hasPermission) {
            return response()->json([
                'error' => "Insufficient permissions to {$action} this widget",
                'required_permissions' => $widget->required_permissions ?? [],
                'user_roles' => $user->getRoleNames(),
            ], 403);
        }

        // Add widget to request for controller use
        $request->merge(['validated_widget' => $widget]);

        return $next($request);
    }

    /**
     * Get widget from request parameters
     */
    private function getWidgetFromRequest(Request $request): ?Widget
    {
        // Try route parameter first
        if ($request->route('widget')) {
            return $request->route('widget');
        }

        // Try request parameter
        if ($request->has('widget_id')) {
            return Widget::find($request->widget_id);
        }

        return null;
    }

    /**
     * Check if user can edit widget
     */
    private function canEditWidget($user, Widget $widget): bool
    {
        return $user->hasPermissionTo('edit widgets') &&
               $this->permissionService->canViewWidget($user, $widget);
    }

    /**
     * Check if user can delete widget
     */
    private function canDeleteWidget($user, Widget $widget): bool
    {
        return $user->hasPermissionTo('delete widgets') &&
               $user->hasRole('super-admin');
    }
}
