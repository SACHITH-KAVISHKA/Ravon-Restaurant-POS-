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
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions

        // Admin Role
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo(Permission::all());

        // Cashier Role
        $cashier = Role::create(['name' => 'cashier']);
        $cashier->givePermissionTo([
            'view-orders',
            'create-orders',
            'process-payments',
            'view-payments',
            'view-reports',
        ]);

        // Waiter Role
        $waiter = Role::create(['name' => 'waiter']);
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
        $kitchen = Role::create(['name' => 'kitchen']);
        $kitchen->givePermissionTo([
            'view-kot',
            'update-kot',
            'reprint-kot',
        ]);

        // Create default users
        $adminUser = User::create([
            'name' => 'Admin User',
            'username' => 'admin',
            'email' => 'admin@ravon.com',
            'password' => Hash::make('password'),
            'employee_id' => 'EMP001',
            'phone' => '+94771234567',
            'is_active' => true,
        ]);
        $adminUser->assignRole('admin');

        $cashierUser = User::create([
            'name' => 'Cashier User',
            'username' => 'cashier',
            'email' => 'cashier@ravon.com',
            'password' => Hash::make('password'),
            'employee_id' => 'EMP002',
            'phone' => '+94771234568',
            'is_active' => true,
        ]);
        $cashierUser->assignRole('cashier');

        $waiterUser = User::create([
            'name' => 'Waiter User',
            'username' => 'waiter',
            'email' => 'waiter@ravon.com',
            'password' => Hash::make('password'),
            'employee_id' => 'EMP003',
            'phone' => '+94771234569',
            'is_active' => true,
        ]);
        $waiterUser->assignRole('waiter');

        $kitchenUser = User::create([
            'name' => 'Kitchen User',
            'username' => 'kitchen',
            'email' => 'kitchen@ravon.com',
            'password' => Hash::make('password'),
            'employee_id' => 'EMP004',
            'phone' => '+94771234570',
            'is_active' => true,
        ]);
        $kitchenUser->assignRole('kitchen');
    }
}
