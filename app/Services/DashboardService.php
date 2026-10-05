<?php

namespace App\Services;

use App\Models\CateringEvent;
use App\Models\Ingredient;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\PatientDiet;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Support\Carbon;

class DashboardService
{
    public function __construct(
        private ProductionService $production,
        private DeliveryService $delivery,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        $tomorrow = $this->production->currentDate();
        $perLine = $this->production->portionsPerLine($tomorrow);

        $routeDate = $this->delivery->currentDate();
        $routes = $this->delivery->routesFor($routeDate);
        $deliveryStats = $this->delivery->stats($routes);

        $thisMonth = $this->revenue(now());
        $lastMonth = $this->revenue(now()->subMonthNoOverflow());

        $overdue = Invoice::overdue()->with('customer')->get();

        return [
            'portionsTomorrow' => array_sum($perLine),
            'activeOrders' => Order::where('status', '!=', 'Selesai')->count(),
            'perLine' => $perLine,
            'routes' => $routes,
            'deliveryStats' => $deliveryStats,
            'revenue' => $thisMonth,
            'revenueDelta' => $lastMonth > 0 ? round(($thisMonth - $lastMonth) / $lastMonth * 100, 1) : null,
            'overdueAmount' => (int) $overdue->sum('amount'),
            'overdueCount' => $overdue->count(),
            'attention' => $this->attention($overdue),
            'latestOrders' => Order::with('customer', 'histories')->latest('id')->limit(6)->get(),
        ];
    }

    public function revenue(Carbon $month): int
    {
        return (int) Payment::whereBetween('paid_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])->sum('amount');
    }

    /**
     * Daftar "Perlu perhatian" di dashboard.
     *
     * @return list<array{route:string, title:string, detail:string}>
     */
    private function attention($overdue): array
    {
        $items = [];

        $expiring = Subscription::expiringSoon()->with('customer')->get();
        if ($expiring->isNotEmpty()) {
            $items[] = ['route' => 'subscriptions.index', 'title' => $expiring->count().' langganan habis ≤ 3 hari', 'detail' => 'Kirim pengingat perpanjang via WhatsApp'];
        }

        $diets = PatientDiet::with('contract.customer')->where('is_new', true)->get();
        if ($diets->isNotEmpty()) {
            $items[] = [
                'route' => 'contracts.index',
                'title' => $diets->count().' perubahan diet pasien '.$diets->first()->contract->customer->name,
                'detail' => $diets->take(2)->map(fn ($d) => 'Kamar '.$d->room.' '.mb_strtolower($d->diet_type))->implode(' · '),
            ];
        }

        $low = Ingredient::all()->filter->isBelowMinimum();
        if ($low->isNotEmpty()) {
            $items[] = ['route' => 'stock.index', 'title' => $low->count().' bahan di bawah stok minimum', 'detail' => $low->pluck('name')->implode(', ')];
        }

        if ($overdue->isNotEmpty()) {
            $items[] = [
                'route' => 'invoices.index',
                'title' => $overdue->count().' invoice lewat jatuh tempo',
                'detail' => $overdue->map(fn ($i) => $i->customer->name)->unique()->implode(' & '),
            ];
        }

        $pending = CateringEvent::with('customer')->where('stage', 0)->orderBy('event_date')->first();
        if ($pending) {
            $items[] = ['route' => 'events.index', 'title' => 'Quotation event belum dijawab', 'detail' => $pending->name.' '.$pending->customer->name.' · '.$pending->pax.' pax'];
        }

        return $items;
    }
}
