<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DepartmentResource\Pages;
use App\Models\Department;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    protected static ?string $navigationIcon  = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Manajemen Pengguna';
    protected static ?string $navigationLabel = 'Kelola Departemen';
    protected static ?int    $navigationSort  = 2;

    public static function canAccess(): bool
    {
        $u = auth()->user();
        return $u && $u->hasPermissionTo('department.manage');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('department_name')
                ->required()
                ->label('Nama Departemen'),

            Toggle::make('is_active')
                ->label('Aktif')
                ->default(true),

            Forms\Components\Repeater::make('approvers')
                ->label('Approver Levels')
                ->relationship('approvers')
                ->schema([
                    Forms\Components\Select::make('level')
                        ->label('Level')
                        ->options([
                            1 => 'Approver 1',
                            2 => 'Approver 2',
                            3 => 'Checker',
                            4 => 'Manager',
                            5 => 'Director',
                        ])
                        ->required(), // ✅ distinct & rules duplikat dihapus

                    Forms\Components\Select::make('approver_id')
                        ->label('Approver (Karyawan)')
                        ->options(function (Get $get) {
                            $departmentId = $get('../../id');

                            $query = \App\Models\Worker::query()->with('user');

                            if ($departmentId) {
                                $query->where(function ($q) use ($departmentId) {
                                    $q->where('department_id', $departmentId)
                                        ->orWhereHas('user.roles', function ($qr) {
                                            $qr->whereIn('name', ['manager', 'director']);
                                        });
                                });
                            }

                            return $query->get()
                                ->mapWithKeys(function ($worker) {
                                    $userId = $worker->user_id;
                                    $label = "{$worker->worker_name} ({$worker->department->department_name})";
                                    return [$userId => $label];
                                });
                        })
                        ->searchable()
                        ->required(),
                ])
                ->maxItems(5)
                ->nullable()
                ->default([])
                ->columns(2)
                ->createItemButtonLabel('Tambah Approver'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('department_name')
                    ->label('Nama Departemen')
                    ->searchable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

                TextColumn::make('approvers_count')
                    ->counts('approvers')
                    ->label('Jumlah Approver'),

                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getSlug(): string
    {
        return 'department';
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDepartments::route('/'),
            'create' => Pages\CreateDepartment::route('/create'),
            'edit'   => Pages\EditDepartment::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }
}
