<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Filament\Notifications\Notification;
use App\Models\User;
use App\Models\Worker;
use App\Models\LeaveBalance;
use App\Models\LeaveBalanceLog;
use Carbon\Carbon;

class ResetLeaveBalanceCommand extends Command
{
    protected $signature = 'leave-balance:reset {--all : Reset saldo cuti untuk semua karyawan aktif}';
    protected $description = 'Reset cuti tahunan berdasarkan DOH dengan kebijakan hak cuti bertingkat dan kirim notifikasi.';

    public function handle()
    {
        $today = now();
        $isResetAll = $this->option('all');

        $this->info(
            $isResetAll
                ? 'Menjalankan reset saldo cuti untuk SEMUA karyawan aktif'
                : "Menjalankan reset saldo cuti untuk DOH: {$today->format('d M')}"
        );

        // ===================================================
        // Ambil worker aktif
        // ===================================================
        $workersQuery = Worker::where('is_active', 1)->with('user');

        if (! $isResetAll) {
            $workersQuery
                ->whereMonth('date_of_hire', $today->month)
                ->whereDay('date_of_hire', $today->day);
        }

        $workers = $workersQuery->get();

        if ($workers->isEmpty()) {
            $this->info('Tidak ada karyawan yang memenuhi kriteria reset.');
            return Command::SUCCESS;
        }

        $totalReset = 0;
        $resetList = [];

        // ===================================================
        // Proses reset per worker
        // ===================================================
        foreach ($workers as $worker) {
            $this->info("Memproses reset untuk {$worker->worker_name}");

            $lastYear = $today->year - 1;

            $previousBalance = LeaveBalance::where('worker_id', $worker->id)
                ->where('year', $lastYear)
                ->first();

            $oldHak   = $previousBalance?->hak_cuti ?? 0;
            $oldSisa  = $previousBalance?->sisa_cuti ?? 0;
            $oldUtang = $previousBalance?->utang_cuti ?? 0;

            // ===================================================
            // Hitung masa kerja & hak cuti baru
            // ===================================================
            $doh = Carbon::parse($worker->date_of_hire);
            $yearsOfService = $doh->diffInYears($today);

            $annualQuota = $this->determineAnnualQuota($yearsOfService);

            // Utang lama memotong hak baru
            $hakBaru = max(0, $annualQuota - $oldUtang);

            // Reset date mengikuti DOH (ulang tahun kerja berikutnya)
            $resetAt = Carbon::create($today->year, $doh->month, $doh->day)->addYear();

            // ===================================================
            // Update / create saldo cuti tahun berjalan
            // ===================================================
            $balance = LeaveBalance::updateOrCreate(
                [
                    'worker_id' => $worker->id,
                    'year'      => $today->year,
                ],
                [
                    'hak_cuti'   => $annualQuota,
                    'sisa_cuti'  => $hakBaru,
                    'utang_cuti' => 0,
                    'reset_at'   => $resetAt,
                ]
            );

            // ===================================================
            // Log reset (AUDITABLE)
            // ===================================================
            LeaveBalanceLog::create([
                'leave_balance_id' => $balance->id,
                'user_id'          => 1, // system
                'old_hak_cuti'     => $oldHak,
                'new_hak_cuti'     => $annualQuota,
                'old_sisa_cuti'    => $oldSisa,
                'new_sisa_cuti'    => $hakBaru,
                'old_utang_cuti'   => $oldUtang,
                'new_utang_cuti'   => 0,
                'note'             => "Reset tahunan ({$yearsOfService} tahun masa kerja): hak cuti ditetapkan {$annualQuota} hari."
                    . ($oldUtang > 0 ? " Utang {$oldUtang} hari dipotong." : ''),
            ]);

            // ===================================================
            // Notifikasi ke worker
            // ===================================================
            if ($worker->user && $worker->user->hasPermissionTo('leave.balance.reset.self')) {
                Notification::make()
                    ->title('Saldo Cuti Tahunan Telah Direset')
                    ->body("Saldo cuti Anda tahun {$today->year} adalah {$hakBaru} hari.")
                    ->success()
                    ->sendToDatabase($worker->user);

                if (! empty($worker->user->email)) {
                    Mail::raw(
                        "Halo {$worker->worker_name},\n\n" .
                        "Saldo cuti tahunan Anda telah direset.\n" .
                        "Hak cuti: {$annualQuota} hari\n" .
                        "Sisa cuti: {$hakBaru} hari\n\n" .
                        "HRD PT FSS",
                        function ($message) use ($worker) {
                            $message->to($worker->user->email)
                                ->subject('Saldo Cuti Tahunan Telah Direset');
                        }
                    );
                }
            }

            $resetList[] = "{$worker->worker_name} ({$hakBaru} hari)";
            $totalReset++;
        }

        // ===================================================
        // Notifikasi admin
        // ===================================================
        $adminUsers = User::permission('leave.balance.reset.all')->get();
        $resetSummary = implode("\n", $resetList);

        foreach ($adminUsers as $admin) {
            Notification::make()
                ->title('Proses Reset Saldo Cuti Tahunan Selesai')
                ->body("Total: {$totalReset} karyawan.\n\n{$resetSummary}")
                ->success()
                ->sendToDatabase($admin);

            if (! empty($admin->email)) {
                Mail::raw(
                    "Proses reset saldo cuti tahunan telah selesai.\n\n" .
                    "Total: {$totalReset} karyawan.\n\n{$resetSummary}",
                    function ($message) use ($admin) {
                        $message->to($admin->email)
                            ->subject('Laporan Reset Saldo Cuti Tahunan');
                    }
                );
            }
        }

        $this->info("✅ Reset saldo cuti selesai untuk {$totalReset} karyawan.");
        return Command::SUCCESS;
    }

    // ===================================================
    // Penentuan hak cuti berdasarkan masa kerja
    // ===================================================
    private function determineAnnualQuota(int $yearsOfService): int
    {
        return match (true) {
            $yearsOfService >= 21 => 30,
            $yearsOfService >= 16 => 24,
            $yearsOfService >= 11 => 18,
            $yearsOfService >= 6  => 15,
            default               => 12,
        };
    }
}
