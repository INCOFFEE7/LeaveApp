<?php

namespace Tests\Feature\UAT;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Worker;
use App\Models\Department;
use App\Models\LeavePermission;
use App\Models\LeaveStatusLog;

class AuditTrailFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_leave_status_log_created_on_status_changes()
    {
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

        // Add DepartmentApprover to allow normal flow
        $approver = User::factory()->create();
        $this->actingAs($approver);
        \App\Models\DepartmentApprover::create([
            'department_id' => $department->id,
            'level' => 1,
            'approver_id' => $approver->id,
        ]);

        // Create leave type
        $leaveType = \App\Models\LeaveType::create([
            'leave_type_name' => 'Cuti Tahunan',
            'is_active' => 1,
        ]);

        $leave = LeavePermission::create([
            'worker_id' => $worker->id,
            'leave_type_id' => $leaveType->id,
            'leave_date_from' => now()->addDays(3)->toDateString(),
            'leave_date_to' => now()->addDays(4)->toDateString(),
            'leave_reason' => 'UAT audit',
            'created_by' => $employeeUser->id,
        ]);

        // Simulate approval flow: create a single approver and approve (final)
        $this->actingAs($approver);
        \App\Models\LeaveApproval::create([
            'leave_permission_id' => $leave->id,
            'level' => 1,
            'status' => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        $this->assertDatabaseHas('leave_status_logs', [
            'leave_permission_id' => $leave->id,
            'new_status' => 'approved',
        ]);
    }
}
