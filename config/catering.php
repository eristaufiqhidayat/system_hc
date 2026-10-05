<?php

return [
    'name' => env('CATERING_NAME', 'HC Catering'),
    'owner' => env('CATERING_OWNER', 'Chef Hady Chandra Kurniawan'),
    'area' => env('CATERING_AREA', 'Tangerang · Bintaro'),

    /*
    | Layanan & paket di web pemesanan. Harga dalam rupiah per porsi per hari antar.
    */
    'services' => [
        'Rantangan' => [
            'line' => 'Rantangan',
            'packages' => [
                'Harian' => ['label' => 'Harian', 'hint' => 'Pesan per hari, H-1 sebelum 19.00', 'days' => 1, 'price' => 30000],
                'Mingguan' => ['label' => 'Mingguan · 5 hari', 'hint' => 'Senin–Jumat, menu berganti tiap hari', 'days' => 5, 'price' => 27500],
                'Bulanan' => ['label' => 'Bulanan · 20 hari', 'hint' => 'Paling hemat, bisa jeda saat libur', 'days' => 20, 'price' => 25000],
            ],
        ],
        'Nasi box' => [
            'line' => 'Nasi box',
            'packages' => [
                'Reguler' => ['label' => 'Nasi box reguler', 'hint' => 'Nasi, lauk utama, sayur, sambal, kerupuk', 'days' => 1, 'price' => 30000],
                'Premium' => ['label' => 'Nasi box premium', 'hint' => 'Dua lauk utama, buah, air mineral', 'days' => 1, 'price' => 45000],
            ],
        ],
        'Prasmanan' => [
            'line' => 'Event',
            'packages' => [
                'Standar' => ['label' => 'Prasmanan standar', 'hint' => 'Minimal 50 pax, termasuk peralatan', 'days' => 1, 'price' => 75000],
                'Lengkap' => ['label' => 'Prasmanan + gubukan', 'hint' => 'Minimal 100 pax, dengan pramusaji', 'days' => 1, 'price' => 95000],
            ],
        ],
    ],

    'delivery_windows' => ['11.00–12.00', '12.00–13.00', '17.00–18.00'],

    'preferences' => ['Tidak pedas', 'Tanpa seafood', 'Nasi porsi kecil'],
];
