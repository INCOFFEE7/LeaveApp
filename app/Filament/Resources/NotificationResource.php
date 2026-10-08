<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationResource\Pages;
use App\Models\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;

class NotificationResource extends Resource
{
    protected static ?string $model = Notification::class;
    protected static ?string $slug = 'notifications';
    protected static ?string $navigationIcon = 'heroicon-o-bell';
    protected static ?string $navigationLabel = 'User Notifications';
    protected static ?string $pluralModelLabel = 'Logs';
    protected static ?string $modelLabel = 'Log';
    protected static ?int $navigationSort = 99;

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Title')->wrap(),
                TextColumn::make('type')->label('Type'),
                TextColumn::make('notifiable_type')->label('User Type'),
                TextColumn::make('notifiable_id')->label('User ID'),
                TextColumn::make('related_table')->label('Related Table'),
                TextColumn::make('related_id')->label('Related ID'),
                BadgeColumn::make('priority')
                    ->label('Priority')
                    ->colors([
                        'success' => fn ($state) => $state === 'low',
                        'warning' => fn ($state) => $state === 'medium',
                        'danger' => fn ($state) => $state === 'high',
                    ])
                    ->getStateUsing(fn ($record) => ucfirst($record->priority ?? 'low')), // tampilkan teks
                TextColumn::make('read_at')->label('Read At')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('created_at')->label('Created At')->dateTime('d M Y H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('priority')
                    ->options([
                        'low' => 'Low',
                        'medium' => 'Medium',
                        'high' => 'High',
                    ]),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotifications::route('/'),
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
