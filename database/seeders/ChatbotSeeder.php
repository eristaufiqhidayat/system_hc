<?php

namespace Database\Seeders;

use App\Models\ChatbotRule;
use App\Models\ScheduledMessage;
use App\Models\Setting;
use App\Models\WhatsappMessage;
use Illuminate\Database\Seeder;

class ChatbotSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            ['komplain, telat, salah', 'Langsung teruskan ke admin (prioritas)', 'Mohon maaf atas ketidaknyamanannya 🙏 Pesan Anda kami teruskan ke admin dan akan segera ditangani.', null, true, 100],
            ['menu, menu hari ini', 'Kirim menu minggu ini + link pesan', "Menu minggu ini 🍱\n{menu_minggu_ini}", '🛒 Buka halaman pesan', false, 50],
            ['harga, paket, price', 'Kirim daftar paket & harga', "Paket rantangan:\n• Harian Rp 30.000\n• Mingguan (5 hari) Rp 137.500\n• Bulanan (20 hari) Rp 500.000\nGratis ongkir Bintaro.", null, false, 40],
            ['pesan, order, langganan', 'Kirim link web pemesanan', 'Silakan pesan di sini, bayar pakai QRIS, selesai dalam 2 menit 👇', '🛒 Buka halaman pesan', false, 30],
            ['bayar, qris, transfer', 'Kirim QRIS & nomor rekening', 'Pembayaran bisa via QRIS (semua e-wallet & m-banking) atau transfer ke rekening BCA a.n. HC Catering.', null, false, 20],
            ['kantor, karyawan, rs', 'Kumpulkan data → teruskan ke admin', "Siap! Untuk makan karyawan kantor, kami melayani minimal 30 porsi/hari di Tangerang & Bintaro, antar 11.30, dengan invoice bulanan.\nBerapa kira-kira jumlah karyawannya?", '📋 Isi form penawaran', true, 10],
        ];
        foreach ($rules as [$keywords, $desc, $response, $button, $forward, $priority]) {
            ChatbotRule::updateOrCreate(['keywords' => $keywords], [
                'description' => $desc, 'response' => $response, 'quick_button' => $button,
                'forward_to_admin' => $forward, 'priority' => $priority,
            ]);
        }

        $scheduled = [
            ['Menu minggu depan', 'Setiap Jumat 16.00 · ke pelanggan langganan'],
            ['Pengingat perpanjang', 'H-2 sebelum paket habis'],
            ['Konfirmasi antar', 'Saat kurir berangkat, dengan estimasi jam'],
            ['Minta ulasan', 'Hari ke-3 langganan baru'],
        ];
        foreach ($scheduled as [$name, $schedule]) {
            ScheduledMessage::updateOrCreate(['name' => $name], ['schedule' => $schedule, 'active' => true]);
        }

        Setting::put('chatbot_active', '1');

        // Contoh percakapan minggu ini untuk statistik chatbot.
        WhatsappMessage::where('direction', 'in')->delete();
        for ($i = 0; $i < 60; $i++) {
            WhatsappMessage::create([
                'phone' => '0813-9999-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'direction' => 'in',
                'body' => ['menu', 'harga paket', 'mau pesan', 'bayar qris', 'kantor 80 orang'][$i % 5],
                'handled_by_bot' => $i % 11 !== 0 && $i % 7 !== 0,
                'created_at' => now()->subHours($i * 2),
            ]);
        }
    }
}
