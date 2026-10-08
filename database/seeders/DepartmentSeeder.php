<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['department_name' => 'Finance Accounting and Tax', 'created_by' => 1, 'updated_by' => 1],
            ['department_name' => 'Human Resource General Affairs Document', 'created_by' => 1, 'updated_by' => 1],
            ['department_name' => 'Nasional Sales and Marketing', 'created_by' => 1, 'updated_by' => 1],
            ['department_name' => 'Part and Service Operation', 'created_by' => 1, 'updated_by' => 1],
            ['department_name' => 'Regional Manager ', 'created_by' => 1, 'updated_by' => 1],
            ['department_name' => 'Project and Engineering Service', 'created_by' => 1, 'updated_by' => 1],
            ['department_name' => 'Fabrication and Workshop', 'created_by' => 1, 'updated_by' => 1],
            ['department_name' => 'UNASSIGNED', 'created_by' => 1, 'updated_by' => 1],
            ['department_name' => 'Manager', 'created_by' => 1, 'updated_by' => 1],
            ['department_name' => 'Director', 'created_by' => 1, 'updated_by' => 1],
        ];

        foreach ($departments as $dept) {
            Department::create($dept);
        }
    }
}
