<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'worker_id',
        'year',
        'hak_cuti',
        'sisa_cuti',
        'utang_cuti',
        'reset_at',
    ];

    // Properti sementara untuk log
    public ?string $customNote = null;
    public ?int $customUserId = null;

    public function worker()
    {
        return $this->belongsTo(Worker::class, 'worker_id', 'id');
    }

    public function logs()
    {
        return $this->hasMany(\App\Models\LeaveBalanceLog::class);
    }

    public function leavePermissions()
    {
        return $this->worker?->leavePermissions();
    }

    protected static function booted()
    {
        static::updating(function (LeaveBalance $model) {
            // Cek apakah ada perubahan cuti
            if (!$model->isDirty(['hak_cuti', 'sisa_cuti', 'utang_cuti'])) {
                return;
            }

            // Deteksi apakah update berasal dari form (manual) atau otomatis
            $isFromForm = request()->has('note');

            // Hindari log dobel (cek log terbaru dalam 2 detik terakhir)
            $latest = LeaveBalanceLog::where('leave_balance_id', $model->id)
                ->where('created_at', '>=', now()->subSeconds(2))
                ->latest()
                ->first();

            if ($latest) {
                return;
            }

            $changes = [];

            // Hak Cuti
            if ($model->isDirty('hak_cuti')) {
                $changes['old_hak_cuti'] = $model->getOriginal('hak_cuti') ?? 0;
                $changes['new_hak_cuti'] = $model->hak_cuti ?? 0;
            }

            // Sisa Cuti
            if ($model->isDirty('sisa_cuti')) {
                $changes['old_sisa_cuti'] = $model->getOriginal('sisa_cuti') ?? 0;
                $changes['new_sisa_cuti'] = $model->sisa_cuti ?? 0;
            }

            // Utang Cuti
            if ($model->isDirty('utang_cuti')) {
                $changes['old_utang_cuti'] = $model->getOriginal('utang_cuti') ?? 0;
                $changes['new_utang_cuti'] = $model->utang_cuti ?? 0;
            }
        });
    }
}
