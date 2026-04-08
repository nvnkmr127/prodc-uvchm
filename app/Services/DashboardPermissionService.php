<?php

namespace App\Services;

use App\Models\Dashboard;
use App\Models\User;
use App\Models\Widget;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

class DashboardPermissionService
{
    /**
     * Safely check if user has permission (handles missing permissions)
     */
    private function safeHasPermission($user, $permission): bool
    {
        try {
            return $user->hasPermissionTo($permission);
        } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist $e) {
            \Log::warning("Permission '{$permission}' does not exist");

            return false;
        }
    }

    /**
     * Check if user can view a specific dashboard
     */
    public function canViewDashboard($user, Dashboard $dashboard): bool
    {
        // Super admin can view all dashboards
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Check if user has permission to view all dashboards
        if ($this->safeHasPermission($user, 'view all dashboards')) {
            return true;
        }

        // Check if user has the role assigned to this dashboard
        if ($dashboard->role && $user->hasRole($dashboard->role->name)) {
            return true;
        }

        // Check if dashboard is active
        if (! $dashboard->is_active) {
            return false;
        }

        return false;
    }

    /**
     * Check if user can edit a specific dashboard
     */
    public function canEditDashboard($user, Dashboard $dashboard): bool
    {
        // Super admin can edit all dashboards
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Check if user has permission to edit dashboards
        if ($this->safeHasPermission($user, 'edit dashboards')) {
            // And user has the role for this dashboard
            return $dashboard->role && $user->hasRole($dashboard->role->name);
        }

        return false;
    }

    /**
     * Check if user can customize a specific dashboard
     */
    public function canCustomizeDashboard($user, Dashboard $dashboard): bool
    {
        // First check if they can view it
        if (! $this->canViewDashboard($user, $dashboard)) {
            return false;
        }

        // Check if user has customize permission
        return $this->safeHasPermission($user, 'customize dashboard');
    }

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
     * Get dashboards that user can view
     */
    public function getViewableDashboards($user)
    {
        // Super admin can view all dashboards
        if ($user->hasRole('super-admin')) {
            return Dashboard::active()->get();
        }

        // Get user's roles
        $userRoles = $user->roles->pluck('id')->toArray();

        // Get dashboards for user's roles
        $dashboards = Dashboard::active()
            ->whereIn('role_id', $userRoles)
            ->get();

        // Filter based on permissions
        return $dashboards->filter(function ($dashboard) use ($user) {
            return $this->canViewDashboard($user, $dashboard);
        });
    }

    /**
     * Get widgets that user can view
     */
    public function getViewableWidgets($user)
    {
        $widgets = Widget::active()->get();

        return $widgets->filter(function ($widget) use ($user) {
            return $this->canViewWidget($user, $widget);
        });
    }

    /**
     * Apply row-level security to widget data
     */
    public function applyRowLevelSecurity($user, $data, Widget $widget)
    {
        // Super admin sees all data
        if ($user->hasRole('super-admin')) {
            return $data;
        }

        // Apply filters based on widget type and user permissions
        switch ($widget->type) {
            case 'financial':
                return $this->applyFinancialFilters($user, $data);
            case 'student':
                return $this->applyStudentFilters($user, $data);
            case 'academic':
                return $this->applyAcademicFilters($user, $data);
            default:
                return $data;
        }
    }

    /**
     * Filter widgets by user permissions
     */
    public function filterWidgetsByPermissions(Collection $widgets, User $user)
    {
        return $widgets->filter(function ($dashboardWidget) use ($user) {
            // Access the actual Widget model through the relationship
            return $this->canViewWidget($user, $dashboardWidget->widget);
        });
    }

    /**
     * Apply financial data filters
     */
    private function applyFinancialFilters($user, $data)
    {
        if (! $this->safeHasPermission($user, 'view financial data')) {
            // Remove sensitive financial information
            if (isset($data['amount'])) {
                unset($data['amount']);
            }
            if (isset($data['revenue'])) {
                unset($data['revenue']);
            }
            if (isset($data['salary'])) {
                unset($data['salary']);
            }
        }

        return $data;
    }

    /**
     * Apply student data filters
     */
    private function applyStudentFilters($user, $data)
    {
        if (! $this->safeHasPermission($user, 'view student data')) {
            // Limit student information
            if (isset($data['personal_details'])) {
                unset($data['personal_details']);
            }
        }

        return $data;
    }

    /**
     * Apply academic data filters
     */
    private function applyAcademicFilters($user, $data)
    {
        if (! $this->safeHasPermission($user, 'view academic data')) {
            // Filter academic information
            if (isset($data['grades'])) {
                unset($data['grades']);
            }
            if (isset($data['marks'])) {
                unset($data['marks']);
            }
        }

        return $data;
    }

    /**
     * Check if user can access dashboard builder
     */
    public function canAccessDashboardBuilder($user): bool
    {
        return $this->safeHasPermission($user, 'access dashboard builder') ||
               $user->hasRole('super-admin');
    }

    /**
     * Check if user can manage widgets
     */
    public function canManageWidgets($user): bool
    {
        return $this->safeHasPermission($user, 'manage widgets') ||
               $user->hasRole('super-admin');
    }

    /**
     * Check if user can create dashboard templates
     */
    public function canCreateTemplates($user): bool
    {
        return $this->safeHasPermission($user, 'create dashboard templates') ||
               $user->hasRole('super-admin');
    }

    /**
     * Get permissions summary for user
     */
    public function getPermissionsSummary($user): array
    {
        return [
            'can_view_dashboard' => $this->safeHasPermission($user, 'view dashboard'),
            'can_customize_dashboard' => $this->safeHasPermission($user, 'customize dashboard'),
            'can_edit_dashboards' => $this->safeHasPermission($user, 'edit dashboards'),
            'can_manage_dashboards' => $this->safeHasPermission($user, 'manage dashboards'),
            'can_view_all_dashboards' => $this->safeHasPermission($user, 'view all dashboards'),
            'can_access_builder' => $this->canAccessDashboardBuilder($user),
            'can_manage_widgets' => $this->canManageWidgets($user),
            'can_create_templates' => $this->canCreateTemplates($user),
            'roles' => $user->roles->pluck('name')->toArray(),
            'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
        ];
    }

    /**
     * Apply role-based filters to dashboard data
     */
    public function applyRoleFilters($user, $data, $context = 'dashboard')
    {
        // Super admin sees everything
        if ($user->hasRole('super-admin')) {
            return $data;
        }

        switch ($context) {
            case 'financial':
                if (! $this->safeHasPermission($user, 'view financial data')) {
                    return $this->filterSensitiveFinancialData($data);
                }
                break;

            case 'academic':
                if (! $this->safeHasPermission($user, 'view academic data')) {
                    return $this->filterSensitiveAcademicData($data);
                }
                break;

            case 'student':
                if (! $this->safeHasPermission($user, 'view student data')) {
                    return $this->filterSensitiveStudentData($data);
                }
                break;

            case 'system':
                if (! $this->safeHasPermission($user, 'view system data')) {
                    return [];
                }
                break;
        }

        return $data;
    }

    /**
     * Filter sensitive financial data
     */
    private function filterSensitiveFinancialData($data)
    {
        if (is_array($data)) {
            foreach ($data as &$item) {
                if (is_array($item)) {
                    unset($item['amount'], $item['balance'], $item['salary']);
                }
            }
        }

        return $data;
    }

    /**
     * Filter sensitive academic data
     */
    private function filterSensitiveAcademicData($data)
    {
        if (is_array($data)) {
            foreach ($data as &$item) {
                if (is_array($item)) {
                    unset($item['grades'], $item['marks'], $item['performance']);
                }
            }
        }

        return $data;
    }

    /**
     * Filter sensitive student data
     */
    private function filterSensitiveStudentData($data)
    {
        if (is_array($data)) {
            foreach ($data as &$item) {
                if (is_array($item)) {
                    unset($item['phone'], $item['address'], $item['parent_details']);
                }
            }
        }

        return $data;
    }
}
