<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Create Super Admin role
        $adminRole = Role::create([
            'name' => 'Super Admin',
            'code' => 'admin',
            'description' => 'System Super Administrator with all permissions',
            'status' => 1,
        ]);

        // Create Staff role
        $userRole = Role::create([
            'name' => 'Staff',
            'code' => 'staff',
            'description' => 'Regular staff member role',
            'status' => 1,
        ]);

        // Create permissions
        $permissions = [
            // Dashboard
            ['name' => 'Dashboard', 'code' => 'dashboard', 'type' => 1, 'parent_id' => 0],
            
            // Product Management
            ['name' => 'Product Management', 'code' => 'product', 'type' => 1, 'parent_id' => 0],
            ['name' => 'Product List', 'code' => 'product.list', 'type' => 1, 'parent_id' => 1],
            ['name' => 'Create Product', 'code' => 'product.create', 'type' => 2, 'parent_id' => 1],
            ['name' => 'Edit Product', 'code' => 'product.edit', 'type' => 2, 'parent_id' => 1],
            ['name' => 'Delete Product', 'code' => 'product.delete', 'type' => 2, 'parent_id' => 1],
            
            // Inventory Management
            ['name' => 'Inventory Management', 'code' => 'inventory', 'type' => 1, 'parent_id' => 0],
            ['name' => 'Inventory List', 'code' => 'inventory.list', 'type' => 1, 'parent_id' => 6],
            ['name' => 'Stock Alert', 'code' => 'inventory.warning', 'type' => 1, 'parent_id' => 6],
            ['name' => 'Stock Adjustment', 'code' => 'inventory.adjust', 'type' => 2, 'parent_id' => 6],
            
            // Sales Management
            ['name' => 'Sales Management', 'code' => 'sales', 'type' => 1, 'parent_id' => 0],
            ['name' => 'Sales Orders', 'code' => 'sales.order', 'type' => 1, 'parent_id' => 10],
            ['name' => 'Create Order', 'code' => 'sales.order.create', 'type' => 2, 'parent_id' => 11],
            ['name' => 'Approve Order', 'code' => 'sales.order.approve', 'type' => 2, 'parent_id' => 11],
            ['name' => 'Delivery', 'code' => 'sales.delivery', 'type' => 1, 'parent_id' => 10],
            ['name' => 'Return', 'code' => 'sales.return', 'type' => 1, 'parent_id' => 10],
            
            // Purchase Management
            ['name' => 'Purchase Management', 'code' => 'purchase', 'type' => 1, 'parent_id' => 0],
            ['name' => 'Purchase Orders', 'code' => 'purchase.order', 'type' => 1, 'parent_id' => 16],
            ['name' => 'Purchase Receipt', 'code' => 'purchase.receive', 'type' => 2, 'parent_id' => 16],
            
            // Customer Management
            ['name' => 'Customer Management', 'code' => 'customer', 'type' => 1, 'parent_id' => 0],
            ['name' => 'Customer List', 'code' => 'customer.list', 'type' => 1, 'parent_id' => 19],
            
            // Supplier Management
            ['name' => 'Supplier Management', 'code' => 'supplier', 'type' => 1, 'parent_id' => 0],
            ['name' => 'Supplier List', 'code' => 'supplier.list', 'type' => 1, 'parent_id' => 21],
            
            // Promotion Management
            ['name' => 'Promotion Management', 'code' => 'promotion', 'type' => 1, 'parent_id' => 0],
            ['name' => 'Promotions', 'code' => 'promotion.list', 'type' => 1, 'parent_id' => 23],
            ['name' => 'Coupons', 'code' => 'promotion.coupon', 'type' => 1, 'parent_id' => 23],
            
            // Finance Management
            ['name' => 'Finance Management', 'code' => 'finance', 'type' => 1, 'parent_id' => 0],
            ['name' => 'Receipt', 'code' => 'finance.receipt', 'type' => 1, 'parent_id' => 26],
            ['name' => 'Payment', 'code' => 'finance.payment', 'type' => 1, 'parent_id' => 26],
            
            // System Settings
            ['name' => 'System Settings', 'code' => 'system', 'type' => 1, 'parent_id' => 0],
            ['name' => 'Employee Management', 'code' => 'system.employee', 'type' => 1, 'parent_id' => 29],
            ['name' => 'Roles & Permissions', 'code' => 'system.role', 'type' => 1, 'parent_id' => 29],
        ];

        foreach ($permissions as $permission) {
            Permission::create([
                'name' => $permission['name'],
                'code' => $permission['code'],
                'type' => $permission['type'],
                'parent_id' => $permission['parent_id'],
                'status' => 1,
            ]);
        }

        // Assign all permissions to admin
        $allPermissionIds = Permission::pluck('id')->toArray();
        $adminRole->permissions()->sync($allPermissionIds);

        // Assign basic permissions to staff
        $basicPermissions = Permission::whereIn('code', [
            'dashboard',
            'product.list',
            'inventory.list',
            'sales.order',
            'customer.list',
            'supplier.list',
        ])->pluck('id')->toArray();
        $userRole->permissions()->sync($basicPermissions);
    }
}
