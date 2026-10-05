<?php

namespace Database\Seeders;

use App\Models\DeliveryRoute;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;

class DeliverySeeder extends Seeder
{
    public function run(): void
    {
        DeliveryRoute::query()->delete();

        $routes = [
            ['Bintaro Sektor 9', 'Andi', 12, 12, '#1B7A37', [[18, 62], [25, 55], [31, 58], [36, 49], [42, 52]], false, null],
            ['BSD · Alam Sutera', 'Rudi', 15, 9, '#1F4468', [[18, 62], [14, 48], [10, 38], [16, 30], [8, 22]], false, null],
            ['Karawaci · Cikokol', 'Dede', 14, 10, '#7E4C00', [[18, 62], [30, 40], [44, 30], [58, 22], [66, 16]], false, null],
            ['Ciputat · Pondok Aren', 'Yoga', 11, 3, '#B0302A', [[18, 62], [40, 66], [58, 72], [72, 68], [86, 76]], true, 'Ciputat · macet Jl. Ceger'],
        ];

        $bsdStops = [
            ['Kantor C · Lobi lt. 1', 'Jl. BSD Raya Utama, BSD City', ['150 box', '12 tidak pedas', 'Invoice'], 'Serahkan ke resepsionis, minta tanda tangan.', 'Kontrak', false, 'HC-2285'],
            ['Ibu Sari · Rantangan', 'Cluster Anggrek, BSD', ['2 porsi', 'Tidak pedas'], 'Titip satpam bila tidak ada orang.', 'Lunas', false, null],
            ['Bpk. Tono · Rantangan', 'Alam Sutera', ['1 porsi', 'Ambil rantang kemarin'], 'Telepon saat sampai.', 'Lunas', false, null],
            ['Kos Anggrek', 'BSD Sektor 1', ['6 porsi'], 'Tagih di tempat via QRIS.', 'Tagih', true, null],
            ['Ibu Dewi · Rantangan', 'The Green BSD', ['3 porsi', 'Tanpa seafood'], 'Rantang kemarin diambil kembali.', 'Lunas', false, null],
            ['Kantor G · Lt. 3', 'BSD Green Office Park', ['40 box'], 'Naik lift barang.', 'Kontrak', false, null],
        ];

        foreach ($routes as $r => [$area, $courier, $total, $done, $color, $points, $late, $reason]) {
            $route = DeliveryRoute::create([
                'route_date' => today(),
                'area' => $area,
                'courier_id' => User::where('name', $courier)->value('id'),
                'color' => $color,
                'map_points' => $points,
                'is_late' => $late,
                'late_reason' => $reason,
            ]);

            for ($i = 1; $i <= $total; $i++) {
                $isDone = $i <= $done;
                $special = $r === 1 && $i > $done ? ($bsdStops[$i - $done - 1] ?? null) : null;
                [$name, $address, $tags, $note, $pay, $collect, $orderCode] = $special
                    ?? ['Pelanggan '.$area.' #'.$i, $area, [rand(1, 4).' porsi'], null, 'Lunas', false, null];

                $route->stops()->create([
                    'order_id' => $orderCode ? Order::where('code', $orderCode)->value('id') : null,
                    'sequence' => $i,
                    'name' => $name,
                    'address' => $address,
                    'distance_km' => round(2 + $i * 0.8 + ($i % 3) * 0.3, 2),
                    'tags' => $tags,
                    'note' => $note,
                    'payment_label' => $pay,
                    'collect_payment' => $collect,
                    'eta' => $isDone ? null : sprintf('%02d.%02d', 11 + intdiv(($i - $done) * 15 + 5, 60), (($i - $done) * 15 + 5) % 60),
                    'delivered_at' => $isDone ? today()->setTime(10, 30)->addMinutes($i * 6) : null,
                ]);
            }
        }
    }
}
