<?php

namespace App\Filament\Widgets;

use App\Models\Department;
use Filament\Widgets\ChartWidget;

class LeaveByDepartmentChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Pengajuan Cuti per Departemen';

    protected function getData(): array
    {
        $departments = Department::withCount(['workers as total' => fn($q) => $q->where('is_active', 1)])->get();
        $labels = $departments->pluck('department_name');
        $data = $departments->pluck('total');

        $colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#6366f1', '#ec4899', '#22c55e', '#14b8a6'];

        return [
            'datasets' => [[
                'label' => 'Jumlah Karyawan Aktif',
                'data' => $data,
                'backgroundColor' => array_pad($colors, $data->count(), $colors[0]),
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
