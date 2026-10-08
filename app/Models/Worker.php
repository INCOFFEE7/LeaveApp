<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;
/**
 * @property int $department_id
 */
class Worker extends Model
{
    use SoftDeletes, Notifiable;

    protected $fillable = [
        'user_id',
        'company',
        'worker_name',
        'employment_status',
        'department_id',
        'position',
        'cabang',
        'site',
        'date_of_hire',
        'is_active',
        'created_by',
        'updated_by',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function leavePermissions()
    {
        return $this->hasMany(LeavePermission::class);
    }

    public function leaveBalances()
    {
        return $this->hasMany(LeaveBalance::class, 'worker_id');
    }
}

