<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\DepartmentApprover;
use App\Models\LeavePermission;
use App\Models\LeaveType;
use App\Models\User;
use App\Models\Worker;
use App\Services\ApprovalFlowResolver;
use App\Services\LeaveWorkflowService;
use Database\Seeders\ApprovalFlowMatrixSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApprovalFlowMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['worker', 'manager'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }

    public function test_hrga_hse_worker_starts_directly_at_am_manager_level_2(): void
    {
        $this->seed(ApprovalFlowMatrixSeeder::class);

        $department = Department::where('department_name', 'HRGA')->firstOrFail();
        $unit = $department->organizationUnits()->where('name', 'HSE')->firstOrFail();

        $workerUser = User::factory()->create();
        $worker = Worker::factory()->create([
            'user_id' => $workerUser->id,
            'department_id' => $department->id,
            'organization_unit_id' => $unit->id,
            'position' => 'Staff',
        ]);

        $am = User::factory()->create();
        $am->assignRole('manager');
        Worker::factory()->create([
            'user_id' => $am->id,
            'department_id' => $department->id,
            'position' => 'Assistant Manager',
        ]);
        DepartmentApprover::create([
            'department_id' => $department->id,
            'level' => 2,
            'approver_id' => $am->id,
        ]);

        $rule = app(ApprovalFlowResolver::class)->resolveForWorker($worker);
        $snapshot = app(ApprovalFlowResolver::class)->buildSnapshot($rule, $worker);
        $first = app(ApprovalFlowResolver::class)->firstActiveStep($snapshot);

        $this->assertSame(2, (int) $first['level']);
        $this->assertSame($am->id, (int) $first['approver_id']);
    }

    public function test_final_approval_creates_auto_hrd_acknowledgement(): void
    {
        $this->seed(ApprovalFlowMatrixSeeder::class);

        $department = Department::where('department_name', 'Procurement & Purchasing')->firstOrFail();
        $workerUser = User::factory()->create();
        $worker = Worker::factory()->create([
            'user_id' => $workerUser->id,
            'department_id' => $department->id,
            'position' => 'Assistant Manager',
        ]);

        $manager = User::factory()->create();
        $manager->assignRole('manager');
        Worker::factory()->create([
            'user_id' => $manager->id,
            'department_id' => $department->id,
            'position' => 'Manager',
        ]);
        DepartmentApprover::create([
            'department_id' => $department->id,
            'level' => 1,
            'approver_id' => $manager->id,
        ]);

        $leaveType = LeaveType::factory()->create(['leave_type_name' => 'Cuti Tahunan']);
        $rule = app(ApprovalFlowResolver::class)->resolveForWorker($worker);
        $snapshot = app(ApprovalFlowResolver::class)->buildSnapshot($rule, $worker);
        $first = app(ApprovalFlowResolver::class)->firstActiveStep($snapshot);

        $leave = LeavePermission::create([
            'worker_id' => $worker->id,
            'organization_unit_id' => $worker->organization_unit_id,
            'leave_type_id' => $leaveType->id,
            'approval_flow_rule_id' => $rule->id,
            'approval_snapshot' => $snapshot,
            'leave_date_from' => '2026-07-10',
            'leave_date_to' => '2026-07-10',
            'current_level' => $first['level'],
            'status' => 'pending',
            'created_by' => $workerUser->id,
        ]);

        app(LeaveWorkflowService::class)->approve($leave, $manager);

        $this->assertDatabaseHas('leave_acknowledgements', [
            'leave_permission_id' => $leave->id,
            'acknowledgement_type' => 'auto_hrd',
            'acknowledged_role' => 'HRD',
        ]);
        $this->assertSame('approved', $leave->fresh()->status);
    }
}
