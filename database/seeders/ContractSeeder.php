<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ContractSeeder extends Seeder
{
    public function run(): void
    {
        $contracts = [
            [
                'customer' => 'RS B', 'type' => 'Rumah sakit', 'summary' => '296 porsi/hari · 3 shift', 'daily' => 296, 'price' => 26000,
                'deliv' => '06.30 · 11.30 · 17.00, pintu dapur', 'days' => [0, 1, 2, 3, 4, 5, 6], 'start' => '2024-02-01',
                'shifts' => [['Shift pagi · 06.30', '110', 'Pasien rawat inap'], ['Shift siang · 11.30', '130', '96 pasien · 34 staf'], ['Shift malam · 17.00', '56', 'Pasien + jaga malam']],
                'diets' => [['204', 'Rendah garam', 'Tanpa kacang (alergi)', 4, true], ['311', 'Makanan lunak', 'Pasca operasi', 3, true], ['118', 'Diet DM', 'Tanpa gula tambahan, nasi merah', null, false], ['215', 'Cair', 'Bubur saring', 2, false], ['302', 'Rendah lemak', null, null, false]],
            ],
            [
                'customer' => 'Kantor A', 'type' => 'Kantor', 'summary' => '180 porsi/hari · Sen–Jum', 'daily' => 180, 'price' => 24000,
                'deliv' => '11.30, resepsionis lt. 1', 'days' => [1, 2, 3, 4, 5], 'start' => '2025-01-15',
                'shifts' => [['Makan siang · 11.30', '180', 'Senin–Jumat'], ['Tidak pedas', '24', 'Tercatat per karyawan'], ['Vegetarian', '6', 'Menu pengganti otomatis']],
            ],
            [
                'customer' => 'Kantor C', 'type' => 'Kantor', 'summary' => '150 porsi/hari · Sen–Sab', 'daily' => 150, 'price' => 24000,
                'deliv' => '11.30, lobi lt. 1', 'days' => [1, 2, 3, 4, 5, 6], 'start' => '2025-04-01',
                'shifts' => [['Makan siang · 11.30', '150', 'Senin–Sabtu'], ['Tidak pedas', '12', 'Tercatat per karyawan'], ['Tambahan rapat', '20', 'Rata-rata per minggu']],
            ],
            [
                'customer' => 'Klinik D', 'type' => 'Klinik', 'summary' => '40 porsi/hari · 2 shift', 'daily' => 40, 'price' => 26000,
                'deliv' => '11.00 · 17.00', 'days' => [0, 1, 2, 3, 4, 5, 6], 'start' => '2025-06-01',
                'shifts' => [['Shift siang · 11.00', '24', 'Pasien & staf'], ['Shift malam · 17.00', '16', 'Pasien'], ['Diet khusus', '5', 'Diperbarui harian']],
                'diets' => [['03', 'Rendah garam', null, null, false], ['07', 'Lunak', 'Lansia', 5, false]],
            ],
            [
                'customer' => 'Pabrik E', 'type' => 'Pabrik', 'summary' => '210 porsi/hari · 2 shift', 'daily' => 210, 'price' => 22000,
                'deliv' => '11.30 · 19.30, kantin pabrik', 'days' => [1, 2, 3, 4, 5, 6], 'start' => '2025-03-01',
                'shifts' => [['Shift 1 · 11.30', '130', 'Senin–Sabtu'], ['Shift 2 · 19.30', '80', 'Senin–Sabtu'], ['Tidak pedas', '15', 'Tercatat per karyawan']],
            ],
            [
                'customer' => 'Sekolah F', 'type' => 'Sekolah', 'summary' => 'Kontrak baru · mulai 1 Nov', 'daily' => 0, 'price' => 20000,
                'deliv' => '11.45, kantin sekolah', 'days' => [], 'start' => '2026-11-01',
                'shifts' => [['Makan siang · 11.45', '—', 'Mulai 1 Nov'], ['Alergi siswa', '—', 'Diisi pihak sekolah'], ['Menu anak', '—', 'Siklus 4 minggu']],
            ],
        ];

        $from = today()->subMonthNoOverflow()->startOfMonth();
        $to = today()->subDay();

        foreach ($contracts as $c) {
            $customer = Customer::where('name', $c['customer'])->firstOrFail();

            $contract = Contract::updateOrCreate(['customer_id' => $customer->id], [
                'type' => $c['type'],
                'summary' => $c['summary'],
                'daily_portions' => $c['daily'],
                'delivery_info' => $c['deliv'],
                'pic' => 'PIC '.$customer->name.' · 0811-0000-'.str_pad((string) $customer->id, 4, '0', STR_PAD_LEFT),
                'price_per_portion' => $c['price'],
                'payment_term_days' => 10,
                'starts_at' => $c['start'],
                'ends_at' => '2026-12-31',
            ]);

            $contract->shifts()->delete();
            foreach ($c['shifts'] as [$label, $value, $note]) {
                $contract->shifts()->create(compact('label', 'value', 'note'));
            }

            $contract->diets()->delete();
            foreach ($c['diets'] ?? [] as [$room, $type, $note, $until, $new]) {
                $contract->diets()->create([
                    'room' => $room,
                    'diet_type' => $type,
                    'note' => $note,
                    'valid_until' => $until ? today()->addDays($until) : null,
                    'is_new' => $new,
                ]);
            }

            // Riwayat porsi terkirim bulan lalu s/d kemarin untuk rekap tagihan.
            $contract->deliveries()->delete();
            $rows = [];
            for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
                if ($c['daily'] && in_array($d->dayOfWeek, $c['days'], true) && $d->gte(Carbon::parse($c['start']))) {
                    $rows[] = ['delivered_on' => $d->toDateString(), 'portions' => $c['daily'], 'extra_portions' => $d->day % 7 === 0 ? 30 : 0];
                }
            }
            $contract->deliveries()->createMany($rows);
        }
    }
}
