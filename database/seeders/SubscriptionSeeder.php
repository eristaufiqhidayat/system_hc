<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Subscription;
use Illuminate\Database\Seeder;

class SubscriptionSeeder extends Seeder
{
    public function run(): void
    {
        $subs = [
            // pelanggan, paket, porsi, sisa, preferensi, jeda s/d (hari dari sekarang)
            ['Ibu Rina', 'Bulanan', 2, 18, 'Tidak pedas', null],
            ['Kos Melati', 'Mingguan', 6, 1, null, null],
            ['Bpk. Tono', 'Bulanan', 1, 3, 'Nasi sedikit', null],
            ['Ibu Dewi', 'Bulanan', 3, 11, 'Tanpa seafood', null],
            ['Ibu Sari', 'Mingguan', 2, 2, 'Tidak pedas', null],
            ['Bpk. Arif', 'Harian', 1, 1, null, 7],
            ['Keluarga Wijaya', 'Bulanan', 4, 14, 'Anak-anak, tidak pedas', null],
            ['Kos Anggrek', 'Mingguan', 6, 4, null, null],
        ];

        foreach ($subs as [$name, $package, $portions, $left, $pref, $pause]) {
            $customer = Customer::where('name', $name)->firstOrFail();
            $total = Subscription::PACKAGES[$package]['days'];

            Subscription::updateOrCreate(['customer_id' => $customer->id], [
                'package' => $package,
                'total_days' => $total,
                'remaining_days' => $left,
                'portions' => $portions,
                'preference' => $pref,
                'starts_at' => today()->subDays(($total - $left) * 7 / 5),
                'paused_until' => $pause ? today()->addDays($pause) : null,
            ]);
        }
    }
}
