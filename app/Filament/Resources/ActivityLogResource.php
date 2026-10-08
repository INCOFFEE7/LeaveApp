<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use App\Models\ActivityLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;

class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document';
    protected static ?string $navigationLabel = 'Activity Logs';
    protected static ?string $pluralModelLabel = 'Logs';
    protected static ?string $modelLabel = 'Log';
    protected static ?int $navigationSort = 100;

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('User')->sortable()->searchable(),
                TextColumn::make('activity_type')->label('Type')->sortable()->searchable(),
                TextColumn::make('description')->label('Description')->wrap(),
                TextColumn::make('related_table')->label('Table'),
                TextColumn::make('related_id')->label('Record ID'),
                TextColumn::make('ip_address')->label('IP'),
                TextColumn::make('user_agent')->label('User Agent')->limit(30)->tooltip(fn ($record) => $record->user_agent),
                TextColumn::make('created_at')->label('Created At')->dateTime('d M Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('log.view');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('log.view');
    }
}
