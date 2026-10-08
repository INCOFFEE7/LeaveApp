<?php

namespace App\Filament\Resources;

use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\CheckboxList;
use Spatie\Permission\Models\Permission;
use App\Filament\Resources\RoleResource\Pages;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;
    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';
    protected static ?string $navigationGroup = 'Manajemen Akses';
    protected static ?int $navigationSort = 999;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Role')
                    ->required()
                    ->unique(ignoreRecord: true),

                CheckboxList::make('permissions')
                    ->label('Permissions')
                    ->columns(2)
                    ->options(function () {
                        return Permission::pluck('name', 'name')->mapWithKeys(function ($name) {
                            return [$name => str($name)->replace(['.', '-'], ' ')->title()];
                        })->toArray();
                    })
                    ->searchable()
                    ->bulkToggleable()
                    ->afterStateHydrated(function ($component, $state, $record) {
                        if ($record) {
                            $component->state(
                                $record->permissions()->pluck('name')->toArray()
                            );
                        }
                    })
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('permissions_count')
                    ->label('Total Permissions')
                    ->counts('permissions'),
            ])
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
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }

    /** Authorization Check */
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('role.manage') ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('role.manage') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('role.manage') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('role.manage') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('role.manage') ?? false;
    }
}
