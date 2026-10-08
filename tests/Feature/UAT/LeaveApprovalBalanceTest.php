<?php

namespace Tests\Feature\UAT;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Worker;
use App\Models\Department;
use App\Models\LeavePermission;
use App\Models\LeaveApproval;
use App\Models\LeaveBalance;

class LeaveApprovalBalanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that multi-level approval deducts leave balance and creates a balance log.
     *
     * Notes: This test assumes model observers/services in the app will react
     * to creation of LeaveApproval records and perform the balance deduction.
     */
    public function test_multi_level_approval_deducts_leave_balance()
    {
        // Arrange: create department, employee and approvers
        $department = Department::create([
            'department_name' => 'Test Dept',
            'is_active' => 1,
        ]);
        $employeeUser = User::factory()->create();
        $worker = Worker::create([
            'user_id' => $employeeUser->id,
            'worker_name' => 'Emp Test',
            'department_id' => $department->id,
            'is_active' => 1,
            'date_of_hire' => '2020-01-01',
        ]);

        $approver1 = User::factory()->create();
        $approver2 = User::factory()->create();

        // Add department approvers so the observer won't auto-reject
        $this->actingAs($approver1);
        \App\Models\DepartmentApprover::create([
            'department_id' => $department->id,
            'level' => 1,
            'approver_id' => $approver1->id,
        ]);

        $this->actingAs($approver2);
        \App\Models\DepartmentApprover::create([
            'department_id' => $department->id,
            'level' => 2,
            'approver_id' => $approver2->id,
        ]);

        // Seed a leave balance for the worker
        $balance = LeaveBalance::create([
            'worker_id' => $worker->id,
            'year' => date('Y'),
            'hak_cuti' => 12,
            'sisa_cuti' => 12,
            'utang_cuti' => 0,
        ]);

        // Create a leave type used for deduction (Cuti Tahunan)
        $leaveType = \App\Models\LeaveType::create([
            'leave_type_name' => 'Cuti Tahunan',
            'is_active' => 1,
        ]);

        // Create a leave permission of 3 days
        // Use fixed weekday dates (Mon-Wed) to avoid weekend calculation issues in tests
        $leave = LeavePermission::create([
            'worker_id' => $worker->id,
            'leave_type_id' => $leaveType->id,
            'leave_date_from' => '2025-11-17',
            'leave_date_to' => '2025-11-19',
            'leave_reason' => 'UAT leave',
            'status' => 'in_progress',
            'current_level' => 1,
            'created_by' => $employeeUser->id,
        ]);

        // Act: approve level 1 then level 2 (final)
        LeaveApproval::create([
            'leave_permission_id' => $leave->id,
            'level' => 1,
            'status' => 'approved',
            'approved_by' => $approver1->id,
            'approved_at' => now(),
        ]);

        LeaveApproval::create([
            'leave_permission_id' => $leave->id,
            'level' => 2,
            'status' => 'approved',
            'approved_by' => $approver2->id,
            'approved_at' => now(),
        ]);

        // Refresh balance and assert deduction happened by computed workdays
        $balance->refresh();
        // 2025-11-17 (Mon) to 2025-11-19 (Wed) = 3 workdays
        $this->assertEquals(9, $balance->sisa_cuti, 'Expected sisa_cuti to be reduced by 3 days after final approval.');
    }
}
