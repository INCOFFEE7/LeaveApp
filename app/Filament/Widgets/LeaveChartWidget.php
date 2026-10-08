<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\LeavePermission;
use Carbon\Carbon;

class LeaveChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Pengajuan Cuti Bulanan';

    protected function getData(): array
    {
        $data = LeavePermission::selectRaw("DATE_FORMAT(leave_date_from,'%Y-%m') as month, COUNT(*) as total")
            ->groupBy('month')->orderBy('month')->get();

        return [
            'datasets'=>[[
                'label'=>'Pengajuan',
                'data'=>$data->pluck('total'),
                'backgroundColor'=>'#3b82f6',
            ]],
            'labels'=>$data->pluck('month')->map(fn($m)=>Carbon::parse($m.'-01')->translatedFormat('F Y')),
        ];
    }

    protected function getType(): string { return 'bar'; }
}
