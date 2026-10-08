<?php

namespace App\Events;

use App\Models\LeavePermission;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeaveApprovalUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public LeavePermission $leave,
        public ?User $approver = null,
        public string $status = 'rejected'
    ) {}
}
