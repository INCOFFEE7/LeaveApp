<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveResource\Pages;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\EnsureLeaveBalanceForYear;
use App\Models\DepartmentApprover;
use App\Helpers\ApprovalHelper;
use App\Models\Worker;
use Illuminate\Support\Str;
use App\Models\LeavePermission;
use App\Models\NationalHoliday;
use App\Models\ExtraHoliday;
use App\Helpers\DateHelper;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\{
    Card, DatePicker, FileUpload, Grid, Placeholder,
    Select, Textarea, TextInput, Hidden, Section, Toggle
};
use App\Models\LeaveBalance;
use App\Models\LeaveBalanceLog;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;


class LeaveResource extends Resource
{
    protected static ?string $model = LeavePermission::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Pengajuan Cuti';
    protected static ?string $pluralLabel = 'Pengajuan Cuti';
    protected static ?string $slug = 'pengajuan-cuti';
    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return ! auth()->user()->hasRole('admin');
    }

    public static function form(Form $form): Form
    {
        $user = Auth::user();
        $worker = $user?->worker;
        $holidayDates = NationalHoliday::pluck('date')
            ->merge(ExtraHoliday::pluck('date'))
            ->map(fn($date) => Carbon::parse($date)->format('Y-m-d'))
            ->values()
            ->toArray();

        return $form->schema([
            // 📋 Informasi Karyawan
            Card::make([
                Placeholder::make('info_karyawan')
                    ->label('Informasi Karyawan')
                    ->content('Berikut informasi identitas Anda, pastikan sudah benar.'),
                Grid::make(2)->schema([
                    TextInput::make('nama_karyawan')
                        ->label('Nama Karyawan')
                        ->disabled()
                        ->dehydrated(false)
                        ->default(fn() => $worker?->worker_name)
                        ->afterStateHydrated(fn($component, $state, $record) =>
                            $component->state($record?->worker?->worker_name ?? $worker?->worker_name)
                        ),

                    TextInput::make('nik')
                        ->label('NIK')
                        ->disabled()
                        ->dehydrated(false)
                        ->default(fn() => $user?->payroll_number)
                        ->afterStateHydrated(fn($component, $state, $record) =>
                            $component->state($record?->worker?->user?->payroll_number ?? $user?->payroll_number)
                        ),

                    TextInput::make('jabatan')
                        ->label('Jabatan')
                        ->disabled()
                        ->dehydrated(false)
                        ->default(fn() => $worker?->position)
                        ->afterStateHydrated(fn($component, $state, $record) =>
                            $component->state($record?->worker?->position ?? $worker?->position)
                        ),

                    TextInput::make('departemen')
                        ->label('Departemen')
                        ->disabled()
                        ->dehydrated(false)
                        ->default(fn() => $worker?->department?->department_name)
                        ->afterStateHydrated(fn($component, $state, $record) =>
                            $component->state($record?->worker?->department?->department_name ?? $worker?->department?->department_name)
                        ),

                    TextInput::make('tanggal_masuk')
                        ->label('Tanggal Masuk (DOH)')
                        ->disabled()
                        ->dehydrated(false)
                        ->default(fn() => DateHelper::formatDate($worker?->date_of_hire))
                        ->afterStateHydrated(fn($component, $state, $record) =>
                            $component->state(DateHelper::formatDate($record?->worker?->date_of_hire ?? $worker?->date_of_hire))
                        ),
                ]),
            ]),

            // 🗓️ Formulir Cuti
            Card::make([
                Placeholder::make('form_cuti')
                    ->label('Formulir Pengajuan Cuti')
                    ->content('Silakan isi detail cuti yang akan diajukan.'),

                Hidden::make('worker_id')->default(fn($record) => $record?->worker_id ?? $worker?->id)->required(),
                Hidden::make('status')->default('pending'),
                Hidden::make('created_by')->default(fn() => Auth::id()),
                Hidden::make('updated_by')->default(fn() => Auth::id()),
                Hidden::make('is_manual') // ⬅️ WAJIB ADA
                    ->default(false)
                    ->dehydrated(true),
                Hidden::make('current_level')
                    ->default(function ($get) {
                        $workerId = $get('worker_id');

                        return $workerId
                            ? \App\Helpers\ApprovalHelper::getFirstApprovalLevel($workerId)
                            : 1;
                    }),

                Grid::make(2)->schema([
                    Select::make('leave_type_id')
                        ->label('Jenis Cuti')
                        ->relationship('leaveType', 'leave_type_name')
                        ->required()
                        ->reactive(),

                    DatePicker::make('onsite_date_from')
                        ->label('Tanggal Mulai Onsite')
                        ->visible(fn (Get $get) => static::isFieldBreak($get('leave_type_id')))
                        ->required(fn (Get $get) => static::isFieldBreak($get('leave_type_id')))
                        ->reactive()
                        ->afterStateHydrated(fn (Set $set, Get $get) =>
                            self::updateComputedDates($set, $get)
                        )
                        ->afterStateUpdated(fn (Set $set, Get $get) =>
                            self::updateComputedDates($set, $get)
                        ),

                    DatePicker::make('onsite_date_to')
                        ->label('Tanggal Selesai Onsite')
                        ->visible(fn (Get $get) => static::isFieldBreak($get('leave_type_id')))
                        ->required(fn (Get $get) => static::isFieldBreak($get('leave_type_id')))
                        ->reactive()
                        ->afterStateHydrated(fn (Set $set, Get $get) =>
                            self::updateComputedDates($set, $get)
                        )
                        ->afterStateUpdated(fn (Set $set, Get $get) =>
                            self::updateComputedDates($set, $get)
                        ),

                    TextInput::make('onsite_total_days')
                        ->label('Lama Onsite')
                        ->visible(fn(Get $get) => static::isFieldBreak($get('leave_type_id')))
                        ->disabled()
                        ->dehydrated(true)
                        ->numeric()
                        ->default(0),

                    TextInput::make('earned_leave')
                        ->label('Cuti Tambahan yang Diperoleh')
                        ->numeric()
                        ->disabled()
                        ->dehydrated(true),

                    TextInput::make('adjusted_earned_leave')
                        ->label('Penyesuaian Cuti Tambahan (+ / -)')
                        ->numeric()
                        ->nullable()
                        ->visible(fn (Get $get) =>
                            auth()->user()->hasRole('admin')
                            && static::isFieldBreak($get('leave_type_id'))
                        )
                        ->helperText('Kosongkan jika tidak ada penyesuaian'),

                    Textarea::make('adjustment_note')
                        ->label('Catatan Penyesuaian')
                        ->rows(2)
                        ->nullable()
                        ->visible(fn (Get $get) =>
                            auth()->user()->hasRole('admin')
                            && static::isFieldBreak($get('leave_type_id'))
                        ),

                    DatePicker::make('leave_date_from')
                        ->label('Tanggal Mulai')
                        ->reactive()
                        ->disabledDates($holidayDates)
                        ->required()
                        ->live() // bukan hanya reactive
                        ->minDate(now())
                        ->afterStateUpdated(fn(Set $set, Get $get, $state) => self::updateComputedDates($set, $get)),

                    DatePicker::make('leave_date_to')
                        ->label('Tanggal Selesai')
                        ->reactive()
                        ->required()
                        ->live()
                        ->minDate(fn (Get $get) => $get('leave_date_from') ?: now())
                        ->afterStateUpdated(
                            fn (Set $set, Get $get) => self::updateComputedDates($set, $get)
                        ),

                    TextInput::make('total_leave')
                        ->label('Jumlah Hari Cuti')
                        ->disabled(fn (Get $get) => true) // jangan hardcoded, biar tetap di-observe
                        ->dehydrated()
                        ->numeric()
                        ->live()
                        ->default(0),

                    DatePicker::make('return_to_work_date')
                        ->label('Tanggal Kembali Bekerja')
                        ->disabled()          // view only
                        ->dehydrated(true)    // tetap disimpan
                        ->displayFormat('d/m/Y'),

                    Textarea::make('leave_reason')
                        ->label('Alasan Cuti')
                        ->rows(3)
                        ->required()
                        ->maxLength(500)
                        ->columnSpanFull(),

                    FileUpload::make('supporting_document')
                            ->label('Dokumen Pendukung (Opsional)')
                            ->disk('public')
                            ->visibility('public')
                            ->directory('cuti/dokumen')
                            ->preserveFilenames()
                            ->openable()
                            ->downloadable()
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->maxSize(2048)
                            ->nullable()
                            ->multiple(false)

                            // Rename file (INI PARAMETERNYA WAJIB 2)
                            ->getUploadedFileNameForStorageUsing(function ($file, $storedFileName): string {

                            // Jika edit mode → Filament mengirim array
                            if (is_array($file)) {
                                // Jangan ubah nama file, pakai yang sudah ada
                                return basename($file['file_path'] ?? $storedFileName ?? 'file.pdf');
                            }

                            // Jika sudah string → return langsung
                            if (is_string($file)) {
                                return basename($file);
                            }

                            // Upload baru (TemporaryUploadedFile)
                            if ($file instanceof TemporaryUploadedFile) {
                                $clean = time() . '-' . \Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
                                return $clean . '.' . $file->getClientOriginalExtension();
                            }

                            return 'unknown.dat';
                        })

                            // Save file (INI PARAMETERNYA WAJIB 1 SAJA)
                            ->saveUploadedFileUsing(function ($file) {
                            // Jika edit mode → Filament mengirim array
                            if (is_array($file)) {
                                return $file['file_path'] ?? '';
                            }

                            // Jika string (mis. tidak berubah)
                            if (is_string($file)) {
                                return $file;
                            }

                            // Upload baru
                            if ($file instanceof TemporaryUploadedFile) {
                                $clean = time() . '-' . \Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
                                $filename = $clean . '.' . $file->getClientOriginalExtension();

                                return $file->storeAs('cuti/dokumen', $filename, 'public');
                            }

                            return '';
                        })

                            ->afterStateHydrated(function ($component, $state, $set, $operation) {
                            if ($operation === 'edit') {
                                // simpan data asli ke field hidden untuk preview
                                $set('supporting_document_original', $state);

                                // Kosongkan FileUpload supaya tidak memuat array → aman
                                $set('supporting_document', null);
                            } else {
                                // Mode create → sama seperti sebelumnya
                                if (is_string($state) && !empty($state)) {
                                    $component->state([
                                        [
                                            'name' => basename($state),
                                            'file_path' => $state,
                                            'url' => asset('storage/' . $state),
                                        ],
                                    ]);
                                }
                            }
                        })

                            ->dehydrated(true)

                            ->mutateDehydratedStateUsing(function ($state, $get) {
                            // Jika tidak upload baru → pakai file lama
                            if (empty($state)) {
                                return $get('supporting_document_original');
                            }

                            // Jika array wrapper Filament
                            if (is_array($state)) {
                                $first = $state[0] ?? null;
                                return $first['file_path'] ?? null;
                            }

                            return is_string($state) ? $state : null;
                        }),

                        Hidden::make('supporting_document_original'),
                                            Placeholder::make('lihat_dokumen')
                                                ->label('Lihat Dokumen yang Telah Diupload')
                                                ->content(function ($get) {
                            // Ambil data dari FileUpload atau dari hidden original
                            $state = $get('supporting_document') ?: $get('supporting_document_original');

                            $fileUrl = null;
                            $fileName = null;

                            // Jika state berupa array (Filament)
                            if (is_array($state) && !empty($state)) {
                                $firstFile = $state[0] ?? null;

                                if ($firstFile && isset($firstFile['url'], $firstFile['name'])) {
                                    $fileUrl = $firstFile['url'];
                                    $fileName = $firstFile['name'];
                                }
                            }
                            // Jika state berupa string (selain array)
                            elseif (is_string($state) && !empty($state)) {
                                $fileUrl = asset('storage/' . ltrim($state, '/'));
                                $fileName = basename($state);
                            }

                            // Jika masih tidak ada file
                            if (! $fileUrl) {
                                return new \Illuminate\Support\HtmlString(
                                    '<span class="text-gray-500">Belum ada dokumen diunggah.</span>'
                                );
                            }

                            $isPdf = str_ends_with(strtolower($fileName), '.pdf');

                            return new \Illuminate\Support\HtmlString(
                                $isPdf
                                    ? '<iframe src="' . $fileUrl . '" width="100%" height="400" class="rounded-lg border"></iframe>'
                                    : '<img src="' . $fileUrl . '" alt="Dokumen" class="rounded-lg border max-h-96 mx-auto">'
                            );
                        })
                        ->visible(fn(string $operation) => $operation === 'edit')
                ]),
            ]),

            // ✅ Status Persetujuan
            Placeholder::make('final_approver')
                ->label('Disetujui Terakhir Oleh')
                ->content(function ($record) {

                    if (!$record) {
                        return '-';
                    }

                    // 1️⃣ Jika sudah ada approval
                    if ($record->lastApprovedBy?->approver?->name) {
                        return $record->lastApprovedBy->approver->name;
                    }

                    // 2️⃣ Jika belum ada approval, fallback ke manager
                    if ($record->manager?->name) {
                        return $record->manager->name;
                    }

                    return '-';
                }),
        ]);
    }

    protected static function mutateFormDataBeforeCreate(array $data): array
    {
        return self::normalizeFieldBreakData($data);
    }

    protected static function mutateFormDataBeforeSave(array $data): array
    {
        return self::normalizeFieldBreakData($data);
    }
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('worker.worker_name')
                    ->label('Nama Karyawan')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('leaveType.leave_type_name')
                    ->label('Jenis Cuti')
                    ->sortable(),

                TextColumn::make('leave_date_from')
                    ->label('Mulai')
                    ->date()
                    ->sortable(),

                TextColumn::make('leave_date_to')
                    ->label('Selesai')
                    ->date()
                    ->sortable(),

                TextColumn::make('total_leave')
                    ->label('Lama (Hari)')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'gray'   => 'pending',
                        'warning'=> 'in_progress',
                        'success'=> 'approved',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => 'Menunggu',
                        'in_progress' => 'Diproses',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        default => ucfirst($state),
                    }),


                TextColumn::make('final_approver')
                    ->label('Disetujui Terakhir Oleh')
                    ->default(fn($record) => $record?->lastApprovedBy?->approver?->name ?? '-')
                    ->disabled(),

                TextColumn::make('latestReport.report_path')
                    ->label('Laporan Cuti')
                    ->formatStateUsing(function ($state) {
                        if (! $state) {
                            return '-';
                        }

                        // Normalisasi backslash ke slash (Windows paths)
                        $state = str_replace('\\', '/', $state);

                        // Paths yang mungkin muncul di DB:
                        // - relative path stored by disk 'public', e.g. "reports/leave_8.pdf"
                        // - full storage path, e.g. "C:/xampp/htdocs/leave-app2/storage/app/public/reports/leave_8.pdf"
                        // - public storage path, e.g. public_path('storage') . '/reports/leave_8.pdf'

                        $appPublic = rtrim(str_replace('\\', '/', storage_path('app/public')), '/') . '/';
                        $app = rtrim(str_replace('\\', '/', storage_path('app')), '/') . '/';
                        $publicStorage = rtrim(str_replace('\\', '/', public_path('storage')), '/') . '/';

                        if (str_starts_with($state, $appPublic)) {
                            $relative = substr($state, strlen($appPublic));
                        } elseif (str_starts_with($state, $app)) {
                            // storage/app/... maybe contains 'public/' prefix
                            $relative = substr($state, strlen($app));
                            $relative = preg_replace('#^public/#', '', $relative);
                        } elseif (str_starts_with($state, $publicStorage)) {
                            $relative = substr($state, strlen($publicStorage));
                        } else {
                            // already relative or unknown format: use as-is
                            $relative = ltrim($state, '/');
                        }

                        $relative = ltrim(str_replace('\\', '/', $relative), '/');

                        $url = asset('storage/' . $relative);

                        return '<a href="' . $url . '" target="_blank" style="color:#2563eb;">Download PDF</a>';
                    })
                    ->html()
                    ->toggleable(isToggledHiddenByDefault: false),
                ])
            ->filters([])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn() => auth()->user()->hasRole('admin'))
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Ubah Data')
                    ->modalDescription('Apakah Anda yakin ingin mengubah data pengajuan cuti ini?')
                    ->successNotificationTitle('Data pengajuan berhasil diubah'),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn() => auth()->user()->hasRole('admin'))
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Hapus Data')
                    ->modalDescription('Apakah Anda yakin ingin menghapus data pengajuan cuti ini secara permanen?')
                    ->successNotificationTitle('Data pengajuan berhasil dihapus'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->headerActions([
            Tables\Actions\Action::make('import')
            ->label('Import Cuti Historis')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('success')
            ->visible(fn() => auth()->user()->hasRole('admin'))
            ->form([
                FileUpload::make('file')
                    ->label('File Excel (.xlsx)')
                    ->disk('local')
                    ->directory('imports')
                    ->preserveFilenames()
                    ->acceptedFileTypes([
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ])
                    ->maxSize(2048) // 2MB
                    ->required(),
            ])
            ->action(function (array $data) {

                // Path relatif dari storage/app/
                $relative = ltrim($data['file'], '/');

                // Maatwebsite bisa langsung menerima full path
                $file = storage_path("app/{$relative}");

                \Maatwebsite\Excel\Facades\Excel::import(
                    new \App\Imports\LeavePermissionImport(),
                    $file
                );

                \Filament\Notifications\Notification::make()
                    ->title('Import Selesai')
                    ->body('Data cuti historis berhasil diproses dan saldo cuti telah diperbarui.')
                    ->success()
                    ->send();
            })
            ->requiresConfirmation()
            ->modalHeading('Import Data Cuti Historis')
            ->modalButton('Proses Import'),

            // Tombol manual entry historis khusus admin
            Tables\Actions\Action::make('manual_leave')
                ->label('Tambah Cuti Historis')
                ->icon('heroicon-o-plus')
                ->visible(fn() => auth()->user()->hasRole('admin'))
                ->modalHeading('Tambah Cuti Historis')
                ->modalButton('Simpan')
                ->form([
                    Toggle::make('is_manual')
                        ->label('Pengajuan Manual (sudah disetujui)')
                        ->default(true)
                        ->dehydrated(true)
                        ->required(),

                    Select::make('worker_id')
                        ->relationship('worker', 'worker_name')
                        ->label('Karyawan')
                        ->required(),

                    Select::make('leave_type_id')
                        ->relationship('leaveType', 'leave_type_name')
                        ->label('Jenis Cuti')
                        ->required(),

                    DatePicker::make('leave_date_from')
                        ->label('Mulai')
                        ->required(),

                    DatePicker::make('leave_date_to')
                        ->label('Selesai')
                        ->required(),

                    Textarea::make('leave_reason')
                        ->label('Keterangan')
                        ->required()
                        ->columnSpanFull(),
                ])
                ->action(function (array $data) {

                    DB::transaction(function () use ($data) {

                        $workerId = $data['worker_id'];

                        $isManual = !empty($data['is_manual']);

                        $status        = 'pending';
                        $currentLevel  = ApprovalHelper::getFirstApprovalLevel($workerId);
                        $finalApprover = null;
                        $remarks       = null;
                        $approvedAt    = null;
                        $approvedBy    = null;

                        $isFieldBreak = static::isFieldBreak($data['leave_type_id']);
                        $totalLeave = $isFieldBreak 
                            ? DateHelper::daysBetween($data['leave_date_from'], $data['leave_date_to'])
                            : DateHelper::workdaysBetween($data['leave_date_from'], $data['leave_date_to']);

                        $returnDate = DateHelper::nextWorkday($data['leave_date_to']);

                        // MODE MANUAL
                        if ($isManual) {
                            $status        = 'approved';
                            $currentLevel  = 0;
                            $finalApprover = auth()->id();
                            $approvedAt    = now();
                            $approvedBy    = auth()->id();
                            $remarks       = $data['remarks'] ?? 'Input manual oleh admin — dianggap sudah disetujui.';
                        } else {
                            $remarks = 'Pengajuan melalui sistem — menunggu persetujuan.';
                        }

                        $leave = LeavePermission::create([
                            'worker_id'              => $workerId,
                            'leave_type_id'          => $data['leave_type_id'],
                            'leave_date_from'        => $data['leave_date_from'],
                            'leave_date_to'          => $data['leave_date_to'],
                            'return_to_work_date'    => $returnDate,
                            'leave_reason'           => $data['leave_reason'],
                            'total_leave'            => $totalLeave,
                            'status'                 => $status,
                            'current_level'          => $currentLevel,
                            'final_approver'         => $finalApprover,
                            'is_manual'              => $isManual,
                            'remarks'                => $remarks,
                            'approved_at'            => $approvedAt,
                            'approved_by'            => $approvedBy,
                            'created_by'             => auth()->id(),
                            'updated_by'             => auth()->id(),
                        ]);

                        /**
                         * ⭐ UPDATE BALANCE + LOG
                         * hanya jika manual & langsung approved
                         */
                        if ($isManual && $status === 'approved') {

                            $leaveYear = Carbon::parse($data['leave_date_from'])->year;
                            $balance = EnsureLeaveBalanceForYear::handle(
                                workerId: $workerId,
                                year: $leaveYear,
                                actorUserId: auth()->id(),
                                note: 'Auto-generate saldo untuk histori manual'
                            );

                            $oldHak   = $balance->hak_cuti;
                            $oldSisa  = $balance->sisa_cuti;
                            $oldUtang = $balance->utang_cuti;

                            $sisaSesudah = $oldSisa - $totalLeave;

                            if ($sisaSesudah < 0) {
                                $balance->sisa_cuti  = 0;
                                $balance->utang_cuti = $oldUtang;
                            } else {
                                $balance->sisa_cuti = $sisaSesudah;
                            }

                            $balance->save();

                        }
                    });

                    \Filament\Notifications\Notification::make()
                        ->title('Berhasil')
                        ->body('Cuti berhasil disimpan & saldo cuti diperbarui.')
                        ->success()
                        ->send();
                }),
        ]);
    }

    protected static function normalizeFieldBreakData(array $data): array
{
    // Pastikan key selalu ada (PENTING)
    $data['onsite_date_from'] = $data['onsite_date_from'] ?? null;
    $data['onsite_date_to']   = $data['onsite_date_to'] ?? null;

    // Jika FIELD BREAK
    if (
        isset($data['leave_type_id']) &&
        static::isFieldBreak($data['leave_type_id'])
    ) {
        if ($data['onsite_date_from'] && $data['onsite_date_to']) {

            $onsiteDays = DateHelper::daysBetween(
                $data['onsite_date_from'],
                $data['onsite_date_to']
            );

            $data['onsite_total_days'] = max(1, $onsiteDays);
            $data['earned_leave']      = intdiv($data['onsite_total_days'], 7);

        } else {
            $data['onsite_total_days'] = 0;
            $data['earned_leave']      = 0;
        }

    } else {
        // Bukan field break → bersihkan
        $data['onsite_date_from']  = null;
        $data['onsite_date_to']    = null;
        $data['onsite_total_days'] = null;
        $data['earned_leave']      = 0;
        $data['adjusted_earned_leave'] = null;
        $data['adjustment_note'] = null;
    }

    return $data;
}
    // 🔹 Helper untuk hitung hari kerja cuti
    public static function updateTotalLeave(Set $set, Get $get): void
    {
        $start = $get('leave_date_from');
        $end   = $get('leave_date_to');
        $isFieldBreak = static::isFieldBreak($get('leave_type_id'));
        $set('total_leave', $start && $end ? ($isFieldBreak ? DateHelper::daysBetween($start, $end) : DateHelper::workdaysBetween($start, $end)) : 0);
    }

    // 🔹 Helper untuk mendeteksi Field Break
    public static function isFieldBreak($leaveTypeId): bool
{
    if (!$leaveTypeId) return false;

    $type = \App\Models\LeaveType::find($leaveTypeId);

    return $type && strtolower($type->leave_type_name ?? '') === 'field break';
}


    public static function afterSave($record): void
    {
        // Recompute return_to_work_date server-side to ensure DB matches UI logic
        $returnDate = $record->leave_date_to
        ? DateHelper::nextWorkday($record->leave_date_to)
        : null;


        $record->updateQuietly([
            'updated_by' => Auth::id(),
            'total_leave' => $record->total_leave ?? 0,
            'return_to_work_date' => $returnDate,
            'onsite_total_days' => $record->onsite_total_days,
        ]);
    }

    protected static function updateComputedDates(Set $set, Get $get): void
{
    // =========================
    // TOTAL CUTI (hari kerja)
    // =========================
    $leaveFrom = $get('leave_date_from');
    $leaveTo   = $get('leave_date_to');

    $isFieldBreak = static::isFieldBreak($get('leave_type_id'));
    $totalLeave = $leaveFrom && $leaveTo
        ? ($isFieldBreak ? DateHelper::daysBetween($leaveFrom, $leaveTo) : DateHelper::workdaysBetween($leaveFrom, $leaveTo))
        : 0;

    $set('total_leave', $totalLeave);

    // =========================
    // ONSITE (khusus Field Break)
    // =========================
    if (static::isFieldBreak($get('leave_type_id'))) {
        $onsiteFrom = $get('onsite_date_from');
        $onsiteTo   = $get('onsite_date_to');

        $onsiteDays = $onsiteFrom && $onsiteTo
            ? max(1, DateHelper::daysBetween($onsiteFrom, $onsiteTo))
            : 0;

        $earnedLeave = intdiv($onsiteDays, 7);

        $set('onsite_total_days', $onsiteDays);
        $set('earned_leave', $earnedLeave);
    }

    // =========================
    // RETURN TO WORK
    // SELALU DARI CUTI
    // =========================
    if ($leaveTo) {
        $set('return_to_work_date', DateHelper::nextWorkday($leaveTo));
    }
}

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $user = \Illuminate\Support\Facades\Auth::user();

        // Jika user memiliki relasi worker (bukan admin/HR)
        if ($user && $user->worker) {
            $query->where('worker_id', $user?->getWorkerId());
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListLeaves::route('/'),
            'create' => Pages\CreateLeave::route('/create'),
            'edit'   => Pages\EditLeave::route('/{record}/edit'),
        ];
    }

    public static function getFirstApprovalLevel($workerId)
    {
        $worker = \App\Models\Worker::with('department')->find($workerId);

        if (!$worker || !$worker->department) {
            return 1; // fallback, tapi kondisi ini seharusnya tidak terjadi
        }

        return \App\Models\DepartmentApprover::where('department_id', $worker->department_id)
            ->orderBy('level', 'ASC')
            ->value('level') ?? 1;
    }
}
