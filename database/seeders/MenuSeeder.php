<?php

namespace Database\Seeders;

use App\Models\DietVariant;
use App\Models\Menu;
use App\Models\MenuRotation;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public const WEEKS = [
        ['Ayam bakar kecap', 'Semur daging', 'Ikan kukus jahe', 'Rendang', 'Soto ayam'],
        ['Ayam goreng lengkuas', 'Sop iga', 'Pepes ikan', 'Ayam rica (tidak pedas tersedia)', 'Nasi liwet komplit'],
        ['Ayam teriyaki', 'Empal gepuk', 'Ikan asam manis', 'Gulai ayam', 'Sayur lodeh + tempe'],
        ['Ayam opor', 'Daging lada hitam', 'Ikan bakar kecap', 'Rawon', 'Nasi kuning komplit'],
    ];

    private const RATINGS = ['Ayam bakar kecap' => 4.8, 'Rendang' => 4.9, 'Rendang daging' => 4.9, 'Soto ayam' => 4.7, 'Ikan kukus jahe' => 4.6, 'Semur daging' => 4.6];

    public function run(): void
    {
        foreach (self::WEEKS as $w => $days) {
            foreach ($days as $d => $name) {
                $isBeef = preg_match('/daging|rendang|iga|empal|rawon/i', $name);
                $isFish = preg_match('/ikan/i', $name);
                $proteinCost = $isBeef ? 9500 : ($isFish ? 7500 : 6500);

                $menu = Menu::updateOrCreate(['name' => $name], [
                    'selling_price' => $isBeef ? 32000 : 28000,
                    'recipe_locked' => true,
                    'rating' => self::RATINGS[$name] ?? 4.5,
                ]);

                $menu->recipeItems()->delete();
                $menu->recipeItems()->createMany([
                    ['component' => 'Protein utama', 'amount' => '120 g', 'cost' => $proteinCost],
                    ['component' => 'Bumbu & rempah', 'amount' => '25 g', 'cost' => 1500],
                    ['component' => 'Nasi', 'amount' => '180 g', 'cost' => 2200],
                    ['component' => 'Sayur pendamping', 'amount' => '80 g', 'cost' => 1600],
                    ['component' => 'Sambal, kerupuk, buah', 'amount' => '—', 'cost' => 1500],
                    ['component' => 'Kemasan', 'amount' => '1 box', 'cost' => 1200],
                ]);

                MenuRotation::updateOrCreate(['week' => $w + 1, 'weekday' => $d + 1], ['menu_id' => $menu->id]);
            }
        }

        $variants = [
            ['Tidak pedas', 'Sambal dipisah, bumbu tanpa cabai'],
            ['Rendah garam (RS)', 'Garam −70%, tanpa kecap asin'],
            ['Lunak (RS)', 'Protein dicincang, nasi tim'],
            ['Diet DM (RS)', 'Nasi merah, tanpa gula tambahan'],
            ['Vegetarian', 'Protein diganti tahu/tempe bacem'],
        ];
        foreach ($variants as [$name, $rule]) {
            DietVariant::updateOrCreate(['name' => $name], ['rule' => $rule, 'active' => true]);
        }
    }
}
