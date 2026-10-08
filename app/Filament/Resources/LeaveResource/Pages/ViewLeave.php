<?php

namespace App\Filament\Resources\LeaveResource\Pages;

use App\Filament\Resources\LeaveResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\Action;

class ViewLeave extends ViewRecord
{
    protected static string $resource = LeaveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_report')
                ->label('Download Laporan PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->visible(fn ($record) => (bool) ($record->latestReport?->report_path))
                ->url(function ($record) {
                    $path = $record->latestReport?->report_path;
                    if (! $path) {
                        return '#';
                    }

                    // Normalisasi backslash ke slash (Windows paths)
                    $state = str_replace('\\', '/', $path);

                    $appPublic = rtrim(str_replace('\\', '/', storage_path('app/public')), '/') . '/';
                    $app = rtrim(str_replace('\\', '/', storage_path('app')), '/') . '/';
                    $publicStorage = rtrim(str_replace('\\', '/', public_path('storage')), '/') . '/';

                    if (str_starts_with($state, $appPublic)) {
                        $relative = substr($state, strlen($appPublic));
                    } elseif (str_starts_with($state, $app)) {
                        $relative = substr($state, strlen($app));
                        $relative = preg_replace('#^public/#', '', $relative);
                    } elseif (str_starts_with($state, $publicStorage)) {
                        $relative = substr($state, strlen($publicStorage));
                    } else {
                        $relative = ltrim($state, '/');
                    }

                    $relative = ltrim(str_replace('\\', '/', $relative), '/');

                    return asset('storage/' . $relative);
                })
                ->openUrlInNewTab(),
        ];
    }
}
