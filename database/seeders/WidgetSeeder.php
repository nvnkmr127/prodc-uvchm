<?php

namespace Database\Seeders;

use App\Models\Widget;
use App\Models\WidgetCategory;
use Illuminate\Database\Seeder;

class WidgetSeeder extends Seeder
{
    public function run()
    {
        $analyticsCategory = WidgetCategory::where('slug', 'analytics-charts')->first();
        $academicCategory = WidgetCategory::where('slug', 'academic')->first();
        $financialCategory = WidgetCategory::where('slug', 'financial')->first();
        $userCategory = WidgetCategory::where('slug', 'user-management')->first();
        $actionsCategory = WidgetCategory::where('slug', 'quick-actions')->first();
        $systemCategory = WidgetCategory::where('slug', 'system')->first();

        $widgets = [
            // Analytics & Charts
            [
                'name' => 'Revenue Chart',
                'slug' => 'revenue-chart',
                'type' => 'chart',
                'component' => 'dashboard.widgets.revenue-chart',
                'view_path' => 'dashboard.widgets.revenue-chart',
                'category_id' => $analyticsCategory?->id,
                'description' => 'Monthly revenue analytics chart',
                'required_permissions' => ['view financials'],
                'allowed_roles' => ['super-admin', 'college-admin', 'accountant'],
                'default_width' => 8,
                'default_height' => 400,
                'cache_duration' => 300,
            ],
            [
                'name' => 'Attendance Analytics',
                'slug' => 'attendance-analytics',
                'type' => 'chart',
                'component' => 'dashboard.widgets.attendance-analytics',
                'view_path' => 'dashboard.widgets.attendance-analytics',
                'category_id' => $analyticsCategory?->id,
                'description' => 'Student attendance trends and analytics',
                'required_permissions' => ['view attendance'],
                'allowed_roles' => ['super-admin', 'college-admin', 'staff'],
                'default_width' => 6,
                'default_height' => 350,
                'cache_duration' => 600,
            ],
            [
                'name' => 'Student Performance Chart',
                'slug' => 'student-performance-chart',
                'type' => 'chart',
                'component' => 'dashboard.widgets.student-performance-chart',
                'view_path' => 'dashboard.widgets.student-performance-chart',
                'category_id' => $analyticsCategory?->id,
                'description' => 'Academic performance metrics and trends',
                'required_permissions' => ['view academics'],
                'allowed_roles' => ['super-admin', 'college-admin', 'staff'],
                'default_width' => 6,
                'default_height' => 350,
                'cache_duration' => 600,
            ],
            [
                'name' => 'Fee Collection Chart',
                'slug' => 'fee-collection-chart',
                'type' => 'chart',
                'component' => 'dashboard.widgets.fee-collection-chart',
                'view_path' => 'dashboard.widgets.fee-collection-chart',
                'category_id' => $financialCategory?->id,
                'description' => 'Fee collection trends and analytics',
                'required_permissions' => ['view financials'],
                'allowed_roles' => ['super-admin', 'college-admin', 'accountant'],
                'default_width' => 8,
                'default_height' => 400,
                'cache_duration' => 300,
            ],

            // Academic Widgets
            [
                'name' => 'Student Enrollment Stats',
                'slug' => 'student-enrollment-stats',
                'type' => 'stats',
                'component' => 'dashboard.widgets.student-enrollment-stats',
                'view_path' => 'dashboard.widgets.student-enrollment-stats',
                'category_id' => $academicCategory?->id,
                'description' => 'Current student enrollment statistics',
                'required_permissions' => ['view students'],
                'allowed_roles' => ['super-admin', 'college-admin', 'staff'],
                'default_width' => 4,
                'default_height' => 200,
                'cache_duration' => 1800,
            ],
            [
                'name' => 'Course Progress Tracker',
                'slug' => 'course-progress-tracker',
                'type' => 'progress',
                'component' => 'dashboard.widgets.course-progress-tracker',
                'view_path' => 'dashboard.widgets.course-progress-tracker',
                'category_id' => $academicCategory?->id,
                'description' => 'Track course completion progress',
                'required_permissions' => ['view academics'],
                'allowed_roles' => ['super-admin', 'college-admin', 'staff'],
                'default_width' => 6,
                'default_height' => 300,
                'cache_duration' => 900,
            ],
            [
                'name' => 'Recent Admissions',
                'slug' => 'recent-admissions',
                'type' => 'list',
                'component' => 'dashboard.widgets.recent-admissions',
                'view_path' => 'dashboard.widgets.recent-admissions',
                'category_id' => $academicCategory?->id,
                'description' => 'Recently admitted students',
                'required_permissions' => ['view students'],
                'allowed_roles' => ['super-admin', 'college-admin'],
                'default_width' => 6,
                'default_height' => 350,
                'cache_duration' => 600,
            ],

            // Financial Widgets
            [
                'name' => 'Fee Summary Card',
                'slug' => 'fee-summary-card',
                'type' => 'card',
                'component' => 'dashboard.widgets.fee-summary-card',
                'view_path' => 'dashboard.widgets.fee-summary-card',
                'category_id' => $financialCategory?->id,
                'description' => 'Fee collection summary statistics',
                'required_permissions' => ['view financials'],
                'allowed_roles' => ['super-admin', 'college-admin', 'accountant'],
                'default_width' => 4,
                'default_height' => 200,
                'cache_duration' => 300,
            ],
            [
                'name' => 'Outstanding Fees',
                'slug' => 'outstanding-fees',
                'type' => 'table',
                'component' => 'dashboard.widgets.outstanding-fees',
                'view_path' => 'dashboard.widgets.outstanding-fees',
                'category_id' => $financialCategory?->id,
                'description' => 'Students with outstanding fees',
                'required_permissions' => ['view financials'],
                'allowed_roles' => ['super-admin', 'college-admin', 'accountant'],
                'default_width' => 8,
                'default_height' => 400,
                'cache_duration' => 600,
            ],
            [
                'name' => 'Daily Revenue',
                'slug' => 'daily-revenue',
                'type' => 'metric',
                'component' => 'dashboard.widgets.daily-revenue',
                'view_path' => 'dashboard.widgets.daily-revenue',
                'category_id' => $financialCategory?->id,
                'description' => 'Today\'s revenue collection',
                'required_permissions' => ['view financials'],
                'allowed_roles' => ['super-admin', 'college-admin', 'accountant'],
                'default_width' => 3,
                'default_height' => 150,
                'cache_duration' => 300,
            ],

            // User Management Widgets
            [
                'name' => 'Staff Directory',
                'slug' => 'staff-directory',
                'type' => 'directory',
                'component' => 'dashboard.widgets.staff-directory',
                'view_path' => 'dashboard.widgets.staff-directory',
                'category_id' => $userCategory?->id,
                'description' => 'Quick access to staff information',
                'required_permissions' => ['view staff'],
                'allowed_roles' => ['super-admin', 'college-admin'],
                'default_width' => 6,
                'default_height' => 350,
                'cache_duration' => 1800,
            ],
            [
                'name' => 'User Activity Log',
                'slug' => 'user-activity-log',
                'type' => 'log',
                'component' => 'dashboard.widgets.user-activity-log',
                'view_path' => 'dashboard.widgets.user-activity-log',
                'category_id' => $userCategory?->id,
                'description' => 'Recent user activities and logins',
                'required_permissions' => ['view system logs'],
                'allowed_roles' => ['super-admin'],
                'default_width' => 8,
                'default_height' => 400,
                'cache_duration' => 300,
            ],

            // Quick Actions
            [
                'name' => 'Quick Add Student',
                'slug' => 'quick-add-student',
                'type' => 'action',
                'component' => 'dashboard.widgets.quick-add-student',
                'view_path' => 'dashboard.widgets.quick-add-student',
                'category_id' => $actionsCategory?->id,
                'description' => 'Quick student admission form',
                'required_permissions' => ['create students'],
                'allowed_roles' => ['super-admin', 'college-admin'],
                'default_width' => 4,
                'default_height' => 250,
                'cache_duration' => 0,
            ],
            [
                'name' => 'Quick Fee Collection',
                'slug' => 'quick-fee-collection',
                'type' => 'action',
                'component' => 'dashboard.widgets.quick-fee-collection',
                'view_path' => 'dashboard.widgets.quick-fee-collection',
                'category_id' => $actionsCategory?->id,
                'description' => 'Quick fee payment entry',
                'required_permissions' => ['collect fees'],
                'allowed_roles' => ['super-admin', 'college-admin', 'accountant'],
                'default_width' => 4,
                'default_height' => 250,
                'cache_duration' => 0,
            ],

            // System Widgets
            [
                'name' => 'System Status',
                'slug' => 'system-status',
                'type' => 'status',
                'component' => 'dashboard.widgets.system-status',
                'view_path' => 'dashboard.widgets.system-status',
                'category_id' => $systemCategory?->id,
                'description' => 'System health and status indicators',
                'required_permissions' => ['view system status'],
                'allowed_roles' => ['super-admin'],
                'default_width' => 6,
                'default_height' => 300,
                'cache_duration' => 60,
            ],
            [
                'name' => 'Backup Status',
                'slug' => 'backup-status',
                'type' => 'status',
                'component' => 'dashboard.widgets.backup-status',
                'view_path' => 'dashboard.widgets.backup-status',
                'category_id' => $systemCategory?->id,
                'description' => 'Database backup status and schedule',
                'required_permissions' => ['view system status'],
                'allowed_roles' => ['super-admin'],
                'default_width' => 4,
                'default_height' => 200,
                'cache_duration' => 300,
            ],
        ];

        foreach ($widgets as $widget) {
            // Check if widget already exists by slug
            $existingWidget = Widget::where('slug', $widget['slug'])->first();

            if (! $existingWidget) {
                Widget::create($widget);
                echo '✅ Created widget: '.$widget['name']."\n";
            } else {
                echo '⚠️  Widget already exists: '.$widget['name']."\n";
            }
        }
    }
}
