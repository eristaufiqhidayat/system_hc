<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $t = today();
        $orders = [
            // kode, pelanggan, lini, item, porsi, mulai, selesai, jam, via, metode, status bayar, status, harga/porsi
            ['HC-2282', 'Ibu Lestari', 'Nasi box', 'Nasi box syukuran', 60, -1, null, '10.00', 'Web', 'QRIS', 'lunas', 'Selesai', 30000],
            ['HC-2283', 'Klinik D', 'Rumah sakit', '2 shift pasien', 40, 0, null, '2 shift', 'Kontrak', 'Invoice', 'invoice', 'Selesai', 0],
            ['HC-2284', 'Bpk. Arif', 'Rantangan', 'Paket harian · 1 porsi', 1, 0, null, 'Siang', 'WA bot', 'QRIS', 'lunas', 'Selesai', 30000],
            ['HC-2285', 'Kantor C', 'Kantor', 'Makan siang karyawan', 150, 0, null, '11.30', 'Kontrak', 'Invoice', 'invoice', 'Dikirim', 0],
            ['HC-2286', 'Ibu Maya', 'Nasi box', 'Nasi box arisan', 35, 0, null, '12.00', 'WA bot', 'Transfer', 'lunas', 'Dikirim', 30000],
            ['HC-2287', 'RS B', 'Rumah sakit', '3 shift pasien & staf', 296, 1, null, '3 shift', 'Kontrak', 'Invoice', 'invoice', 'Diproses', 0],
            ['HC-2288', 'Kos Melati', 'Rantangan', 'Paket mingguan · 6 porsi', 6, 6, 10, 'Siang', 'Web', 'QRIS', 'menunggu', 'Baru', 27500],
            ['HC-2289', 'Bpk. Hendra', 'Event', 'Prasmanan 120 pax', 120, 4, null, '18.00', 'Web', 'Transfer', 'dp', 'Dikonfirmasi', 85000],
            ['HC-2290', 'Kantor A', 'Kantor', 'Makan siang karyawan', 180, 1, null, '11.30', 'Kontrak', 'Invoice', 'invoice', 'Diproses', 0],
            ['HC-2291', 'Ibu Rina', 'Rantangan', 'Paket bulanan · 2 porsi', 2, 2, 32, 'Siang', 'WA bot', 'QRIS', 'lunas', 'Baru', 25000],
        ];

        $steps = Order::STATUSES;

        foreach ($orders as [$code, $cust, $line, $item, $qty, $from, $to, $time, $via, $method, $pay, $status, $price]) {
            $customer = Customer::where('name', $cust)->firstOrFail();
            $order = Order::updateOrCreate(['code' => $code], [
                'customer_id' => $customer->id,
                'business_line' => $line,
                'item' => $item,
                'portions' => $qty,
                'delivery_date' => $t->copy()->addDays($from),
                'delivery_date_end' => $to !== null ? $t->copy()->addDays($to) : null,
                'delivery_time' => $time,
                'address' => $customer->address,
                'area' => $customer->area,
                'notes' => $customer->preference,
                'channel' => $via,
                'payment_method' => $method,
                'payment_status' => $pay,
                'status' => $status,
            ]);
            $order->update(['total' => $qty * $price * $order->serving_days]);

            $order->histories()->delete();
            $placed = $t->copy()->subDays(1)->setTime(19, 42);
            foreach (array_slice($steps, 0, array_search($status, $steps, true) + 1) as $i => $s) {
                $order->histories()->create(['status' => $s, 'changed_at' => $placed->copy()->addMinutes([0, 3, 500, 900, 980][$i])]);
            }
        }
    }
}
