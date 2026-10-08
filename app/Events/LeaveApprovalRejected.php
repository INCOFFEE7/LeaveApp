<?php

namespace App\Events;

use App\Models\LeavePermission;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeaveApprovalRejected
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public LeavePermission $leave,
        public ?User $approver = null // approver bisa null jika auto-rejected
    ) {}
}
