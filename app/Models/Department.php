<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $fillable = [
        'department_name',
        'is_active',
        'created_by',
        'updated_by',
    ];


    public function workers(): HasMany
    {
        return $this->hasMany(Worker::class);
    }

    public function approvers(): HasMany
    {
        return $this->hasMany(DepartmentApprover::class);
    }


}

