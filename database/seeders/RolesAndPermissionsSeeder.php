<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Define all permissions with proper grouping
        $permissions = [
            // General System
            'view backend',
            'access dashboard',
            
            // Students Management
            'view students',
            'create students',
            'edit students',
            'delete students',
            'export students',
            'import students',
            'manage students',
            
            // Faculty Management
            'view faculty',
            'create faculty',
            'edit faculty',
            'delete faculty',
            'manage faculty',
            'assign subjects',
            
            // Courses Management
            'view courses',
            'create courses',
            'edit courses',
            'delete courses',
            'manage courses',
            'manage structure',
            
            // Subjects Management
            'view subjects',
            'create subjects',
            'edit subjects',
            'delete subjects',
            'manage subjects',
            
            // Batches Management
            'view batches',
            'create batches',
            'edit batches',
            'delete batches',
            'manage batches',
            'graduate batches',
            
            // Admissions Management
            'view admissions',
            'create admissions',
            'edit admissions',
            'delete admissions',
            'manage admissions',
            'approve admissions',
            'reject admissions',
            
            // Enquiries Management
            'view enquiries',
            'create enquiries',
            'edit enquiries',
            'delete enquiries',
            'manage enquiries',
            'convert enquiries',
            
            // Attendance Management
            'view attendance',
            'take attendance',
            'edit attendance',
            'delete attendance',
            'manage attendance',
            'import attendance',
            
            // Timetable Management
            'view timetable',
            'create timetable',
            'edit timetable',
            'delete timetable',
            'manage timetable',
            'generate timetable',
            
            // Financial Management
            'view financials',
            'create invoices',
            'edit invoices',
            'delete invoices',
            'manage financials',
            'collect payments',
            'apply concessions',
            'view ledgers',
            
            // Fee Management
            'view fees',
            'create fees',
            'edit fees',
            'delete fees',
            'manage fees',
            'set structures',
            
            // Expenses Management
            'view expenses',
            'create expenses',
            'edit expenses',
            'delete expenses',
            'manage expenses',
            
            // HR Management
            'view hr',
            'manage hr',
            'view leaves',
            'approve leaves',
            'reject leaves',
            'manage payroll',
            'generate payslips',
            
            // Inventory Management
            'view inventory',
            'create assets',
            'edit assets',
            'delete assets',
            'manage inventory',
            'conduct audits',
            
            // Reports Management
            'view reports',
            'generate reports',
            'export reports',
            'manage reports',
            
            // User Management
            'view users',
            'create users',
            'edit users',
            'delete users',
            'manage users',
            
            // Role & Permission Management
            'view roles',
            'create roles',
            'edit roles',
            'delete roles',
            'manage roles',
            'view permissions',
            'create permissions',
            'edit permissions',
            'delete permissions',
            'manage permissions',
            
            // Settings Management
            'view settings',
            'edit settings',
            'manage settings',
            'backup system',
            'restore system',
            
            // Documents Management
            'view documents',
            'create documents',
            'edit documents',
            'delete documents',
            'manage documents',
            'generate certificates',
            'generate idcards',
            
            // Events & Calendar
            'view events',
            'create events',
            'edit events',
            'delete events',
            'manage events',
            'view calendar',
            
            // Visitors Management
            'view visitors',
            'create visitors',
            'edit visitors',
            'delete visitors',
            'manage visitors',
            
            // Communication
            'send notifications',
            'bulk communications',
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
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions
        $this->createRoles();
    }

    private function createRoles()
    {
        // Student Role - Very limited access
      $studentRole = Role::firstOrCreate(
    ['name' => 'student'], // Attributes to find by
    ['description' => 'Student with limited access to their own data'] // Additional attributes for creation
);
        $studentRole->syncPermissions([
            'view backend',
            'access dashboard',
        ]);

        // Staff Role - Faculty members
        $staffRole = Role::firstOrCreate([
            'name' => 'staff',
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

        // Accountant Role - Financial management
        $accountantRole = Role::firstOrCreate([
            'name' => 'accountant',
            'description' => 'Handles all financial operations and reporting'
        ]);
        $accountantRole->syncPermissions([
            'view backend',
            'access dashboard',
            'view financials',
            'create invoices',
            'edit invoices',
            'manage financials',
            'collect payments',
            'apply concessions',
            'view ledgers',
            'view fees',
            'manage fees',
            'view expenses',
            'create expenses',
            'edit expenses',
            'manage expenses',
            'view reports',
            'generate reports',
            'export reports',
            'view students',
        ]);

        // College Admin Role - Most operations except system settings
        $collegeAdminRole = Role::firstOrCreate([
            'name' => 'college-admin',
            'description' => 'College administrator with comprehensive access'
        ]);
        $collegeAdminRole->syncPermissions([
            'view backend',
            'access dashboard',
            'manage students',
            'manage faculty',
            'manage courses',
            'manage subjects',
            'manage batches',
            'manage admissions',
            'manage enquiries',
            'manage attendance',
            'manage timetable',
            'manage financials',
            'manage fees',
            'manage expenses',
            'manage hr',
            'manage inventory',
            'manage reports',
            'manage events',
            'manage visitors',
            'manage documents',
            'view calendar',
            'send notifications',
            'bulk communications',
            'view analytics',
        ]);

        // Super Admin Role - Full system access
        $superAdminRole = Role::firstOrCreate([
            'name' => 'super-admin',
            'description' => 'Complete system administrator with all permissions'
        ]);
        
        // Assign ALL permissions to super admin
        $allPermissions = Permission::all();
        $superAdminRole->syncPermissions($allPermissions);
    }
}