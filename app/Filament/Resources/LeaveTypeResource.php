<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveTypeResource\Pages;
use App\Models\LeaveType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;

class LeaveTypeResource extends Resource
{
    protected static ?string $model = LeaveType::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup = 'Manajemen Cuti';
    protected static ?string $navigationLabel = 'Jenis Cuti';
    protected static ?string $pluralLabel = 'Jenis Cuti';
    protected static ?int $navigationSort = 0;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('leave_type_name')
                ->label('Nama Jenis Cuti')
                ->required()
                ->maxLength(100),

            Toggle::make('is_active')
                ->label('Aktif')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('leave_type_name')
                    ->label('Jenis Cuti')
                    ->sortable()
                    ->searchable(),

                BadgeColumn::make('is_active')
                    ->label('Status')
                    ->formatStateUsing(fn (bool $state) => $state ? 'Aktif' : 'Nonaktif')
                    ->color(fn (bool $state) => $state ? 'success' : 'danger'),

                TextColumn::make('createdBy.payroll_number')
                    ->label('NIP Pembuat')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('createdBy.name')
                    ->label('Nama Pembuat')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('updatedBy.payroll_number')
                    ->label('NIP Pengubah')
                    ->sortable(),

                TextColumn::make('updatedBy.name')
                    ->label('Nama Pengubah')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Diperbarui Pada')
                    ->dateTime('d M Y, H:i'),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status Cuti')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\ForceDeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make(),
            ]);
    }

    public static function shouldRegisterNavigation(): bool
    {
        $u = auth()->user();
        return $u && $u->hasPermissionTo('leave.type.manage');
        // hanya user dengan permission 'leave.type.manage' bisa lihat menu
    }

    public static function canViewAny(): bool
    {
        $u = auth()->user();
        return $u && $u->hasPermissionTo('leave.type.manage');
    }

    public static function canCreate(): bool
    {
        $u = auth()->user();
        return $u && $u->hasPermissionTo('leave.type.manage');
    }

    public static function canEdit($record): bool
    {
        $u = auth()->user();
        return $u && $u->hasPermissionTo('leave.type.manage');
    }

    public static function canDelete($record): bool
    {
        $u = auth()->user();
        return $u && $u->hasPermissionTo('leave.type.manage');
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeaveTypes::route('/'),
            'create' => Pages\CreateLeaveType::route('/create'),
            'edit' => Pages\EditLeaveType::route('/{record}/edit'),
        ];
    }
}
