<?php

namespace App\Filament\Resources\LeaveBalanceLogResource\Pages;

use App\Filament\Resources\LeaveBalanceLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLeaveBalanceLogs extends ListRecords
{
    protected static string $resource = LeaveBalanceLogResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
