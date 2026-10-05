<?php

namespace App\Services;

use App\Models\CateringEvent;
use App\Models\Contract;
use App\Models\PatientDiet;
use App\Models\ProductionItem;
use App\Models\Subscription;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ProductionService
{
    public function __construct(
        private OrderService $orders,
        private MenuService $menus,
    ) {}

    /**
     * Tanggal produksi yang ditampilkan: tanggal terakhir yang punya rekap, atau besok.
     */
    public function currentDate(): Carbon
    {
        $latest = ProductionItem::max('production_date');

        return $latest ? Carbon::parse($latest) : today()->addDay();
    }

    /**
     * @return Collection<int, ProductionItem>
     */
    public function items(Carbon $date): Collection
    {
        return ProductionItem::whereDate('production_date', $date)->orderBy('id')->get();
    }

    /**
     * Porsi besok per lini bisnis (dari kontrak aktif + pesanan + langganan + event).
     *
     * @return array<string, int>
     */
    public function portionsPerLine(Carbon $date): array
    {
        $byLine = $this->orders->portionsByLine($date);

        $office = (int) Contract::whereIn('type', ['Kantor', 'Pabrik', 'Sekolah'])
            ->whereDate('starts_at', '<=', $date)->whereDate('ends_at', '>=', $date)->sum('daily_portions');
        $hospital = (int) Contract::whereIn('type', ['Rumah sakit', 'Klinik'])
            ->whereDate('starts_at', '<=', $date)->whereDate('ends_at', '>=', $date)->sum('daily_portions');
        $rantang = (int) Subscription::active()->sum('portions');
        $events = (int) CateringEvent::whereDate('event_date', $date)->where('stage', '>=', 2)->sum('pax');

        return [
            'Makan karyawan kantor' => $office + ($byLine['Kantor'] ?? 0),
            'Nasi rantangan harian' => $rantang + ($byLine['Rantangan'] ?? 0),
            'Rumah sakit' => $hospital + ($byLine['Rumah sakit'] ?? 0),
            'Event & prasmanan' => $events + ($byLine['Event'] ?? 0) + ($byLine['Nasi box'] ?? 0),
        ];
    }

    /**
     * Pembagian packing per tujuan untuk tanggal tertentu.
     *
     * @return Collection<int, array{0:string,1:string,2:string}>
     */
    public function packingList(Carbon $date): Collection
    {
        $contracts = Contract::with(['customer', 'diets'])
            ->whereDate('starts_at', '<=', $date)->whereDate('ends_at', '>=', $date)
            ->where('daily_portions', '>', 0)
            ->orderByDesc('daily_portions')
            ->get()
            ->map(fn (Contract $c) => [
                $c->customer->name.' · '.$c->customer->area,
                $c->daily_portions.' '.($c->hasDietTracking() ? 'porsi · '.$c->diets->count().' diet khusus' : 'box'),
                $c->delivery_info ? explode(',', $c->delivery_info)[0] : '—',
            ]);

        $subs = Subscription::active()->get();
        $rantang = $subs->isEmpty() ? collect() : collect([[
            'Rantangan',
            $subs->sum('portions').' porsi · '.$subs->count().' pelanggan',
            '10.30 berangkat',
        ]]);

        $events = CateringEvent::with('customer')
            ->whereBetween('event_date', [$date, $date->copy()->addDays(7)])
            ->where('stage', '>=', 2)
            ->get()
            ->map(fn (CateringEvent $e) => [
                'Event '.$e->customer->name,
                $e->pax.' pax prasmanan',
                date_id($e->event_date).' · '.$e->event_time,
            ]);

        return $contracts->concat($rantang)->concat($events)->values();
    }

    /**
     * @return array{menus:int, special_diet:int}
     */
    public function summary(Collection $items): array
    {
        $special = $items->sum(function (ProductionItem $i) {
            preg_match_all('/(\d+)/', (string) $i->diet_notes, $m);

            return array_sum(array_map('intval', $m[1]));
        });

        return [
            'menus' => $items->count(),
            'special_diet' => $special,
        ];
    }

    /**
     * Susun rekap masak untuk satu tanggal dari kontrak, langganan, pesanan & event.
     * Menu utama mengikuti rotasi 4 minggu; RS mendapat menu diet terpisah.
     */
    public function generate(Carbon $date): Collection
    {
        $lines = $this->portionsPerLine($date);
        $office = $lines['Makan karyawan kantor'];
        $rantang = $lines['Nasi rantangan harian'];
        $hospital = $lines['Rumah sakit'];
        $event = $lines['Event & prasmanan'];

        $notSpicy = (int) Subscription::active()->where('preference', 'like', '%tidak pedas%')->sum('portions');
        $diets = PatientDiet::where(fn ($q) => $q->whereNull('valid_until')->orWhereDate('valid_until', '>=', $date))
            ->get()->groupBy('diet_type')->map->count();
        $soft = ($diets['Makanan lunak'] ?? 0) + ($diets['Lunak'] ?? 0);
        $liquid = ($diets['Cair'] ?? 0) + ($diets['Diet DM'] ?? 0);

        $main = $this->menus->menuForDate($date)?->name ?? 'Menu utama';
        $rows = [
            [$main, $office, $rantang, 0, $event, $notSpicy ? $notSpicy.' tidak pedas' : null, 'Cook A'],
            ['Ikan kukus jahe', 0, 0, max(0, $hospital - $liquid), 0, trim(($diets['Rendah garam'] ?? 0).' rendah garam · '.$soft.' lunak'), 'Cook B'],
            ['Sayur asem / bening', $office, $rantang, $hospital, 0, null, 'Cook C'],
            ['Bubur / nasi tim', 0, 0, $liquid, 0, $liquid ? $liquid.' cair / diet DM' : null, 'Cook B'],
            ['Buah potong', $office, 0, $hospital, $event, null, 'Helper'],
        ];

        ProductionItem::whereDate('production_date', $date)->delete();

        foreach ($rows as [$menu, $o, $r, $h, $e, $diet, $cook]) {
            if ($o + $r + $h + $e === 0) {
                continue;
            }
            ProductionItem::create([
                'production_date' => $date,
                'menu_name' => $menu,
                'office_portions' => $o,
                'rantang_portions' => $r,
                'hospital_portions' => $h,
                'event_portions' => $e,
                'diet_notes' => $diet,
                'cook' => $cook,
                'status' => 'Belum',
            ]);
        }

        return $this->items($date);
    }

    public function setStatus(ProductionItem $item, string $status): void
    {
        $item->update(['status' => $status]);
    }
}
