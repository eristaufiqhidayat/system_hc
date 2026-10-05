<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Subscription;
use App\Services\MenuService;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Akun pelanggan (HP): lihat langganan, lewati hari, jeda, ubah preferensi, perpanjang.
 * Diakses lewat tautan unik yang dikirim via WhatsApp.
 */
class CustomerPortalController extends Controller
{
    public function __construct(private SubscriptionService $subscriptions) {}

    public function show(string $token, MenuService $menus): View
    {
        $customer = $this->customer($token);
        $subscription = $this->subscription($customer);

        return view('portal.show', [
            'customer' => $customer,
            'subscription' => $subscription,
            'schedule' => $subscription ? $this->subscriptions->upcomingSchedule($subscription, $menus) : collect(),
            'payments' => $customer->payments()->latest('paid_at')->limit(5)->get(),
            'preferences' => config('catering.preferences'),
            'activePrefs' => array_map('trim', explode(',', (string) ($subscription?->preference ?? $customer->preference))),
        ]);
    }

    public function skip(Request $request, string $token): RedirectResponse
    {
        $subscription = $this->subscriptionOrFail($token);
        $data = $request->validate(['date' => ['required', 'date', 'after:today']]);

        $skipped = $this->subscriptions->toggleSkip($subscription, Carbon::parse($data['date']));

        return back()->with('toast', $skipped ? 'Hari dilewati · langganan diperpanjang 1 hari' : 'Hari dikembalikan');
    }

    public function pause(string $token): RedirectResponse
    {
        $subscription = $this->subscriptionOrFail($token);

        if ($subscription->isPaused()) {
            $this->subscriptions->resume($subscription);

            return back()->with('toast', 'Langganan dilanjutkan');
        }

        $this->subscriptions->pause($subscription, today()->addWeek());

        return back()->with('toast', 'Langganan dijeda. Sisa hari tidak hangus.');
    }

    public function renew(string $token): RedirectResponse
    {
        $subscription = $this->subscriptionOrFail($token);
        $this->subscriptions->renew($subscription);

        return back()->with('toast', 'Perpanjangan dibuat, silakan bayar via QRIS');
    }

    public function preferences(Request $request, string $token): RedirectResponse
    {
        $subscription = $this->subscriptionOrFail($token);
        $data = $request->validate([
            'preferences' => ['array'],
            'preferences.*' => [Rule::in(config('catering.preferences'))],
        ]);

        $subscription->update(['preference' => implode(', ', $data['preferences'] ?? []) ?: null]);

        return back()->with('toast', 'Preferensi disimpan');
    }

    private function customer(string $token): Customer
    {
        return Customer::where('portal_token', $token)->firstOrFail();
    }

    private function subscription(Customer $customer): ?Subscription
    {
        return $customer->subscriptions()->with('customer')->latest('id')->first();
    }

    private function subscriptionOrFail(string $token): Subscription
    {
        return $this->subscription($this->customer($token)) ?? abort(404);
    }
}
