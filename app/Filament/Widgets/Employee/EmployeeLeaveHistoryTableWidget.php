<?php

namespace App\Filament\Widgets\Employee;

use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;
use Illuminate\Support\Facades\Auth;
use App\Models\LeavePermission;
use Illuminate\Database\Eloquent\Builder;

class EmployeeLeaveHistoryTableWidget extends BaseWidget
{
    protected static ?string $heading = 'Riwayat Pengajuan Cuti';
    protected int|string|array $columnSpan = 'full';

    /** ✅ tambahkan tipe return agar sesuai dengan parent */
    protected function getTableQuery(): Builder
    {
        return LeavePermission::query()
            ->where('worker_id', Auth::user()?->getWorkerId())
            ->latest();
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('leave_date_from')
                ->label('Dari')
                ->date()
                ->sortable(),
            Tables\Columns\TextColumn::make('leave_date_to')
                ->label('Sampai')
                ->date()
                ->sortable(),
            Tables\Columns\TextColumn::make('status')
                ->label('Status')
                ->badge()
                ->color(fn(string $state): string => match ($state) {
                    'approved' => 'success',
                    'pending' => 'warning',
                    'rejected' => 'danger',
                    default => 'gray',
                }),
            Tables\Columns\TextColumn::make('reason')
                ->label('Keterangan')
                ->limit(50)
                ->wrap(),
        ];
    }
}
