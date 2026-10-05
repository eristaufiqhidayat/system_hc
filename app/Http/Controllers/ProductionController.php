<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ProductionItem;
use App\Services\ProductionService;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductionController extends Controller
{
    public function __construct(private ProductionService $production) {}

    public function index(StockService $stock): View
    {
        $date = $this->production->currentDate();
        $items = $this->production->items($date);
        $perLine = $this->production->portionsPerLine($date);
        $lastWeek = $this->production->portionsPerLine($date->copy()->subWeek());

        return view('production.index', [
            'date' => $date,
            'items' => $items,
            'summary' => $this->production->summary($items),
            'total' => array_sum($perLine),
            'growth' => array_sum($lastWeek) ? round((array_sum($perLine) - array_sum($lastWeek)) / array_sum($lastWeek) * 100) : null,
            'hospitalMenus' => $items->filter(fn ($i) => $i->hospital_portions > 0 && $i->office_portions === 0)->count(),
            'shortages' => $stock->shoppingList()->count(),
            'packing' => $this->production->packingList($date),
            'labelSample' => Contract::with(['customer', 'diets'])->where('type', 'Rumah sakit')->first(),
        ]);
    }

    public function update(Request $request, ProductionItem $item): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(array_keys(ProductionItem::STATUSES))]]);
        $this->production->setStatus($item, $data['status']);

        return back()->with('toast', "{$item->menu_name}: {$data['status']}");
    }

    public function generate(): RedirectResponse
    {
        $date = today()->addDay();
        $items = $this->production->generate($date);

        return back()->with('toast', 'Rekap '.date_id($date).' disusun ulang · '.$items->count().' menu');
    }

    public function labels(): View
    {
        $date = $this->production->currentDate();

        return view('production.labels', [
            'date' => $date,
            'contracts' => Contract::with(['customer', 'diets'])->where('daily_portions', '>', 0)->get(),
            'items' => $this->production->items($date),
        ]);
    }
}
