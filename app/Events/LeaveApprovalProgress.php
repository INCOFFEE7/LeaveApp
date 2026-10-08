<?php

namespace App\Events;

use App\Models\LeavePermission;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class LeaveApprovalProgress
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public LeavePermission $leave,
        public int $nextLevel,
        public ?User $approver = null, // ✅ tambahkan ini
    ) {}
}
