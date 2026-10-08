<?php

namespace App\Filament\Widgets\Approver;

use App\Models\LeavePermission;
use App\Models\Worker;
use App\Models\LeaderAssignment;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class LeaveApprovalTableWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getTableQuery(): Builder
    {
        $user = Auth::user();
        $worker = Worker::where('user_id', $user->id)->first();

        if (!$worker) {
            return LeavePermission::whereRaw('1 = 0');
        }

        // Ambil bawahan aktif
        $assignedWorkerIds = LeaderAssignment::where('leader_id', $user->id)
            ->whereNull('ended_at')
            ->pluck('worker_id')
            ->toArray();

        $query = LeavePermission::query()
            ->where('status', 'pending')
            ->with('worker')
            ->latest();

        if (!empty($assignedWorkerIds)) {
            // Jika punya bawahan
            $query->whereIn('worker_id', $assignedWorkerIds);
        } else {
            // fallback: tampilkan data departemen yang di-approve
            $approverDepartmentIds = \App\Models\DepartmentApprover::where('approver_id', $user->id)
                ->pluck('department_id')
                ->toArray();
                
            if (!empty($approverDepartmentIds)) {
                $query->whereHas('worker', fn($q) => $q->whereIn('department_id', $approverDepartmentIds));
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        return $query;
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
            Tables\Columns\TextColumn::make('approval_level')->label('Level Saat Ini'),
        ];
    }
}
