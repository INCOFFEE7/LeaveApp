<?php

namespace App\Helpers;

use Carbon\Carbon;
use App\Models\NationalHoliday;
use App\Models\ExtraHoliday;

class DateHelper
{
    protected static array $cachedHolidays = [];

    /**
     * 🔥 Ambil semua tanggal libur (cached per request)
     */
    protected static function getHolidays(): array
    {
        if (!empty(self::$cachedHolidays)) {
            return self::$cachedHolidays;
        }

        $national = NationalHoliday::pluck('date')->toArray();
        $extra    = ExtraHoliday::pluck('date')->toArray();

        // Ubah menjadi array string 'Y-m-d'
        self::$cachedHolidays = array_unique(array_merge($national, $extra));

        return self::$cachedHolidays;
    }

    /**
     * 🔥 Cek apakah tanggal adalah hari libur / weekend
     */
    public static function isHoliday(Carbon $date): bool
    {
        $holidays = self::getHolidays();

        return $date->isWeekend() || in_array($date->toDateString(), $holidays);
    }

    /**
     * 🔥 Hitung hari kerja (tanpa weekend & libur)
     */
    public static function workdaysBetween($from, $to): int
    {
        $start = Carbon::parse($from);
        $end   = Carbon::parse($to);

        if ($end->lessThan($start)) {
            return 0;
        }

        $count = 0;

        while ($start->lte($end)) {
            if (!self::isHoliday($start)) {
                $count++;
            }
            $start->addDay();
        }

        return $count;
    }

    /**
     * 🔥 Hitung total hari kalender (termasuk weekend & libur)
     */
    public static function daysBetween($from, $to): int
    {
        $start = Carbon::parse($from)->startOfDay();
        $end   = Carbon::parse($to)->startOfDay();

        if ($end->lessThan($start)) {
            return 0;
        }

        return $start->diffInDays($end) + 1;
    }

    /**
     * 🔥 Dapatkan hari kerja berikutnya setelah tanggal tertentu
     */
    public static function nextWorkday($date): string
    {
        $date = Carbon::parse($date)->addDay();

        while (self::isHoliday($date)) {
            $date->addDay();
        }

        return $date->toDateString();
    }

    /**
     * 🔥 Format tanggal dd/mm/yyyy
     */
    public static function formatDate($date): ?string
    {
        if (!$date) return null;
        return Carbon::parse($date)->format('d/m/Y');
    }
}
