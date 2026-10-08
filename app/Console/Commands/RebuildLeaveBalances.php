<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Worker;
use App\Services\EnsureLeaveBalanceForYear;
use Carbon\Carbon;

class RebuildLeaveBalances extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'leave:rebuild-balances
        {--from= : Tahun awal (optional)}
        {--to= : Tahun akhir (optional)}';

    /**
     * The console command description.
     */
    protected $description = 'Generate saldo cuti tahunan historis berdasarkan DOH tanpa mengubah saldo existing';

    public function handle(): int
    {
        $fromOption = $this->option('from');
        $toOption   = $this->option('to');

        $today = Carbon::today();
        $defaultTo = $today->year;

        $this->info('🔄 Rebuild saldo cuti tahunan dimulai...');
        $this->line('--------------------------------------');

        $workers = Worker::where('is_active', 1)->get();

        foreach ($workers as $worker) {

            if (!$worker->date_of_hire) {
                $this->warn("⚠️ Worker {$worker->id} tidak punya DOH, dilewati.");
                continue;
            }

            $doh = Carbon::parse($worker->date_of_hire);

            /**
             * Tahun pertama cuti:
             * DOH + 1 tahun
             */
            $firstEligibleYear = $doh->copy()->addYear()->year;

            $fromYear = $fromOption
                ? max((int) $fromOption, $firstEligibleYear)
                : $firstEligibleYear;

            $toYear = $toOption
                ? (int) $toOption
                : $defaultTo;

            if ($fromYear > $toYear) {
                continue;
            }

            $this->line("👤 {$worker->worker_name} | {$fromYear} → {$toYear}");

            for ($year = $fromYear; $year <= $toYear; $year++) {

                EnsureLeaveBalanceForYear::handle(
                    workerId: $worker->id,
                    year: $year,
                    actorUserId: null,
                    note: 'Rebuild saldo cuti historis via artisan'
                );

                $this->line("   ✔ Saldo {$year} dipastikan");
            }
        }

        $this->line('--------------------------------------');
        $this->info('✅ Rebuild saldo cuti selesai.');

        return Command::SUCCESS;
    }
}
