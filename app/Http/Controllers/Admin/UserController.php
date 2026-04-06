<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;

class UserController extends Controller
{
    /**
     * Display a listing of users
     */
    public function index(Request $request)
    {
        $query = User::with(['roles', 'student']);
        
        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        // Filter by role
        if ($request->filled('role')) {
            $query->whereHas('roles', function($q) use ($request) {
                $q->where('name', $request->role);
            });
        }
        
        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        }
        
        $users = $query->latest()->paginate(15);
        $roles = Role::all();
        
        return view('admin.users.index', compact('users', 'roles'));
    }

    /**
     * Show the form for creating a new user
     */
    public function create()
    {
        $roles = Role::all();
        return view('admin.users.create', compact('roles'));
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,name',
            'status' => 'sometimes|in:active,inactive'
        ]);

        try {
            DB::beginTransaction();

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'email_verified_at' => $request->status === 'inactive' ? null : now(),
            ]);

            $user->assignRole($request->roles);

            DB::commit();

            return redirect()->route('admin.users.index')
                            ->with('success', 'User created successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('User creation failed: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Failed to create user. Please try again.');
        }
    }

    /**
     * Display the specified user
     */
    public function show(User $user)
    {
        $user->load(['roles.permissions', 'student']);
        return view('admin.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user
     */
   public function edit(User $user)
{
    // Get all roles for the dropdown
    $roles = Role::all();
    
    // Get user's current roles
    $userRoles = $user->roles->pluck('id')->toArray();
    
    // Get all permissions (if needed)
    $permissions = Permission::all();
    
    // Get user's current permissions
    $userPermissions = $user->getAllPermissions()->pluck('id')->toArray();
    
    return view('admin.users.edit', compact(
        'user', 
        'roles', 
        'userRoles', 
        'permissions', 
        'userPermissions'
    ));
}


 public function update(Request $request, User $user)
{
    // Debug the incoming roles (remove this after fixing)
    // dd($request->all(), $request->roles);
    
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => [
            'required', 
            'email', 
            'unique:users,email,' . $user->id
        ],
        'password' => 'nullable|min:8|confirmed',
        'roles' => 'nullable|array',
        'roles.*' => 'integer|exists:roles,id', // Changed from string to integer
        'status' => 'nullable|in:active,inactive,suspended'
    ]);

    try {
        // Update user basic info
        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'status' => $request->status ?? 'active'
        ];

        // Add password if provided
        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }

        $user->update($userData);

        // Handle roles with authorization checks
        if ($request->has('roles') && is_array($request->roles)) {
            // Filter out empty values and ensure they're integers
            $roleIds = array_filter($request->roles, function($role) {
                return !empty($role) && is_numeric($role);
            });
            
            // Convert to integers
            $roleIds = array_map('intval', $roleIds);
            
            // Authorization check: Prevent privilege escalation
            $currentUser = auth()->user();
            $requestedRoles = Role::whereIn('id', $roleIds)->get();
            
            // Check if user is trying to assign super-admin role
            if ($requestedRoles->contains('name', 'super-admin') && !$currentUser->hasRole('super-admin')) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'You cannot assign super-admin role.');
            }
            
            // Prevent users from assigning roles they don't have
            foreach ($requestedRoles as $role) {
                if (!$currentUser->hasRole('super-admin') && !$currentUser->hasRole($role->name)) {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'You cannot assign roles that you do not have.');
                }
            }
            
            // Sync roles using role IDs
            $user->syncRoles($roleIds);
            
            // Clear Spatie permission cache to ensure roles/permissions refresh immediately
            app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        } else {
            // Remove all roles if none selected (only if user has permission)
            if (auth()->user()->hasRole('super-admin') || auth()->user()->can('manage users')) {
                $user->syncRoles([]);
                
                // Clear Spatie permission cache
                app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
            }
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully.');

    } catch (\Exception $e) {
        return redirect()->back()
            ->withInput()
            ->with('error', 'Failed to update user: ' . $e->getMessage());
    }
}

    /**
     * Remove the specified user
     */
    public function destroy(User $user)
    {
        // Prevent deleting the current user
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')
                           ->with('error', 'You cannot delete your own account.');
        }

        // Prevent deleting super-admin users (unless current user is also super-admin)
        if ($user->hasRole('super-admin') && !auth()->user()->hasRole('super-admin')) {
            return redirect()->route('admin.users.index')
                           ->with('error', 'Cannot delete super-admin users.');
        }

        try {
            DB::beginTransaction();

            // Remove all roles and permissions
            $user->syncRoles([]);
            $user->syncPermissions([]);

            // Delete the user
            $user->delete();

            // Clear Spatie permission cache
            app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

            DB::commit();

            return redirect()->route('admin.users.index')
                            ->with('success', 'User deleted successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('User deletion failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to delete user. Please try again.');
        }
    }

    /**
     * Update user status (AJAX)
     */
    public function updateStatus(Request $request, User $user)
    {
        $request->validate([
            'status' => 'required|in:active,inactive'
        ]);

        // Prevent deactivating current user
        if ($user->id === auth()->id() && $request->status === 'inactive') {
            return response()->json([
                'success' => false,
                'message' => 'You cannot deactivate your own account.'
            ], 422);
        }

        try {
            if ($request->status === 'active') {
                $user->update(['email_verified_at' => now()]);
            } else {
                $user->update(['email_verified_at' => null]);
            }

            return response()->json([
                'success' => true,
                'message' => 'User status updated successfully.',
                'status' => $request->status
            ]);
        } catch (\Exception $e) {
            Log::error('Status update failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user status.'
            ], 500);
        }
    }

    /**
     * Bulk actions for users
     */
    public function bulkActions(Request $request)
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,delete',
            'users' => 'required|array|min:1',
            'users.*' => 'exists:users,id'
        ]);

        $users = User::whereIn('id', $request->users)->get();
        $currentUserId = auth()->id();
        $count = 0;
        $errors = [];

        try {
            DB::beginTransaction();

            foreach ($users as $user) {
                /** @var \App\Models\User $user */
                // Skip current user for certain actions
                if ($user->id === $currentUserId) {
                    if (in_array($request->action, ['deactivate', 'delete'])) {
                        $errors[] = "Skipped your own account.";
                        continue;
                    }
                }

                // Skip super-admins if current user is not super-admin
                if ($user->hasRole('super-admin') && !auth()->user()->hasRole('super-admin')) {
                    $errors[] = "Skipped super-admin: {$user->name}";
                    continue;
                }

                switch ($request->action) {
                    case 'activate':
                        $user->update(['email_verified_at' => now()]);
                        $count++;
                        break;
                    case 'deactivate':
                        $user->update(['email_verified_at' => null]);
                        $count++;
                        break;
                    case 'delete':
                        $user->syncRoles([]);
                        $user->syncPermissions([]);
                        $user->delete();
                        $count++;
                        break;
                }
            }

            // Clear Spatie permission cache after bulk updates
            app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

            DB::commit();

            $message = "Successfully {$request->action}d {$count} users.";
            if (!empty($errors)) {
                $message .= " Warnings: " . implode(' ', $errors);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'count' => $count,
                'errors' => $errors
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Bulk action failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Bulk action failed. Please try again.'
            ], 500);
        }
    }

    /**
     * Bulk destroy users (for DataTables compatibility)
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:users,id'
        ]);

        return $this->bulkActions(new Request([
            'action' => 'delete',
            'users' => $request->ids
        ]));
    }

    /**
     * Export users data
     */
    public function export(Request $request)
    {
        $query = User::with('roles');
        
        if ($request->filled('role')) {
            $query->whereHas('roles', function($q) use ($request) {
                $q->where('name', $request->role);
            });
        }
        
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        }

        $users = $query->get();

        $csvData = "Name,Email,Roles,Status,Created At\n";
        foreach ($users as $user) {
            $roles = $user->roles->pluck('name')->implode('; ');
            $status = $user->email_verified_at ? 'Active' : 'Inactive';
            $csvData .= "\"{$user->name}\",\"{$user->email}\",\"{$roles}\",\"{$status}\",\"{$user->created_at->format('Y-m-d H:i:s')}\"\n";
        }

        return response($csvData)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="users_export_' . date('Y-m-d_H-i-s') . '.csv"');
    }
}