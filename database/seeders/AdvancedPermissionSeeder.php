<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdvancedPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define permission structure with modules and actions
        $permissionStructure = [
            'users' => [
                'actions' => ['view', 'create', 'edit', 'delete', 'manage'],
                'description' => 'User account management',
                'sub_permissions' => [
                    'view profile' => 'View user profiles and basic information',
                    'edit profile' => 'Edit user basic information',
                    'change password' => 'Reset or change user passwords',
                    'manage roles' => 'Assign or remove roles from users',
                    'activate users' => 'Activate or deactivate user accounts',
                    'export users' => 'Export user data',
                    'bulk operations' => 'Perform bulk actions on multiple users'
                ]
            ],
            'roles' => [
                'actions' => ['view', 'create', 'edit', 'delete', 'manage'],
                'description' => 'Role and permission management',
                'sub_permissions' => [
                    'view permissions' => 'View role permissions',
                    'assign permissions' => 'Assign permissions to roles',
                    'create custom roles' => 'Create new custom roles',
                    'edit system roles' => 'Edit system-defined roles'
                ]
            ],
            'students' => [
                'actions' => ['view', 'create', 'edit', 'delete', 'manage'],
                'description' => 'Student information management',
                'sub_permissions' => [
                    'view personal info' => 'View student personal information',
                    'edit personal info' => 'Edit student personal information',
                    'view academic records' => 'View student academic records',
                    'edit academic records' => 'Edit student academic records',
                    'view financials' => 'View student financial information',
                    'manage financials' => 'Manage student fees and payments',
                    'view attendance' => 'View student attendance records',
                    'take attendance' => 'Mark student attendance',
                    'import students' => 'Import student data from files',
                    'export students' => 'Export student data',
                    'promote students' => 'Promote students to next level',
                    'transfer students' => 'Transfer students between batches'
                ]
            ],
            'faculty' => [
                'actions' => ['view', 'create', 'edit', 'delete', 'manage'],
                'description' => 'Faculty and staff management',
                'sub_permissions' => [
                    'view profile' => 'View faculty profiles',
                    'edit profile' => 'Edit faculty information',
                    'assign subjects' => 'Assign subjects to faculty',
                    'view schedule' => 'View faculty schedules',
                    'manage schedule' => 'Create and edit faculty schedules',
                    'view salary' => 'View faculty salary information',
                    'manage salary' => 'Manage faculty salary and components'
                ]
            ],
            'courses' => [
                'actions' => ['view', 'create', 'edit', 'delete', 'manage'],
                'description' => 'Course and curriculum management',
                'sub_permissions' => [
                    'view structure' => 'View course structure and terms',
                    'edit structure' => 'Edit course structure and terms',
                    'assign subjects' => 'Assign subjects to courses',
                    'manage prerequisites' => 'Manage course prerequisites'
                ]
            ],
            'subjects' => [
                'actions' => ['view', 'create', 'edit', 'delete', 'manage'],
                'description' => 'Subject management',
                'sub_permissions' => [
                    'assign faculty' => 'Assign faculty to subjects',
                    'manage syllabus' => 'Manage subject syllabus'
                ]
            ],
            'batches' => [
                'actions' => ['view', 'create', 'edit', 'delete', 'manage'],
                'description' => 'Batch and class management',
                'sub_permissions' => [
                    'manage students' => 'Add or remove students from batches',
                    'graduate batch' => 'Graduate entire batches',
                    'split batch' => 'Split batches into smaller groups'
                ]
            ],
            'attendance' => [
                'actions' => ['view', 'create', 'edit', 'delete', 'manage'],
                'description' => 'Attendance tracking and management',
                'sub_permissions' => [
                    'take daily attendance' => 'Mark daily attendance',
                    'edit past attendance' => 'Edit previously marked attendance',
                    'view reports' => 'View attendance reports',
                    'export reports' => 'Export attendance data',
                    'import attendance' => 'Import attendance from external sources'
                ]
            ],
            'timetable' => [
                'actions' => ['view', 'create', 'edit', 'delete', 'manage'],
                'description' => 'Timetable and scheduling',
                'sub_permissions' => [
                    'create schedule' => 'Create new timetables',
                    'auto generate' => 'Auto-generate timetables',
                    'assign classrooms' => 'Assign classrooms to sessions',
                    'manage conflicts' => 'Resolve scheduling conflicts'
                ]
            ],
            'financials' => [
                'actions' => ['view', 'create', 'edit', 'delete', 'manage'],
                'description' => 'Financial management',
                'sub_permissions' => [
                    'view fee structures' => 'View fee structures',
                    'create fee structures' => 'Create new fee structures',
                    'generate invoices' => 'Generate student invoices',
                    'collect payments' => 'Record fee payments',
                    'apply concessions' => 'Apply fee concessions',
                    'view reports' => 'View financial reports',
                    'manage expenses' => 'Record and manage expenses',
                    'bank reconciliation' => 'Perform bank reconciliation'
                ]
            ],
            'reports' => [
                'actions' => ['view', 'create', 'edit', 'delete', 'manage'],
                'description' => 'Reports and analytics',
                'sub_permissions' => [
                    'attendance reports' => 'Generate attendance reports',
                    'financial reports' => 'Generate financial reports',
                    'academic reports' => 'Generate academic performance reports',
                    'custom reports' => 'Create custom reports',
                    'export data' => 'Export data for external analysis'
                ]
            ],
            'settings' => [
                'actions' => ['view', 'create', 'edit', 'delete', 'manage'],
                'description' => 'System settings and configuration',
                'sub_permissions' => [
                    'general settings' => 'Manage general system settings',
                    'email settings' => 'Configure email settings',
                    'backup settings' => 'Manage backup configurations',
                    'integration settings' => 'Manage third-party integrations',
                    'security settings' => 'Configure security settings'
                ]
            ],
            'inventory' => [
                'actions' => ['view', 'create', 'edit', 'delete', 'manage'],
                'description' => 'Asset and inventory management',
                'sub_permissions' => [
                    'track assets' => 'Track college assets',
                    'asset maintenance' => 'Manage asset maintenance',
                    'conduct audits' => 'Perform inventory audits',
                    'generate reports' => 'Generate inventory reports'
                ]
            ],
            'hr' => [
                'actions' => ['view', 'create', 'edit', 'delete', 'manage'],
                'description' => 'Human resources management',
                'sub_permissions' => [
                    'leave management' => 'Manage leave applications',
                    'payroll processing' => 'Process monthly payroll',
                    'performance reviews' => 'Conduct performance reviews',
                    'recruitment' => 'Manage recruitment process'
                ]
            ],
            'documents' => [
                'actions' => ['view', 'create', 'edit', 'delete', 'manage'],
                'description' => 'Document and certificate management',
                'sub_permissions' => [
                    'generate certificates' => 'Generate student certificates',
                    'create id cards' => 'Create student/staff ID cards',
                    'manage templates' => 'Manage document templates',
                    'digital signatures' => 'Apply digital signatures'
                ]
            ],
            'admissions' => [
                'actions' => ['view', 'create', 'edit', 'delete', 'manage'],
                'description' => 'Admission and enquiry management',
                'sub_permissions' => [
                    'process enquiries' => 'Handle admission enquiries',
                    'conduct interviews' => 'Schedule and conduct interviews',
                    'approve admissions' => 'Approve or reject admissions',
                    'manage waitlist' => 'Manage admission waitlist',
                    'send communications' => 'Send admission-related communications'
                ]
            ],
            'events' => [
                'actions' => ['view', 'create', 'edit', 'delete', 'manage'],
                'description' => 'Event and calendar management',
                'sub_permissions' => [
                    'schedule events' => 'Schedule college events',
                    'manage holidays' => 'Manage holiday calendar',
                    'send notifications' => 'Send event notifications',
                    'track attendance' => 'Track event attendance'
                ]
            ]
        ];

        // Create permissions
        foreach ($permissionStructure as $module => $config) {
            // Create basic CRUD permissions
            foreach ($config['actions'] as $action) {
                Permission::firstOrCreate([
                    'name' => "{$action} {$module}",
                    'guard_name' => 'web'
                ]);
            }

            // Create sub-permissions
            if (isset($config['sub_permissions'])) {
                foreach ($config['sub_permissions'] as $permission => $description) {
                    Permission::firstOrCreate([
                        'name' => "{$permission} {$module}",
                        'guard_name' => 'web'
                    ]);
                }
            }
        }

        // Create special system permissions
        $systemPermissions = [
            'view backend' => 'Access admin panel',
            'view dashboard' => 'View admin dashboard',
            'manage api tokens' => 'Manage API tokens',
            'system backup' => 'Create and restore system backups',
            'system maintenance' => 'Put system in maintenance mode',
            'view activity logs' => 'View system activity logs',
            'manage webhooks' => 'Configure system webhooks',
            'impersonate users' => 'Login as other users',
            'manage widgets' => 'Manage dashboard widgets',
            'access developer tools' => 'Access developer debugging tools'
        ];

        foreach ($systemPermissions as $permission => $description) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }

        // Define role permissions
        $rolePermissions = [
            'super-admin' => 'all', // Gets all permissions
            'admin' => [
                // User Management
                'view users', 'create users', 'edit users', 'delete users',
                'view profile users', 'edit profile users', 'change password users', 'manage roles users',
                'activate users users', 'export users users', 'bulk operations users',
                
                // Role Management
                'view roles', 'create roles', 'edit roles',
                'view permissions roles', 'assign permissions roles',
                
                // Student Management
                'view students', 'create students', 'edit students', 'delete students',
                'view personal info students', 'edit personal info students',
                'view academic records students', 'edit academic records students',
                'view attendance students', 'take attendance students',
                'import students students', 'export students students',
                
                // Academic Management
                'view courses', 'create courses', 'edit courses', 'delete courses',
                'view subjects', 'create subjects', 'edit subjects', 'delete subjects',
                'view batches', 'create batches', 'edit batches', 'delete batches',
                
                // Financial Management
                'view financials', 'create financials', 'edit financials',
                'view fee structures financials', 'create fee structures financials',
                'generate invoices financials', 'collect payments financials',
                'view reports financials',
                
                // System Access
                'view backend', 'view dashboard', 'view reports',
                'manage settings', 'view activity logs'
            ],
            'college-admin' => [
                // Limited User Management
                'view users', 'edit users',
                'view profile users', 'edit profile users',
                
                // Student Management
                'view students', 'create students', 'edit students',
                'view personal info students', 'edit personal info students',
                'view academic records students', 'edit academic records students',
                'view attendance students', 'take attendance students',
                'export students students',
                
                // Academic Management
                'view courses', 'edit courses',
                'view subjects', 'edit subjects',
                'view batches', 'edit batches', 'manage students batches',
                
                // Attendance
                'view attendance', 'create attendance', 'edit attendance',
                'take daily attendance attendance', 'view reports attendance',
                
                // Financial (Limited)
                'view financials', 'view fee structures financials',
                'generate invoices financials', 'collect payments financials',
                
                // Admissions
                'view admissions', 'create admissions', 'edit admissions',
                'process enquiries admissions', 'approve admissions admissions',
                
                // System Access
                'view backend', 'view dashboard', 'view reports'
            ],
            'staff' => [
                // Limited Student Access
                'view students',
                'view personal info students', 'view academic records students',
                'view attendance students', 'take attendance students',
                
                // Faculty specific
                'view faculty', 'edit profile faculty',
                'view schedule faculty', 'view salary faculty',
                
                // Attendance
                'view attendance', 'create attendance',
                'take daily attendance attendance',
                
                // Limited Course Access
                'view courses', 'view subjects', 'view batches',
                
                // System Access
                'view backend', 'view dashboard'
            ],
            'accountant' => [
                // Financial Management
                'view financials', 'create financials', 'edit financials',
                'view fee structures financials', 'create fee structures financials',
                'generate invoices financials', 'collect payments financials',
                'apply concessions financials', 'view reports financials',
                'manage expenses financials',
                
                // Student Financial Info
                'view students', 'view financials students',
                
                // Reports
                'view reports', 'financial reports reports', 'export data reports',
                
                // System Access
                'view backend', 'view dashboard'
            ],
            'student' => [
                // Very limited access - mainly for student portal
                'view backend' // Basic access to student portal
            ]
        ];

        // Assign permissions to roles
        foreach ($rolePermissions as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            
            if ($permissions === 'all') {
                // Super admin gets all permissions
                $role->givePermissionTo(Permission::all());
            } else {
                // Clear existing permissions and assign new ones
                $role->syncPermissions($permissions);
            }
        }

        $this->command->info('Advanced permissions created and assigned successfully!');
    }
}