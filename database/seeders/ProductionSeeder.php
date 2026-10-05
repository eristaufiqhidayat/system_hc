<?php

namespace Database\Seeders;

use App\Models\ProductionItem;
use App\Services\ProductionService;
use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    public function run(ProductionService $production): void
    {
        ProductionItem::query()->delete();

        // Rekap besok disusun dari data kontrak, langganan, pesanan & event yang sudah di-seed.
        $items = $production->generate(today()->addDay());
        $statuses = ['Dimasak', 'Persiapan', 'Dimasak', 'Persiapan', 'Belum'];
        $items->values()->each(fn (ProductionItem $item, int $i) => $item->update(['status' => $statuses[$i] ?? 'Belum']));

        // Riwayat produksi bulan ini untuk laporan "Menu terlaris".
        $history = ['Ayam bakar kecap' => 4120, 'Rendang' => 3380, 'Soto ayam' => 2960, 'Ikan kukus jahe' => 2710, 'Semur daging' => 2440];
        foreach ($history as $menu => $portions) {
            ProductionItem::create([
                'production_date' => today()->subMonthNoOverflow()->startOfMonth()->addDays(10),
                'menu_name' => $menu,
                'office_portions' => (int) round($portions * 0.6),
                'rantang_portions' => (int) round($portions * 0.4),
                'cook' => 'Cook A',
                'status' => 'Selesai',
            ]);
        }
    }
}
