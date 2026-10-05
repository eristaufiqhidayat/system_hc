# Sistem HC Catering

Aplikasi Laravel untuk operasional **HC Catering** (Tangerang · Bintaro): pesanan, rekap dapur, pengiriman, stok, langganan rantangan, kontrak kantor & rumah sakit, event prasmanan, tagihan, laporan, chatbot WhatsApp, serta tiga aplikasi HP (web pemesanan, akun pelanggan, aplikasi kurir). Tampilan mengikuti mockup *SISTEM HC CATERING*.

## Kebutuhan

- PHP 8.3+ dengan ekstensi `pdo_mysql` (atau `pdo_sqlite` untuk uji lokal), `mbstring`, `xml`
- Composer 2
- MySQL 8 / MariaDB 10.6+ (produksi) atau SQLite (uji lokal)

Tidak perlu Node/Vite: CSS dan JS sudah ada di `public/css` dan `public/js`.

## Instalasi

```bash
git clone https://github.com/eristaufiqhidayat/system_hc.git
cd system_hc
composer install
cp .env.example .env
php artisan key:generate
# atur DB_DATABASE, DB_USERNAME, DB_PASSWORD di .env, lalu:
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Buka http://127.0.0.1:8000 dan masuk dengan akun contoh (kata sandi semua: `password`):

| Peran | Email |
| --- | --- |
| Pemilik | owner@hccatering.test |
| Admin | admin@hccatering.test |
| Kepala dapur | dapur@hccatering.test |
| Keuangan | keuangan@hccatering.test |
| Kurir | andi@, rudi@, dede@, yoga@hccatering.test |

Ganti kata sandi akun contoh sebelum dipakai sungguhan.

### Uji cepat dengan SQLite

```bash
# di .env
DB_CONNECTION=sqlite
# hapus/komentari DB_HOST, DB_DATABASE, dst.
touch database/database.sqlite
php artisan migrate:fresh --seed
```

### Menjalankan test

```bash
php artisan test
```

## Halaman

| Menu | URL | Isi |
| --- | --- | --- |
| Dashboard | `/` | KPI porsi besok, pengiriman, omzet, piutang, daftar perlu perhatian, pesanan terbaru |
| Pesanan | `/pesanan` | Filter status & lini, cari, detail (drawer) dengan riwayat status, pesanan baru, ekspor CSV, cetak nota |
| Produksi Dapur | `/produksi` | Rekap masak per menu (disusun otomatis dari kontrak, langganan, pesanan, event), status masak, packing per tujuan, cetak label |
| Pengiriman | `/pengiriman` | Rute per kurir, ganti kurir, optimasi urutan, kirim info antar ke pelanggan |
| Stok & Belanja | `/stok` | Stok vs minimum vs kebutuhan besok, stok masuk/keluar/terbuang, daftar belanja, PO ke pemasok |
| Langganan Rantangan | `/langganan` | Sisa hari, pengingat perpanjang, jeda/lanjutkan |
| Kontrak Kantor & RS | `/kontrak` | Shift, diet pasien per kamar, rekap tagihan bulan lalu, terbitkan invoice |
| Event & Prasmanan | `/event` | Kanban Permintaan → Selesai, rincian & quotation, checklist persiapan, invoice DP/pelunasan otomatis |
| Data Pelanggan | `/pelanggan` | Profil semua lini, segmen, promo WhatsApp |
| Tagihan & Piutang | `/tagihan` | Invoice, tandai lunas, tagih yang lewat tempo, ekspor |
| Laporan | `/laporan` | Omzet bulanan, porsi per lini, menu terlaris, profitabilitas (HPP dari resep), versi cetak/PDF |
| Menu & Resep | `/menu` | Rotasi menu 4 minggu, resep & HPP per porsi, varian diet, umumkan menu |
| Chatbot WhatsApp | `/chatbot` | Simulasi chat, ubah balasan otomatis per kata kunci, pesan terjadwal |
| Pengguna & Akses | `/akses` | Tim, peran, matriks hak akses |
| Web Pemesanan (publik) | `/pesan` | Pilih paket → alamat & preferensi → bayar QRIS → konfirmasi |
| Akun Pelanggan (publik) | `/akun/{token}` | Lewati hari, jeda, preferensi, perpanjang |
| Aplikasi Kurir | `/kurir` | Titik berikutnya, navigasi, tandai terkirim + foto bukti |

## Hak akses

Diatur di `App\Models\User::PERMISSIONS` dan middleware `akses:{izin}`:

| Akses | Pemilik | Admin | Kepala dapur | Keuangan | Kurir |
| --- | :-: | :-: | :-: | :-: | :-: |
| Lihat dashboard | ✓ | ✓ | ✓ | ✓ | |
| Kelola pesanan | ✓ | ✓ | | | |
| Rekap produksi & stok | ✓ | ✓ | ✓ | | |
| Ubah resep & HPP | ✓ | | ✓ | | |
| Invoice & pembayaran | ✓ | | | ✓ | |
| Laporan keuangan | ✓ | | | ✓ | |
| Aplikasi kurir | ✓ | ✓ | | | ✓ |
| Atur pengguna | ✓ | | | | |

## Struktur

```
app/
  Http/Controllers/   satu controller per menu + PublicOrder, CustomerPortal, CourierApp, WhatsAppWebhook
  Http/Middleware/    EnsureCanAccess (hak akses per peran)
  Http/Requests/      StoreOrderRequest
  Models/             Customer, Order, Subscription, Contract, CateringEvent, Invoice, Payment,
                      Ingredient, Menu, ProductionItem, DeliveryRoute, DeliveryStop, ChatbotRule, ...
  Services/           OrderService, SubscriptionService, InvoiceService, StockService, ProductionService,
                      DeliveryService, MenuService, ChatbotService, DashboardService, ReportService,
                      WhatsAppService
  Support/helpers.php format rupiah & tanggal Indonesia
  View/               NavigationComposer (menu samping & badge)
config/catering.php   paket & harga web pemesanan, jam antar, preferensi
database/migrations   skema lengkap
database/seeders      data contoh sesuai mockup (tanggal relatif terhadap hari ini)
resources/views       Blade per halaman, komponen (kpi, badge, chart, icon, qr), layout admin/HP/cetak
```

## Integrasi

- **WhatsApp**: isi `WHATSAPP_API_URL` dan `WHATSAPP_API_TOKEN` untuk meneruskan pesan keluar ke gateway (POST JSON `{to, message}`). Tanpa itu, semua pesan dicatat di tabel `whatsapp_messages` dan log (mode simulasi).
- **Webhook chatbot**: gateway mengirim `POST /webhook/whatsapp` dengan `{from, message}` dan header `X-Webhook-Secret` (= `WHATSAPP_WEBHOOK_SECRET`); responsnya berisi balasan bot.
- **QRIS**: halaman bayar memakai QR ilustratif. Hubungkan payment gateway (Midtrans/Xendit) di `PublicOrderController::confirm` untuk QRIS dinamis dan konfirmasi otomatis.
- **Peta**: peta rute masih ilustrasi SVG; titik antar diurutkan berdasarkan `distance_km`.
