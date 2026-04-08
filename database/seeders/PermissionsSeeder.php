<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define all permissions
        $permissions = [
            // Backend Access
            'view backend',

            // Settings Management
            'manage settings',
            'view settings',
            'update settings',
            'reset settings',
            'backup settings',
            'restore settings',

            // User Management
            'manage users',
            'view users',
            'create users',
            'update users',
            'delete users',

            // Student Management
            'manage students',
            'view students',
            'create students',
            'update students',
            'delete students',
            'import students',
            'export students',

            // Course Management
            'manage courses',
            'view courses',
            'create courses',
            'update courses',
            'delete courses',

            // Subject Management
            'manage subjects',
            'view subjects',
            'create subjects',
            'update subjects',
            'delete subjects',

            // Batch Management
            'manage batches',
            'view batches',
            'create batches',
            'update batches',
            'delete batches',

            // Faculty Management
            'manage faculty',
            'view faculty',
            'create faculty',
            'update faculty',
            'delete faculty',

            // Attendance Management
            'manage attendance',
            'view attendance',
            'take attendance',
            'edit attendance',
            'import attendance',
            'export attendance',

            // Timetable Management
            'manage timetable',
            'view timetable',
            'create timetable',
            'update timetable',
            'delete timetable',

            // Financial Management
            'manage financials',
            'view financials',
            'create invoices',
            'update invoices',
            'delete invoices',
            'manage payments',
            'view payments',
            'create payments',
            'update payments',
            'delete payments',
            'manage fee structures',
            'view fee structures',

            // HR Management
            'manage hr',
            'view hr',
            'manage leaves',
            'approve leaves',
            'reject leaves',
            'manage salaries',
            'view salaries',
            'generate payslips',

            // Inventory Management
            'manage inventory',
            'view inventory',
            'create assets',
            'update assets',
            'delete assets',
            'audit assets',

            // Document Management
            'manage documents',
            'view documents',
            'generate certificates',
            'generate id cards',
            'manage templates',

            // Admission Management
            'manage admissions',
            'view admissions',
            'create admissions',
            'update admissions',
            'approve admissions',
            'reject admissions',
            'manage enquiries',
            'view enquiries',

            // Report Management
            'manage reports',
            'view reports',
            'generate reports',
            'export reports',
            'view attendance reports',
            'view financial reports',
            'view admission reports',
            'view asset reports',

            // System Management
            'manage system',
            'view system info',
            'manage backups',
            'clear cache',
            'toggle maintenance',
            'manage api tokens',
            'view logs',

            // Dashboard & Analytics
            'view dashboard',
            'view analytics',
            'manage widgets',
            'customize dashboard',
        ];

        // Create permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Define roles and their permissions
        $roles = [
            'super-admin' => $permissions, // All permissions

            'admin' => [
                'view backend',
                'manage settings',
                'view settings',
                'update settings',
                'backup settings',
                'manage users',
                'view users',
                'create users',
                'update users',
                'manage students',
                'view students',
                'create students',
                'update students',
                'import students',
                'export students',
                'manage courses',
                'view courses',
                'create courses',
                'update courses',
                'manage subjects',
                'view subjects',
                'create subjects',
                'update subjects',
                'manage batches',
                'view batches',
                'create batches',
                'update batches',
                'manage faculty',
                'view faculty',
                'create faculty',
                'update faculty',
                'manage attendance',
                'view attendance',
                'take attendance',
                'edit attendance',
                'manage timetable',
                'view timetable',
                'create timetable',
                'update timetable',
                'manage financials',
                'view financials',
                'create invoices',
                'update invoices',
                'manage payments',
                'view payments',
                'create payments',
                'update payments',
                'manage fee structures',
                'view fee structures',
                'manage hr',
                'view hr',
                'manage leaves',
                'approve leaves',
                'manage salaries',
                'view salaries',
                'generate payslips',
                'manage inventory',
                'view inventory',
                'create assets',
                'update assets',
                'audit assets',
                'manage documents',
                'view documents',
                'generate certificates',
                'generate id cards',
                'manage templates',
                'manage admissions',
                'view admissions',
                'create admissions',
                'update admissions',
                'approve admissions',
                'manage enquiries',
                'view enquiries',
                'manage reports',
                'view reports',
                'generate reports',
                'export reports',
                'view dashboard',
                'view analytics',
                'manage widgets',
            ],

            'college-admin' => [
                'view backend',
                'view settings',
                'manage students',
                'view students',
                'create students',
                'update students',
                'import students',
                'export students',
                'view courses',
                'view subjects',
                'view batches',
                'manage faculty',
                'view faculty',
                'create faculty',
                'update faculty',
                'manage attendance',
                'view attendance',
                'take attendance',
                'edit attendance',
                'manage timetable',
                'view timetable',
                'create timetable',
                'update timetable',
                'manage financials',
                'view financials',
                'create invoices',
                'update invoices',
                'manage payments',
                'view payments',
                'create payments',
                'update payments',
                'view fee structures',
                'manage hr',
                'view hr',
                'manage leaves',
                'approve leaves',
                'view salaries',
                'manage inventory',
                'view inventory',
                'create assets',
                'update assets',
                'manage documents',
                'view documents',
                'generate certificates',
                'generate id cards',
                'manage admissions',
                'view admissions',
                'create admissions',
                'update admissions',
                'approve admissions',
                'manage enquiries',
                'view enquiries',
                'view reports',
                'generate reports',
                'export reports',
                'view dashboard',
                'view analytics',
            ],

            'staff' => [
                'view backend',
                'view students',
                'view courses',
                'view subjects',
                'view batches',
                'view faculty',
                'manage attendance',
                'view attendance',
                'take attendance',
                'view timetable',
                'view financials',
                'view payments',
                'manage leaves',
                'view documents',
                'view dashboard',
            ],

            'accountant' => [
                'view backend',
                'view students',
                'manage financials',
                'view financials',
                'create invoices',
                'update invoices',
                'manage payments',
                'view payments',
                'create payments',
                'update payments',
                'manage fee structures',
                'view fee structures',
                'view financial reports',
                'generate reports',
                'export reports',
                'view dashboard',
            ],

            'librarian' => [
                'view backend',
                'view students',
                'manage inventory',
                'view inventory',
                'create assets',
                'update assets',
                'audit assets',
                'view asset reports',
                'view dashboard',
            ],

            'admission-officer' => [
                'view backend',
                'manage admissions',
                'view admissions',
                'create admissions',
                'update admissions',
                'approve admissions',
                'manage enquiries',
                'view enquiries',
                'view admission reports',
                'generate reports',
                'view dashboard',
            ],

            'manage biometric mapping' => [
                'display_name' => 'Manage Biometric Mapping',
                'description' => 'Access biometric mapping interface',
                'group' => 'Student Management',
            ],

            'import biometric data' => [
                'display_name' => 'Import Biometric Data',
                'description' => 'Import biometric mappings from Excel/CSV',
                'group' => 'Student Management',
            ],

            'export biometric data' => [
                'display_name' => 'Export Biometric Data',
                'description' => 'Export biometric mapping reports',
                'group' => 'Student Management',
            ],

            'auto generate biometric codes' => [
                'display_name' => 'Auto Generate Biometric Codes',
                'description' => 'Auto-generate biometric codes for students',
                'group' => 'Student Management',
            ],
        ];

        // Create roles and assign permissions
        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($rolePermissions);
        }

        // Create a default super admin user if it doesn't exist
        $user = \App\Models\User::firstOrCreate(
            ['email' => 'admin@college.edu'],
            [
                'name' => 'Super Administrator',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        // Assign super-admin role to the default user
        $user->assignRole('super-admin');

        $this->command->info('Permissions and roles seeded successfully!');
        $this->command->info('Default super admin created: admin@college.edu / password');
    }
}
