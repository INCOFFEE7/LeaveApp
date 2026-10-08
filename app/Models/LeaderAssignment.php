<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaderAssignment extends Model
{
    protected $fillable = [
        'leader_id',
        'worker_id',
        'department_id',
        'level',
        'effective_date',
        'ended_at',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'ended_at' => 'date',
    ];

    // ✅ Relasi ke user yang jadi leader
    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    // ✅ Relasi ke worker (mengarah ke tabel workers)
    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class, 'worker_id');
    }

    // ✅ Relasi ke departemen
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    // ✅ Scope aktif
    public function scopeActive($query)
    {
        return $query->whereNull('ended_at')
                     ->where('effective_date', '<=', now());
    }
}
