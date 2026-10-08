<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Events\LeaveApprovalUpdated;
use App\Events\LeaveApprovalProgress;
use App\Events\LeaveApprovalFinal;
use Illuminate\Support\Facades\Log;
use App\Models\DepartmentApprover;

class LeaveApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'leave_permission_id',
        'approved_by',
        'level',
        'status',
        'approved_at',
        'note',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    public function leavePermission()
    {
        return $this->belongsTo(LeavePermission::class, 'leave_permission_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function approvals()
    {
        return $this->hasMany(self::class, 'leave_permission_id');
    }

    public function currentApproval()
    {
        return $this->approvals()
            ->whereNull('approved_at')
            ->orderBy('level')
            ->first();
    }

    public function lastApproval()
    {
        return $this->approvals()
            ->whereNotNull('approved_at')
            ->orderByDesc('approved_at')
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | BOOT EVENT OBSERVER
    |--------------------------------------------------------------------------
    */
    protected static function booted()
    {
        static::created(fn($approval) => self::triggerApprovalEvents($approval));
        static::updated(fn($approval) => self::triggerApprovalEvents($approval));
    }

    /*
    |--------------------------------------------------------------------------
    | CUSTOM EVENT TRIGGER LOGIC
    |--------------------------------------------------------------------------
    */
    protected static function triggerApprovalEvents(self $approval): void
    {
        // Abaikan jika dijalankan dari artisan seeder / migrate
        if (app()->runningInConsole()) {
            return;
        }

        $approval->loadMissing(['leavePermission.worker.department', 'approver']);
        $leave = $approval->leavePermission;
        $approver = $approval->approver;
        $status = $approval->status;

        if (!$leave) {
            Log::warning("LeaveApproval {$approval->id} tidak punya relasi leave_permission_id");
            return;
        }

        // Jika REJECTED → langsung kirim event Updated (ke worker & approver)
        if ($status === 'rejected') {
            Log::info("Leave approval rejected", [
                'approval_id' => $approval->id,
                'leave_id'    => $leave->id,
                'approver_id' => $approver?->id,
                'status'      => $status,
            ]);

            event(new LeaveApprovalUpdated($leave, $approver, 'rejected'));
            return;
        }

        // Jika APPROVED → cek apakah masih ada level berikutnya
        if ($status === 'approved') {
            $department = $leave->worker?->department;

            // Ambil semua level approver dari tabel department_approvers
            $levels = $department
                ? \App\Models\DepartmentApprover::where('department_id', $department->id)
                    ->pluck('level')
                    ->toArray()
                : [];

            $current = (int) $approval->level;
            $last = !empty($levels) ? max($levels) : $current;

            Log::info("LeaveApproval Flow Check", [
                'approval_id' => $approval->id,
                'current' => $current,
                'last' => $last,
                'levels' => $levels,
            ]);

            if ($current < $last) {
                // Masih ada level berikutnya
                Log::info("Triggering LeaveApprovalProgress", [
                    'approval_id' => $approval->id,
                    'next_level' => $current + 1,
                ]);

                // Update status cuti menjadi "in_progress" dan next level
                $leave->update([
                    'status' => 'in_progress',
                    'current_level' => $current + 1,
                ]);

                event(new \App\Events\LeaveApprovalProgress($leave, $current + 1));
            } else {
                // Sudah disetujui semua → final approved
                Log::info("Triggering LeaveApprovalFinal", [
                    'approval_id' => $approval->id,
                    'current' => $current,
                    'last' => $last,
                ]);

                $leave->update(['status' => 'approved']);
                event(new \App\Events\LeaveApprovalFinal($leave, $approver, 'approved'));
            }
        }
    }
}
