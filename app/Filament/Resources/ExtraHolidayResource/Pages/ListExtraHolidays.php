<?php

namespace App\Filament\Resources\ExtraHolidayResource\Pages;

use App\Filament\Resources\ExtraHolidayResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExtraHolidays extends ListRecords
{
    protected static string $resource = ExtraHolidayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
