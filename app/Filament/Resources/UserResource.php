<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Models\Worker;
use App\Models\Department;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\UserWorkerImport;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;


class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Manajemen Pengguna';
    protected static ?string $pluralModelLabel = 'Kelola User';
    protected static ?string $modelLabel = 'User';
    protected static ?int $navigationSort = 1;

    public static int $defaultPagination = 25;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['worker.department', 'roles'])
            ->withCount('roles')
            ->where('is_active', true);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Akun')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nama Lengkap')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->unique(ignoreRecord: true)
                        ->required(),

                    Forms\Components\TextInput::make('payroll_number')
                        ->label('PN (Payroll Number)')
                        ->required()
                        ->maxLength(20),

                    Forms\Components\TextInput::make('password')
                        ->label('Password Baru')
                        ->password() // field tipe password
                        ->dehydrated(fn($state) => filled($state)) // hanya disimpan jika diisi
                        ->dehydrateStateUsing(fn($state) => Hash::make($state)) // hash sebelum simpan
                        ->placeholder('Kosongkan jika tidak ingin mengubah password')
                        ->helperText('Isi hanya jika ingin mengubah password pengguna')
                        ->columnSpan('full')
                        ->required(fn (string $context) => $context === 'create'),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true),

                    Forms\Components\Select::make('roles')
                        ->label('Peran (Role)')
                        ->options(fn () => Cache::remember('role_options', 3600, fn () =>
                            Role::query()
                                ->pluck('name', 'id')
                                ->filter(fn ($name) => filled($name))
                                ->toArray()
                        ))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->default(fn ($record) => $record?->roles?->pluck('id')->first()),
                ])
                ->columns(2),

            Forms\Components\Section::make('Data Karyawan')
                ->relationship('worker')
                ->lazy()
                ->schema([
                    Forms\Components\Select::make('department_id')
                        ->label('Departemen')
                        ->options(fn () => Cache::remember('department_options', 3600, fn () =>
                            Department::query()
                                ->pluck('department_name', 'id')
                                ->filter(fn ($name) => filled($name))
                                ->toArray()
                        ))
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\TextInput::make('position')
                        ->label('Jabatan')
                        ->required(),

                    Forms\Components\TextInput::make('company')
                        ->label('Perusahaan')
                        ->required(),

                    Forms\Components\TextInput::make('cabang')
                        ->label('Cabang')
                        ->nullable(),

                    Forms\Components\DatePicker::make('date_of_hire')
                        ->label('Tanggal Mulai Bekerja (DOH)')
                        ->required()
                        ->displayFormat('Y-m-d')
                        ->native(false),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(static::$defaultPagination)
            ->columns([
                Tables\Columns\TextColumn::make('payroll_number')
                    ->label('PN')
                    ->sortable()
                    ->alignCenter()
                    ->limit(4),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->sortable()
                    ->searchable()
                    ->limit(15)
                    ->tooltip(fn ($record) => $record->name),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->sortable()
                    ->searchable()
                    ->limit(15)
                    ->tooltip(fn ($record) => $record->email),

                Tables\Columns\BadgeColumn::make('worker.department.department_name')
                    ->label('Departemen')
                    ->limit(15)
                    ->tooltip(fn ($record) => $record->worker?->department?->department_name),

                Tables\Columns\BadgeColumn::make('worker.position')
                    ->label('Jabatan')
                    ->limit(15)
                    ->tooltip(fn ($record) => $record->worker?->position),

                Tables\Columns\BadgeColumn::make('worker.company')
                    ->label('Perusahaan')
                    ->limit(15)
                    ->tooltip(fn ($record) => $record->worker?->company),

                Tables\Columns\BadgeColumn::make('worker.cabang')
                    ->label('Cabang')
                    ->limit(15)
                    ->tooltip(fn ($record) => $record->worker?->cabang),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('company')
                    ->label('Perusahaan')
                    ->options(fn () => Cache::remember('company_options', 3600, fn () =>
                        Worker::query()
                            ->distinct()
                            ->pluck('company', 'company')
                            ->filter(fn ($v) => filled($v))
                            ->toArray()
                    ))
                    ->query(fn (Builder $query, array $data) =>
                        $data['value']
                            ? $query->whereHas('worker', fn ($w) => $w->where('company', $data['value']))
                            : null
                    ),

                Tables\Filters\SelectFilter::make('department')
                    ->label('Departemen')
                    ->options(fn () => Cache::remember('department_filter_options', 3600, fn () =>
                        Department::query()
                            ->pluck('department_name', 'id')
                            ->filter(fn ($v) => filled($v))
                            ->toArray()
                    ))
                    ->query(fn (Builder $query, array $data) =>
                        $data['value']
                            ? $query->whereHas('worker', fn ($w) => $w->where('department_id', $data['value']))
                            : null
                    ),

                Tables\Filters\SelectFilter::make('cabang')
                    ->label('Cabang')
                    ->options(fn () => Cache::remember('cabang_options', 3600, fn () =>
                        Worker::query()
                            ->distinct()
                            ->pluck('cabang', 'cabang')
                            ->filter(fn ($v) => filled($v))
                            ->toArray()
                    ))
                    ->query(fn (Builder $query, array $data) =>
                        $data['value']
                            ? $query->whereHas('worker', fn ($w) => $w->where('cabang', $data['value']))
                            : null
                    ),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('name', 'asc');
    }

    public static function getHeaderActions(): array
    {
        return [
            // ðŸ”’ Tampilkan tombol Import hanya untuk Admin
            Action::make('import')
                ->label('Import Data')
                ->icon('heroicon-o-arrow-up-tray')
                ->button()
                ->visible(fn () => Auth::user()?->hasRole('admin'))
                ->modalHeading('Import Data Karyawan')
                ->modalDescription('Unggah file Excel (.xlsx, .xls, .csv) yang berisi data karyawan.')
                ->form([
                    FileUpload::make('file')
                        ->label('Pilih File Excel')
                        ->disk('public')
                        ->directory('imports/users')
                        ->required()
                        ->acceptedFileTypes([
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'text/csv',
                            '.xls',
                            '.xlsx',
                            '.csv',
                        ]),
                ])
                ->action(function (array $data): void {
                    $filePath = Storage::disk('public')->path($data['file']);
                    Excel::import(new UserWorkerImport, $filePath);

                    Cache::forget('department_options');
                    Cache::forget('role_options');
                    Cache::forget('company_options');
                    Cache::forget('cabang_options');

                    Notification::make()
                        ->title('Import berhasil')
                        ->success()
                        ->send();
                }),
        ];
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
