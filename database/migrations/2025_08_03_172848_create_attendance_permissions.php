<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        // Create attendance permissions
        $permissions = [
            'view attendance',
            'manage attendance',
            'take attendance',
            'edit attendance',
            'delete attendance',
            'export attendance',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Grant to admin and super-admin roles
        $adminRole = Role::where('name', 'admin')->first();
        $superAdminRole = Role::where('name', 'super-admin')->first();

        if ($adminRole) {
            $adminRole->givePermissionTo($permissions);
        }

        if ($superAdminRole) {
            $superAdminRole->givePermissionTo($permissions);
        }
    }

    public function down(): void
    {
        $permissions = [
            'view attendance',
            'manage attendance',
            'take attendance',
            'edit attendance',
            'delete attendance',
            'export attendance',
        ];

        foreach ($permissions as $permission) {
            Permission::where('name', $permission)->delete();
        }
    }
};
