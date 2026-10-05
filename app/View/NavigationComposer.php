<?php

namespace App\View;

use App\Models\Ingredient;
use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Menyusun menu samping sesuai hak akses pengguna, beserta angka penanda (badge).
 */
class NavigationComposer
{
    public function compose(View $view): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $groups = [
            'Ringkasan' => [
                ['dashboard', 'Dashboard', 'home', 'dashboard', null],
            ],
            'Operasional' => [
                ['orders.index', 'Pesanan', 'order', 'orders', fn () => Order::where('status', 'Baru')->count()],
                ['production.index', 'Produksi Dapur', 'chef', 'production', null],
                ['delivery.index', 'Pengiriman', 'truck', 'orders', null],
                ['stock.index', 'Stok & Belanja', 'box', 'production', fn () => Ingredient::all()->filter->needsPurchase()->count()],
            ],
            'Pelanggan' => [
                ['subscriptions.index', 'Langganan Rantangan', 'repeat', 'orders', null],
                ['contracts.index', 'Kontrak Kantor & RS', 'building', 'orders', null],
                ['events.index', 'Event & Prasmanan', 'party', 'orders', null],
                ['customers.index', 'Data Pelanggan', 'users', 'orders', null],
            ],
            'Keuangan' => [
                ['invoices.index', 'Tagihan & Piutang', 'invoice', 'billing', fn () => Invoice::overdue()->count()],
                ['reports.index', 'Laporan', 'chart', 'reports', null],
            ],
            'Pengaturan' => [
                ['menus.index', 'Menu & Resep', 'book', 'recipes', null],
                ['chatbot.index', 'Chatbot WhatsApp', 'chat', 'orders', null],
                ['users.index', 'Pengguna & Akses', 'shield', 'users', null],
            ],
            'Aplikasi HP' => [
                ['preview.shop', 'Web Pemesanan', 'phone', 'orders', null],
                ['preview.portal', 'Akun Pelanggan', 'user', 'orders', null],
                ['preview.courier', 'Aplikasi Kurir', 'bike', 'courier', null],
            ],
        ];

        $nav = [];
        foreach ($groups as $group => $items) {
            $visible = array_values(array_filter($items, fn ($i) => $user->canAccess($i[3])));
            if ($visible) {
                $nav[$group] = array_map(fn ($i) => [
                    'route' => $i[0],
                    'label' => $i[1],
                    'icon' => $i[2],
                    'count' => $i[4] ? ($i[4])() : null,
                    'active' => request()->routeIs($i[0])
                        || (str_ends_with($i[0], '.index') && request()->routeIs(explode('.', $i[0])[0].'.*')),
                ], $visible);
            }
        }

        $view->with([
            'nav' => $nav,
            'notifications' => collect($nav)->flatten(1)->sum('count'),
        ]);
    }
}
