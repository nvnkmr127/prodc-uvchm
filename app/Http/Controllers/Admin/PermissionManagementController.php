<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;

class PermissionManagementController extends Controller
{
    /**
     * Display the permission management dashboard
     */
    public function index()
    {
        $totalPermissions = Permission::count();
        $totalRoles = Role::count();
        $totalUsers = User::count();
        
        // Get permissions by module
        $permissionsByModule = Permission::all()->groupBy(function($permission) {
            $parts = explode(' ', $permission->name);
            return count($parts) > 1 ? $parts[1] : 'general';
        });
        
        // Get role permission statistics
        $roleStats = Role::withCount('permissions')->get();
        
        // Get recently created permissions
        $recentPermissions = Permission::latest()->limit(10)->get();
        
        // Get orphaned permissions (permissions not assigned to any role)
        $orphanedPermissions = Permission::whereDoesntHave('roles')->get();
        
        return view('admin.permission-management.index', compact(
            'totalPermissions',
            'totalRoles', 
            'totalUsers',
            'permissionsByModule',
            'roleStats',
            'recentPermissions',
            'orphanedPermissions'
        ));
    }

    /**
     * Bulk create permissions
     */
    public function bulkCreate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'permissions' => 'required|array',
            'permissions.*' => 'required|string|max:255|unique:permissions,name',
            'guard_name' => 'sometimes|string|in:web,api'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $guardName = $request->input('guard_name', 'web');
        $createdPermissions = [];

        DB::beginTransaction();
        try {
            foreach ($request->permissions as $permissionName) {
                $permission = Permission::create([
                    'name' => trim($permissionName),
                    'guard_name' => $guardName
                ]);
                $createdPermissions[] = $permission;
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => count($createdPermissions) . ' permissions created successfully',
                'data' => $createdPermissions
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create permissions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync permissions with application routes/features
     */
    public function syncPermissions(Request $request)
    {
        try {
            // Define standard permissions for common modules
            $standardPermissions = [
                // User Management
                'view users',
                'create users', 
                'edit users',
                'delete users',
                'manage users',
                
                // Role Management
                'view roles',
                'create roles',
                'edit roles', 
                'delete roles',
                'manage roles',
                
                // Permission Management
                'view permissions',
                'create permissions',
                'edit permissions',
                'delete permissions', 
                'manage permissions',
                
                // Course Management
                'view courses',
                'create courses',
                'edit courses',
                'delete courses',
                'manage courses',
                
                // Student Management
                'view students',
                'create students',
                'edit students',
                'delete students',
                'manage students',
                
                // Financial Management
                'view financials',
                'create financials',
                'edit financials',
                'delete financials',
                'manage financials',
                
                // HR Management
                'view hr',
                'create hr',
                'edit hr',
                'delete hr',
                'manage hr',
                
                // Timetable Management
                'view timetable',
                'create timetable',
                'edit timetable',
                'delete timetable',
                'manage timetable',
                
                // Inventory Management
                'view inventory',
                'create inventory',
                'edit inventory',
                'delete inventory',
                'manage inventory',
                
                // Document Management
                'view documents',
                'create documents',
                'edit documents',
                'delete documents',
                'manage documents',
                
                // Settings Management
                'view settings',
                'edit settings',
                'manage settings',
                
                // Report Management
                'view reports',
                'create reports',
                'manage reports',
                
                // API Token Management
                'view api tokens',
                'create api tokens',
                'edit api tokens',
                'delete api tokens',
                'manage api tokens',
                
                // Admission Management
                'view admissions',
                'create admissions',
                'edit admissions',
                'delete admissions',
                'manage admissions',
                
                // Backend Access
                'view backend',
                'access admin panel'
            ];

            $createdCount = 0;
            $existingCount = 0;

            foreach ($standardPermissions as $permissionName) {
                $permission = Permission::firstOrCreate([
                    'name' => $permissionName,
                    'guard_name' => 'web'
                ]);

                if ($permission->wasRecentlyCreated) {
                    $createdCount++;
                } else {
                    $existingCount++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Sync completed: {$createdCount} new permissions created, {$existingCount} already existed",
                'data' => [
                    'created' => $createdCount,
                    'existing' => $existingCount,
                    'total' => count($standardPermissions)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clean up orphaned permissions
     */
    public function cleanupOrphaned(Request $request)
    {
        try {
            $orphanedPermissions = Permission::whereDoesntHave('roles')->get();
            $count = $orphanedPermissions->count();
            
            if ($request->input('confirm') === 'true') {
                foreach ($orphanedPermissions as $permission) {
                    // Also check if any users have this permission directly
                    $usersWithPermission = User::permission($permission->name)->count();
                    if ($usersWithPermission === 0) {
                        $permission->delete();
                    }
                }
                
                return response()->json([
                    'success' => true,
                    'message' => "{$count} orphaned permissions cleaned up"
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => "Found {$count} orphaned permissions",
                'data' => $orphanedPermissions,
                'requires_confirmation' => true
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cleanup failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Apply permission template to role
     */
    public function applyTemplate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'role_id' => 'required|exists:roles,id',
            'template' => 'required|string|in:admin,manager,staff,student,viewer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $role = Role::findById($request->role_id);
            
            // Define permission templates
            $templates = [
                'admin' => [
                    'manage users', 'manage roles', 'manage permissions', 'manage courses',
                    'manage students', 'manage financials', 'manage hr', 'manage timetable',
                    'manage inventory', 'manage documents', 'manage settings', 'manage reports',
                    'manage api tokens', 'manage admissions', 'view backend'
                ],
                'manager' => [
                    'view users', 'edit users', 'view roles', 'manage courses', 'manage students',
                    'view financials', 'manage hr', 'manage timetable', 'view reports', 'view backend'
                ],
                'staff' => [
                    'view students', 'edit students', 'view courses', 'manage timetable',
                    'view reports', 'view backend'
                ],
                'student' => [
                    'view backend'
                ],
                'viewer' => [
                    'view users', 'view roles', 'view courses', 'view students',
                    'view financials', 'view reports', 'view backend'
                ]
            ];

            $permissions = $templates[$request->template] ?? [];
            
            // Clear existing permissions and assign new ones
            $role->syncPermissions($permissions);
            
            return response()->json([
                'success' => true,
                'message' => "Template '{$request->template}' applied successfully to role '{$role->name}'",
                'data' => [
                    'role' => $role->name,
                    'template' => $request->template,
                    'permissions_count' => count($permissions)
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to apply template: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get role permissions for comparison
     */
    public function getRolePermissions(Role $role)
    {
        try {
            $permissions = $role->permissions()->get();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'role' => $role->name,
                    'permissions' => $permissions->pluck('name'),
                    'permissions_count' => $permissions->count()
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get role permissions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Copy permissions from one role to another
     */
    public function copyPermissions(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'source_role_id' => 'required|exists:roles,id',
            'target_role_id' => 'required|exists:roles,id|different:source_role_id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $sourceRole = Role::findById($request->source_role_id);
            $targetRole = Role::findById($request->target_role_id);
            
            $sourcePermissions = $sourceRole->permissions()->pluck('name')->toArray();
            $targetRole->syncPermissions($sourcePermissions);
            
            return response()->json([
                'success' => true,
                'message' => "Permissions copied from '{$sourceRole->name}' to '{$targetRole->name}'",
                'data' => [
                    'source_role' => $sourceRole->name,
                    'target_role' => $targetRole->name,
                    'permissions_copied' => count($sourcePermissions)
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to copy permissions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get permission analytics
     */
    public function getAnalytics()
    {
        try {
            $analytics = [
                'total_permissions' => Permission::count(),
                'total_roles' => Role::count(),
                'total_users' => User::count(),
                'orphaned_permissions' => Permission::whereDoesntHave('roles')->count(),
                'permissions_by_module' => Permission::all()->groupBy(function($permission) {
                    $parts = explode(' ', $permission->name);
                    return count($parts) > 1 ? $parts[1] : 'general';
                })->map->count(),
                'role_permission_distribution' => Role::withCount('permissions')->get()->map(function($role) {
                    return [
                        'role' => $role->name,
                        'permissions_count' => $role->permissions_count
                    ];
                }),
                'most_used_permissions' => Permission::withCount('roles')->orderBy('roles_count', 'desc')->limit(10)->get(),
                'least_used_permissions' => Permission::withCount('roles')->orderBy('roles_count', 'asc')->limit(10)->get()
            ];

            return response()->json([
                'success' => true,
                'data' => $analytics
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get analytics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Find orphaned permissions
     */
    public function findOrphanedPermissions()
    {
        try {
            $orphanedPermissions = Permission::whereDoesntHave('roles')
                ->whereDoesntHave('users')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $orphanedPermissions,
                'count' => $orphanedPermissions->count()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to find orphaned permissions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate permission report
     */
    public function generateReport(Request $request)
    {
        try {
            $reportType = $request->input('type', 'full');
            
            $report = [
                'generated_at' => now()->toISOString(),
                'report_type' => $reportType,
                'summary' => [
                    'total_permissions' => Permission::count(),
                    'total_roles' => Role::count(),
                    'total_users' => User::count(),
                    'orphaned_permissions' => Permission::whereDoesntHave('roles')->count()
                ]
            ];

            if ($reportType === 'full' || $reportType === 'permissions') {
                $report['permissions'] = Permission::with('roles')->get();
            }

            if ($reportType === 'full' || $reportType === 'roles') {
                $report['roles'] = Role::with('permissions', 'users')->get();
            }

            if ($reportType === 'full' || $reportType === 'users') {
                $report['users'] = User::with('roles', 'permissions')->get();
            }

            return response()->json([
                'success' => true,
                'data' => $report
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate permission structure
     */
    public function validateStructure()
    {
        try {
            $issues = [];
            
            // Check for duplicate permissions
            $duplicates = Permission::select('name')
                ->groupBy('name')
                ->havingRaw('COUNT(*) > 1')
                ->get();
            
            if ($duplicates->count() > 0) {
                $issues[] = [
                    'type' => 'duplicate_permissions',
                    'message' => 'Duplicate permissions found',
                    'data' => $duplicates
                ];
            }
            
            // Check for orphaned permissions
            $orphaned = Permission::whereDoesntHave('roles')->whereDoesntHave('users')->get();
            if ($orphaned->count() > 0) {
                $issues[] = [
                    'type' => 'orphaned_permissions',
                    'message' => 'Orphaned permissions found',
                    'count' => $orphaned->count()
                ];
            }
            
            // Check for roles without permissions
            $emptyRoles = Role::whereDoesntHave('permissions')->get();
            if ($emptyRoles->count() > 0) {
                $issues[] = [
                    'type' => 'empty_roles',
                    'message' => 'Roles without permissions found',
                    'data' => $emptyRoles->pluck('name')
                ];
            }
            
            // Check for inconsistent naming
            $permissions = Permission::all();
            $namingIssues = [];
            foreach ($permissions as $permission) {
                if (!preg_match('/^[a-z]+(\s[a-z]+)*$/', $permission->name)) {
                    $namingIssues[] = $permission->name;
                }
            }
            
            if (count($namingIssues) > 0) {
                $issues[] = [
                    'type' => 'naming_issues',
                    'message' => 'Permissions with inconsistent naming found',
                    'data' => $namingIssues
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'is_valid' => count($issues) === 0,
                    'issues_count' => count($issues),
                    'issues' => $issues
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . $e->getMessage()
            ], 500);
        }
    }
}