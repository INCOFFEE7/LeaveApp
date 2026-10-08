<?php

namespace Tests\Feature;

use App\Models\{LeavePermission, User, Worker, Department, DepartmentApprover, LeaveType};
use App\Events\{
    LeavePermissionCreated,
    LeaveApprovalProgress,
    LeaveApprovalFinal,
    LeaveApprovalRejected
};
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class NotificationFlowTest extends TestCase
{
    protected $employee;
    protected $approver1;
    protected $approver2;
    protected $approverFinal;
    protected $department;
    protected $worker;
    protected $leaveType;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Department
        $this->department = Department::factory()->create([
            'department_name' => 'IT Department'
        ]);

        // Create Leave Type
        $this->leaveType = LeaveType::factory()->create([
            'leave_type_name' => 'Cuti Tahunan'
        ]);

        // Create Users
        $this->employee = User::factory()->create(['name' => 'John Employee']);
        $this->approver1 = User::factory()->create(['name' => 'Manager Level 1']);
        $this->approver2 = User::factory()->create(['name' => 'Manager Level 2']);
        $this->approverFinal = User::factory()->create(['name' => 'Director']);

        // Create Worker
        $this->worker = Worker::factory()->create([
            'user_id' => $this->employee->id,
            'department_id' => $this->department->id,
        ]);

        // Create Department Approvers (3 levels)
        DepartmentApprover::create([
            'department_id' => $this->department->id,
            'approver_id' => $this->approver1->id,
            'level' => 1,
        ]);

        DepartmentApprover::create([
            'department_id' => $this->department->id,
            'approver_id' => $this->approver2->id,
            'level' => 2,
        ]);

        DepartmentApprover::create([
            'department_id' => $this->department->id,
            'approver_id' => $this->approverFinal->id,
            'level' => 3,
        ]);
    }

    /**
     * Test 1: CREATED Event - Pengajuan baru dibuat
     */
    public function test_created_event_triggers_notification(): void
    {
        Event::fake();

        $leave = LeavePermission::create([
            'worker_id' => $this->worker->id,
            'leave_type_id' => $this->leaveType->id,
            'leave_date_from' => now()->addDay(),
            'leave_date_to' => now()->addDays(3),
            'current_level' => 1,
            'status' => 'pending',
            'created_by' => $this->employee->id,
        ]);

        Event::assertDispatched(LeavePermissionCreated::class);

        // Check notifications to employee
        $notifications = $this->employee->notifications()
            ->where('type', 'App\\Notifications\\LeaveApprovalNotification')
            ->get();

        $this->assertGreaterThanOrEqual(1, $notifications->count());
    }

    /**
     * Test 2: APPROVAL PROGRESS - Approver 1 approves (not final)
     */
    public function test_approval_progress_event(): void
    {
        Event::fake();

        // Create leave
        $leave = LeavePermission::create([
            'worker_id' => $this->worker->id,
            'leave_type_id' => $this->leaveType->id,
            'leave_date_from' => now()->addDay(),
            'leave_date_to' => now()->addDays(3),
            'current_level' => 1,
            'status' => 'pending',
            'created_by' => $this->employee->id,
        ]);

        // Approver 1 approves
        $this->actingAs($this->approver1);

        // Simulate approveAction from LeaveApprovalResource
        $leave->updateQuietly([
            'current_level' => 2,
            'status' => 'in_progress',
        ]);

        Event::assertDispatched(LeaveApprovalProgress::class);
    }

    /**
     * Test 3: FINAL APPROVAL - Last approver approves
     */
    public function test_final_approval_event(): void
    {
        Event::fake();

        // Create leave at level 3 (final)
        $leave = LeavePermission::create([
            'worker_id' => $this->worker->id,
            'leave_type_id' => $this->leaveType->id,
            'leave_date_from' => now()->addDay(),
            'leave_date_to' => now()->addDays(3),
            'current_level' => 3,
            'status' => 'in_progress',
            'created_by' => $this->employee->id,
        ]);

        // Final approver approves
        $this->actingAs($this->approverFinal);

        // Simulate final approval
        $leave->updateQuietly([
            'current_level' => 3,
            'status' => 'approved',
        ]);

        Event::assertDispatched(LeaveApprovalFinal::class);
    }

    /**
     * Test 4: REJECTION - Approver rejects
     */
    public function test_rejection_event(): void
    {
        Event::fake();

        // Create leave
        $leave = LeavePermission::create([
            'worker_id' => $this->worker->id,
            'leave_type_id' => $this->leaveType->id,
            'leave_date_from' => now()->addDay(),
            'leave_date_to' => now()->addDays(3),
            'current_level' => 1,
            'status' => 'pending',
            'created_by' => $this->employee->id,
        ]);

        // Approver rejects
        $this->actingAs($this->approver1);

        $leave->updateQuietly(['status' => 'rejected']);

        Event::assertDispatched(LeaveApprovalRejected::class);
    }

    /**
     * Test 5: NO DUPLICATE NOTIFICATIONS
     */
    public function test_no_duplicate_notifications_on_final_approval(): void
    {
        // Create leave at final level
        $leave = LeavePermission::create([
            'worker_id' => $this->worker->id,
            'leave_type_id' => $this->leaveType->id,
            'leave_date_from' => now()->addDay(),
            'leave_date_to' => now()->addDays(3),
            'current_level' => 3,
            'status' => 'in_progress',
            'created_by' => $this->employee->id,
        ]);

        $this->actingAs($this->approverFinal);

        // Simulate approveAction
        $leave->updateQuietly([
            'current_level' => 3,
            'status' => 'approved',
        ]);

        // Dispatch event manually (simulating from approveAction)
        event(new LeaveApprovalFinal($leave, $this->approverFinal));

        // Check no duplicate in worker notifications
        $finalNotifications = $this->employee->notifications()
            ->where('data->status', 'final')
            ->get();

        $this->assertLessThanOrEqual(1, $finalNotifications->count());
    }

    /**
     * Test 6: APPROVER PROGRESS RECEIVER
     */
    public function test_approver_level_2_receives_notification_when_level_1_approves(): void
    {
        // Create leave at level 1
        $leave = LeavePermission::create([
            'worker_id' => $this->worker->id,
            'leave_type_id' => $this->leaveType->id,
            'leave_date_from' => now()->addDay(),
            'leave_date_to' => now()->addDays(3),
            'current_level' => 1,
            'status' => 'pending',
            'created_by' => $this->employee->id,
        ]);

        // Approver 1 approves - should notify Approver 2
        $leave->updateQuietly([
            'current_level' => 2,
            'status' => 'in_progress',
        ]);

        // Simulate event dispatch
        event(new LeaveApprovalProgress($leave, 2, $this->approver2));

        // Check Approver 2 has notification
        $notificationCount = $this->approver2->notifications()
            ->where('type', 'App\\Notifications\\LeaveApprovalNotification')
            ->count();

        $this->assertGreaterThan(0, $notificationCount);
    }

    /**
     * Test 7: ONLY FINAL APPROVER GETS FINAL NOTIFICATION
     */
    public function test_only_final_approver_gets_final_notification(): void
    {
        // Create leave at final level
        $leave = LeavePermission::create([
            'worker_id' => $this->worker->id,
            'leave_type_id' => $this->leaveType->id,
            'leave_date_from' => now()->addDay(),
            'leave_date_to' => now()->addDays(3),
            'current_level' => 3,
            'status' => 'in_progress',
            'created_by' => $this->employee->id,
        ]);

        $leave->updateQuietly([
            'status' => 'approved',
        ]);

        event(new LeaveApprovalFinal($leave, $this->approverFinal));

        // Approver 1 & 2 should NOT have final notification
        $approver1FinalNotif = $this->approver1->notifications()
            ->where('data->status', 'final')
            ->count();

        $approver2FinalNotif = $this->approver2->notifications()
            ->where('data->status', 'final')
            ->count();

        $this->assertEquals(0, $approver1FinalNotif);
        $this->assertEquals(0, $approver2FinalNotif);
    }
}
