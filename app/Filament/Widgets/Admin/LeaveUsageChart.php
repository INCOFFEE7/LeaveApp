<?php

namespace App\Filament\Widgets\Admin;

use App\Models\LeavePermission;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class LeaveUsageChart extends ChartWidget
{
    protected static ?string $heading = 'Cuti Disetujui per Bulan';
    protected static ?string $maxHeight = '250px';

    protected function getData(): array
    {
        $year = now()->year;

        $data = Cache::remember("leave_usage_chart_{$year}", 300, function () use ($year) {
            return LeavePermission::selectRaw('MONTH(leave_date_from) as month, COUNT(*) as total')
                ->whereYear('leave_date_from', $year)
                ->where('status', 'approved')
                ->groupBy('month')
                ->pluck('total', 'month')
                ->toArray();
        });

        $labels = [];
        $values = [];
        for ($m = 1; $m <= 12; $m++) {
            $labels[] = Carbon::create()->month($m)->translatedFormat('M');
            $values[] = $data[$m] ?? 0;
        }

        return [
            'datasets' => [[
                'label' => 'Cuti Disetujui',
                'data' => $values,
                'backgroundColor' => 'rgba(59,130,246,0.7)',
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
