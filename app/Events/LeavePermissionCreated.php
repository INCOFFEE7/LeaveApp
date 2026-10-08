<?php

namespace App\Events;

use App\Models\LeavePermission;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeavePermissionCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public LeavePermission $leave) {}
}
