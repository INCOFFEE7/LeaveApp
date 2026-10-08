<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Register custom Artisan commands.
     * Note: define in the $commands property to avoid calling commands() recursively.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\ReportAndFixLeaderIds::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        $schedule->command('leave-balance:reset')->dailyAt('01:00');
        $schedule->command('holiday:sync')->yearlyOn(1, 1, '01:00');
        $schedule->command('holiday:sync')->monthlyOn(1, '02:00');
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
