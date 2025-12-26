<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DashboardPermissionSeeder extends Seeder
{
    public function run()
    {
        // Create dashboard-specific permissions
        $dashboardPermissions = [
            // Dashboard Management
            'view dashboard',
            'customize dashboard',
            'manage dashboards',
            'view all dashboards',
            
            // Widget Permissions
            'view widgets',
            'create widgets',
            'edit widgets',
            'delete widgets',
            'manage widget categories',
            
            // Data Access Permissions
            'view student data',
            'view financial data',
            'view staff data',
            'view academic data',
            'view system data',
            
            // Dashboard Builder
            'access dashboard builder',
            'create dashboard templates',
            'apply dashboard templates'
        ];

        // Create permissions
        foreach ($dashboardPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign permissions to roles
        $this->assignPermissionsToRoles();
    }

    private function assignPermissionsToRoles()
    {
        // Super Admin - Full access
        $superAdmin = Role::where('name', 'super-admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo([
                'view dashboard', 'customize dashboard', 'manage dashboards',
                'view all dashboards', 'view widgets', 'create widgets',
                'edit widgets', 'delete widgets', 'manage widget categories',
                'view student data', 'view financial data', 'view staff data',
                'view academic data', 'view system data', 'access dashboard builder',
                'create dashboard templates', 'apply dashboard templates'
            ]);
        }

        // College Admin - Academic focus
        $collegeAdmin = Role::where('name', 'college-admin')->first();
        if ($collegeAdmin) {
            $collegeAdmin->givePermissionTo([
                'view dashboard', 'customize dashboard',
                'view student data', 'view academic data', 'view staff data',
                'view financial data'
            ]);
        }

        // Accountant - Financial focus
        $accountant = Role::where('name', 'accountant')->first();
        if ($accountant) {
            $accountant->givePermissionTo([
                'view dashboard', 'customize dashboard',
                'view financial data', 'view student data'
            ]);
        }

        // Staff - Limited access
        $staff = Role::where('name', 'staff')->first();
        if ($staff) {
            $staff->givePermissionTo([
                'view dashboard',
                'view student data', 'view academic data'
            ]);
        }

        // Student - Personal data only
        $student = Role::where('name', 'student')->first();
        if ($student) {
            $student->givePermissionTo([
                'view dashboard'
            ]);
        }
    }
}