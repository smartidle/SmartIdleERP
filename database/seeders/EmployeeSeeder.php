<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        // Get admin role
        $adminRole = Role::where('code', 'admin')->first();
        $staffRole = Role::where('code', 'staff')->first();
        $dept = Department::first();

        // Create admin user
        Employee::create([
            'code' => 'EMP001',
            'name' => 'John Smith',
            'email' => 'admin@erp.com',
            'password' => Hash::make('admin123'),
            'department_id' => $dept->id ?? 1,
            'position' => 'System Administrator',
            'role_id' => $adminRole->id ?? 1,
            'status' => 1,
        ]);

        // Create more staff
        Employee::create([
            'code' => 'EMP002',
            'name' => 'Sarah Johnson',
            'email' => 'sarah@erp.com',
            'password' => Hash::make('password123'),
            'department_id' => $dept->id ?? 1,
            'position' => 'Sales Manager',
            'role_id' => $staffRole->id ?? 2,
            'status' => 1,
        ]);

        Employee::create([
            'code' => 'EMP003',
            'name' => 'Michael Chen',
            'email' => 'michael@erp.com',
            'password' => Hash::make('password123'),
            'department_id' => $dept->id ?? 1,
            'position' => 'Warehouse Supervisor',
            'role_id' => $staffRole->id ?? 2,
            'status' => 1,
        ]);

        Employee::create([
            'code' => 'EMP004',
            'name' => 'Emily Davis',
            'email' => 'emily@erp.com',
            'password' => Hash::make('password123'),
            'department_id' => $dept->id ?? 1,
            'position' => 'Accountant',
            'role_id' => $staffRole->id ?? 2,
            'status' => 1,
        ]);
    }
}
