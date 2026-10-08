<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LeaveBalanceLogExport implements FromQuery, WithMapping, WithHeadings
{
    protected Builder $query;

    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    public function query()
    {
        return $this->query->with([
            'leaveBalance.worker.department', // ✅ Load department relation
            'leaveBalance.leavePermissions.leaveType',
            'leaveBalance.leavePermissions.worker',
            'user'
        ]);
    }

    public function map($log): array
    {
        $lb = $log->leaveBalance;
        $worker = $lb?->worker;
        $leave = $lb?->leavePermission;

        return [
            $log->id,
            $lb->id ?? null,
            $worker?->worker_name,
            $worker?->department?->department_name ?? 'N/A', // ✅ Show department name
            $log->user?->name ?? 'Sistem',
            $log->old_hak_cuti,
            $log->new_hak_cuti,
            $log->old_sisa_cuti,
            $log->new_sisa_cuti,
            $log->old_utang_cuti,
            $log->new_utang_cuti,
            $log->note,
            $log->created_at?->format('Y-m-d H:i:s'),
            $leave?->id,
            $leave?->worker_id,
            $leave?->leave_type_id,
            $leave?->leave_date_from?->format('Y-m-d'),
            $leave?->leave_date_to?->format('Y-m-d'),
            $leave?->return_to_work_date?->format('Y-m-d'),
            $leave?->total_leave,
            $leave?->leave_reason,
            $leave?->status,
        ];
    }

    public function headings(): array
    {
        return [
            'Log ID',
            'Leave Balance ID',
            'Worker Name',
            'Department Name', // ✅ Changed from Department ID
            'Changed By',
            'Old Hak Cuti',
            'New Hak Cuti',
            'Old Sisa Cuti',
            'New Sisa Cuti',
            'Old Utang Cuti',
            'New Utang Cuti',
            'Note',
            'Logged At',
            'Leave Permission ID',
            'Leave Worker ID',
            'Leave Type ID',
            'Leave Date From',
            'Leave Date To',
            'Return To Work Date',
            'Total Leave',
            'Leave Reason',
            'Leave Status',
        ];
    }
}
