<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CustomerSeeder::class,
            MenuSeeder::class,
            IngredientSeeder::class,
            OrderSeeder::class,
            SubscriptionSeeder::class,
            ContractSeeder::class,
            EventSeeder::class,
            InvoiceSeeder::class,
            ProductionSeeder::class,
            DeliverySeeder::class,
            ChatbotSeeder::class,
        ]);
    }
}
