<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaderAssignmentResource\Pages;
use App\Models\LeaderAssignment;
use App\Models\User;
use App\Models\Worker;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class LeaderAssignmentResource extends Resource
{
    protected static ?string $model = LeaderAssignment::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Kelola Pimpinan';
    protected static ?string $pluralModelLabel = 'Kelola Pimpinan';
    protected static ?string $navigationGroup = 'Manajemen Pengguna';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            // LEADER
            Forms\Components\Select::make('leader_id')
                ->label('Leader')
                ->options(
                    User::query()
                        ->whereHas('roles', fn($q) => $q->whereIn('name', [
                            'senior_staff', 'leader', 'department_head', 'manager',
                        ]))
                        ->orderBy('name')
                        ->pluck('name', 'id')
                )
                ->searchable()
                ->preload()
                ->reactive()
                ->required()
                ->afterStateUpdated(fn (callable $set) => $set('worker_ids', [])),

            // WORKERS
            Forms\Components\Select::make('worker_ids')
                ->label('Daftar Employee')
                ->multiple()
                ->searchable()
                ->preload()
                ->required()
                ->rules(['required', 'array', 'min:1'])
                ->options(function (callable $get) {
                    $leaderId = $get('leader_id');
                    if (!$leaderId) return [];

                    $leaderWorker = Worker::where('user_id', $leaderId)->first();
                    if (!$leaderWorker) return [];

                    return Worker::query()
                        ->where('department_id', $leaderWorker->department_id)
                        ->whereHas('user.roles', fn($q) => $q->where('name', 'employee'))
                        ->where('user_id', '!=', $leaderId)
                        ->with('user')
                        ->get()
                        ->pluck('user.name', 'id'); // ✅ gunakan worker.id
                }),

            // EFFECTIVE DATE
            Forms\Components\DatePicker::make('effective_date')
                ->label('Tanggal Efektif')
                ->required()
                ->default(now()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('leader.name')
                ->label('Leader')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('worker.user.name')
                ->label('Employee')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('department.department_name')
                ->label('Departemen')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('level')
                ->label('Level')
                ->sortable(),

            Tables\Columns\TextColumn::make('effective_date')
                ->label('Efektif')
                ->date(),
        ])
        ->filters([])
        ->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ])
        ->bulkActions([
            Tables\Actions\DeleteBulkAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListLeaderAssignments::route('/'),
            'create' => Pages\CreateLeaderAssignment::route('/create'),
            'edit'   => Pages\EditLeaderAssignment::route('/{record}/edit'),
        ];
    }

    /** 🔐 Permission-based Access Control */
    public static function canViewAny(): bool
    {
        return Auth::user()?->can('user.assign-role') ?? false;
    }

    public static function canView($record): bool
    {
        return Auth::user()?->can('user.assign-role') ?? false;
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->can('user.assign-role') ?? false;
    }

    public static function canEdit($record): bool
    {
        return Auth::user()?->can('user.assign-role') ?? false;
    }

    public static function canDelete($record): bool
    {
        return Auth::user()?->can('user.assign-role') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->can('user.assign-role') ?? false;
    }
}
