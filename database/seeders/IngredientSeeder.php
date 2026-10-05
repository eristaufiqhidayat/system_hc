<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use Illuminate\Database\Seeder;

class IngredientSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['Dada & paha ayam', 'Protein', 38, 50, 'kg', 62, 'Pemasok ayam', 42000],
            ['Daging sapi', 'Protein', 30, 20, 'kg', 28, 'Pemasok daging', 135000],
            ['Ikan kakap fillet', 'Protein', 22, 15, 'kg', 21, 'Pasar ikan', 85000],
            ['Beras', 'Pokok', 300, 150, 'kg', 140, 'Toko beras', 14000],
            ['Sayur campur', 'Sayur', 20, 40, 'kg', 85, 'Pasar induk', 12000],
            ['Minyak goreng', 'Pokok', 45, 30, 'L', 24, 'Distributor minyak', 18000],
            ['Telur', 'Protein', 18, 20, 'kg', 15, 'Pemasok telur', 29000],
            ['Box nasi + rantang', 'Kemasan', 1400, 800, 'pcs', 1138, 'Toko kemasan', 1200],
        ];

        foreach ($items as [$name, $cat, $stock, $min, $unit, $need, $sup, $price]) {
            $ingredient = Ingredient::updateOrCreate(['name' => $name], [
                'category' => $cat,
                'stock' => $stock,
                'min_stock' => $min,
                'unit' => $unit,
                'need_tomorrow' => $need,
                'supplier' => $sup,
                'last_price' => $price,
            ]);

            // Riwayat pemakaian & sisa terbuang minggu ini (untuk KPI "Sisa & terbuang").
            $ingredient->movements()->delete();
            $ingredient->movements()->create(['type' => 'out', 'quantity' => $need * 5, 'note' => 'Produksi minggu ini']);
            $ingredient->movements()->create(['type' => 'waste', 'quantity' => round($need * 5 * 0.021, 2), 'note' => 'Sisa & terbuang']);
        }
    }
}
