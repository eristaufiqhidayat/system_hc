<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            // nama, segmen, area, sejak, preferensi, nilai
            ['RS B', 'Rumah sakit', 'Bintaro', '2024-02-01', 'Diet pasien per kamar', 'Tinggi'],
            ['Kantor A', 'Kantor', 'Karawaci', '2025-01-15', '24 tidak pedas', 'Tinggi'],
            ['Kantor C', 'Kantor', 'BSD', '2025-04-01', '12 tidak pedas', 'Tinggi'],
            ['Klinik D', 'Klinik', 'Ciputat', '2025-06-01', 'Diet harian', 'Tinggi'],
            ['Pabrik E', 'Pabrik', 'Cikupa', '2025-03-01', '15 tidak pedas', 'Tinggi'],
            ['Sekolah F', 'Sekolah', 'Bintaro', '2026-09-20', 'Menu anak', 'Sedang'],
            ['Kantor G', 'Kantor', 'BSD', '2026-09-25', null, 'Sedang'],
            ['Ibu Rina', 'Rantangan', 'Bintaro Sektor 3', '2026-03-01', 'Tidak pedas', 'Sedang'],
            ['Bpk. Hendra', 'Event', 'Alam Sutera', '2026-09-10', null, 'Sedang'],
            ['Keluarga Wijaya', 'Rantangan', 'Bintaro Sektor 7', '2026-01-10', 'Anak-anak, tidak pedas', 'Sedang'],
            ['Ibu Maya', 'Nasi box', 'Bintaro Sektor 9', '2026-07-05', null, 'Rendah'],
            ['Kos Melati', 'Rantangan', 'Ciputat', '2026-08-01', null, 'Sedang'],
            ['Bpk. Tono', 'Rantangan', 'Alam Sutera', '2026-05-12', 'Nasi sedikit', 'Sedang'],
            ['Ibu Dewi', 'Rantangan', 'The Green BSD', '2026-04-20', 'Tanpa seafood', 'Sedang'],
            ['Ibu Sari', 'Rantangan', 'BSD', '2026-08-18', 'Tidak pedas', 'Sedang'],
            ['Bpk. Arif', 'Rantangan', 'Pondok Aren', '2026-06-01', null, 'Rendah'],
            ['Ibu Lestari', 'Nasi box', 'Graha Raya', '2026-09-01', null, 'Rendah'],
            ['Ibu Nurul', 'Event', 'Bintaro', '2026-09-27', null, 'Rendah'],
            ['Kel. Santoso', 'Event', 'Karawaci', '2026-09-15', null, 'Sedang'],
            ['Kos Anggrek', 'Rantangan', 'BSD Sektor 1', '2026-09-02', null, 'Sedang'],
        ];

        foreach ($customers as $i => [$name, $segment, $area, $since, $pref, $value]) {
            Customer::updateOrCreate(['name' => $name], [
                'segment' => $segment,
                'area' => $area,
                'address' => 'Jl. Contoh No. '.($i + 1).', '.$area,
                'whatsapp' => sprintf('0813-%04d-%04d', 1000 + $i, 2000 + $i),
                'preference' => $pref,
                'customer_since' => $since,
                'value_tier' => $value,
            ]);
        }
    }
}
