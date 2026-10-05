<?php

namespace Tests\Unit;

use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    public function test_rupiah_formats(): void
    {
        $this->assertSame('Rp 1.250.000', rupiah(1250000));
        $this->assertSame('Rp 241 jt', rupiah_short(241_000_000));
        $this->assertSame('Rp 18,4 jt', rupiah_short(18_400_000));
    }

    public function test_dates_in_indonesian(): void
    {
        $date = Carbon::create(2026, 9, 29);
        $this->assertSame('29 Sep', date_id($date));
        $this->assertSame('Selasa, 29 September 2026', date_long_id($date));
        $this->assertSame('1–31 Okt', date_range_id(Carbon::create(2026, 10, 1), Carbon::create(2026, 10, 31)));
    }

    public function test_initials(): void
    {
        $this->assertSame('CH', initials('Chef Hady'));
        $this->assertSame('BH', initials('Bpk. Hendra'));
    }
}
