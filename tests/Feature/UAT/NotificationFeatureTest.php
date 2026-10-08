<?php

namespace Tests\Feature\UAT;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use App\Models\User;
use App\Models\Worker;
use App\Models\Department;
use App\Models\LeavePermission;
use App\Notifications\LeaveNotification;

class NotificationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_sent_on_leave_creation()
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

        $approver = User::factory()->create();

        // Add a department approver so observer will set initial level
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

        // Create leave permission which should trigger notification(s)
        $leave = LeavePermission::create([
            'worker_id' => $worker->id,
            'leave_type_id' => $leaveType->id,
            'leave_date_from' => '2025-11-18',
            'leave_date_to' => '2025-11-18',
            'leave_reason' => 'UAT create',
            'created_by' => $employeeUser->id,
        ]);
        // Assert that a database notification row exists for the approver (Filament sends DB notifications)
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $approver->id,
        ]);
    }
}
