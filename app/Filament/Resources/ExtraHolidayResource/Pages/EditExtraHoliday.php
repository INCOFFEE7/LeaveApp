<?php

namespace App\Filament\Resources\ExtraHolidayResource\Pages;

use App\Filament\Resources\ExtraHolidayResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExtraHoliday extends EditRecord
{
    protected static string $resource = ExtraHolidayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
