<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    /**
     * Display a listing of roles
     */
    public function index()
    {
        $roles = Role::withCount('users', 'permissions')->get();
        return view('admin.roles.index', compact('roles'));
    }

    /**
     * Show the form for creating a new role
     */
    public function create()
    {
        $permissions = Permission::all();
        $groupedPermissions = $this->groupPermissions($permissions);
        
        return view('admin.roles.create', compact('permissions', 'groupedPermissions'));
    }

    /**
     * Store a newly created role
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name|max:255',
            'description' => 'nullable|string|max:500',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name'
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null
        ]);

        // Assign permissions if provided
        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role created successfully.');
    }

    /**
     * Display the specified role
     */
    public function show($id)
    {
        $role = Role::with('permissions', 'users')->findOrFail($id);
        $groupedPermissions = $this->groupPermissions($role->permissions);
        
        return view('admin.roles.show', compact('role', 'groupedPermissions'));
    }
    
    /**
     * Show the form for editing the specified role
     */
    public function edit(Role $role)
    {
        $allPermissions = Permission::all();
        
        $groupedPermissions = $allPermissions->groupBy(function($permission) {
            $parts = explode(' ', $permission->name);
            return $parts[1] ?? 'general';
        });
        
        $rolePermissions = $role->permissions->pluck('name')->toArray();
        $allRoles = Role::all();
        
        return view('admin.roles.edit', compact(
            'role',
            'groupedPermissions', 
            'rolePermissions',
            'allRoles'
        ));
    }

    /**
     * Update the specified role
     */
    public function update(Request $request, Role $role)
{
    // DEBUG: Log the incoming request
    \Log::info('Role Update Debug', [
        'role_id' => $role->id,
        'role_name' => $role->name,
        'request_permissions' => $request->permissions,
        'request_permissions_type' => gettype($request->permissions),
        'request_all' => $request->all(),
        'has_permissions_key' => $request->has('permissions'),
    ]);

    $request->validate([
        'name' => [
            'required',
            'string',
            'max:255',
            Rule::unique('roles')->ignore($role->id),
        ],
        'description' => 'nullable|string|max:500',
        'permissions' => 'nullable|array',
        'permissions.*' => 'string|exists:permissions,name'
    ]);

    // Update role basic info
    $role->update([
        'name' => $request->name,
        'description' => $request->description
    ]);

    // DEBUG: Log before permission sync
    \Log::info('Before Permission Sync', [
        'role_id' => $role->id,
        'current_permissions' => $role->permissions->pluck('name')->toArray(),
        'incoming_permissions' => $request->permissions ?? [],
        'permissions_to_sync' => $request->has('permissions') ? $request->permissions : []
    ]);

    // Sync permissions
    if ($request->has('permissions')) {
        $role->syncPermissions($request->permissions);
    } else {
        $role->syncPermissions([]);
    }

    // DEBUG: Log after permission sync
    $role->refresh();
    \Log::info('After Permission Sync', [
        'role_id' => $role->id,
        'final_permissions' => $role->permissions->pluck('name')->toArray(),
        'permission_count' => $role->permissions->count()
    ]);

    return redirect()->route('admin.roles.index')
        ->with('success', 'Role updated successfully.');
}

    /**
     * Remove the specified role
     */
    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        
        // Prevent deletion of core system roles
        if (in_array($role->name, ['super-admin', 'student', 'staff', 'college-admin', 'accountant'])) {
            return back()->with('error', 'Cannot delete core system roles.');
        }

        // Check if role has users
        if ($role->users()->count() > 0) {
            return back()->with('error', 'Cannot delete role that has users assigned to it.');
        }

        $role->delete();
        
        return back()->with('success', 'Role deleted successfully.');
    }

    /**
     * Update role permissions (for AJAX requests)
     */
    public function updatePermissions(Request $request, Role $role)
    {
        $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name'
        ]);

        try {
            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            } else {
                $role->syncPermissions([]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Permissions updated successfully.',
                'permissions_count' => $role->permissions->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update permissions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get role permissions for comparison (for AJAX calls)
     */
    public function getPermissions(Role $role)
    {
        try {
            return response()->json([
                'success' => true,
                'role' => $role->name,
                'permissions' => $role->permissions->pluck('name')->toArray(),
                'permissions_count' => $role->permissions->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch role permissions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clone a role with its permissions
     */
    public function clone(Role $role)
    {
        try {
            $newRole = Role::create([
                'name' => $role->name . ' (Copy)',
                'description' => $role->description ? $role->description . ' (Copy)' : null
            ]);

            // Copy all permissions
            $newRole->syncPermissions($role->permissions);

            return redirect()->route('admin.roles.edit', $newRole)
                ->with('success', 'Role cloned successfully. You can now modify the cloned role.');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to clone role: ' . $e->getMessage());
        }
    }

    /**
     * Bulk actions for roles
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:delete,clone',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id'
        ]);

        $roleIds = $request->roles;
        $protectedRoles = ['super-admin', 'student', 'staff', 'college-admin', 'accountant'];

        try {
            switch ($request->action) {
                case 'delete':
                    $rolesToDelete = Role::whereIn('id', $roleIds)
                        ->whereNotIn('name', $protectedRoles)
                        ->whereDoesntHave('users')
                        ->get();

                    $deletedCount = $rolesToDelete->count();
                    
                    foreach ($rolesToDelete as $role) {
                        $role->delete();
                    }

                    return response()->json([
                        'success' => true,
                        'message' => "Successfully deleted {$deletedCount} roles."
                    ]);

                case 'clone':
                    $clonedCount = 0;
                    $roles = Role::whereIn('id', $roleIds)->get();

                    foreach ($roles as $role) {
                        $newRole = Role::create([
                            'name' => $role->name . ' (Copy ' . now()->format('Y-m-d') . ')',
                            'description' => $role->description ? $role->description . ' (Copy)' : null
                        ]);
                        $newRole->syncPermissions($role->permissions);
                        $clonedCount++;
                    }

                    return response()->json([
                        'success' => true,
                        'message' => "Successfully cloned {$clonedCount} roles."
                    ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk action failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export role permissions as JSON
     */
    public function export(Role $role)
    {
        $data = [
            'role' => [
                'name' => $role->name,
                'description' => $role->description,
                'permissions' => $role->permissions->pluck('name')->toArray(),
                'exported_at' => now()->toISOString(),
                'exported_by' => auth()->user()->name
            ]
        ];

        $filename = 'role_' . str_replace(' ', '_', strtolower($role->name)) . '_' . now()->format('Y_m_d') . '.json';

        return response()->json($data)
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Import role permissions from JSON
     */
    public function import(Request $request)
    {
        $request->validate([
            'import_file' => 'required|file|mimes:json',
            'role_id' => 'required|exists:roles,id'
        ]);

        try {
            $role = Role::findOrFail($request->role_id);
            $jsonContent = file_get_contents($request->file('import_file')->path());
            $data = json_decode($jsonContent, true);

            if (!isset($data['role']['permissions'])) {
                throw new \Exception('Invalid file format. Missing permissions data.');
            }

            $permissions = $data['role']['permissions'];
            $validPermissions = Permission::whereIn('name', $permissions)->pluck('name')->toArray();

            $role->syncPermissions($validPermissions);

            $importedCount = count($validPermissions);
            $skippedCount = count($permissions) - $importedCount;

            $message = "Successfully imported {$importedCount} permissions.";
            if ($skippedCount > 0) {
                $message .= " {$skippedCount} permissions were skipped (not found in system).";
            }

            return redirect()->route('admin.roles.edit', $role)
                ->with('success', $message);

        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Get role statistics
     */
    public function statistics()
    {
        try {
            $stats = [
                'total_roles' => Role::count(),
                'roles_with_users' => Role::has('users')->count(),
                'empty_roles' => Role::doesntHave('users')->count(),
                'total_permissions' => Permission::count(),
                'most_permissions' => Role::withCount('permissions')->orderBy('permissions_count', 'desc')->first(),
                'most_users' => Role::withCount('users')->orderBy('users_count', 'desc')->first(),
                'recent_roles' => Role::latest()->take(5)->get(['id', 'name', 'created_at']),
                'permission_distribution' => Role::withCount('permissions')->get(['name', 'permissions_count'])
            ];

            return response()->json([
                'success' => true,
                'statistics' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Group permissions by module for better organization
     */
    private function groupPermissions($permissions)
    {
        $grouped = [];
        
        foreach ($permissions as $permission) {
            // Extract module name from permission name
            // E.g., "view students" -> "students", "manage admissions" -> "admissions"
            $parts = explode(' ', $permission->name);
            
            if (count($parts) >= 2) {
                $action = $parts[0]; // view, create, edit, delete, manage
                $module = $parts[1]; // students, courses, etc.
                
                if (!isset($grouped[$module])) {
                    $grouped[$module] = [];
                }
                
                $grouped[$module][] = $permission;
            } else {
                // Handle single word permissions
                if (!isset($grouped['general'])) {
                    $grouped['general'] = [];
                }
                $grouped['general'][] = $permission;
            }
        }
        
        // Sort modules alphabetically
        ksort($grouped);
        
        return $grouped;
    }

    /**
     * Get permissions template for quick assignment
     */
    public function getPermissionTemplate(Request $request)
    {
        $request->validate([
            'template' => 'required|in:viewer,editor,manager,admin',
            'modules' => 'nullable|array'
        ]);

        $modules = $request->modules ?? ['users', 'students', 'courses'];
        $template = $request->template;
        
        $permissions = [];

        foreach ($modules as $module) {
            switch ($template) {
                case 'viewer':
                    $permissions[] = "view {$module}";
                    break;
                
                case 'editor':
                    $permissions = array_merge($permissions, [
                        "view {$module}",
                        "create {$module}",
                        "edit {$module}"
                    ]);
                    break;
                
                case 'manager':
                    $permissions = array_merge($permissions, [
                        "view {$module}",
                        "create {$module}",
                        "edit {$module}",
                        "delete {$module}"
                    ]);
                    break;
                
                case 'admin':
                    $permissions = array_merge($permissions, [
                        "view {$module}",
                        "create {$module}",
                        "edit {$module}",
                        "delete {$module}",
                        "manage {$module}"
                    ]);
                    break;
            }
        }

        // Add system permissions for admin template
        if ($template === 'admin') {
            $permissions = array_merge($permissions, [
                'view backend',
                'manage settings',
                'view reports'
            ]);
        }

        // Filter to only existing permissions
        $validPermissions = Permission::whereIn('name', $permissions)->pluck('name')->toArray();

        return response()->json([
            'success' => true,
            'permissions' => $validPermissions,
            'count' => count($validPermissions)
        ]);
    }

    /**
     * Compare two roles
     */
    public function compare(Request $request)
    {
        $request->validate([
            'role1_id' => 'required|exists:roles,id',
            'role2_id' => 'required|exists:roles,id|different:role1_id'
        ]);

        try {
            $role1 = Role::with('permissions')->findOrFail($request->role1_id);
            $role2 = Role::with('permissions')->findOrFail($request->role2_id);

            $role1Permissions = $role1->permissions->pluck('name')->toArray();
            $role2Permissions = $role2->permissions->pluck('name')->toArray();

            $comparison = [
                'role1' => [
                    'name' => $role1->name,
                    'permissions' => $role1Permissions,
                    'count' => count($role1Permissions)
                ],
                'role2' => [
                    'name' => $role2->name,
                    'permissions' => $role2Permissions,
                    'count' => count($role2Permissions)
                ],
                'common' => array_intersect($role1Permissions, $role2Permissions),
                'only_role1' => array_diff($role1Permissions, $role2Permissions),
                'only_role2' => array_diff($role2Permissions, $role1Permissions)
            ];

            return response()->json([
                'success' => true,
                'comparison' => $comparison
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Comparison failed: ' . $e->getMessage()
            ], 500);
        }
    }
}