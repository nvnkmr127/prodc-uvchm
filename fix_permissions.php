<?php

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

echo "--- Fixing Permissions ---\n";

// Ensure permissions exist
$permissions = ['manage admissions', 'view backend'];
foreach ($permissions as $p) {
    Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
}

// Assign to Admin
$adminRole = Role::where('name', 'admin')->first();
if ($adminRole) {
    $adminRole->givePermissionTo($permissions);
    echo "Granted 'manage admissions' and 'view backend' to 'admin' role.\n";
} else {
    echo "Role 'admin' not found!\n";
}

// Assign to Super Admin if exists
$superAdminRole = Role::where('name', 'super-admin')->first();
if ($superAdminRole) {
    $superAdminRole->givePermissionTo($permissions);
    echo "Granted 'manage admissions' and 'view backend' to 'super-admin' role.\n";
}

echo "Done.\n";
