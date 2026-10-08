<?php

namespace App\Observers;

use App\Models\Worker;
use App\Models\LeaveBalance;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;

class WorkerObserver
{
    public function created(Worker $worker): void
    {
        $dateOfHire = Carbon::parse($worker->date_of_hire);

        // Ambil tahun DOH
        $year = Carbon::parse($worker->date_of_hire)->copy()->addYear()->year;

        // Tentukan hak cuti, bisa pakai logika pro-rata jika mau
        $hakCuti = 12; // default quota
        $sisaCuti = $hakCuti;

        LeaveBalance::create([
            'worker_id' => $worker->id,
            'year' => $year,         // Tahun dari date_of_hire
            'hak_cuti' => $hakCuti,
            'sisa_cuti' => $sisaCuti,
            'utang_cuti' => 0,
            'reset_at' => $dateOfHire->copy()->addYears(1),
        ]);
    }
}

