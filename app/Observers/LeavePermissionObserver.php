<?php

namespace App\Observers;

use App\Models\LeavePermission;
use App\Helpers\LeaveHelper;
use App\Services\EnsureLeaveBalanceForYear;
use App\Models\LeaveBalanceLog;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

// Event
use App\Events\{
    LeavePermissionCreated,
    LeavePermissionInProgress,
    LeaveApprovalProgress,
    LeaveApprovalRejected,
    LeavePermissionUpdated
};

class LeavePermissionObserver
{
    /**
     * 🔹 Saat pengajuan cuti baru dibuat (SEBELUM disimpan)
     */
    public function creating(LeavePermission $leave)
    {
        // ✅ Set leave_year
        if (empty($leave->leave_year)) {
            $leave->leave_year = Carbon::parse($leave->leave_date_from)->year;
        }

        // ⛔ Manual → skip approval flow
        if ($leave->is_manual === true) {
            Log::info("⏭️ Manual leave detected, skip approval flow (creating)");
            return;
        }

        // ===========================
        // LOGIC APPROVAL
        // ===========================
        $initialLevel = LeaveHelper::getInitialApprovalLevel($leave);

        if ($initialLevel === null) {
            $leave->status = 'rejected';
            $leave->current_level = 0;
            $leave->remarks = 'Pengajuan tidak dapat diproses karena belum ada approver di departemen ini.';

            Log::warning("❌ Leave auto-rejected (no approver)");
            return;
        }

        $leave->current_level = $initialLevel;
        $leave->status = 'pending';
    }

    /**
     * 🔹 Setelah pengajuan BERHASIL dibuat
     */
    public function created(LeavePermission $leave)
    {
        // ✅ Manual → langsung potong saldo
        if ($leave->is_manual === true) {
            $this->processLeaveBalance($leave);
            return;
        }

        // ===========================
        // Event normal
        // ===========================
        event(new LeavePermissionCreated($leave));
    }

    /**
     * 🔹 Setelah pengajuan di-update
     */
    public function updated(LeavePermission $leave)
    {
        // ✅ NON-MANUAL → potong saldo hanya saat approved
        if ($leave->is_manual !== true &&
            $leave->wasChanged('status') &&
            $leave->status === 'approved') {
            $this->processLeaveBalance($leave);
        }

        // Manual → skip event update
        if ($leave->is_manual === true) {
            return;
        }

        // ===========================
        // Event normal
        // ===========================
        event(new LeavePermissionUpdated($leave, $leave->getChanges()));

        if ($leave->wasChanged('status')) {
            if ($leave->status === 'in_progress') {
                event(new LeavePermissionInProgress($leave));

            } elseif ($leave->status === 'approved') {

                $nextApproverId = LeaveHelper::getNextApprover($leave);

                if ($nextApproverId) {
                    $approver = \App\Models\User::find($nextApproverId);
                    $nextLevel = $approver?->approverLevel($leave->worker->department_id) ?? 0;
                    event(new LeaveApprovalProgress($leave, $nextLevel, $approver));
                }

            } elseif ($leave->status === 'rejected') {
                event(new LeaveApprovalRejected($leave, null));
            }
        }
    }

    /**
     * ======================================================
     * 🔧 CORE LOGIC — POTONG SALDO CUTI (BENAR LINTAS TAHUN)
     * ======================================================
     */
    public function processLeaveBalance(LeavePermission $leave): void
    {
        $workerId   = $leave->worker_id;
        $startYear  = Carbon::parse($leave->leave_date_from)->year;
        $totalDays  = $leave->total_leave;
        $actorId    = $leave->created_by;
        $remaining  = $totalDays;

        for ($year = $startYear; $remaining > 0; $year++) {

            // Ambil atau buat saldo tahun ini (handle utang sebelumnya otomatis)
            $balance = EnsureLeaveBalanceForYear::handle(
                workerId: $workerId,
                year: $year,
                actorUserId: $actorId,
                note: "Ensure saldo otomatis karena cuti " . ($leave->is_manual ? 'manual' : 'approved')
            );

            $oldSisa  = $balance->sisa_cuti;
            $oldUtang = $balance->utang_cuti;

            // ===========================
            // CASE 1 — SALDO MASIH CUKUP
            // ===========================
            if ($oldSisa >= $remaining) {

                $balance->update(['sisa_cuti' => $oldSisa - $remaining]);

                LeaveBalanceLog::create([
                    'leave_balance_id' => $balance->id,
                    'user_id'          => $actorId,
                    'old_sisa_cuti'    => $oldSisa,
                    'new_sisa_cuti'    => $balance->sisa_cuti,
                    'old_utang_cuti'   => $oldUtang,
                    'new_utang_cuti'   => $oldUtang,
                    'note'             => "Pemotongan {$remaining} hari cuti (" . ($leave->is_manual ? 'manual' : 'approved') . ") tahun {$year}",
                ]);

                break;
            }

            // ===========================
            // CASE 2 — SALDO HABIS → jadi utang tahun ini
            // ===========================
            $balance->update([
                'sisa_cuti'  => 0,
                'utang_cuti' => ($remaining - $oldSisa),
            ]);

            LeaveBalanceLog::create([
                'leave_balance_id' => $balance->id,
                'user_id'          => $actorId,
                'old_sisa_cuti'    => $oldSisa,
                'new_sisa_cuti'    => 0,
                'old_utang_cuti'   => $oldUtang,
                'new_utang_cuti'   => $balance->utang_cuti,
                'note'             => "Saldo tahun {$year} habis, sisa dipindahkan menjadi utang",
            ]);

            $remaining -= $oldSisa;
        }

        Log::info('📘 Leave balance processed per year', [
            'leave_id'   => $leave->id,
            'worker_id'  => $workerId,
            'total_days' => $totalDays,
            'start_year' => $startYear,
        ]);
    }
}
