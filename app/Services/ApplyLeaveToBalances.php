<?php

namespace App\Services;

use App\Models\LeavePermission;
use App\Models\LeaveBalance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ApplyLeaveToBalances
{
    public static function handle(LeavePermission $leave): void
    {
        DB::transaction(function () use ($leave) {

            $remaining = $leave->total_leave;
            $year = Carbon::parse($leave->leave_date_from)->year;

            while ($remaining > 0) {

                // 1️⃣ Pastikan saldo tahun tersedia
                $balance = EnsureLeaveBalanceForYear::handle(
                    workerId: $leave->worker_id,
                    year: $year,
                    note: "Auto ensure saldo {$year}"
                );

                // 2️⃣ Potong saldo
                if ($balance->sisa_cuti >= $remaining) {
                    $balance->decrement('sisa_cuti', $remaining);
                    break;
                }

                // 3️⃣ Saldo habis → jadi utang
                $remaining -= $balance->sisa_cuti;

                $balance->update([
                    'sisa_cuti' => 0,
                    'utang_cuti' => $balance->utang_cuti + $remaining,
                ]);

                $remaining = 0;
            }
        });
    }
}
