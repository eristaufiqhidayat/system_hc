<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public const FILTERS = ['Semua', 'Aktif', 'Habis ≤3 hari', 'Dijeda'];

    public function __construct(private SubscriptionService $subscriptions) {}

    public function index(Request $request): View
    {
        $filter = in_array($request->query('filter'), self::FILTERS, true) ? $request->query('filter') : 'Semua';

        $all = Subscription::with('customer')->orderBy('remaining_days')->get();
        $list = $all->filter(fn (Subscription $s) => match ($filter) {
            'Aktif' => $s->state[1] === 'ok',
            'Habis ≤3 hari' => in_array($s->state[1], ['wait', 'bad'], true),
            'Dijeda' => $s->isPaused(),
            default => true,
        })->values();

        $total = Subscription::count();
        $newThisMonth = Subscription::where('created_at', '>=', now()->startOfMonth())->count();
        // Pelanggan yang sudah berlangganan > 1 bulan dianggap memperpanjang.
        $renewed = Subscription::whereHas('customer', fn ($q) => $q->where('customer_since', '<', now()->subMonth()))->count();

        return view('subscriptions.index', [
            'filter' => $filter,
            'list' => $list,
            'total' => $total,
            'active' => Subscription::active()->count(),
            'newThisMonth' => $newThisMonth,
            'expiring' => Subscription::expiringSoon()->count(),
            'paused' => Subscription::paused()->count(),
            'renewRate' => $total ? (int) round($renewed / $total * 100) : 0,
        ]);
    }

    public function remindAll(): RedirectResponse
    {
        $count = $this->subscriptions->remindExpiring();

        return back()->with('toast', "Pengingat perpanjang dikirim ke {$count} pelanggan via WhatsApp");
    }

    public function remind(Subscription $subscription): RedirectResponse
    {
        $this->subscriptions->remind($subscription->load('customer'));

        return back()->with('toast', 'Pengingat perpanjang dikirim ke '.$subscription->customer->name);
    }

    public function pause(Request $request, Subscription $subscription): RedirectResponse
    {
        $data = $request->validate(['until' => ['nullable', 'date', 'after_or_equal:today']]);
        $until = isset($data['until']) ? Carbon::parse($data['until']) : today()->addWeek();

        $this->subscriptions->pause($subscription->load('customer'), $until);

        return back()->with('toast', 'Langganan '.$subscription->customer->name.' dijeda s/d '.date_id($until));
    }

    public function resume(Subscription $subscription): RedirectResponse
    {
        $this->subscriptions->resume($subscription);

        return back()->with('toast', 'Langganan '.$subscription->customer->name.' dilanjutkan');
    }
}
