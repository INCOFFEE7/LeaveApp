<?php

namespace App\Filament\Resources\LeaderAssignmentResource\Pages;

use App\Filament\Resources\LeaderAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLeaderAssignments extends ListRecords
{
    protected static string $resource = LeaderAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
