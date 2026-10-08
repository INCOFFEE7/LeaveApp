<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveBalanceResource\Pages;
use App\Models\LeaveBalance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class LeaveBalanceResource extends Resource
{
    protected static ?string $model = LeaveBalance::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Manajemen Cuti';
    protected static ?string $navigationLabel = 'Saldo Cuti';
    protected static ?string $pluralModelLabel = 'Saldo Cuti';
    protected static ?int $navigationSort = 0;
    protected static ?string $modelLabel = 'Saldo Cuti';

    // ===================================================
    // Permissions
    // ===================================================
    public static function canViewAny(): bool
    {
        return Auth::user()->can('leave.balance.manage');
    }

    public static function canEdit($record): bool
    {
        return Auth::user()->can('leave.balance.manage');
    }

    public static function canDelete($record): bool
    {
        return Auth::user()->can('leave.balance.manage');
    }

    public static function canCreate(): bool
    {
        return Auth::user()->can('leave.balance.manage');
    }

    // ===================================================
    // Form (Edit / Create)
    // ===================================================
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Placeholder::make('Nama Karyawan')
                ->label('Nama Karyawan')
                ->content(fn ($record) => $record?->worker?->worker_name ?? '-'),

            Forms\Components\Placeholder::make('Payroll Number')
                ->label('Payroll Number')
                ->content(fn ($record) => $record?->worker?->user?->payroll_number ?? '-'),

            Forms\Components\TextInput::make('year')
                ->label('Tahun')
                ->numeric()
                ->required(),

            Forms\Components\TextInput::make('hak_cuti')
                ->label('Hak Cuti')
                ->numeric()
                ->required(),

            Forms\Components\TextInput::make('sisa_cuti')
                ->label('Sisa Cuti')
                ->numeric()
                ->required(),

            Forms\Components\TextInput::make('utang_cuti')
                ->label('Utang Cuti')
                ->numeric()
                ->default(0),

            Forms\Components\DatePicker::make('reset_at')
                ->label('Tanggal Reset'),

            Forms\Components\Textarea::make('note')
                ->label('Alasan Perubahan')
                ->rows(3)
                ->nullable()
                ->required(fn (string $operation): bool => $operation === 'edit'),
        ]);
    }

    // ===================================================
    // Table
    // ===================================================
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('worker.worker_name')->label('Nama Karyawan')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('worker.user.payroll_number')->label('Payroll Number')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('worker.department.department_name')->label('Departemen')->limit(15)->searchable()->sortable(),
                Tables\Columns\TextColumn::make('reset_at')->label('Tanggal Reset')->date()->sortable(),
                Tables\Columns\TextColumn::make('year')->label('Tahun')->sortable(),
                Tables\Columns\TextColumn::make('hak_cuti')->label('Hak Cuti'),
                Tables\Columns\TextColumn::make('sisa_cuti')->label('Sisa Cuti'),
                Tables\Columns\TextColumn::make('utang_cuti')->label('Utang Cuti')->color(fn ($state) => $state > 0 ? 'danger' : null),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('year')->label('Tahun')->options(fn () => LeaveBalance::select('year')->distinct()->pluck('year', 'year')->toArray()),
                Tables\Filters\SelectFilter::make('worker.department_id')->label('Departemen')->relationship('worker.department', 'department_name'),
            ])
            ->defaultSort('reset_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make()->label('Ubah'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    // ===================================================
    // Query Eloquent
    // ===================================================
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['worker.user', 'worker.department']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeaveBalances::route('/'),
            'edit' => Pages\EditLeaveBalance::route('/{record}/edit'),
        ];
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        $data['note'] = $data['note'] ?? 'Perubahan saldo cuti manual';
        return $data;
    }

    public static function afterSave(\Filament\Resources\Pages\EditRecord $page, $record): void
    {
        $record->customNote = request()->get('note') ?? 'Perubahan saldo cuti manual';
        $record->customUserId = auth()->id();
        $record->save();
    }
}
