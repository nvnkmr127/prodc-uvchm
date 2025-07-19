<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::all();

        // Group permissions by their category (e.g., 'students', 'courses')
        $groupedPermissions = $permissions->groupBy(function ($permission) {
            // Extracts the second word to use as a group name (e.g., from 'manage students' it gets 'students')
            return explode(' ', $permission->name)[1] ?? 'general';
        });

        return view('admin.permissions.index', compact('groupedPermissions'));
    }

    public function create()
    {
        return view('admin.permissions.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:permissions,name',
        ]);

        Permission::create(['name' => $request->name]);

        return redirect()->route('admin.permissions.index')->with('success', 'Permission created successfully.');
    }

    public function edit(Permission $permission)
    {
        $roles = Role::all();
        return view('admin.permissions.edit', compact('permission', 'roles'));
    }

    public function update(Request $request, Permission $permission)
    {
        $request->validate([
            'name' => 'required|string|unique:permissions,name,' . $permission->id,
        ]);

        $permission->update(['name' => $request->name]);

        return redirect()->route('admin.permissions.index')->with('success', 'Permission updated successfully.');
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();
        return redirect()->route('admin.permissions.index')->with('success', 'Permission deleted successfully.');
    }

    public function assignRole(Request $request, Permission $permission)
    {
        if ($permission->hasRole($request->role)) {
            return back()->with('message', 'Role exists.');
        }

        $permission->assignRole($request->role);
        return back()->with('message', 'Role assigned.');
    }

    public function removeRole(Permission $permission, Role $role)
    {
        if ($permission->hasRole($role)) {
            $permission->removeRole($role);
            return back()->with('message', 'Role removed.');
        }

        return back()->with('message', 'Role not exists.');
    }

    /**
     * Show the permission management dashboard
     */
    public function management()
    {
        $stats = [
            'total_permissions' => Permission::count(),
            'total_roles' => Role::count(),
            'unassigned_permissions' => Permission::whereDoesntHave('roles')->count(),
            'system_permissions' => Permission::where('name', 'like', 'manage %')->count(),
        ];
        
        $groupedPermissions = Permission::all()->groupBy(function($permission) {
            // Extract module from permission name (e.g., 'manage students' -> 'students')
            $parts = explode(' ', $permission->name);
            return $parts[1] ?? 'general';
        });
        
        $roles = Role::withCount('permissions')->get();
        
        return view('admin.permissions.permission-management', compact('stats', 'groupedPermissions', 'roles'));
    }

    /**
     * Bulk create permissions for a module
     */
    public function bulkCreate(Request $request)
    {
        $request->validate([
            'module' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'actions' => 'required|array',
            'actions.*' => 'in:view,create,edit,delete,manage',
            'sub_permissions' => 'nullable|array',
            'sub_permissions.*.name' => 'nullable|string|max:255',
            'sub_permissions.*.description' => 'nullable|string|max:255',
        ]);

        try {
            $created = 0;
            $module = strtolower($request->module);

            // Create basic action permissions
            foreach ($request->actions as $action) {
                $permissionName = $action . ' ' . $module;
                
                if (!Permission::where('name', $permissionName)->exists()) {
                    Permission::create(['name' => $permissionName]);
                    $created++;
                }
            }

            // Create additional sub-permissions
            if ($request->sub_permissions) {
                foreach ($request->sub_permissions as $subPerm) {
                    if (!empty($subPerm['name'])) {
                        $permissionName = strtolower($subPerm['name']);
                        
                        if (!Permission::where('name', $permissionName)->exists()) {
                            Permission::create(['name' => $permissionName]);
                            $created++;
                        }
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully created {$created} permissions for {$module} module."
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create permissions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Apply permission template to a role
     */
    public function applyTemplate(Request $request)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'template' => 'required|in:viewer,editor,manager,admin',
            'modules' => 'required|array',
            'modules.*' => 'string',
        ]);

        try {
            $role = Role::findOrFail($request->role_id);
            $permissions = collect();

            foreach ($request->modules as $module) {
                switch ($request->template) {
                    case 'viewer':
                        $permissions = $permissions->merge(
                            Permission::where('name', 'like', "view {$module}%")->get()
                        );
                        break;
                    
                    case 'editor':
                        $permissions = $permissions->merge(
                            Permission::whereIn('name', [
                                "view {$module}",
                                "create {$module}",
                                "edit {$module}"
                            ])->get()
                        );
                        break;
                    
                    case 'manager':
                        $permissions = $permissions->merge(
                            Permission::where('name', 'like', "%{$module}%")
                                ->whereNotIn('name', ["manage {$module}"])
                                ->get()
                        );
                        break;
                    
                    case 'admin':
                        $permissions = $permissions->merge(
                            Permission::where('name', 'like', "%{$module}%")->get()
                        );
                        break;
                }
            }

            $role->syncPermissions($permissions->pluck('name')->unique());

            return response()->json([
                'success' => true,
                'message' => "Successfully applied {$request->template} template to {$role->name} role with " . $permissions->count() . " permissions."
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to apply template: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Copy permissions from one role to another
     */
    public function copyRolePermissions(Request $request)
    {
        $request->validate([
            'source_role_id' => 'required|exists:roles,id',
            'target_role_id' => 'required|exists:roles,id|different:source_role_id',
            'merge' => 'boolean',
        ]);

        try {
            $sourceRole = Role::findOrFail($request->source_role_id);
            $targetRole = Role::findOrFail($request->target_role_id);

            $sourcePermissions = $sourceRole->permissions->pluck('name');

            if ($request->merge) {
                // Merge with existing permissions
                $existingPermissions = $targetRole->permissions->pluck('name');
                $allPermissions = $sourcePermissions->merge($existingPermissions)->unique();
                $targetRole->syncPermissions($allPermissions);
            } else {
                // Replace all permissions
                $targetRole->syncPermissions($sourcePermissions);
            }

            $action = $request->merge ? 'merged with' : 'replaced';
            
            return response()->json([
                'success' => true,
                'message' => "Successfully {$action} permissions from {$sourceRole->name} to {$targetRole->name}."
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to copy permissions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get orphaned permissions
     */
    public function getOrphaned()
    {
        try {
            $orphanedPermissions = Permission::whereDoesntHave('roles')
                ->pluck('name')
                ->toArray();

            return response()->json([
                'success' => true,
                'orphaned_count' => count($orphanedPermissions),
                'orphaned_permissions' => $orphanedPermissions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch orphaned permissions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cleanup orphaned permissions
     */
    public function cleanupOrphaned(Request $request)
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'string',
        ]);

        try {
            $deleted = Permission::whereIn('name', $request->permissions)
                ->whereDoesntHave('roles')
                ->delete();

            return response()->json([
                'success' => true,
                'message' => "Successfully cleaned up {$deleted} orphaned permissions."
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cleanup orphaned permissions: ' . $e->getMessage()
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
                ->pluck('name');

            foreach ($duplicates as $duplicate) {
                $issues[] = ['issue' => "Duplicate permission found: {$duplicate}"];
            }

            // Check for malformed permission names
            $permissions = Permission::all();
            foreach ($permissions as $permission) {
                if (!preg_match('/^[a-z\s]+$/', $permission->name)) {
                    $issues[] = ['issue' => "Malformed permission name: {$permission->name}"];
                }
            }

            // Check for roles without permissions
            $emptyRoles = Role::whereDoesntHave('permissions')->pluck('name');
            foreach ($emptyRoles as $roleName) {
                $issues[] = ['issue' => "Role '{$roleName}' has no permissions assigned"];
            }

            return response()->json([
                'success' => true,
                'issues_found' => count($issues),
                'issues' => $issues
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to validate permission structure: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync permissions (existing method - keeping for compatibility)
     */
    public function sync(Request $request)
    {
        try {
            $created = 0;
            $removed = 0;

            // Define standard permissions that should exist
            $standardPermissions = [
                'view backend',
                'manage users',
                'manage roles',
                'manage permissions',
                'manage students',
                'manage courses',
                'manage timetable',
                'manage financials',
                'manage hr',
                'manage inventory',
                'manage documents',
                'manage settings',
                'manage admissions',
                'manage reports',
                'manage api tokens',
            ];

            if ($request->create_missing) {
                foreach ($standardPermissions as $permission) {
                    if (!Permission::where('name', $permission)->exists()) {
                        Permission::create(['name' => $permission]);
                        $created++;
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Permissions synced successfully.',
                'results' => [
                    'created' => $created,
                    'removed' => $removed
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync permissions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get permission analytics
     */
    public function analytics()
    {
        try {
            $totalPermissions = Permission::count();
            $totalRoles = Role::count();
            $orphanedPermissions = Permission::whereDoesntHave('roles')->count();
            $unusedRoles = Role::whereDoesntHave('users')->count();

            // Permission distribution by module
            $permissionDistribution = Permission::all()
                ->groupBy(function($permission) {
                    $parts = explode(' ', $permission->name);
                    return $parts[1] ?? 'general';
                })
                ->map(function($permissions, $module) {
                    $total = $permissions->count();
                    $assigned = $permissions->filter(function($permission) {
                        return $permission->roles->count() > 0;
                    })->count();
                    
                    return [
                        'count' => $total,
                        'assigned' => $assigned,
                        'percentage' => $total > 0 ? round(($assigned / $total) * 100, 1) : 0
                    ];
                });

            // Role complexity
            $roleComplexity = Role::withCount(['permissions', 'users'])
                ->get()
                ->map(function($role) {
                    return [
                        'role' => $role->name,
                        'permissions_count' => $role->permissions_count,
                        'users_count' => $role->users_count
                    ];
                });

            // Top permissions
            $topPermissions = Permission::withCount(['roles as roles_count'])
                ->with(['roles.users'])
                ->get()
                ->map(function($permission) {
                    $usersCount = $permission->roles->sum(function($role) {
                        return $role->users->count();
                    });
                    
                    return [
                        'permission' => $permission->name,
                        'roles_count' => $permission->roles_count,
                        'users_count' => $usersCount
                    ];
                })
                ->sortByDesc('users_count')
                ->take(10)
                ->values();

            // Permission usage by action type
            $permissionUsage = Permission::all()
                ->groupBy(function($permission) {
                    $parts = explode(' ', $permission->name);
                    return $parts[0] ?? 'other';
                })
                ->map(function($permissions, $action) {
                    $total = $permissions->count();
                    $assigned = $permissions->filter(function($permission) {
                        return $permission->roles->count() > 0;
                    })->count();
                    
                    return [
                        'count' => $total,
                        'assigned' => $assigned
                    ];
                });

            return response()->json([
                'success' => true,
                'analytics' => [
                    'overview' => [
                        'total_permissions' => $totalPermissions,
                        'total_roles' => $totalRoles,
                        'orphaned_permissions' => $orphanedPermissions,
                        'unused_roles' => $unusedRoles
                    ],
                    'permission_distribution' => $permissionDistribution,
                    'role_complexity' => $roleComplexity,
                    'top_permissions' => $topPermissions,
                    'permission_usage' => $permissionUsage
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate analytics: ' . $e->getMessage()
            ], 500);
        }
    }
}