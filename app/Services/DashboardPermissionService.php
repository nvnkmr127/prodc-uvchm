<?php

namespace App\Services;

use App\Models\User;
use App\Models\Widget;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class DashboardPermissionService
{
    /**
     * Check if user can view a specific widget
     */
    public function canViewWidget($user, Widget $widget): bool
    {
        // Check if widget is active
        if (! $widget->is_active) {
            return false;
        }

        // Check required permissions
        if (! empty($widget->required_permissions)) {
            foreach ($widget->required_permissions as $permission) {
                if (! $this->safeHasPermission($user, $permission)) {
                    return false;
                }
            }
        }

        // Check allowed roles
        if (! empty($widget->allowed_roles)) {
            $hasAllowedRole = false;
            foreach ($widget->allowed_roles as $role) {
                if ($user->hasRole($role)) {
                    $hasAllowedRole = true;
                    break;
                }
            }
            if (! $hasAllowedRole) {
                return false;
            }
        }

        return true;
    }

    /**
     * Safely check if user has permission (handles missing permissions)
     */
    private function safeHasPermission($user, $permission): bool
    {
        try {
            return $user->hasPermissionTo($permission);
        } catch (PermissionDoesNotExist $e) {
            \Log::warning("Permission '{$permission}' does not exist");

            return false;
        }
    }
}
