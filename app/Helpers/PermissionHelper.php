<?php

namespace App\Helpers;

class PermissionHelper
{
    /**
     * Get FontAwesome icon for a module
     */
    public static function getModuleIcon($module)
    {
        $icons = [
            'users' => 'users',
            'roles' => 'user-shield',
            'permissions' => 'shield-alt',
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
            'api' => 'code',
            'backend' => 'desktop',
            'general' => 'cog',
        ];

        return $icons[$module] ?? 'cog';
    }

    /**
     * Format permission name for display
     */
    public static function formatPermissionName($permission)
    {
        return ucwords(str_replace(['_', '-'], ' ', $permission));
    }
}
