<?php

// Create this file: app/Helpers/RoleHelper.php

namespace App\Helpers;

class RoleHelper
{
    public static function getModuleIcon($module)
    {
        $icons = [
            'users' => 'users',
            'roles' => 'user-shield',
            'students' => 'user-graduate',
            'faculty' => 'chalkboard-teacher',
            'courses' => 'book',
            'subjects' => 'book-open',
            'batches' => 'users',
            'attendance' => 'calendar-check',
            'timetable' => 'calendar',
            'financials' => 'dollar-sign',
            'reports' => 'chart-bar',
            'settings' => 'cogs',
            'inventory' => 'boxes',
            'hr' => 'briefcase',
            'documents' => 'file-alt',
            'admissions' => 'user-plus',
            'events' => 'calendar-alt',
            'general' => 'cog',
        ];

        return $icons[$module] ?? 'cog';
    }

    public static function getActionIcon($action)
    {
        $icons = [
            'view' => 'eye',
            'create' => 'plus',
            'edit' => 'edit',
            'delete' => 'trash',
            'manage' => 'cogs',
        ];

        return $icons[$action] ?? 'circle';
    }

    public static function getActionBadgeColor($action)
    {
        $colors = [
            'view' => 'info',
            'create' => 'success',
            'edit' => 'warning',
            'delete' => 'danger',
            'manage' => 'primary',
        ];

        return $colors[$action] ?? 'secondary';
    }

    public static function getPermissionBadgeColor($permission)
    {
        if (strpos($permission, 'view') !== false) {
            return 'info';
        }
        if (strpos($permission, 'create') !== false) {
            return 'success';
        }
        if (strpos($permission, 'edit') !== false) {
            return 'warning';
        }
        if (strpos($permission, 'delete') !== false) {
            return 'danger';
        }
        if (strpos($permission, 'manage') !== false) {
            return 'primary';
        }

        return 'secondary';
    }

    public static function formatPermissionName($permission)
    {
        return ucwords(str_replace(['_', '-'], ' ', $permission));
    }

    public static function getPermissionDescription($permission)
    {
        $descriptions = [
            'view users' => 'View user accounts and basic information',
            'create users' => 'Create new user accounts',
            'edit users' => 'Modify existing user accounts',
            'delete users' => 'Remove user accounts from the system',
            'manage users' => 'Full control over user management',
            'view students' => 'View student records and information',
            'create students' => 'Add new students to the system',
            'edit students' => 'Modify student information',
            'delete students' => 'Remove student records',
            'manage students' => 'Full control over student management',
            // Add more descriptions as needed
        ];

        return $descriptions[$permission] ?? null;
    }
}
