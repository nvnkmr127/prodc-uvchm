<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Clear cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define all permissions
        $permissions = [
            // Basic Access
            'view backend',
            'access dashboard',
            
            // Student Management
            'view students',
            'create students',
            'edit students',
            'delete students',
            'manage students',
            'view student profile',
            'edit student profile',
            'view student academic records',
            'edit student academic records',
            'bulk import students',
            'export students',
            
            // User Management
            'view users',
            'create users',
            'edit users',
            'delete users',
            'manage users',
            'view user profile',
            'edit user profile',
            'change user password',
            'manage user roles',
            'activate users',
            'export users',
            'bulk operations users',
            
            // Role and Permission Management
            'view roles',
            'create roles',
            'edit roles',
            'delete roles',
            'manage roles',
            'view permissions',
            'assign permissions',
            'create custom roles',
            'edit system roles',
            'manage permissions',
            
            // Attendance Management
            'view attendance',
            'take attendance',
            'edit attendance',
            'delete attendance',
            'manage attendance',
            'manage attendance settings',
            'view attendance reports',
            'export attendance',
            
            // Financial Management
            'view financials',
            'create invoices',
            'edit invoices',
            'delete invoices',
            'manage invoices',
            'view payments',
            'process payments',
            'manage payments',
            'view fee structures',
            'create fee structures',
            'edit fee structures',
            'delete fee structures',
            'manage fee structures',
            'view payment reports',
            'export financials',
            'manage payment defaulters',
            'send payment reminders',
            
            // Course Management
            'view courses',
            'create courses',
            'edit courses',
            'delete courses',
            'manage courses',
            
            // Batch Management
            'view batches',
            'create batches',
            'edit batches',
            'delete batches',
            'manage batches',
            
            // Timetable Management
            'view timetable',
            'create timetable',
            'edit timetable',
            'delete timetable',
            'manage timetable',
            
            // Leave Management
            'view leaves',
            'create leaves',
            'edit leaves',
            'delete leaves',
            'manage leaves',
            'approve leaves',
            
            // Event Management
            'view events',
            'create events',
            'edit events',
            'delete events',
            'manage events',
            
            // Calendar Management
            'view calendar',
            'manage calendar',
            
            // Asset Management
            'view assets',
            'create assets',
            'edit assets',
            'delete assets',
            'manage assets',
            
            // Enquiry Management
            'view enquiries',
            'create enquiries',
            'edit enquiries',
            'delete enquiries',
            'manage enquiries',
            
            // Settings Management
            'view settings',
            'edit settings',
            'manage settings',
            'view system settings',
            'edit system settings',
            
            // Notification Management
            'view notifications',
            'create notifications',
            'edit notifications',
            'delete notifications',
            'manage notifications',
            'send notifications',
            'manage communications',
            
            // Advanced Features
            'view analytics',
            'manage integrations',
            'manage webhooks',
            'manage api',
            'system monitoring',
        ];

        // Create all permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create roles and assign permissions
        $this->createRoles();
    }

    private function createRoles()
    {
        // Super Admin Role - Full access
        $superAdminRole = Role::firstOrCreate([
            'name' => 'super-admin',
            'guard_name' => 'web'
        ], [
            'description' => 'Super Administrator with full system access'
        ]);
        // Give super admin all permissions
        $superAdminRole->syncPermissions(Permission::all());

        // Admin Role - High level management
        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web'
        ], [
            'description' => 'Administrator with high-level management access'
        ]);
        $adminRole->syncPermissions([
            'view backend',
            'access dashboard',
            'view students',
            'create students',
            'edit students',
            'delete students',
            'manage students',
            'view users',
            'create users',
            'edit users',
            'delete users',
            'manage users',
            'view roles',
            'manage roles',
            'view permissions',
            'manage permissions',
            'view attendance',
            'take attendance',
            'edit attendance',
            'manage attendance',
            'view financials',
            'create invoices',
            'edit invoices',
            'manage invoices',
            'view payments',
            'process payments',
            'manage payments',
            'view courses',
            'create courses',
            'edit courses',
            'manage courses',
            'view batches',
            'create batches',
            'edit batches',
            'manage batches',
            'view timetable',
            'manage timetable',
            'view events',
            'manage events',
            'view calendar',
            'manage calendar',
            'view assets',
            'manage assets',
            'view enquiries',
            'manage enquiries',
            'view settings',
            'edit settings',
            'manage settings',
            'view analytics',
            'manage payment defaulters',
            'send payment reminders',
        ]);

        // Staff Role - Faculty members
        $staffRole = Role::firstOrCreate([
            'name' => 'staff',
            'guard_name' => 'web'
        ], [
            'description' => 'Faculty members who can take attendance and manage their classes'
        ]);
        $staffRole->syncPermissions([
            'view backend',
            'access dashboard',
            'take attendance',
            'view attendance',
            'view students',
            'view timetable',
            'view leaves',
            'view calendar',
            'view events',
        ]);

        // Faculty Role - Alternative name for staff (for compatibility)
        $facultyRole = Role::firstOrCreate([
            'name' => 'faculty',
            'guard_name' => 'web'
        ], [
            'description' => 'Faculty members (alternative name for staff role)'
        ]);
        $facultyRole->syncPermissions([
            'view backend',
            'access dashboard',
            'take attendance',
            'view attendance',
            'view students',
            'view timetable',
            'view leaves',
            'view calendar',
            'view events',
        ]);

        // Accountant Role - Financial management
        $accountantRole = Role::firstOrCreate([
            'name' => 'accountant',
            'guard_name' => 'web'
        ], [
            'description' => 'Handles all financial operations and reporting'
        ]);
        $accountantRole->syncPermissions([
            'view backend',
            'access dashboard',
            'view financials',
            'create invoices',
            'edit invoices',
            'manage invoices',
            'view payments',
            'process payments',
            'manage payments',
            'view fee structures',
            'create fee structures',
            'edit fee structures',
            'manage fee structures',
            'view payment reports',
            'export financials',
            'manage payment defaulters',
            'send payment reminders',
            'view students',
            'view batches',
            'view courses',
        ]);

        // Student Role - Very limited access
        $studentRole = Role::firstOrCreate([
            'name' => 'student',
            'guard_name' => 'web'
        ], [
            'description' => 'Student with limited access to their own data'
        ]);
        $studentRole->syncPermissions([
            'view backend',
            'access dashboard',
        ]);

        // Academic Coordinator Role
        $academicRole = Role::firstOrCreate([
            'name' => 'academic-coordinator',
            'guard_name' => 'web'
        ], [
            'description' => 'Academic coordinator for managing courses and timetables'
        ]);
        $academicRole->syncPermissions([
            'view backend',
            'access dashboard',
            'view students',
            'edit students',
            'view courses',
            'create courses',
            'edit courses',
            'manage courses',
            'view batches',
            'create batches',
            'edit batches',
            'manage batches',
            'view timetable',
            'create timetable',
            'edit timetable',
            'manage timetable',
            'view attendance',
            'take attendance',
            'edit attendance',
            'view events',
            'create events',
            'edit events',
            'manage events',
            'view calendar',
            'manage calendar',
        ]);

        // Office Staff Role
        $officeRole = Role::firstOrCreate([
            'name' => 'office-staff',
            'guard_name' => 'web'
        ], [
            'description' => 'Office staff for general administrative tasks'
        ]);
        $officeRole->syncPermissions([
            'view backend',
            'access dashboard',
            'view students',
            'create students',
            'edit students',
            'view enquiries',
            'create enquiries',
            'edit enquiries',
            'manage enquiries',
            'view assets',
            'create assets',
            'edit assets',
            'manage assets',
            'view leaves',
            'create leaves',
            'edit leaves',
            'view events',
            'view calendar',
        ]);

        // ADDED: Counselor Role
        $counselorRole = Role::firstOrCreate([
            'name' => 'counselor',
            'guard_name' => 'web'
        ], [
            'description' => 'Counselor for managing student enquiries and admissions'
        ]);
        $counselorRole->syncPermissions([
            'view backend',
            'access dashboard',
            'view enquiries',
            'create enquiries',
            'edit enquiries',
            'delete enquiries',
            'manage enquiries',
            'view students',
            'create students',
            'view courses',
            'view batches',
            'view calendar',
            'view events',
        ]);
    }
}