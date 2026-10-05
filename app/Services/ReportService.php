<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractDelivery;
use App\Models\Invoice;
use App\Models\Menu;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductionItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    public const LINES = [
        'Kantor & pabrik' => ['orders' => ['Kantor'], 'contracts' => ['Kantor', 'Pabrik', 'Sekolah']],
        'Rumah sakit & klinik' => ['orders' => ['Rumah sakit'], 'contracts' => ['Rumah sakit', 'Klinik']],
        'Rantangan' => ['orders' => ['Rantangan'], 'contracts' => []],
        'Event & nasi box' => ['orders' => ['Event', 'Nasi box'], 'contracts' => []],
    ];

    /**
     * @return array{0:Carbon,1:Carbon}
     */
    public function range(string $period): array
    {
        return match ($period) {
            'bulan' => [now()->startOfMonth(), now()->endOfMonth()],
            'tahun' => [now()->startOfYear(), now()->endOfMonth()],
            default => [now()->subMonthsNoOverflow(5)->startOfMonth(), now()->endOfMonth()],
        };
    }

    /**
     * Omzet per bulan dalam juta rupiah.
     *
     * @return array{labels:list<string>, values:list<float>}
     */
    public function monthlyRevenue(Carbon $from, Carbon $to): array
    {
        $labels = [];
        $values = [];
        $cursor = $from->copy()->startOfMonth();

        while ($cursor->lte($to)) {
            $sum = Payment::whereBetween('paid_at', [$cursor->copy()->startOfMonth(), $cursor->copy()->endOfMonth()])->sum('amount');
            $labels[] = month_id($cursor->month);
            $values[] = round($sum / 1_000_000, 1);
            $cursor->addMonthNoOverflow();
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * @return array<string, int>
     */
    public function portionsPerLine(Carbon $from, Carbon $to): array
    {
        $result = [];
        foreach (self::LINES as $label => $map) {
            $orders = Order::whereIn('business_line', $map['orders'])
                ->where('channel', '!=', 'Kontrak')
                ->whereBetween('delivery_date', [$from, $to])->get()->sum('total_portions');
            $contracts = $map['contracts']
                ? ContractDelivery::whereIn('contract_id', Contract::whereIn('type', $map['contracts'])->pluck('id'))
                    ->whereBetween('delivered_on', [$from, $to])
                    ->selectRaw('COALESCE(SUM(portions + extra_portions),0) as total')->value('total')
                : 0;
            $result[$label] = (int) $orders + (int) $contracts;
        }

        return $result;
    }

    /**
     * @return Collection<int, array{name:string, portions:int, rating:?float}>
     */
    public function topMenus(Carbon $from, Carbon $to, int $limit = 5): Collection
    {
        $ratings = Menu::pluck('rating', 'name');

        return ProductionItem::whereBetween('production_date', [$from, $to])
            ->selectRaw('menu_name, SUM(office_portions + rantang_portions + hospital_portions + event_portions) as portions')
            ->groupBy('menu_name')
            ->orderByDesc('portions')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => ['name' => $r->menu_name, 'portions' => (int) $r->portions, 'rating' => $ratings[$r->menu_name] ?? null]);
    }

    /**
     * Omzet, HPP dan margin per lini. HPP memakai rata-rata HPP resep × porsi.
     *
     * @return Collection<int, array{line:string, revenue:int, hpp_percent:?float, margin_percent:?float}>
     */
    public function profitability(Carbon $from, Carbon $to): Collection
    {
        $menus = Menu::with('recipeItems')->get();
        $avgHpp = $menus->avg(fn (Menu $m) => $m->hpp) ?: 0;
        $portions = $this->portionsPerLine($from, $to);

        return collect(self::LINES)->map(function ($map, $label) use ($from, $to, $avgHpp, $portions) {
            $revenue = (int) Order::whereIn('business_line', $map['orders'])->whereBetween('delivery_date', [$from, $to])->sum('total');
            if ($map['contracts']) {
                $revenue += (int) Invoice::whereIn('contract_id', Contract::whereIn('type', $map['contracts'])->pluck('id'))
                    ->whereBetween('issued_at', [$from, $to])->sum('amount');
            }
            $hpp = $avgHpp * ($portions[$label] ?? 0);
            $hppPercent = $revenue > 0 ? round($hpp / $revenue * 100, 1) : null;

            return [
                'line' => $label,
                'revenue' => $revenue,
                'hpp_percent' => $hppPercent,
                'margin_percent' => $hppPercent !== null ? round(100 - $hppPercent, 1) : null,
            ];
        })->values();
    }

    /**
     * @return array{revenue:int, portions:int, hpp_percent:?float, margin_percent:?float}
     */
    public function totals(Carbon $from, Carbon $to): array
    {
        $rows = $this->profitability($from, $to);
        $revenue = (int) $rows->sum('revenue');
        $portions = array_sum($this->portionsPerLine($from, $to));
        $hppAmount = $rows->sum(fn ($r) => $r['hpp_percent'] !== null ? $r['revenue'] * $r['hpp_percent'] / 100 : 0);
        $hppPercent = $revenue > 0 ? round($hppAmount / $revenue * 100, 1) : null;

        return [
            'revenue' => $revenue,
            'portions' => $portions,
            'hpp_percent' => $hppPercent,
            'margin_percent' => $hppPercent !== null ? round(100 - $hppPercent, 1) : null,
        ];
    }
}
