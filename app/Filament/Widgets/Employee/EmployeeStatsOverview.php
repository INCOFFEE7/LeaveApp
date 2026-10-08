<?php

namespace App\Filament\Widgets\Employee;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use Illuminate\Support\Facades\Auth;
use App\Models\LeaveBalance;
use App\Models\LeaveBalanceLog;
use App\Models\LeavePermission;

class EmployeeStatsOverview extends BaseWidget
{
    protected function getCards(): array
    {
        $user = Auth::user();
        $worker = $user?->worker;

        if (! $worker) {
            return [];
        }

        $balance = LeaveBalance::where('worker_id', $worker->id)
            ->where('year', now()->year)
            ->first();

        $total = $balance->hak_cuti ?? 0;
        $remaining = 0;
        $utang = 0;

        if ($balance) {
            $latestLog = LeaveBalanceLog::where('leave_balance_id', $balance->id)
                ->orderByDesc('created_at')
                ->first();

            $remaining = $latestLog
                ? $latestLog->new_sisa_cuti
                : $balance->sisa_cuti;
                
            $utang = $balance->utang_cuti ?? 0;
        }

        $used = max(0, $total - $remaining);

        $approvedCount = LeavePermission::where('worker_id', $worker->id)
            ->where('status', 'approved')
            ->count();

        $pendingCount = LeavePermission::where('worker_id', $worker->id)
            ->where('status', 'pending')
            ->count();

        $rejectedCount = LeavePermission::where('worker_id', $worker->id)
            ->where('status', 'rejected')
            ->count();

        return [
            Card::make('Sisa Cuti', "{$remaining} Hari")
                ->description("Dari total {$total} hari")
                ->icon('heroicon-o-briefcase')
                ->color('primary'),

            Card::make('Disetujui', "{$approvedCount} Pengajuan")
                ->description('Cuti telah disetujui')
                ->icon('heroicon-o-check-circle')
                ->color('success'),

            Card::make('Menunggu', "{$pendingCount} Pengajuan")
                ->description('Sedang menunggu persetujuan')
                ->icon('heroicon-o-clock')
                ->color('warning'),

            Card::make('Ditolak', "{$rejectedCount} Pengajuan")
                ->description('Tidak disetujui oleh atasan')
                ->icon('heroicon-o-x-circle')
                ->color('danger'),

            Card::make('Utang Cuti', $utang . ' Hari')
                ->description('Akumulasi utang cuti')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger'),
        ];
    }
}
