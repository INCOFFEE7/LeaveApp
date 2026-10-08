<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('import')
            ->label('Import Data')
            ->icon('heroicon-o-arrow-up-tray')
            ->button()
            ->modalHeading('Import Data Karyawan')
            ->modalDescription('Unggah file Excel (.xlsx) yang berisi data karyawan.')
            ->form([
                \Filament\Forms\Components\FileUpload::make('file')
                    ->label('Pilih File Excel')
                    ->disk('public')
                    ->directory('imports/users')
                    ->required()
                    ->acceptedFileTypes([
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                    ]),
            ])
            ->action(function (array $data): void {
                $filePath = \Illuminate\Support\Facades\Storage::disk('public')->path($data['file']);
                \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\UserWorkerImport, $filePath);

                \Illuminate\Support\Facades\Cache::forget('department_options');
                \Illuminate\Support\Facades\Cache::forget('role_options');
                \Illuminate\Support\Facades\Cache::forget('company_options');
                \Illuminate\Support\Facades\Cache::forget('cabang_options');

                \Filament\Notifications\Notification::make()
                    ->title('Import berhasil')
                    ->success()
                    ->send();
            }),
            // Tombol default Filament (create)
            Actions\CreateAction::make()
            ->color('success'),
        ];
    }
}
