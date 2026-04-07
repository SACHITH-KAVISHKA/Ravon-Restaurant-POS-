<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Menu Management
            'view-menu',
            'create-menu',
            'edit-menu',
            'delete-menu',

            // Order Management
            'view-orders',
            'create-orders',
            'edit-orders',
            'cancel-orders',

            // Table Management
            'view-tables',
            'manage-tables',
            'merge-tables',
            'transfer-tables',

            // Payment Management
            'process-payments',
            'refund-payments',
            'view-payments',

            // KOT Management
            'view-kot',
            'update-kot',
            'reprint-kot',

            // Reports
            'view-reports',
            'export-reports',

            // User Management
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',

            // System Settings
            'view-settings',
            'edit-settings',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions

        // Admin Role
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->givePermissionTo(Permission::all());

        // Super Admin Role
        $superAdmin = Role::firstOrCreate(['name' => 'superadmin']);
        $superAdmin->givePermissionTo(Permission::all());

        // Cashier Role
        $cashier = Role::firstOrCreate(['name' => 'cashier']);
        $cashier->givePermissionTo([
            'view-orders',
            'create-orders',
            'process-payments',
            'view-payments',
            'view-reports',
        ]);

        // Waiter Role
        $waiter = Role::firstOrCreate(['name' => 'waiter']);
        $waiter->givePermissionTo([
            'view-menu',
            'view-orders',
            'create-orders',
            'edit-orders',
            'view-tables',
            'manage-tables',
            'merge-tables',
            'transfer-tables',
        ]);

        // Kitchen Role
        $kitchen = Role::firstOrCreate(['name' => 'kitchen']);
        $kitchen->givePermissionTo([
            'view-kot',
            'update-kot',
            'reprint-kot',
        ]);

        // Manager Role
        $manager = Role::firstOrCreate(['name' => 'manager']);
        $manager->givePermissionTo([
            'view-reports',
            'export-reports',
        ]);

        // Create default users
        $adminUser = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $adminUser->assignRole('admin');

        // Super Admin user (has both superadmin and admin privileges)
        $superAdminUser = User::firstOrCreate(
            ['username' => 'finance'],
            [
                'name' => 'Finance Super Admin',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $superAdminUser->syncRoles(['superadmin', 'admin']);

        $cashierUser = User::firstOrCreate(
            ['username' => 'cashier'],
            [
                'name' => 'Cashier User',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $cashierUser->assignRole('cashier');

        $waiterUser = User::firstOrCreate(
            ['username' => 'waiter'],
            [
                'name' => 'Waiter User',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $waiterUser->assignRole('waiter');

        $kitchenUser = User::firstOrCreate(
            ['username' => 'kitchen'],
            [
                'name' => 'Kitchen User',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $kitchenUser->assignRole('kitchen');

        $managerUser = User::firstOrCreate(
            ['username' => 'Manoj'],
            [
                'name' => 'Manager User',
                'password' => Hash::make('manoj123'),
                'is_active' => true,
            ]
        );
        $managerUser->assignRole('manager');
    }
}
