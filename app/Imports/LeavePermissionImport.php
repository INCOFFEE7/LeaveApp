<?php

namespace App\Imports;

use App\Models\LeavePermission;
use App\Models\LeaveBalance;
use App\Helpers\DateHelper;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;

class LeavePermissionImport implements ToCollection, WithHeadingRow
{
    /**
     * Collection of rows
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $workerId = $row['worker_id'] ?? null;
            $leaveTypeId = $row['leave_type_id'] ?? null;
            $fromDate = isset($row['leave_date_from']) ? Carbon::parse($row['leave_date_from']) : null;
            $toDate = isset($row['leave_date_to']) ? Carbon::parse($row['leave_date_to']) : null;
            $note = $row['note'] ?? 'Import historis';

            if (!$workerId || !$leaveTypeId || !$fromDate || !$toDate) {
                continue; // skip baris invalid
            }

            $leave = LeavePermission::create([
                'worker_id' => $workerId,
                'leave_type_id' => $leaveTypeId,
                'leave_date_from' => $fromDate,
                'leave_date_to' => $toDate,
                'status' => 'approved', // langsung approved untuk historis
                'note' => $note,
                'created_by' => $row['created_by'] ?? null,
                'updated_by' => $row['updated_by'] ?? null,
                'total_leave' => DateHelper::workdaysBetween($fromDate, $toDate),
                'return_to_work_date' => DateHelper::nextWorkday($toDate),
            ]);

            // Update saldo cuti otomatis jika perlu
            app(\App\Observers\LeaveApprovalObserver::class)->deductLeaveBalance($leave);
        }
    }
}
