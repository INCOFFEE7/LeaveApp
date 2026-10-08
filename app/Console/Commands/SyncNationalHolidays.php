<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\NationalHoliday;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use ICal\ICal;

class SyncNationalHolidays extends Command
{
    protected $signature = 'holiday:sync {year?}';
    protected $description = 'Sinkronisasi libur nasional dari Google Calendar';

    public function handle()
    {
        $year = $this->argument('year') ?? now()->year;

        $this->info("Sinkronisasi libur nasional untuk tahun $year...");

        $icalUrl = 'https://calendar.google.com/calendar/ical/en.indonesian%23holiday%40group.v.calendar.google.com/public/basic.ics';

        try {
            $ical = new ICal($icalUrl, [
                'defaultTimeZone' => 'Asia/Jakarta',
            ]);

            $events = $ical->eventsFromRange(Carbon::parse("$year-01-01"), Carbon::parse("$year-12-31"));

            $count = 0;
            foreach ($events as $event) {
                $date = Carbon::parse($event->dtstart)->toDateString();
                $name = $event->summary;

                NationalHoliday::updateOrCreate(
                    ['date' => $date],
                    ['name' => $name]
                );

                $count++;
            }

            $this->info("Selesai: $count hari libur disinkronisasi.");

        } catch (\Throwable $e) {
            $this->error("Gagal sinkronisasi: " . $e->getMessage());
        }
    }
}
