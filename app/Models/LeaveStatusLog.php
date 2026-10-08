<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveStatusLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'leave_permission_id',
        'changed_by',
        'action',
        'new_status',
        'note',
        'report_path',
    ];

    public function leavePermission()
    {
        return $this->belongsTo(LeavePermission::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function worker()
    {
        return $this->leavePermission->worker();
    }
}
