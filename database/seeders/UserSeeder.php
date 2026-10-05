<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['Chef Hady', 'owner@hccatering.test', 'pemilik', '0812-0000-0001'],
            ['Admin HC', 'admin@hccatering.test', 'admin', '0812-0000-0002'],
            ['Kepala Dapur', 'dapur@hccatering.test', 'kepala_dapur', '0812-0000-0003'],
            ['Staf Keuangan', 'keuangan@hccatering.test', 'keuangan', '0812-0000-0004'],
            ['Andi', 'andi@hccatering.test', 'kurir', '0812-0000-0011'],
            ['Rudi', 'rudi@hccatering.test', 'kurir', '0812-0000-0012'],
            ['Dede', 'dede@hccatering.test', 'kurir', '0812-0000-0013'],
            ['Yoga', 'yoga@hccatering.test', 'kurir', '0812-0000-0014'],
            ['Fajar (cadangan)', 'fajar@hccatering.test', 'kurir', '0812-0000-0015'],
        ];

        foreach ($users as [$name, $email, $role, $phone]) {
            User::updateOrCreate(['email' => $email], [
                'name' => $name,
                'role' => $role,
                'phone' => $phone,
                'password' => 'password',
            ]);
        }
    }
}
