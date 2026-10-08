<?php

namespace App\Filament\Widgets\Admin;

use App\Models\LeavePermission;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class LeaveTableWidget extends BaseWidget
{
    protected function getTableQuery(): Builder
    {
        return LeavePermission::with('worker')
            ->latest()
            ->take(10);
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('worker.worker_name')->label('Nama Karyawan')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('leave_date_from')->label('Dari')->date(),
            Tables\Columns\TextColumn::make('leave_date_to')->label('Sampai')->date(),
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

    protected function getTableActions(): array
    {
        return [
            Tables\Actions\EditAction::make()
                ->url(fn (LeavePermission $record): string => \App\Filament\Resources\LeaveResource::getUrl('edit', ['record' => $record]))
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Ubah Data')
                ->modalDescription('Apakah Anda yakin ingin mengubah data pengajuan cuti ini?')
                ->successNotificationTitle('Aksi diarahkan'),
            Tables\Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Hapus Data')
                ->modalDescription('Apakah Anda yakin ingin menghapus data pengajuan cuti ini secara permanen?')
                ->successNotificationTitle('Data pengajuan berhasil dihapus'),
        ];
    }
}
