<?php

namespace App\Filament\Resources\LeaveBalanceResource\Actions;

use App\Models\LeaveBalance;
use Filament\Tables\Actions\Action;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportLeaveBalances
{
    public static function make(): Action
    {
        return Action::make('exportCsv')
            ->label('Export CSV')
            ->icon('heroicon-o-arrow-down-tray')
            ->action(function (): StreamedResponse {
                $filename = 'leave_balances_export_' . now()->format('Ymd_His') . '.csv';

                return response()->streamDownload(function () {
                    $writer = SimpleExcelWriter::streamDownload('leave_balances.csv');

                    LeaveBalance::with('worker')->chunk(100, function ($balances) use ($writer) {
                        foreach ($balances as $balance) {
                            $writer->addRow([
                                'Nama Karyawan' => $balance->worker->worker_name ?? '-',
                                'Tahun' => $balance->year,
                                'Hak Cuti' => $balance->hak_cuti,
                                'Sisa Cuti' => $balance->sisa_cuti,
                                'Carry Over' => $balance->carry_over,
                                'Reset Pada' => optional($balance->reset_at)->format('Y-m-d'),
                            ]);
                        }
                    });

                    $writer->close();
                }, $filename);
            });
    }
}
