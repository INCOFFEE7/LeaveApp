<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveBalanceLogResource\Pages;
use App\Models\LeaveBalanceLog;
use Filament\Tables;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\Action;
use Filament\Forms;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use App\Exports\LeaveBalanceLogExport;

class LeaveBalanceLogResource extends Resource
{
    protected static ?string $model = LeaveBalanceLog::class;
    protected static ?string $navigationLabel = 'Log Perubahan Saldo Cuti';
    protected static ?string $slug = 'leave-balance-logs';
    protected static ?string $pluralModelLabel = 'Log Saldo Cuti';
    protected static ?string $modelLabel = 'Log Saldo';
    protected static ?int $navigationSort = 99;
    protected static ?string $navigationIcon = 'heroicon-o-clock';

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(fn () => static::getDataQuery())
            ->defaultSort('leaveBalance.year', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('leaveBalance.worker.worker_name')
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Diubah Oleh')
                    ->formatStateUsing(fn ($state) => $state ?? 'Sistem')
                    ->searchable(),
                Tables\Columns\TextColumn::make('leaveBalance.year')
                    ->label('Tahun Saldo')
                    ->sortable(),

                Tables\Columns\TextColumn::make('old_sisa_cuti')->label('Sisa Sebelumnya'),
                Tables\Columns\TextColumn::make('new_sisa_cuti')->label('Sisa Sekarang'),
                Tables\Columns\TextColumn::make('old_utang_cuti')
                                    ->label('Utang Sebelumnya')
                                    ->color(fn ($state) => $state > 0 ? 'danger' : null),

                                Tables\Columns\TextColumn::make('new_utang_cuti')
                                    ->label('Utang Sekarang')
                                    ->color(fn ($state) => $state > 0 ? 'danger' : null),
                Tables\Columns\TextColumn::make('note')->label('Alasan')->wrap(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu Perubahan')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([])
            ->headerActions([
                // === Export action (semua user bisa) ===
                Action::make('export')
                    ->label('Export Data')
                    ->icon('heroicon-o-arrow-down')
                    ->action(function (array $data) {
                        $scope = $data['scope'] ?? 'all';
                        $user = auth()->user();
                        $query = LeaveBalanceLog::query();

                        if ($scope === 'department') {
                            $query->whereHas('leaveBalance.worker', fn ($q) =>
                                $q->where('department_id', $user->worker?->department_id)
                            );
                        } elseif ($scope === 'own') {
                            $query->whereHas('leaveBalance.worker', fn ($q) =>
                                $q->where('id', $user?->getWorkerId())
                            );
                        }

                        $filename = "leave_balance_log_{$scope}_" . now()->format('Y-m-d_His') . ".xlsx";
                        return Excel::download(new LeaveBalanceLogExport($query), $filename);
                    })
                    ->form([
                        Forms\Components\Select::make('scope')
                            ->label('Pilih Scope Export')
                            ->options([
                                'all' => 'Semua Data',
                                'department' => 'Departemen Saya',
                                'own' => 'Data Saya',
                            ])
                            ->required(),
                    ]),

                // === Import Action (hanya admin) ===
                Action::make('import')
                    ->label('Import Log Saldo')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->visible(fn () => auth()->user()->hasRole('admin') || auth()->user()->can('leave.import'))
                    ->form([
                        Forms\Components\FileUpload::make('file')
                            ->label('File Excel (.xlsx)')
                            ->disk('local')
                            ->directory('imports')
                            ->preserveFilenames()
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                            ])
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        // Use Storage facade to build path and verify file exists
                        $fileKey = $data['file']; // e.g. imports/filename.xlsx
                        if (! Storage::disk('local')->exists($fileKey)) {
                            \Filament\Notifications\Notification::make()
                                ->title('File tidak ditemukan')
                                ->body("File yang dipilih tidak ditemukan pada path: {$fileKey}. Pastikan file sudah terupload dan direktori 'storage/app/imports' ada.")
                                ->danger()
                                ->send();
                            return;
                        }

                        $filePath = Storage::disk('local')->path($fileKey);
                        $importer = new \App\Imports\LeaveBalanceLogImport();
                        \Maatwebsite\Excel\Facades\Excel::import($importer, $filePath);

                        \Filament\Notifications\Notification::make()
                            ->title('Import Selesai')
                            ->body('Data log saldo cuti berhasil diimport dan saldo terkini telah diperbarui.')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Import Log Saldo Cuti')
                    ->modalButton('Proses Import'),
            ]);
    }

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('leave_balance_id')
                    ->label('Karyawan')
                    ->relationship('leaveBalance.worker', 'worker_name')
                    ->searchable()
                    ->required(),

                Forms\Components\TextInput::make('old_hak_cuti')->numeric()->label('Hak Sebelumnya'),
                Forms\Components\TextInput::make('new_hak_cuti')->numeric()->label('Hak Sekarang'),
                Forms\Components\TextInput::make('old_sisa_cuti')->numeric()->label('Sisa Sebelumnya'),
                Forms\Components\TextInput::make('new_sisa_cuti')->numeric()->label('Sisa Sekarang'),
                Forms\Components\TextInput::make('old_utang_cuti')->numeric()->label('Utang Sebelumnya'),
                Forms\Components\TextInput::make('new_utang_cuti')->numeric()->label('Utang Sekarang'),
                Forms\Components\Textarea::make('note')->label('Catatan')->rows(3)->required(),
            ]);
    }

    public static function getDataQuery(): Builder
    {
        $user = auth()->user();
        $query = LeaveBalanceLog::query()
            ->with(['leaveBalance.worker', 'user']); // eager load supaya UI lebih cepat

        if ($user->can('leave.view.all')) {
            // semua data
        } elseif ($user->can('leave.view.department')) {
            $query->whereHas('leaveBalance.worker', fn ($q) =>
                $q->where('department_id', $user->worker?->department_id)
            );
        } else {
            // hanya own data
            $query->whereHas('leaveBalance.worker', fn ($q) =>
                $q->where('id', $user?->getWorkerId())
            );
        }

        // ðŸ”¹ Urutkan: saldo lama dulu (leave_year ascending), lalu log baru dulu (id ascending)
        return $query
            ->with(['leaveBalance.worker', 'user'])
            ->join('leave_balances', 'leave_balance_logs.leave_balance_id', '=', 'leave_balances.id')
            ->orderBy('leave_balance_logs.id', 'desc')
            ->orderBy('leave_balances.year', 'desc')
            ->select('leave_balance_logs.*'); // penting supaya tetap memilih kolom log saja

    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeaveBalanceLogs::route('/'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
}
