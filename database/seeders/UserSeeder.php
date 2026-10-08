<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Worker;
use App\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Schema;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // --- Pastikan Roles sudah ada ---
        $roles = [
            'dev', 'admin', 'director', 'manager',
            'hrd', 'department_head', 'leader',
            'senior_staff', 'employee',
        ];

        foreach ($roles as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }

        // --- Daftar User ---
        $users = [
            ['email' => 'developer@fss.com', 'payroll_number' => '007', 'name' => 'Developer', 'role' => 'dev'],
            ['email' => 'admin@fss.com', 'payroll_number' => 1001, 'name' => 'Admin', 'role' => 'admin'],

            [
                'email' => 'director@fss.com',
                'payroll_number' => 1006,
                'name' => 'Director User',
                'role' => 'director',
                'company' => 'FSS',
                'department_name' => 'Director',
                'position' => 'Director',
                'date_of_hire' => '2010-01-01',
            ],
            [
                'email' => 'manager@fss.com',
                'payroll_number' => 1011,
                'name' => 'Manager User',
                'role' => 'manager',
                'company' => 'FSS',
                'department_name' => 'Manager',
                'position' => 'General Manager',
                'date_of_hire' => '2015-09-01',
            ],
            [
                'email' => 'hrd@fss.com',
                'payroll_number' => 1007,
                'name' => 'HRD User',
                'role' => 'hrd',
                'company' => 'FSS',
                'department_name' => 'Human Resource General Affairs Document',
                'position' => 'HRD',
                'date_of_hire' => '2016-02-20',
            ],
            [
                'email' => 'worker@fss.com',
                'payroll_number' => 1002,
                'name' => 'Worker User',
                'role' => 'employee',
                'company' => 'FSS',
                'department_name' => 'Finance Accounting and Tax',
                'position' => 'Staff',
                'date_of_hire' => '2020-01-01',
            ],
            [
                'email' => 'seniorstaff@fss.com',
                'payroll_number' => 1003,
                'name' => 'Senior Staff User',
                'role' => 'senior_staff',
                'company' => 'FSS',
                'department_name' => 'Finance Accounting and Tax',
                'position' => 'Head of IT',
                'date_of_hire' => '2019-05-01',
            ],
            [
                'email' => 'leader@fss.com',
                'payroll_number' => 1004,
                'name' => 'Leader User',
                'role' => 'leader',
                'company' => 'FSS',
                'department_name' => 'Finance Accounting and Tax',
                'position' => 'Leader',
                'date_of_hire' => '2018-03-10',
            ],
            [
                'email' => 'depthead@fss.com',
                'payroll_number' => 1005,
                'name' => 'Department Head User',
                'role' => 'department_head',
                'company' => 'FSS',
                'department_name' => 'Finance Accounting and Tax',
                'position' => 'Dept Head',
                'date_of_hire' => '2017-06-15',
            ],
            [
                'email' => 'salesworker@fss.com',
                'payroll_number' => 1008,
                'name' => 'Sales Worker',
                'role' => 'employee',
                'company' => 'FSS',
                'department_name' => 'Nasional Sales and Marketing',
                'position' => 'Sales Staff',
                'date_of_hire' => '2021-04-01',
            ],
            // --- Site Leader dan Employee dengan Site ---
            [
                'email' => 'leader.mks@fss.com',
                'payroll_number' => 1101,
                'name' => 'Leader Site Makassar',
                'role' => 'leader',
                'company' => 'FSS',
                'department_name' => 'Part and Service Operation',
                'position' => 'Site Leader',
                'date_of_hire' => '2018-02-15',
                'site' => 'Makassar',
            ],
            [
                'email' => 'leader.jkt@fss.com',
                'payroll_number' => 1102,
                'name' => 'Leader Site Jakarta',
                'role' => 'leader',
                'company' => 'FSS',
                'department_name' => 'Part and Service Operation',
                'position' => 'Site Leader',
                'date_of_hire' => '2019-01-10',
                'site' => 'Jakarta',
            ],
            [
                'email' => 'employee.mks1@fss.com',
                'payroll_number' => 1201,
                'name' => 'Employee Makassar 1',
                'role' => 'employee',
                'company' => 'FSS',
                'department_name' => 'Part and Service Operation',
                'position' => 'Technician',
                'date_of_hire' => '2021-05-10',
                'site' => 'Makassar',
            ],
            [
                'email' => 'employee.mks2@fss.com',
                'payroll_number' => 1202,
                'name' => 'Employee Makassar 2',
                'role' => 'employee',
                'company' => 'FSS',
                'department_name' => 'Part and Service Operation',
                'position' => 'Technician',
                'date_of_hire' => '2022-03-01',
                'site' => 'Makassar',
            ],
            [
                'email' => 'employee.jkt1@fss.com',
                'payroll_number' => 1203,
                'name' => 'Employee Jakarta 1',
                'role' => 'employee',
                'company' => 'FSS',
                'department_name' => 'Part and Service Operation',
                'position' => 'Technician',
                'date_of_hire' => '2021-05-10',
                'site' => 'Jakarta',
            ],
            [
                'email' => 'employee.jkt2@fss.com',
                'payroll_number' => 1204,
                'name' => 'Employee Jakarta 2',
                'role' => 'employee',
                'company' => 'FSS',
                'department_name' => 'Part and Service Operation',
                'position' => 'Technician',
                'date_of_hire' => '2022-03-01',
                'site' => 'Jakarta',
            ],
        ];

        // --- Proses Insert / Update ---
        $hasRoleColumn = Schema::hasColumn('users', 'role_id');

        foreach ($users as $userData) {
            $role = Role::where('name', $userData['role'])->first();
            if (!$role) continue;

            $userAttributes = [
                'payroll_number' => $userData['payroll_number'],
                'name' => $userData['name'],
                'password' => Hash::make('password'),
                'is_active' => 1,
            ];

            if ($hasRoleColumn) {
                $userAttributes['role_id'] = $role->id;
            }

            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                $userAttributes
            );

            $user->assignRole($userData['role']);

            if (!empty($userData['department_name'])) {
                $department = Department::firstOrCreate([
                    'department_name' => $userData['department_name'],
                ]);
            }

            if (in_array($userData['role'], [
                'employee', 'senior_staff', 'leader',
                'department_head', 'manager', 'director', 'hrd'
            ])) {
                Worker::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'company' => $userData['company'] ?? 'FSS',
                        'worker_name' => $userData['name'],
                        'department_id' => $department?->id,
                        'position' => $userData['position'] ?? '-',
                        'date_of_hire' => $userData['date_of_hire'] ?? now()->subYears(1)->toDateString(),
                        'is_active' => 1,
                        'site' => $userData['site'] ?? null,
                        'created_by' => 1,
                        'updated_by' => 1,
                    ]
                );
            }
        }

        $this->command->info("✅ UserSeeder selesai: seluruh role, user, worker, dan department berhasil dibuat.");
    }
}
