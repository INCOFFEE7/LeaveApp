<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExtraHolidayResource\Pages;
use App\Filament\Resources\ExtraHolidayResource\RelationManagers;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use App\Models\ExtraHoliday;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ExtraHolidayResource extends Resource
{
    protected static ?string $model = ExtraHoliday::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Manajemen Cuti';
    protected static ?string $navigationLabel = 'Libur Tambahan';
    protected static ?string $slug = 'libur-tambahan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
            DatePicker::make('date')->label('Tanggal')->required()->unique(ExtraHoliday::class, 'date'),
            TextInput::make('name')->label('Nama Libur')->required(),
            TextInput::make('source')->label('Sumber')->default('manual'),
        ]);

    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')->label('Tanggal')->date(),
                TextColumn::make('name')->label('Nama Libur'),
                TextColumn::make('source')->label('Sumber'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExtraHolidays::route('/'),
            'create' => Pages\CreateExtraHoliday::route('/create'),
            'edit' => Pages\EditExtraHoliday::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        $u = auth()->user();
        return $u && $u->hasPermissionTo('holiday.extra.manage');
    }

    public static function canViewAny(): bool
    {
        $u = auth()->user();
        return $u && $u->hasPermissionTo('holiday.extra.manage');
    }

    public static function canCreate(): bool
    {
        $u = auth()->user();
        return $u && $u->hasPermissionTo('holiday.extra.manage');
    }

    public static function canEdit($record): bool
    {
        $u = auth()->user();
        return $u && $u->hasPermissionTo('holiday.extra.manage');
    }

    public static function canDelete($record): bool
    {
        $u = auth()->user();
        return $u && $u->hasPermissionTo('holiday.extra.manage');
    }

}
