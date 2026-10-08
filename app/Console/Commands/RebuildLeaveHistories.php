<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\{
    Worker,
    LeavePermission,
    LeaveBalance,
    LeaveBalanceLog
};
use App\Services\EnsureLeaveBalanceForYear;
use App\Helpers\DateHelper;

class RebuildLeaveHistories extends Command
{
    protected $signature = 'leave:rebuild-histories
        {--force : Hapus saldo & log lama lalu rebuild ulang}';

    protected $description = 'Rebuild saldo cuti historis berdasarkan data cuti (AMAN & TERKONTROL)';

    public function handle(): int
    {
        $force = $this->option('force');

        $this->info('🔄 Rebuild saldo cuti historis');
        $this->info($force
            ? '⚠️ MODE FORCE: saldo & log lama akan DIHAPUS'
            : '🧪 DRY RUN: tidak ada perubahan data'
        );

        DB::transaction(function () use ($force) {

            $workers = Worker::with('leavePermissions')->get();

            foreach ($workers as $worker) {

                $this->line("👤 Worker: {$worker->worker_name}");

                /**
                 * =========================
                 * Tentukan tahun awal
                 * =========================
                 */
                $dohYear = Carbon::parse($worker->date_of_hire)->year;

                $minLeaveYear = LeavePermission::where('worker_id', $worker->id)
                    ->whereNotNull('leave_year')
                    ->min('leave_year');

                $startYear = min(
                    $dohYear,
                    $minLeaveYear ?? $dohYear
                );

                for ($year = $startYear; $year <= now()->year; $year++) {
                    EnsureLeaveBalanceForYear::handle(...);
                }

                $this->line("  ▶ Mulai dari tahun {$startYear}");

                /**
                 * =========================
                 * FORCE MODE → HAPUS DATA
                 * =========================
                 */
                if ($force) {
                    LeaveBalanceLog::whereHas('leaveBalance', fn ($q) =>
                        $q->where('worker_id', $worker->id)
                    )->delete();

                    LeaveBalance::where('worker_id', $worker->id)->delete();
                }

                /**
                 * =========================
                 * Ambil histori cuti VALID
                 * =========================
                 */
                $leaves = LeavePermission::where('worker_id', $worker->id)
                    ->where(function ($q) {
                        $q->where('status', 'approved')
                          ->orWhere('is_manual', true);
                    })
                    ->orderBy('leave_date_from')
                    ->get();

                foreach ($leaves as $leave) {

                    $year = $leave->leave_year
                        ?? Carbon::parse($leave->leave_date_from)->year;

                    $this->line(
                        "    📝 " . Carbon::parse($leave->leave_date_from)->format('Y-m-d')
                        . " {$leave->total_leave} hari "
                        . "[{$year}] "
                        . ($leave->is_manual ? 'MANUAL' : 'APPROVED')
                    );

                    if (! $force) {
                        continue; // DRY RUN
                    }

                    /**
                     * =========================
                     * ENSURE SALDO TAHUNAN
                     * =========================
                     */
                    $balance = EnsureLeaveBalanceForYear::handle(
                        workerId: $worker->id,
                        year: $year,
                        actorUserId: null,
                        note: 'Rebuild histori cuti'
                    );

                    $beforeSisa  = $balance->sisa_cuti;
                    $beforeUtang = $balance->utang_cuti;

                    /**
                     * =========================
                     * KURANGI SALDO
                     * =========================
                     */
                    $afterSisa = $beforeSisa - $leave->total_leave;

                    if ($afterSisa >= 0) {
                        $balance->sisa_cuti = $afterSisa;
                    } else {
                        $balance->sisa_cuti  = 0;
                        $balance->utang_cuti = $beforeUtang + abs($afterSisa);
                    }

                    $balance->save();

                    /**
                     * =========================
                     * LOG SALDO
                     * =========================
                     */
                    LeaveBalanceLog::create([
                        'leave_balance_id' => $balance->id,
                        'change_type'      => 'rebuild',
                        'old_sisa_cuti'    => $beforeSisa,
                        'new_sisa_cuti'    => $balance->sisa_cuti,
                        'old_utang_cuti'   => $beforeUtang,
                        'new_utang_cuti'   => $balance->utang_cuti,
                        'note'             => $leave->is_manual
                            ? 'Rebuild cuti manual'
                            : 'Rebuild cuti approved',
                        'actor_user_id'    => null,
                    ]);
                }
            }
        });

        $this->info('✅ Rebuild saldo cuti selesai');
        return self::SUCCESS;
    }
}
