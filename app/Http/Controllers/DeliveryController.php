<?php

namespace App\Http\Controllers;

use App\Models\DeliveryRoute;
use App\Models\User;
use App\Services\DeliveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DeliveryController extends Controller
{
    public function __construct(private DeliveryService $delivery) {}

    public function index(Request $request): View
    {
        $date = $this->delivery->currentDate();
        $routes = $this->delivery->routesFor($date);
        $selected = $routes->firstWhere('id', (int) $request->query('rute')) ?? $routes->first(fn ($r) => $r->state[0] !== 'Selesai') ?? $routes->first();

        $rantang = $routes->flatMap->stops->filter(fn ($s) => str_contains(implode(' ', $s->tags ?? []), 'rantang') || str_contains($s->name, 'Rantangan'));

        return view('delivery.index', [
            'date' => $date,
            'routes' => $routes,
            'selected' => $selected,
            'stats' => $this->delivery->stats($routes),
            'couriers' => User::where('role', 'kurir')->where('is_active', true)->orderBy('name')->get(),
            'rantangCount' => $rantang->count(),
        ]);
    }

    public function assign(Request $request, DeliveryRoute $deliveryRoute): RedirectResponse
    {
        $data = $request->validate(['courier_id' => ['required', Rule::exists('users', 'id')->where('role', 'kurir')]]);
        $courier = User::findOrFail($data['courier_id']);
        $this->delivery->assignCourier($deliveryRoute, $courier);

        return redirect()->route('delivery.index', ['rute' => $deliveryRoute->id])->with('toast', 'Rute dialihkan ke '.$courier->name);
    }

    public function optimize(DeliveryRoute $deliveryRoute): RedirectResponse
    {
        $this->delivery->optimize($deliveryRoute);

        return redirect()->route('delivery.index', ['rute' => $deliveryRoute->id])->with('toast', 'Rute disusun ulang berdasarkan jarak terdekat');
    }

    public function notify(): RedirectResponse
    {
        $count = $this->delivery->notifyCustomers($this->delivery->currentDate());

        return back()->with('toast', "Info antar dikirim ke {$count} pelanggan");
    }
}
