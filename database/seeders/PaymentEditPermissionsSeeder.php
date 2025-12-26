<?php

// Fixed PaymentEditPermissionsSeeder without description column
// File: database/seeders/PaymentEditPermissionsSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PaymentEditPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create payment-related permissions (without description field)
        $permissions = [
            'manage financials',
            'edit payments',
            'reverse payments',
            'view payment history',
            'revert payments',
            'delete payments',
            'export payment data',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission],
                ['guard_name' => 'web']
            );
        }

        // Assign permissions to roles
        $this->assignPermissionsToRoles();

        $this->command->info('Payment permissions created and assigned successfully!');
    }

    /**
     * Assign permissions to existing roles
     */
    private function assignPermissionsToRoles(): void
    {
        // Admin role - gets all permissions
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        if ($adminRole) {
            $adminRole->givePermissionTo([
                'manage financials',
                'edit payments',
                'reverse payments', 
                'view payment history',
                'revert payments',
                'delete payments',
                'export payment data'
            ]);
        }

        // Finance role - gets most permissions except dangerous ones
        $financeRole = Role::firstOrCreate(['name' => 'finance']);
        if ($financeRole) {
            $financeRole->givePermissionTo([
                'manage financials',
                'edit payments',
                'view payment history',
                'revert payments',
                'export payment data'
            ]);
        }

        // Accountant role - gets limited permissions
        $accountantRole = Role::firstOrCreate(['name' => 'accountant']);
        if ($accountantRole) {
            $accountantRole->givePermissionTo([
                'manage financials',
                'edit payments',
                'view payment history',
                'export payment data'
            ]);
        }

        // Cashier role - basic payment operations
        $cashierRole = Role::firstOrCreate(['name' => 'cashier']);
        if ($cashierRole) {
            $cashierRole->givePermissionTo([
                'manage financials',
                'view payment history'
            ]);
        }

        // Faculty role - view only
        $facultyRole = Role::where('name', 'faculty')->first();
        if ($facultyRole) {
            $facultyRole->givePermissionTo([
                'view payment history'
            ]);
        }

        $this->command->info('Permissions assigned to roles successfully!');
    }
}