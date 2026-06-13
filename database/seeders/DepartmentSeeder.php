<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['Name' => '总经办', 'code' => 'CEO', 'parent_id' => 0],
            ['Name' => '销售部', 'code' => 'SALES', 'parent_id' => 0],
            ['Name' => '采购部', 'code' => 'PURCHASE', 'parent_id' => 0],
            ['Name' => '仓储部', 'code' => 'WAREHOUSE', 'parent_id' => 0],
            ['Name' => '财务部', 'code' => 'FINANCE', 'parent_id' => 0],
            ['Name' => '技术部', 'code' => 'TECH', 'parent_id' => 0],
            ['Name' => '人力资源部', 'code' => 'HR', 'parent_id' => 0],
        ];

        foreach ($departments as $dept) {
            Department::create($dept);
        }
    }
}
