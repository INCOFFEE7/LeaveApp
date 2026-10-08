<?php
namespace App\Services;

use App\Models\LeaveBalance;
use App\Models\Worker;
use App\Models\LeaveBalanceLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EnsureLeaveBalanceForYear
{
    /**
     * Pastikan saldo cuti tahun tertentu tersedia
     * dan kurangi sisa cuti tahun ini sesuai utang sebelumnya
     */
    public static function handle(
        int $workerId,
        int $year,
        ?int $actorUserId = null,
        string $note = 'Auto generate saldo cuti',
    ): LeaveBalance {
        return DB::transaction(function () use ($workerId, $year, $actorUserId, $note) {

            // Ambil saldo tahun ini
            $balance = LeaveBalance::where('worker_id', $workerId)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if ($balance) {
                // Sinkronisasi dengan log terakhir
                $lastLog = LeaveBalanceLog::where('leave_balance_id', $balance->id)
                    ->orderByDesc('id')
                    ->first();

                if ($lastLog) {
                    $balance->update([
                        'sisa_cuti'  => $lastLog->new_sisa_cuti,
                        'utang_cuti' => $lastLog->new_utang_cuti,
                    ]);
                }

                return $balance;
            }

            // Tidak ada saldo → buat baru
            $worker = Worker::findOrFail($workerId);
            $doh = Carbon::parse($worker->date_of_hire);

            $hakCuti = 12;

            // Ambil utang tahun sebelumnya
            $prevBalance = LeaveBalance::where('worker_id', $workerId)
                ->where('year', $year - 1)
                ->first();

            $prevUtang = $prevBalance?->utang_cuti ?? 0;

            // Hitung sisa cuti tahun ini setelah bayar utang
            $sisa = max(0, $hakCuti - $prevUtang);
            $utang = max(0, $prevUtang - $hakCuti);

            $balance = LeaveBalance::create([
                'worker_id'  => $workerId,
                'year'       => $year,
                'hak_cuti'   => $hakCuti,
                'sisa_cuti'  => $sisa,
                'utang_cuti' => $utang,
                'reset_at'   => Carbon::create($year, $doh->month, $doh->day),
                'note'       => $note,
            ]);

            // Catat pembayaran utang tahun sebelumnya jika ada
            if ($prevUtang > 0 && $sisa > 0) {
                $dibayar = min($prevUtang, $sisa);

                $balance->update(['sisa_cuti' => $sisa - $dibayar]);
                $prevBalance?->update(['utang_cuti' => $prevUtang - $dibayar]);

                LeaveBalanceLog::create([
                    'leave_balance_id' => $balance->id,
                    'user_id'          => $actorUserId,
                    'leave_year'       => $year,
                    'old_sisa_cuti'    => $sisa,
                    'new_sisa_cuti'    => $sisa - $dibayar,
                    'old_utang_cuti'   => $prevUtang,
                    'new_utang_cuti'   => $prevUtang - $dibayar,
                    'note'             => "Pembayaran {$dibayar} hari utang tahun " . ($year - 1),
                ]);
            }

            return $balance;
        });
    }
}
