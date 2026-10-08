<?php

namespace App\Events;

use App\Models\LeavePermission;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeavePermissionUpdated
{
    use Dispatchable, SerializesModels;

    public LeavePermission $leave;
    public array $changes;

    public function __construct(LeavePermission $leave, array $changes = [])
    {
        $this->leave = $leave;
        $this->changes = $changes;
    }
}
