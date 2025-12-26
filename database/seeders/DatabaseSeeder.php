<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        
        // Call the seeder that creates all the permissions and roles
        $this->call([
            RolesAndPermissionsSeeder::class,
            WidgetSeeder::class,
        ]);

        // Create super admin user
        $superAdminUser = User::firstOrCreate(
            ['email' => 'admin@uvchm.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
            ]
        );
        
        // Find the 'super-admin' role and assign it to the user
        $superAdminRole = Role::findByName('super-admin');
        if ($superAdminRole) {
            $superAdminUser->assignRole($superAdminRole);
        }

        // Create API token for the super admin using Sanctum
        $token = $superAdminUser->createToken('Admin Panel Access', ['*']);
        
        $this->command->info('Super Admin created successfully!');
        $this->command->info('Email: admin@uvchm.com');
        $this->command->info('Password: password');
        $this->command->info('API Token: ' . $token->plainTextToken);

        // Call additional seeders
        $this->call([
            RolesAndPermissionsSeeder::class,
            DashboardPermissionSeeder::class,
            WidgetCategorySeeder::class,
            WidgetSeeder::class,
            DashboardSeeder::class,
        ]);
    }
}
