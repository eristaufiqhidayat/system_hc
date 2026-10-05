<?php

namespace App\Http\Controllers;

use App\Models\DeliveryRoute;
use App\Models\DeliveryStop;
use App\Services\DeliveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourierAppController extends Controller
{
    public function __construct(private DeliveryService $delivery) {}

    public function show(Request $request): View
    {
        $user = $request->user();
        $date = $this->delivery->currentDate();

        $route = DeliveryRoute::with(['courier', 'stops'])
            ->withCount(['stops', 'stops as delivered_count' => fn ($q) => $q->whereNotNull('delivered_at')])
            ->whereDate('route_date', $date)
            ->when($user->role === 'kurir', fn ($q) => $q->where('courier_id', $user->id))
            ->when($user->role !== 'kurir' && $request->query('rute'), fn ($q) => $q->where('id', $request->query('rute')))
            ->when($user->role !== 'kurir' && ! $request->query('rute'), fn ($q) => $q->orderByRaw('(SELECT COUNT(*) FROM delivery_stops s WHERE s.delivery_route_id = delivery_routes.id AND s.delivered_at IS NULL) = 0')->orderBy('id'))
            ->first();

        $pending = $route?->stops->whereNull('delivered_at')->values() ?? collect();

        return view('courier.app', [
            'route' => $route,
            'date' => $date,
            'next' => $pending->first(),
            'upcoming' => $pending->slice(1),
        ]);
    }

    public function deliver(Request $request, DeliveryStop $stop): RedirectResponse
    {
        $user = $request->user();
        abort_if($user->role === 'kurir' && $stop->route->courier_id !== $user->id, 403);

        $request->validate(['photo' => ['nullable', 'image', 'max:5120']]);
        $path = $request->file('photo')?->store('bukti-antar', 'public');

        $this->delivery->markDelivered($stop, $path);

        return redirect()->route('courier.app', $user->role === 'kurir' ? [] : ['rute' => $stop->delivery_route_id])
            ->with('toast', $stop->name.' terkirim · notifikasi dikirim');
    }
}
