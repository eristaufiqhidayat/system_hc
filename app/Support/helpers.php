<?php

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

if (! function_exists('rupiah')) {
    /**
     * Format angka menjadi "Rp 1.250.000".
     */
    function rupiah(int|float|null $amount): string
    {
        return 'Rp '.number_format((float) $amount, 0, ',', '.');
    }
}

if (! function_exists('rupiah_short')) {
    /**
     * Format ringkas untuk KPI: "Rp 241 jt", "Rp 18,4 jt", "Rp 850 rb".
     */
    function rupiah_short(int|float|null $amount): string
    {
        $amount = (float) $amount;
        if ($amount >= 1_000_000_000) {
            return 'Rp '.number_format($amount / 1_000_000_000, 1, ',', '.').' M';
        }
        if ($amount >= 1_000_000) {
            $value = $amount / 1_000_000;

            return 'Rp '.number_format($value, $value >= 100 ? 0 : 1, ',', '.').' jt';
        }
        if ($amount >= 1_000) {
            return 'Rp '.number_format($amount / 1_000, 0, ',', '.').' rb';
        }

        return rupiah($amount);
    }
}

if (! function_exists('qty')) {
    /**
     * Angka dengan pemisah ribuan Indonesia, tanpa desimal bila bulat.
     */
    function qty(int|float|null $value): string
    {
        $value = (float) $value;
        $decimals = floor($value) == $value ? 0 : 1;

        return number_format($value, $decimals, ',', '.');
    }
}

if (! function_exists('month_id')) {
    function month_id(int $month, bool $short = true): string
    {
        $long = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $abbr = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        return $short ? $abbr[$month] : $long[$month];
    }
}

if (! function_exists('day_id')) {
    function day_id(CarbonInterface $date, bool $short = false): string
    {
        $long = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $abbr = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

        return ($short ? $abbr : $long)[$date->dayOfWeek];
    }
}

if (! function_exists('date_id')) {
    /**
     * "29 Sep", atau "29 Sep 2026" bila $withYear.
     */
    function date_id(?CarbonInterface $date, bool $withYear = false): string
    {
        if (! $date) {
            return '—';
        }

        return $date->day.' '.month_id($date->month).($withYear ? ' '.$date->year : '');
    }
}

if (! function_exists('date_long_id')) {
    /**
     * "Selasa, 29 September 2026".
     */
    function date_long_id(CarbonInterface $date): string
    {
        return day_id($date).', '.$date->day.' '.month_id($date->month, false).' '.$date->year;
    }
}

if (! function_exists('date_range_id')) {
    /**
     * "1–31 Okt" atau "28 Sep–2 Okt".
     */
    function date_range_id(CarbonInterface $from, CarbonInterface $to): string
    {
        if ($from->month === $to->month) {
            return $from->day.'–'.$to->day.' '.month_id($to->month);
        }

        return date_id($from).'–'.date_id($to);
    }
}

if (! function_exists('working_days_after')) {
    /**
     * Tanggal setelah $days hari kerja (Senin–Jumat) dari $from.
     */
    function working_days_after(CarbonInterface $from, int $days): Carbon
    {
        $date = Carbon::parse($from);
        while ($date->isWeekend()) {
            $date->addDay();
        }
        while ($days > 0) {
            $date->addDay();
            if (! $date->isWeekend()) {
                $days--;
            }
        }

        return $date;
    }
}

if (! function_exists('initials')) {
    function initials(string $name): string
    {
        $words = preg_split('/\s+/', trim(preg_replace('/[^\pL ]/u', '', $name))) ?: [];
        $letters = array_map(fn ($w) => mb_substr($w, 0, 1), array_filter($words));

        return mb_strtoupper(implode('', array_slice($letters, 0, 2))) ?: '?';
    }
}
