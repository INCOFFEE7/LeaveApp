<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveBalanceLog extends Model
{
    protected $fillable = [
        'leave_balance_id',
        'user_id',
        'old_hak_cuti',
        'new_hak_cuti',
        'old_sisa_cuti',
        'new_sisa_cuti',
        'old_utang_cuti',
        'new_utang_cuti',
        'note',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function leaveBalance()
    {
        return $this->belongsTo(LeaveBalance::class);
    }
}
