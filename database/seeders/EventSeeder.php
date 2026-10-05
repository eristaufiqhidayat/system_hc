<?php

namespace Database\Seeders;

use App\Models\CateringEvent;
use App\Models\Customer;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $events = [
            // nama, pelanggan, area, pax, hari dari sekarang, jam, tahap, harga/pax
            ['Syukuran kantor', 'Kantor G', 'BSD', 150, 4, '12.00', 0, 65000],
            ['Arisan keluarga', 'Ibu Nurul', 'Bintaro', 40, 13, '11.00', 0, 60000],
            ['Pernikahan (akad)', 'Kel. Santoso', 'Karawaci', 300, 26, '10.00', 1, 95000],
            ['Ulang tahun 60', 'Bpk. Hendra', 'Alam Sutera', 120, 4, '18.00', 2, 85000],
            ['Gathering karyawan', 'Pabrik E', 'Cikupa', 250, 1, '12.00', 3, 70000],
            ['Seminar kesehatan', 'RS B', 'Bintaro', 80, -3, '09.00', 4, 55000],
        ];

        foreach ($events as [$name, $cust, $area, $pax, $offset, $time, $stage, $price]) {
            $customer = Customer::where('name', $cust)->firstOrFail();

            $event = CateringEvent::updateOrCreate(['name' => $name, 'customer_id' => $customer->id], [
                'venue_area' => $area,
                'pax' => $pax,
                'event_date' => today()->addDays($offset),
                'event_time' => $time,
                'stage' => $stage,
                'price_per_pax' => $price,
                'equipment_cost' => $pax >= 100 ? 2500000 : 1000000,
                'dp_received' => $stage >= 2,
                'menu' => CateringEvent::DEFAULT_MENU,
            ]);

            $event->checklist()->delete();
            foreach (CateringEvent::DEFAULT_CHECKLIST as $i => $label) {
                $event->checklist()->create(['label' => $label, 'done' => $i < ($stage >= 3 ? 5 : 2), 'sort' => $i]);
            }
        }
    }
}
