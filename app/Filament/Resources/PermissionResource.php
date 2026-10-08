<?php

namespace App\Filament\Resources;

use App\Models\Permission;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Filament\Resources\PermissionResource\Pages;

class PermissionResource extends Resource
{
    protected static ?string $model = Permission::class;
    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static ?string $navigationGroup = 'Manajemen Akses';
    protected static ?int $navigationSort = 999;

    /** Label friendly untuk permission */
    public static array $permissionLabels = [
        // 📊 Dashboard
        'dashboard.view.worker'   => 'Lihat Dashboard Karyawan',
        'dashboard.view.general'  => 'Lihat Dashboard Umum',
        'dashboard.view.admin'    => 'Lihat Dashboard Admin',

        // 🏖️ Leave (Cuti)
        'leave.create'                 => 'Ajukan Cuti',
        'leave.view.own'               => 'Lihat Cuti Sendiri',
        'leave.view.department'        => 'Lihat Cuti Departemen',
        'leave.view.all'               => 'Lihat Semua Cuti',
        'leave.edit.own'               => 'Edit Cuti Sendiri',
        'leave.edit.department'        => 'Edit Cuti Departemen',
        'leave.delete.own'             => 'Hapus Cuti Sendiri',
        'leave.delete.department'      => 'Hapus Cuti Departemen',
        'leave.delete.all'             => 'Hapus Semua Cuti',
        'leave.approve'                => 'Setujui / Tolak Cuti',
        'leave.export'                 => 'Ekspor Data Cuti',
        'leave.type.manage'            => 'Kelola Jenis Cuti',
        'leave.balance.manage'         => 'Kelola Saldo Cuti',
        'leave.approval.manage'        => 'Kelola Alur Persetujuan Cuti',
        'leave.balance.reset.self'     => 'Reset Saldo Cuti Sendiri',
        'leave.balance.reset.all'      => 'Reset Saldo Cuti Semua Karyawan',

        // 👥 User Management
        'user.view'           => 'Lihat Pengguna',
        'user.create'         => 'Tambah Pengguna',
        'user.edit'           => 'Edit Pengguna',
        'user.delete'         => 'Hapus Pengguna',
        'user.export'         => 'Ekspor Data Pengguna',
        'user.reset-password' => 'Reset Password Pengguna',
        'user.assign-role'    => 'Atur Role Pengguna',

        // 🔐 Role & Permission
        'role.manage'        => 'Kelola Role',
        'permission.manage'  => 'Kelola Permission',

        // 🏢 Department
        'department.view'             => 'Lihat Departemen',
        'department.manage'           => 'Kelola Departemen',
        'department.approver.manage'  => 'Kelola Approver Departemen',

        // ⚙️ System / Admin
        'system.setting.manage'  => 'Kelola Pengaturan Sistem',
        'log.view'               => 'Lihat Log Sistem',
    ];

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('name')
                ->label('Nama Permission')
                ->options([
                    'Dashboard' => [
                        'dashboard.view.worker'  => 'Lihat Dashboard Karyawan',
                        'dashboard.view.general' => 'Lihat Dashboard Umum',
                        'dashboard.view.admin'   => 'Lihat Dashboard Admin',
                    ],
                    'Cuti' => [
                        'leave.create'                 => 'Ajukan Cuti',
                        'leave.view.own'               => 'Lihat Cuti Sendiri',
                        'leave.view.department'        => 'Lihat Cuti Departemen',
                        'leave.view.all'               => 'Lihat Semua Cuti',
                        'leave.edit.own'               => 'Edit Cuti Sendiri',
                        'leave.edit.department'        => 'Edit Cuti Departemen',
                        'leave.delete.own'             => 'Hapus Cuti Sendiri',
                        'leave.delete.department'      => 'Hapus Cuti Departemen',
                        'leave.delete.all'             => 'Hapus Semua Cuti',
                        'leave.approve'                => 'Setujui / Tolak Cuti',
                        'leave.export'                 => 'Ekspor Data Cuti',
                        'leave.type.manage'            => 'Kelola Jenis Cuti',
                        'leave.balance.manage'         => 'Kelola Saldo Cuti',
                        'leave.approval.manage'        => 'Kelola Alur Persetujuan Cuti',
                        'leave.balance.reset.self'     => 'Reset Saldo Cuti Sendiri',
                        'leave.balance.reset.all'      => 'Reset Saldo Cuti Semua Karyawan',
                    ],
                    'Manajemen Pengguna' => [
                        'user.view'           => 'Lihat Pengguna',
                        'user.create'         => 'Tambah Pengguna',
                        'user.edit'           => 'Edit Pengguna',
                        'user.delete'         => 'Hapus Pengguna',
                        'user.export'         => 'Ekspor Data Pengguna',
                        'user.reset-password' => 'Reset Password Pengguna',
                        'user.assign-role'    => 'Atur Role Pengguna',
                    ],
                    'Role & Permission' => [
                        'role.manage'       => 'Kelola Role',
                        'permission.manage' => 'Kelola Permission',
                    ],
                    'Departemen' => [
                        'department.view'            => 'Lihat Departemen',
                        'department.manage'          => 'Kelola Departemen',
                        'department.approver.manage' => 'Kelola Approver Departemen',
                    ],
                    'Sistem / Admin' => [
                        'system.setting.manage' => 'Kelola Pengaturan Sistem',
                        'log.view'              => 'Lihat Log Sistem',
                    ],
                ])
                ->searchable()
                ->required()
                ->unique(ignoreRecord: true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Permission')
                    ->formatStateUsing(fn (string $state): string => static::$permissionLabels[$state] ?? ucwords(str_replace(['.', '_'], [' › ', ' '], $state)))
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPermissions::route('/'),
            'edit' => Pages\EditPermission::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        return $user && $user->can('permission.manage');
    }

    public static function canViewAny(): bool
    {
        $u = auth()->user();
        return $u && $u->hasPermissionTo('permission.manage');
    }

    public static function canCreate(): bool
    {
        $u = auth()->user();
        return $u && $u->hasPermissionTo('permission.manage');
    }

    public static function canEdit($record): bool
    {
        $u = auth()->user();
        return $u && $u->hasPermissionTo('permission.manage');
    }

    public static function canDelete($record): bool
    {
        $u = auth()->user();
        return $u && $u->hasPermissionTo('permission.manage');
    }
}
