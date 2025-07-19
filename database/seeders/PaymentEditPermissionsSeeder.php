<?php

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
        // Create payment edit permissions
        $permissions = [
            'edit payments' => 'Edit and modify payment records',
            'reverse payments' => 'Reverse payment transactions',
            'view payment history' => 'View payment edit history and audit logs',
            'revert payments' => 'Revert payments to previous states',
        ];

        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name],
                ['guard_name' => 'web']
            );
        }

        // Assign permissions to roles
        $adminRole = Role::where('name', 'admin')->first();
        $financeRole = Role::where('name', 'finance')->first();
        $accountantRole = Role::where('name', 'accountant')->first();

        if ($adminRole) {
            // Admin gets all payment permissions
            $adminRole->givePermissionTo([
                'edit payments',
                'reverse payments', 
                'view payment history',
                'revert payments'
            ]);
        }

        if ($financeRole) {
            // Finance role gets most permissions except reversal
            $financeRole->givePermissionTo([
                'edit payments',
                'view payment history',
                'revert payments'
            ]);
        }

        if ($accountantRole) {
            // Accountant gets limited permissions
            $accountantRole->givePermissionTo([
                'edit payments',
                'view payment history'
            ]);
        }

        $this->command->info('Payment edit permissions created and assigned successfully!');
    }
}