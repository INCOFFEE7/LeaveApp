<?php

namespace App\Filament\Resources\NotificationResource\Pages;

use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\NotificationResource;

class ListNotifications extends ListRecords
{
    protected static string $resource = NotificationResource::class;

    // Tidak perlu override, ini otomatis akan tampil sebagai view-only
}
