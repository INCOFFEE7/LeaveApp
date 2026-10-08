<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;


class LeavePermission extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
    'worker_id',
    'leave_type_id',
    'leave_date_from',
    'total_leave',
    'leave_date_to',
    'return_to_work_date',
    'leave_reason',
    'supporting_document',
    'status',
    'current_level',
    'created_by',
    'updated_by',
    'is_manual',
    'onsite_total_days',
    'earned_leave',
    'adjusted_earned_leave',
    'adjustment_note',
    'leave_date_from',
    'leave_date_to',
    'total_leave',
    'return_to_work_date',
    'onsite_date_from',
    'onsite_date_to',
    ];

    protected $casts = [
        'leave_date_from' => 'date',
        'leave_date_to'   => 'date',
        'return_to_work_date' => 'date',
        'onsite_date_from' => 'date',
        'onsite_date_to'   => 'date',
        'onsite_total_days' => 'integer',
        'supporting_document' => 'string',
        'is_manual' => 'boolean',

    ];

    protected $attributes = [
    'is_manual' => false,
];
    public function worker()
    {
        return $this->belongsTo(Worker::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function approvals()
    {
        return $this->hasMany(LeaveApproval::class);
    }

    public function lastApprovedBy()
    {
        return $this->hasOne(LeaveApproval::class)
            ->where('status', 'approved')
            ->latest('updated_at');
    }

    public function currentApprover()
    {
        return $this->approvals()->where('level', $this->current_level)->first();
    }

    public function logs()
    {
        return $this->hasMany(LeaveStatusLog::class);
    }

    public function statusLogs()
{
    return $this->hasMany(LeaveStatusLog::class, 'leave_permission_id');
}

    public function latestReport()
{
    return $this->hasOne(LeaveStatusLog::class, 'leave_permission_id')
                ->latestOfMany('created_at')
                ->whereNotNull('report_path');
}

    public function getTotalDaysAttribute(): int
    {
        $from = $this->leave_date_from ?? now();
        $to   = $this->leave_date_to ?? $from;
        return Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1;
    }

    protected static function booted(): void
    {
        static::saving(function ($leave) {
            if ($leave->leave_date_from && $leave->leave_date_to) {
                $start = Carbon::parse($leave->leave_date_from);
                $end   = Carbon::parse($leave->leave_date_to);

                $days = 0;
                for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                    if (!in_array($date->dayOfWeek, [6, 0])) {
                        $days++;
                    }
                }
                $leave->total_leave = $days;

                $return = $end->copy()->addDay();
                while (in_array($return->dayOfWeek, [6, 0])) {
                    $return->addDay();
                }
                $leave->return_to_work_date = $return->format('Y-m-d');
            } else {
                $leave->total_leave = 0;
                $leave->return_to_work_date = null;
            }
        });
    }

}
