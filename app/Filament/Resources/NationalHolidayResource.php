<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NationalHolidayResource\Pages;
use App\Filament\Resources\NationalHolidayResource\RelationManagers;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Artisan;
use App\Models\NationalHoliday;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class NationalHolidayResource extends Resource
{
    protected static ?string $model = NationalHoliday::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationGroup = 'Manajemen Cuti';
    protected static ?string $navigationLabel = 'Libur Nasional';
    protected static ?string $slug = 'libur-nasional';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
            DatePicker::make('date')->disabled(),
            TextInput::make('name')->disabled(),
            Toggle::make('is_national')->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
            return $table->columns([
                TextColumn::make('date')->label('Tanggal')->date(),
                TextColumn::make('name')->label('Nama Libur'),
                BooleanColumn::make('is_national')
                    ->label('Libur Nasional')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
            ])
            ->headerActions([
            Action::make('sync')
                ->label('Sync Libur Nasional')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->requiresConfirmation()
                ->action(function () {
                    // Jalankan command sync (sync tahun ini)
                    Artisan::call('holiday:sync');
                    \Filament\Notifications\Notification::make()
                        ->title('Sync dimulai')
                        ->body('Sync libur nasional untuk tahun ini telah dijalankan.')
                        ->success()
                        ->send();
                })
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
            'index' => Pages\ListNationalHolidays::route('/'),
            'create' => Pages\CreateNationalHoliday::route('/create'),
            'edit' => Pages\EditNationalHoliday::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        $u = auth()->user();
        return $u && $u->hasPermissionTo('holiday.national.manage');
    }

    public static function canViewAny(): bool
    {
        $u = auth()->user();
        return $u && $u->hasPermissionTo('holiday.national.manage');
    }

    public static function canCreate(): bool
    {
        $u = auth()->user();
        return $u && $u->hasPermissionTo('holiday.national.manage');
    }

    public static function canEdit($record): bool
    {
        $u = auth()->user();
        return $u && $u->hasPermissionTo('holiday.national.manage');
    }

    public static function canDelete($record): bool
    {
        $u = auth()->user();
        return $u && $u->hasPermissionTo('holiday.national.manage');
    }

}
