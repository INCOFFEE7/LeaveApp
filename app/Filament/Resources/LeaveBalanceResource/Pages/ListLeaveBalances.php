<?php

namespace App\Filament\Resources\LeaveBalanceResource\Pages;

use App\Filament\Resources\LeaveBalanceResource;
use App\Models\LeaveBalance;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListLeaveBalances extends ListRecords
{
    protected static string $resource = LeaveBalanceResource::class;

    protected function getTableQuery(): Builder|null
    {
        return LeaveBalance::query()
            ->whereHas('worker.user.roles', function ($query) {
                $query->whereIn('name', [
                    'leader',
                    'department_head',
                    'senior_staff',
                    'employee',
                ]);
            });
    }
}
