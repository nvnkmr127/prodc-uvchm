<?php
// app/Services/DashboardPermissionService.php

class DashboardPermissionService
{
    public function canViewDashboard(User $user, Dashboard $dashboard): bool
    {
        return $user->hasRole($dashboard->role->name) || 
               $user->hasPermissionTo('view all dashboards');
    }

    public function canEditDashboard(User $user, Dashboard $dashboard): bool
    {
        return $user->hasPermissionTo('edit dashboards') &&
               ($user->hasRole($dashboard->role->name) || $user->hasRole('admin'));
    }

    public function canViewWidget(User $user, Widget $widget): bool
    {
        // Check widget-specific permissions
        $permissions = $widget->required_permissions ?? [];
        
        foreach ($permissions as $permission) {
            if (!$user->hasPermissionTo($permission)) {
                return false;
            }
        }
        
        return true;
    }

    public function filterWidgetsByPermissions(User $user, Collection $widgets): Collection
    {
        return $widgets->filter(function ($widget) use ($user) {
            return $this->canViewWidget($user, $widget);
        });
    }

    public function getSecureWidgetData(User $user, Widget $widget): array
    {
        if (!$this->canViewWidget($user, $widget)) {
            return ['error' => 'Insufficient permissions'];
        }

        $data = $widget->getData();
        
        // Apply row-level security based on user context
        return $this->applyRowLevelSecurity($user, $data, $widget);
    }

    private function applyRowLevelSecurity(User $user, array $data, Widget $widget): array
    {
        // Filter data based on user's department, role, etc.
        if ($widget->type === 'student_list' && !$user->hasRole('admin')) {
            // Filter students by user's department/classes
            return array_filter($data, function ($student) use ($user) {
                return in_array($student['department_id'], $user->accessible_departments);
            });
        }

        return $data;
    }
}