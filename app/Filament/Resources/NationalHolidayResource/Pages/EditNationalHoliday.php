<?php

namespace App\Filament\Resources\NationalHolidayResource\Pages;

use App\Filament\Resources\NationalHolidayResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNationalHoliday extends EditRecord
{
    protected static string $resource = NationalHolidayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
