<?php

namespace App\Filament\Widgets\Employee;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use App\Models\LeavePermission as Leave;
use App\Models\NationalHoliday;
use App\Models\ExtraHoliday;
use App\Models\DepartmentApprover;
use Carbon\Carbon;

class EmployeeLeaveCalendarWidget extends Widget
{
    protected static string $view = 'filament.worker.widgets.leave-calendar-widget';

    public array $leaves = [];
    public array $holidays = [];
    public $selectedLeave = null;

    public function mount(): void
    {
        $this->loadCalendarEvents();
    }

    public function loadCalendarEvents(): void
    {
        $this->leaves = $this->loadLeaveEvents();
        $this->holidays = $this->loadHolidayEvents();
    }

    /**
     * ============================
     * LOAD CUTI KARYAWAN
     * ============================
     */
    protected function loadLeaveEvents(): array
    {
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            $leaves = Leave::with(['worker.user', 'leaveType'])->get();
        }
        elseif ($user->hasRole('manager')) {
            $departmentIds = DepartmentApprover::where('approver_id', $user->id)
                ->pluck('department_id')
                ->toArray();

            $leaves = Leave::with(['worker.user', 'leaveType'])
                ->whereHas('worker', fn ($q) => $q->whereIn('department_id', $departmentIds))
                ->get();
        }
        else {
            // If the user is an approver (has DepartmentApprover entries),
            // show leaves for workers that:
            //  - belong to the approver's departments, and
            //  - have an active leader_assignment relation to this approver.
            // This ensures approvers only see their direct subordinates at the same leader relation.
            $isApprover = DepartmentApprover::where('approver_id', $user->id)->exists();

            if ($isApprover) {
                $departmentIds = DepartmentApprover::where('approver_id', $user->id)
                    ->pluck('department_id')
                    ->toArray();

                $approverWorkerId = $user->worker?->id;

                $leaves = Leave::with(['worker.user', 'leaveType'])
                    ->whereHas('worker', function ($q) use ($departmentIds, $user, $approverWorkerId) {
                        $q->where(function ($q2) use ($departmentIds, $user, $approverWorkerId) {
                            // Workers in approver's departments AND assigned to this approver
                            $q2->whereIn('department_id', $departmentIds)
                               ->whereHas('user.leaderAssignments', function ($r) use ($user) {
                                   $r->where('leader_id', $user->id)
                                     ->whereNull('ended_at');
                               });

                            // Also include the approver's own worker record so approver sees their own leave
                            if ($approverWorkerId) {
                                $q2->orWhere('id', $approverWorkerId);
                            }
                        });
                    })
                    ->get();
            }
            else {
                if (!$user->worker?->id) {
                    return [];
                }

                $leaves = Leave::with(['worker.user', 'leaveType'])
                    ->where('worker_id', $user->worker->id)
                    ->get();
            }
        }

        return $leaves->map(function ($leave) {
            return [
                'id' => 'leave-' . $leave->id,
                'title' => $leave->worker->user->name . ' (' . $leave->leaveType->name . ')',
                'start' => $leave->leave_date_from,
                'end' => Carbon::parse($leave->leave_date_to ?? $leave->leave_date_from)
                    ->addDay()
                    ->toDateString(),
                'color' => match ($leave->status) {
                    'approved' => '#16a34a',
                    'pending' => '#f59e0b',
                    'rejected' => '#dc2626',
                    default => '#6b7280',
                },
                'type' => 'leave',
            ];
        })->toArray();
    }

    /**
     * ============================
     * LOAD LIBUR NASIONAL & TAMBAHAN
     * ============================
     */
    protected function loadHolidayEvents(): array
    {
        $events = [];

        // LIBUR NASIONAL (merah)
        foreach (NationalHoliday::all() as $holiday) {
            $events[] = [
                'id' => 'nh-' . $holiday->id,
                'title' => $holiday->name,
                'start' => $holiday->date,
                'end' => Carbon::parse($holiday->date)->addDay()->toDateString(),
                'color' => '#ef4444',
                'type' => 'national',
            ];
        }

        // LIBUR TAMBAHAN / INSIDENTAL (biru)
        foreach (ExtraHoliday::all() as $holiday) {
            $events[] = [
                'id' => 'eh-' . $holiday->id,
                'title' => $holiday->name,
                'start' => $holiday->date,
                'end' => Carbon::parse($holiday->date)->addDay()->toDateString(),
                'color' => '#e34f4fff',
                'type' => 'extra',
            ];
        }

        return $events;
    }
}
