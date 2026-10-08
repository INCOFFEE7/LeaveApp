<?php

namespace App\Helpers;

use App\Models\DepartmentApprover;
use App\Models\Worker;

class ApprovalHelper
{
    public static function getFirstApprovalLevel(int $workerId): int
    {
        $worker = Worker::find($workerId);

        if (! $worker || ! $worker->department_id) {
            return 1;
        }

        return DepartmentApprover::where('department_id', $worker->department_id)
            ->orderBy('level')
            ->value('level') ?? 1;
    }
}
