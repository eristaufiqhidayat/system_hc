<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Services\MenuService;
use App\Services\OrderService;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Web pemesanan untuk pelanggan (HP): pilih paket → alamat & preferensi → bayar QRIS → konfirmasi.
 */
class PublicOrderController extends Controller
{
    public function start(Request $request, MenuService $menus): View
    {
        $cart = $this->cart($request);

        return view('shop.start', [
            'cart' => $cart,
            'services' => config('catering.services'),
            'packages' => config("catering.services.{$cart['service']}.packages"),
            'weekMenus' => $menus->weekMenus(today()),
        ]);
    }

    public function saveStart(Request $request): RedirectResponse
    {
        $services = config('catering.services');
        $service = $request->input('service');

        $data = $request->validate([
            'service' => ['required', Rule::in(array_keys($services))],
            'package' => ['required', Rule::in(array_keys($services[$service]['packages'] ?? []))],
            'portions' => ['required', 'integer', 'min:1', 'max:1000'],
            'action' => ['nullable', 'string'],
        ]);

        $request->session()->put('shop', array_merge($this->cart($request), collect($data)->except('action')->all()));

        // Tombol ganti layanan / paket / porsi tetap di langkah 1.
        if ($data['action'] !== 'next') {
            return redirect()->route('shop.start');
        }

        return redirect()->route('shop.details');
    }

    public function details(Request $request): View
    {
        $cart = $this->cart($request);

        return view('shop.details', ['cart' => $cart, 'pricing' => $this->pricing($cart)]);
    }

    public function saveDetails(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'whatsapp' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date', 'after_or_equal:tomorrow'],
            'window' => ['required', Rule::in(config('catering.delivery_windows'))],
            'preferences' => ['array'],
            'preferences.*' => [Rule::in(config('catering.preferences'))],
            'notes' => ['nullable', 'string', 'max:300'],
        ]);

        $request->session()->put('shop', array_merge($this->cart($request), $data));

        return redirect()->route('shop.payment');
    }

    public function payment(Request $request): View|RedirectResponse
    {
        $cart = $this->cart($request);
        if (empty($cart['name'])) {
            return redirect()->route('shop.details');
        }

        return view('shop.payment', ['cart' => $cart, 'pricing' => $this->pricing($cart)]);
    }

    public function confirm(Request $request, OrderService $orders, SubscriptionService $subscriptions): RedirectResponse
    {
        $cart = $this->cart($request);
        if (empty($cart['name'])) {
            return redirect()->route('shop.details');
        }

        $pricing = $this->pricing($cart);
        $service = config("catering.services.{$cart['service']}");
        $package = $service['packages'][$cart['package']];
        $start = Carbon::parse($cart['start_date']);
        $end = $package['days'] > 1 ? working_days_after($start, $package['days'] - 1) : null;
        $preference = implode(', ', $cart['preferences'] ?? []) ?: null;

        $order = DB::transaction(function () use ($cart, $orders, $subscriptions, $service, $package, $pricing, $start, $end, $preference) {
            $customer = Customer::firstOrCreate(
                ['whatsapp' => $cart['whatsapp']],
                ['name' => $cart['name'], 'segment' => $service['line'] === 'Event' ? 'Event' : $cart['service'], 'address' => $cart['address'], 'area' => $cart['address'], 'preference' => $preference],
            );

            $order = $orders->create([
                'customer' => $customer,
                'business_line' => $service['line'],
                'item' => $package['label'].' · '.$cart['portions'].' porsi',
                'portions' => $cart['portions'],
                'delivery_date' => $start,
                'delivery_date_end' => $end,
                'delivery_time' => $cart['window'],
                'address' => $cart['address'],
                'notes' => trim($preference.' '.($cart['notes'] ?? '')) ?: null,
                'channel' => 'Web',
                'payment' => 'paid_qris',
                'total' => $pricing['total'],
            ]);

            Payment::create([
                'customer_id' => $customer->id,
                'order_id' => $order->id,
                'method' => 'QRIS',
                'amount' => $pricing['total'],
                'description' => $order->item,
                'paid_at' => now(),
            ]);

            if ($cart['service'] === 'Rantangan') {
                $subscriptions->subscribe($customer, $cart['package'], (int) $cart['portions'], $start, $preference, $cart['window']);
            }

            return $order;
        });

        $orders->advance($order);
        $request->session()->forget('shop');

        return redirect()->route('shop.done', $order);
    }

    public function done(Order $order): View
    {
        return view('shop.done', ['order' => $order->load('customer')]);
    }

    /**
     * @return array<string, mixed>
     */
    private function cart(Request $request): array
    {
        return $request->session()->get('shop', []) + [
            'service' => 'Rantangan',
            'package' => 'Mingguan',
            'portions' => 2,
            'window' => config('catering.delivery_windows')[0],
            'start_date' => today()->addDay()->isWeekend() ? today()->next(Carbon::MONDAY)->toDateString() : today()->addDay()->toDateString(),
            'preferences' => [],
        ];
    }

    /**
     * @return array{subtotal:int, shipping:int, total:int, label:string}
     */
    private function pricing(array $cart): array
    {
        $package = config("catering.services.{$cart['service']}.packages.{$cart['package']}");
        $subtotal = $package['price'] * $package['days'] * (int) $cart['portions'];

        return [
            'label' => $cart['service'].' '.mb_strtolower($package['label']).' × '.$cart['portions'],
            'subtotal' => $subtotal,
            'shipping' => 0,
            'total' => $subtotal,
        ];
    }
}
