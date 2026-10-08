<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Definisi permission granular
        $permissions = [
        // Dashboard
        'dashboard.view.employee',
        'dashboard.view.approver',
        'dashboard.view.admin',

        // Leave (Cuti)
        'leave.create',
        'leave.view.own',
        'leave.view.department',
        'leave.view.all',
        'leave.edit.own',
        'leave.edit.department',
        'leave.delete.own',
        'leave.delete.department',
        'leave.delete.all',
        'leave.approve',
        'leave.export.self',
        'leave.export.department',
        'leave.export.all',
        'leave.type.manage',
        'leave.balance.manage',
        'leave.approval.manage',
        'leave.balance.reset.self',
        'leave.balance.reset.all',

        // User Management
        'user.view',
        'user.create',
        'user.edit',
        'user.delete',
        'user.import',
        'user.reset-password',
        'user.assign-role',

        // Holaday Manage
        'holiday.sync',
        'holiday.extra.manage',
        'holiday.national.manage',

        // Role & Permission
        'role.manage',
        'permission.manage',

        // Department
        'department.view',
        'department.manage',
        'department.approver.manage',

        // System / Admin
        'system.setting.manage',
        'log.view',
    ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        // 2. Mapping role ke permission
        $roles = [
            'employee' => [
                'dashboard.view.employee',
                'leave.create',
                'leave.view.own',
                'leave.edit.own',
                'leave.export.self',
            ],

            'senior_staff' => [
                'dashboard.view.employee',
                'dashboard.view.approver',
                'leave.create',
                'leave.view.own',
                'leave.edit.own',
                'leave.approve',
                'leave.export.self',
            ],

            'leader' => [
                'dashboard.view.employee',
                'dashboard.view.approver',
                'leave.create',
                'leave.view.own',
                'leave.view.department',
                'leave.edit.own',
                'leave.approve',
                'leave.export.self',
            ],

            'department_head' => [
                'dashboard.view.employee',
                'dashboard.view.approver',
                'leave.create',
                'leave.view.own',
                'leave.view.department',
                'leave.approve',
                'leave.export.self',
                'leave.export.department',
            ],

            'manager' => [
                'dashboard.view.employee',
                'dashboard.view.approver',
                'leave.view.department',
                'leave.approve',
            ],

            'director' => [
                'dashboard.view.approver',
                'leave.view.all',
                'leave.approve',
            ],

            'hrd' => [
                'dashboard.view.admin',
                'leave.view.all',
                'leave.approve',
                'user.view',
                'leave.export.self',
            ],

            'admin' => [
                // Dashboard
                'dashboard.view.admin',

                // Leave (Cuti)
                'leave.create',
                'leave.view.own',
                'leave.view.department',
                'leave.view.all',
                'leave.edit.own',
                'leave.edit.department',
                'leave.delete.own',
                'leave.delete.department',
                'leave.delete.all',
                'leave.approve',
                'leave.export.all',
                'leave.type.manage',
                'leave.balance.manage',
                'leave.approval.manage',

                // User Management
                'user.view',
                'user.create',
                'user.edit',
                'user.delete',
                'user.import',
                'user.reset-password',
                'user.assign-role',

                // Role & Permission
                'role.manage',

                // Department
                'department.view',
                'department.manage',
                'department.approver.manage',

                'holiday.sync',
                'holiday.extra.manage',
                'holiday.national.manage',
                // System / Admin
                'system.setting.manage',
            ],

            'dev' => [
                'dashboard.view.employee',
                'dashboard.view.approver',
                'dashboard.view.admin',

                // Leave (Cuti)
                'leave.create',
                'leave.view.own',
                'leave.view.department',
                'leave.view.all',
                'leave.edit.own',
                'leave.edit.department',
                'leave.delete.own',
                'leave.delete.department',
                'leave.delete.all',
                'leave.approve',
                'leave.export.all',
                'leave.type.manage',
                'leave.balance.manage',
                'leave.approval.manage',
                'leave.balance.reset.self',
                'leave.balance.reset.all',

                // User Management
                'user.view',
                'user.create',
                'user.edit',
                'user.delete',
                'user.import',
                'user.reset-password',
                'user.assign-role',

                // Role & Permission
                'role.manage',
                'permission.manage',

                // Department
                'department.view',
                'department.manage',
                'department.approver.manage',

                // System / Admin
                'system.setting.manage',
                'log.view',
            ],
        ];

        foreach ($roles as $roleName => $permissionList) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($permissionList);
        }
    }
}
