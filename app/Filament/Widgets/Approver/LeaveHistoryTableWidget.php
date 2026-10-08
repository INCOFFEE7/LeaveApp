<?php

namespace App\Filament\Widgets\Approver;

use App\Models\LeavePermission;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class LeaveHistoryTableWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getTableQuery(): Builder
    {
        $user = Auth::user();
        $worker = $user->worker;

        if (!$worker) return LeavePermission::whereRaw('1=0');

        return LeavePermission::query()
            ->where('worker_id', $worker->id)
            ->with('worker')
            ->latest();
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('worker.worker_name')->label('Nama Karyawan')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('leave_date_from')->label('Dari')->date(),
            Tables\Columns\TextColumn::make('leave_date_to')->label('Sampai')->date(),
            Tables\Columns\TextColumn::make('leave_reason')->label('Alasan'),
            Tables\Columns\TextColumn::make('status')
                ->label('Status')
                ->badge()
                ->color(fn(string $state): string => match ($state) {
                    'approved' => 'success',
                    'pending' => 'warning',
                    'rejected' => 'danger',
                    default => 'gray',
                }),
        ];
    }
}
