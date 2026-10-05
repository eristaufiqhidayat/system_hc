<?php

namespace App\Services;

use App\Models\DeliveryRoute;
use App\Models\DeliveryStop;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DeliveryService
{
    public function __construct(
        private WhatsAppService $whatsapp,
        private OrderService $orders,
    ) {}

    /**
     * Tanggal rute yang ditampilkan: hari ini bila ada rute, jika tidak tanggal rute terakhir.
     */
    public function currentDate(): Carbon
    {
        if (DeliveryRoute::whereDate('route_date', today())->exists()) {
            return today();
        }

        $latest = DeliveryRoute::max('route_date');

        return $latest ? Carbon::parse($latest) : today();
    }

    /**
     * @return Collection<int, DeliveryRoute>
     */
    public function routesFor(Carbon $date): Collection
    {
        return DeliveryRoute::with(['courier', 'stops'])
            ->withCount(['stops', 'stops as delivered_count' => fn ($q) => $q->whereNotNull('delivered_at')])
            ->whereDate('route_date', $date)
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array{stops:int, delivered:int, late:int, late_reason:?string, couriers:int}
     */
    public function stats(Collection $routes): array
    {
        $late = $routes->filter(fn (DeliveryRoute $r) => $r->state[0] === 'Terlambat');

        return [
            'stops' => (int) $routes->sum('stops_count'),
            'delivered' => (int) $routes->sum('delivered_count'),
            'late' => $late->count(),
            'late_reason' => $late->first()?->late_reason,
            'couriers' => $routes->pluck('courier_id')->filter()->unique()->count(),
        ];
    }

    public function assignCourier(DeliveryRoute $route, User $courier): void
    {
        $route->update(['courier_id' => $courier->id]);
    }

    /**
     * Susun ulang titik yang belum terkirim berdasarkan jarak terdekat dari dapur.
     */
    public function optimize(DeliveryRoute $route): void
    {
        DB::transaction(function () use ($route) {
            $done = $route->stops()->whereNotNull('delivered_at')->orderBy('sequence')->get();
            $pending = $route->stops()->whereNull('delivered_at')->orderBy('distance_km')->get();

            $seq = 1;
            foreach ($done->concat($pending) as $stop) {
                $stop->update(['sequence' => $seq++]);
            }
        });
    }

    public function markDelivered(DeliveryStop $stop, ?string $photoPath = null): void
    {
        $stop->update(['delivered_at' => now(), 'proof_photo' => $photoPath]);

        $order = $stop->order;
        if ($order && $order->status === 'Dikirim') {
            $this->orders->advance($order);
        }

        $customerName = explode(' · ', $stop->name)[0];
        $this->whatsapp->send(
            $order?->customer?->whatsapp,
            "Halo {$customerName}, pesanan HC Catering sudah sampai pukul ".now()->format('H.i').'. Selamat menikmati! 🍱',
            $order?->customer,
        );

        $route = $stop->route;
        if ($route->is_late && $route->stops()->whereNull('delivered_at')->doesntExist()) {
            $route->update(['is_late' => false]);
        }
    }

    public function notifyCustomers(Carbon $date): int
    {
        $stops = DeliveryStop::with('order.customer')
            ->whereHas('route', fn ($q) => $q->whereDate('route_date', $date))
            ->whereNull('delivered_at')
            ->get();

        foreach ($stops as $stop) {
            $this->whatsapp->send(
                $stop->order?->customer?->whatsapp,
                'Pesanan HC Catering Anda sedang dalam perjalanan'.($stop->eta ? ', estimasi tiba '.$stop->eta : '').'.',
                $stop->order?->customer,
            );
        }

        return $stops->count();
    }
}
