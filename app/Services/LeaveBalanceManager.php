<?php

namespace App\Services;

use App\Models\Worker;
use App\Models\LeaveBalance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LeaveBalanceManager
{
    public function syncByDOH(): void
    {
        $today = now()->startOfDay();

        $workers = Worker::where('is_active', 1)
            ->whereDate(DB::raw("DATE_FORMAT(date_of_hire, '%m-%d')"), '=', $today->format('m-d'))
            ->get();

        foreach ($workers as $worker) {
            $doh = Carbon::parse($worker->date_of_hire);
            $yearsWorked = $doh->diffInYears($today);

            if ($yearsWorked < 1) {
                continue; // belum genap 1 tahun
            }

            $leaveYear = $doh->copy()->addYears($yearsWorked)->year;

            $alreadyExists = LeaveBalance::where('worker_id', $worker->id)
                ->where('year', $leaveYear)
                ->exists();

            if ($alreadyExists) {
                continue; // Sudah dibuat tahun ini
            }

            $previous = LeaveBalance::where('worker_id', $worker->id)
                ->orderByDesc('year')
                ->first();

            LeaveBalance::create([
                'worker_id' => $worker->id,
                'year' => $leaveYear,
                'hak_cuti' => 12,
                'sisa_cuti' => 12,
                'utang_cuti' => $previous ? $previous->sisa_cuti : 0,
                'reset_at' => $doh->copy()->addYears($yearsWorked),
            ]);
        }
    }
}
