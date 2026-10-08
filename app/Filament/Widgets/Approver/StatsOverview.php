<?php

namespace App\Filament\Widgets\Approver;

use App\Models\LeavePermission;
use App\Models\LeaderAssignment;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use Illuminate\Support\Facades\Auth;

class StatsOverview extends StatsOverviewWidget
{
    protected function getCards(): array
    {
        $user = Auth::user();
        $worker = $user?->worker;

        // Ambil semua worker bawahan aktif
        $assignedWorkerIds = LeaderAssignment::where('leader_id', $user->id)
            ->whereNull('ended_at')
            ->pluck('worker_id')
            ->toArray();

        // Query dasar
        $baseQuery = LeavePermission::query();

        if (!empty($assignedWorkerIds)) {
            // Jika user punya bawahan aktif → tampilkan semua pengajuan bawahan
            $baseQuery->whereIn('worker_id', $assignedWorkerIds);
        } else {
            // Manager/approver tanpa relasi worker → tampilkan semua departemen yang dia approve
            $approverDepartmentIds = \App\Models\DepartmentApprover::where('approver_id', $user->id)
                ->pluck('department_id')
                ->toArray();

            if (!empty($approverDepartmentIds)) {
                $baseQuery->whereHas('worker', function ($q) use ($approverDepartmentIds) {
                    $q->whereIn('department_id', $approverDepartmentIds);
                });
            } else {
                // fallback: kosong
                $baseQuery->whereRaw('1=0');
            }
        }

        // Hitungan
        $totalDept    = (clone $baseQuery)->count();
        $pendingDept  = (clone $baseQuery)->where('status', 'pending')->count();
        $approvedDept = (clone $baseQuery)->where('status', 'approved')->count();
        // Statistik pribadi
        $personalPending = $worker
            ? LeavePermission::where('worker_id', $worker->id)
                ->where('status', 'pending')
                ->count()
            : 0;

        $personalApproved = $worker
            ? LeavePermission::where('worker_id', $worker->id)
                ->where('status', 'approved')
                ->count()
            : 0;

        return [
            Card::make('Total Cuti (Bawahan/Departemen)', $totalDept)
                ->icon('heroicon-o-clipboard-document-list')
                ->description('Semua pengajuan bawahan')
                ->color('primary'),

            Card::make('Menunggu Persetujuan', $pendingDept)
                ->icon('heroicon-o-clock')
                ->description('Menunggu proses Anda')
                ->color('warning'),

            Card::make('Sudah Disetujui', $approvedDept)
                ->icon('heroicon-o-check-circle')
                ->description('Cuti telah disetujui')
                ->color('success'),

            Card::make('Pengajuan Pribadi Menunggu', $personalPending)
                ->icon('heroicon-o-arrow-path')
                ->description('Cuti pribadi pending')
                ->color('warning'),

            Card::make('Pengajuan Pribadi Disetujui', $personalApproved)
                ->icon('heroicon-o-check-badge')
                ->description('Cuti pribadi disetujui')
                ->color('success'),
        ];
    }
}
