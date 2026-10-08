<?php

namespace App\Filament\Widgets\Admin;

use App\Models\LeavePermission;
use App\Models\Worker;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use Illuminate\Support\Facades\Cache;

class LeaveStatsOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Ringkasan Pengajuan Cuti';

    protected function getCards(): array
    {
        $user = auth()->user();

        $stats = Cache::remember('leave_stats_overview_'.$user->id, 300, function () use ($user) {
            $query = LeavePermission::query();

            if ($user->can('leave.view.all')) {
                // semua pengajuan
            } elseif ($user->can('leave.view.department') && $user->worker) {
                $query->whereHas('worker', fn($q) => $q->where('department_id', $user->worker?->department_id));
            } elseif ($user->can('leave.view.own') && $user->worker) {
                $query->where('worker_id', $user?->getWorkerId());
            } else {
                $query->whereRaw('0=1'); // tidak ada akses
            }

            return [
                'total'    => $query->count(),
                'pending'  => (clone $query)->where('status','pending')->count(),
                'approved' => (clone $query)->where('status','approved')->count(),
                'today'    => (clone $query)->where('status','approved')
                                    ->whereDate('leave_date_from','<=',now())
                                    ->whereDate('leave_date_to','>=',now())
                                    ->count(),
            ];
        });

        return [
            Card::make('Total Pengajuan', $stats['total'])
                ->icon('heroicon-o-clipboard-document-list')
                ->color('primary')
                ->description('Semua pengajuan cuti'),

            Card::make('Menunggu Persetujuan', $stats['pending'])
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->description('Cuti yang masih pending'),

            Card::make('Disetujui', $stats['approved'])
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->description('Cuti yang telah disetujui'),

            Card::make('Cuti Hari Ini', $stats['today'])
                ->icon('heroicon-o-calendar-days')
                ->color('info')
                ->description('Jumlah karyawan sedang cuti hari ini'),
        ];
    }
}
