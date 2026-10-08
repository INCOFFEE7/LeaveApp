<?php

namespace App\Helpers;

use App\Models\DepartmentApprover;
use App\Models\LeavePermission;

class LeaveHelper
{
    /**
     * 🔹 Mendapatkan level inisiator (jika pengaju juga merupakan approver)
     */
    public static function getInitiatorLevel(?int $userId, ?int $departmentId = null): int
    {
        if (!$userId) {
            return 0;
        }

        $query = DepartmentApprover::where('approver_id', $userId);

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        return $query->value('level') ?? 0;
    }

    /**
     * 🔹 Menentukan level awal approval berdasarkan departemen & posisi pengaju
     */
    public static function getInitialApprovalLevel(LeavePermission $leave): ?int
    {
        $departmentId = $leave->worker?->department_id;

        if (!$departmentId) {
            return null;
        }

        $approvers = DepartmentApprover::where('department_id', $departmentId)
            ->orderBy('level')
            ->get();

        if ($approvers->isEmpty()) {
            return null;
        }

        $initiatorLevel = self::getInitiatorLevel($leave->created_by, $departmentId);

        if ($initiatorLevel > 0) {
            // Cari level di atas inisiator
            $next = $approvers->firstWhere('level', '>', $initiatorLevel);

            return $next
                ? $next->level
                : $approvers->last()->level; // fallback ke level tertinggi
        }

        // Jika inisiator bukan approver → mulai dari level terendah
        return $approvers->first()->level;
    }

    /**
     * 🔹 Mengecek apakah pengaju masih boleh edit pengajuan cuti
     */
    public static function canEditLeave(LeavePermission $leave): bool
    {
        $initiatorLevel = self::getInitiatorLevel($leave->created_by, $leave->worker?->department_id);
        $initialLevel   = self::getInitialApprovalLevel($leave);

        return $initialLevel !== null
            && $leave->current_level <= $initialLevel
            && $leave->status === 'pending';
    }

    /**
     * 🔹 Ambil approver berikutnya berdasarkan current_level
     *    Logika:
     *    1. Cari level berikutnya (boleh lompat)
     *    2. Jika ada leader worker yg berada di level tsb → gunakan leader
     *    3. Kalau tidak → gunakan approver struktural
     */
    public static function getNextApprover(LeavePermission $leave): ?int
    {
        $departmentId = $leave->worker?->department_id;
        if (!$departmentId) {
            return null;
        }

        // 1. Cari level berikutnya
        $nextLevelRow = DepartmentApprover::where('department_id', $departmentId)
            ->where('level', '>', $leave->current_level)
            ->orderBy('level')
            ->first();

        if (!$nextLevelRow) {
            return null; // tidak ada level di atasnya → approval selesai
        }

        // 2. Ambil leader assignment aktif untuk worker
        $workerUser = $leave->worker?->user;

        $assignedLeaderIds = $workerUser
            ? $workerUser->leaderAssignments()
                ->whereNull('ended_at')
                ->pluck('leader_id')
                ->toArray()
            : [];

        // 3. Jika ada leader pada level ini → pakai leader
        if (!empty($assignedLeaderIds)) {
            $leaderApprover = DepartmentApprover::where('department_id', $departmentId)
                ->where('level', $nextLevelRow->level)
                ->whereIn('approver_id', $assignedLeaderIds)
                ->first();

            if ($leaderApprover) {
                return $leaderApprover->approver_id;
            }
        }

        // 4. Fallback: approver struktural di level tersebut
        return $nextLevelRow->approver_id;
    }

    /**
     * 🔹 Ambil level berikutnya (bila hanya level yang dibutuhkan)
     */
    public static function getNextLevel(int $departmentId, int $currentLevel): ?int
    {
        return DepartmentApprover::where('department_id', $departmentId)
            ->where('level', '>', $currentLevel)
            ->orderBy('level')
            ->value('level');
    }
}
