<?php

namespace App\Filament\Resources;

use App\Models\{
    LeavePermission,
    DepartmentApprover,
    LeaveApproval
};
use App\Events\LeavePermissionUpdated;
use App\Notifications\LeavePermissionUpdatedNotification;
use Filament\Forms\Components\{
    TextInput,
    Textarea,
    DatePicker,
    View
};
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class LeaveApprovalResource extends Resource
{
    protected static ?string $model = LeavePermission::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    protected static ?string $navigationGroup = 'Manajemen Cuti';
    protected static ?string $modelLabel = 'Persetujuan Cuti';
    protected static ?string $navigationLabel = 'Persetujuan Cuti';
    protected static ?string $slug = 'approval-cuti';
    protected static ?int $navigationSort = 1;

    // ================= TABLE =================
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('worker.user.name')->label('Nama Karyawan')->limit(15)->searchable(),
                Tables\Columns\TextColumn::make('worker.user.payroll_number')->label('NIK'),
                Tables\Columns\TextColumn::make('worker.department.department_name')->label('Departemen')->limit(15),
                Tables\Columns\TextColumn::make('worker.site')->label('Site')->limit(15),
                Tables\Columns\TextColumn::make('leaveType.leave_type_name')->label('Jenis Cuti'),
                Tables\Columns\TextColumn::make('leave_date_from')->label('Mulai')->date(),
                Tables\Columns\TextColumn::make('leave_date_to')->label('Selesai')->date(),
                Tables\Columns\TextColumn::make('current_level')->label('Level Saat Ini'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state) => match ($state) {
                        'pending' => 'warning',
                        'in_progress' => 'info',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->actions([
                ViewAction::make()
                    ->label('Detail')
                    ->modalHeading('Detail Pengajuan Cuti')
                    ->modalWidth('2xl')
                    ->form(fn($record) => self::getViewForm($record)),

                Action::make('edit_submission')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->color('info')
                    ->visible(fn($record) => auth()->user()?->hasRole('leader'))
                    ->form(fn($record) => [
                        DatePicker::make('leave_date_from')->label('Tanggal Mulai')->default($record->leave_date_from)->required(),
                        DatePicker::make('leave_date_to')->label('Tanggal Selesai')->default($record->leave_date_to)->required(),
                        Textarea::make('note')->label('Alasan Perubahan')->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $oldData = $record->only(['leave_date_from', 'leave_date_to']);
                        $record->fill([
                            'leave_date_from' => $data['leave_date_from'],
                            'leave_date_to' => $data['leave_date_to'],
                        ])->save();

                        LeaveApproval::updateOrCreate(
                            [
                                'leave_permission_id' => $record->id,
                                'approved_by' => auth()->id(),
                            ],
                            [
                                'level' => auth()->user()->approverLevel($record->worker->department_id),
                                'status' => $record->status,
                                'note' => $data['note'] ?? null,
                                'approved_at' => now(),
                            ]
                        );

                        $changes = collect($data)
                            ->only(['leave_date_from', 'leave_date_to'])
                            ->filter(fn($val, $key) => $val != ($oldData[$key] ?? null))
                            ->map(fn($val, $key) => ucfirst(str_replace('_', ' ', $key)) . " diubah menjadi: {$val}")
                            ->values()
                            ->implode(', ');

                        // if ($record->worker?->user) {
                        //     $record->worker->user->notify(
                        //         new LeavePermissionUpdatedNotification($record, [
                        //             'changes' => $changes,
                        //             'note' => $data['note'] ?? '-',
                        //         ])
                        //     );
                        // }

                        Notification::make()
                            ->title('Perubahan Pengajuan Cuti Disimpan')
                            ->body('Perubahan berhasil disimpan dan karyawan telah diberi notifikasi.')
                            ->success()
                            ->send();

                        event(new LeavePermissionUpdated($record, $data));
                    }),

                Action::make('approve')
                    ->label('Setujui')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->visible(fn($record) => self::canApprove($record))
                    ->action(fn($record) => self::approveAction($record)),

                Action::make('reject')
                    ->label('Tolak')
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->visible(fn($record) => self::canApprove($record))
                    ->form([
                        Textarea::make('note')->label('Alasan Penolakan')->required(),
                    ])
                    ->action(fn($record, array $data) => self::rejectAction($record, $data)),
            ]);
    }

    // ================= FORM DETAIL =================
    protected static function getViewForm($record): array
{
    return [
        \Filament\Forms\Components\View::make('filament.leave-permission.detail')
            ->Viewdata([
                'record' => $record,
            ]),
    ];
}

    // ================= APPROVAL LOGIC =================
    public static function canApprove($record): bool
    {
        $user = Auth::user();
        $worker = $record->worker;
        if (!$worker) return false;

        $userLevel = $user->approverLevel($worker->department_id);
        if (!$userLevel) return false;
        if ($worker->user_id === $user->id) return false;

        return in_array($record->status, ['pending', 'in_progress'])
            && ($record->current_level <= $userLevel);
    }

    protected static function approveAction($record)
    {
        $user = Auth::user();
        $userLevel = $user->approverLevel($record->worker->department_id);

        // Simpan approval current level
        LeaveApproval::updateOrCreate(
            [
                'leave_permission_id' => $record->id,
                'approved_by' => $user->id,
            ],
            [
                'level' => $userLevel,
                'status' => 'approved',
                'approved_at' => now(),
            ]
        );

        // Cek apakah ada level approver berikutnya
        $nextApprover = DepartmentApprover::where('department_id', $record->worker->department_id)
            ->where('level', '>', $userLevel)
            ->orderBy('level')
            ->first();

        if ($nextApprover) {
            // Masih ada approver berikutnya → update current_level & status
            $record->updateQuietly([
                'current_level' => $nextApprover->level,
                'status' => 'in_progress',
            ]);

            // Event untuk notify level berikutnya
            event(new LeavePermissionUpdated($record, ['message' => 'Menunggu persetujuan level berikutnya']));
        } else {
            // Level terakhir → baru dianggap final
            $record->updateQuietly([
                'current_level' => $userLevel,
                'status' => 'approved',
                'approved_by' => $user->id,
            ]);

            // Event final
            event(new \App\Events\LeaveApprovalFinal($record, $user));
        }
    }

    protected static function rejectAction($record, array $data)
    {
        $user = Auth::user();
        $userLevel = $user->approverLevel($record->worker->department_id);

        LeaveApproval::create([
            'leave_permission_id' => $record->id,
            'approved_by' => $user->id,
            'level' => $userLevel,
            'status' => 'rejected',
            'approved_at' => now(),
            'note' => $data['note'],
        ]);

        $record->updateQuietly(['status' => 'rejected']);
        event(new \App\Events\LeaveApprovalRejected($record, $user));
    }

    // ================= QUERY FILTER =================
    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $query = parent::getEloquentQuery()->with([
            'worker.user',
            'worker.department',
            'leaveType'
        ]);

        // =====================================================
        // MANAGER
        // =====================================================
        if ($user->hasRole('manager')) {
            // Ambil semua departemen yang dia pegang
            $managedDepartments = DepartmentApprover::where('approver_id', $user->id)
                ->pluck('department_id')
                ->toArray();

            if (empty($managedDepartments)) {
                return $query->whereRaw('0=1');
            }

            // Ambil level tertinggi manager di semua departemen
            $userLevel = DepartmentApprover::where('approver_id', $user->id)
                ->whereIn('department_id', $managedDepartments)
                ->max('level');

            return $query->whereHas('worker', function ($q) use ($managedDepartments) {
                    $q->whereIn('department_id', $managedDepartments);
                })
                ->where('current_level', $userLevel)
                ->whereIn('status', ['pending', 'in_progress']);
        }

        // =====================================================
        // DEPARTMENT HEAD / LEADER
        // =====================================================
        $worker = $user->worker;
        if (!$worker) return $query->whereRaw('0=1');

        $userLevel = $user->approverLevel($worker->department_id);
        if (!$userLevel) return $query->whereRaw('0=1');

        // Filter pengajuan di departemen yang sama & level sama
        return $query->whereHas('worker', function ($q) use ($worker, $user) {
                $q->where('department_id', $worker->department_id)
                ->where(function ($sub) use ($user) {
                    $sub->whereHas('user.leaderAssignments', function ($rel) use ($user) {
                            $rel->where('leader_id', $user->id)
                                ->whereNull('ended_at');
                        })
                        ->orWhereDoesntHave('user.leaderAssignments'); // 🔥 ini kuncinya
                });
            })
            ->where('current_level', '<=', $userLevel)
            ->whereIn('status', ['pending', 'in_progress']);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\LeaveApprovalResource\Pages\ListLeaveApprovals::route('/'),
        ];
    }

    // ================= ACCESS CONTROL =================
    public static function canViewAny(): bool
    {
        $user = Auth::user();
        return $user?->can('leave.approve')
            || $user?->hasAnyRole(['leader', 'department_head', 'manager'])
            ?? false;
    }

    public static function canView($record): bool
    {
        $user = Auth::user();
        $worker = $record->worker;
        if (!$worker) return false;
        $userLevel = $user->approverLevel($worker->department_id);

        return $user?->can('leave.approve')
            || ($userLevel && $record->current_level <= $userLevel)
            || $worker->user_id === $user->id;
    }

    public static function canCreate(): bool
    {
        $user = Auth::user();
        return $user?->hasRole('employee') ?? false;
    }

    public static function canEdit($record): bool
    {
        $user = Auth::user();
        $worker = $record->worker;
        if (!$worker) return false;
        $userLevel = $user->approverLevel($worker->department_id);
        return $user?->can('leave.approve') && $record->current_level <= $userLevel;
    }

    public static function canDelete($record): bool
    {
        $user = Auth::user();
        $worker = $record->worker;
        if (!$worker) return false;
        $userLevel = $user->approverLevel($worker->department_id);
        return $user?->can('leave.approve') && $record->current_level <= $userLevel;
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        return $user?->can('leave.approve')
            || $user?->hasAnyRole(['leader', 'department_head', 'manager'])
            ?? false;
    }
}
