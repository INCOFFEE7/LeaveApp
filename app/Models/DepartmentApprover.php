<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartmentApprover extends Model
{
    protected $fillable = [
        'department_id',
        'level',
        'approver_id',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            $model->created_by = auth()->user()->id; // atau auth()->user()->payroll_number
            $model->updated_by = auth()->user()->id;
        });

        static::updating(function ($model) {
            $model->updated_by = auth()->user()->id;
        });
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function approver(): BelongsTo
{
    return $this->belongsTo(User::class, 'approver_id', 'id');
}

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'approver_id');
    }
}

