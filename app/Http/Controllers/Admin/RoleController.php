<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

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
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
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

        $groupedPermissions = $allPermissions->groupBy(function ($permission) {
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
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        // Update role basic info
        $role->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        // DEBUG: Log before permission sync
        \Log::info('Before Permission Sync', [
            'role_id' => $role->id,
            'current_permissions' => $role->permissions->pluck('name')->toArray(),
            'incoming_permissions' => $request->permissions ?? [],
            'permissions_to_sync' => $request->has('permissions') ? $request->permissions : [],
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
            'permission_count' => $role->permissions->count(),
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
     * Clone a role with its permissions
     */
    public function clone(Role $role)
    {
        try {
            $newRole = Role::create([
                'name' => $role->name.' (Copy)',
                'description' => $role->description ? $role->description.' (Copy)' : null,
            ]);

            // Copy all permissions
            $newRole->syncPermissions($role->permissions);

            return redirect()->route('admin.roles.edit', $newRole)
                ->with('success', 'Role cloned successfully. You can now modify the cloned role.');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to clone role: '.$e->getMessage());
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
            'roles.*' => 'exists:roles,id',
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
                        'message' => "Successfully deleted {$deletedCount} roles.",
                    ]);

                case 'clone':
                    $clonedCount = 0;
                    $roles = Role::whereIn('id', $roleIds)->get();

                    foreach ($roles as $role) {
                        $newRole = Role::create([
                            'name' => $role->name.' (Copy '.now()->format('Y-m-d').')',
                            'description' => $role->description ? $role->description.' (Copy)' : null,
                        ]);
                        $newRole->syncPermissions($role->permissions);
                        $clonedCount++;
                    }

                    return response()->json([
                        'success' => true,
                        'message' => "Successfully cloned {$clonedCount} roles.",
                    ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk action failed: '.$e->getMessage(),
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
                'exported_by' => auth()->user()->name,
            ],
        ];

        $filename = 'role_'.str_replace(' ', '_', strtolower($role->name)).'_'.now()->format('Y_m_d').'.json';

        return response()->json($data)
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }

    /**
     * Import role permissions from JSON
     */
    public function import(Request $request)
    {
        $request->validate([
            'import_file' => 'required|file|mimes:json',
            'role_id' => 'required|exists:roles,id',
        ]);

        try {
            $role = Role::findOrFail($request->role_id);
            $jsonContent = file_get_contents($request->file('import_file')->path());
            $data = json_decode($jsonContent, true);

            if (! isset($data['role']['permissions'])) {
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
            return back()->with('error', 'Import failed: '.$e->getMessage());
        }
    }
}
