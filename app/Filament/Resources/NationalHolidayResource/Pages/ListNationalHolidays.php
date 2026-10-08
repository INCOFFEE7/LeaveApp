<?php

namespace App\Filament\Resources\NationalHolidayResource\Pages;

use App\Filament\Resources\NationalHolidayResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNationalHolidays extends ListRecords
{
    protected static string $resource = NationalHolidayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

        ];
    }
}
