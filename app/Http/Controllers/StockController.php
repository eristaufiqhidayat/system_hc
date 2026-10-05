<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Services\StockService;
use App\Services\WhatsAppService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StockController extends Controller
{
    public function __construct(private StockService $stock) {}

    public function index(): View
    {
        $ingredients = Ingredient::orderBy('category')->orderBy('name')->get();
        $shopping = $this->stock->shoppingList();

        return view('stock.index', [
            'ingredients' => $ingredients,
            'shopping' => $shopping,
            'belowMin' => $ingredients->filter->isBelowMinimum()->count(),
            'stockValue' => $this->stock->stockValue(),
            'estimate' => $this->stock->shoppingEstimate($shopping),
            'waste' => $this->stock->wastePercent(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:ingredients,name'],
            'category' => ['required', Rule::in(Ingredient::CATEGORIES)],
            'unit' => ['required', 'string', 'max:10'],
            'stock' => ['required', 'numeric', 'min:0'],
            'min_stock' => ['required', 'numeric', 'min:0'],
            'need_tomorrow' => ['nullable', 'numeric', 'min:0'],
            'supplier' => ['nullable', 'string', 'max:100'],
            'last_price' => ['nullable', 'integer', 'min:0'],
        ]);

        Ingredient::create($data + ['need_tomorrow' => 0, 'last_price' => 0]);

        return back()->with('toast', "Bahan {$data['name']} ditambahkan");
    }

    public function stockIn(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ingredient_id' => ['required', 'exists:ingredients,id'],
            'type' => ['required', Rule::in(['in', 'out', 'waste'])],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit_price' => ['nullable', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:200'],
        ]);

        $ingredient = Ingredient::findOrFail($data['ingredient_id']);
        $this->stock->record($ingredient, $data['type'], (float) $data['quantity'], $data['unit_price'] ?? null, $data['note'] ?? null, $request->user());

        $label = ['in' => 'Stok masuk', 'out' => 'Pemakaian', 'waste' => 'Bahan terbuang'][$data['type']];

        return back()->with('toast', "{$label}: {$ingredient->name} ".qty($data['quantity'])." {$ingredient->unit}");
    }

    public function sendList(Request $request): RedirectResponse
    {
        $count = $this->stock->sendShoppingList(config('services.whatsapp.purchasing_phone'));

        return back()->with('toast', "Daftar belanja ({$count} item) dikirim ke WhatsApp bagian pembelian");
    }

    public function purchaseOrders(WhatsAppService $whatsapp): RedirectResponse
    {
        $bySupplier = $this->stock->shoppingList()->groupBy('supplier');

        foreach ($bySupplier as $supplier => $items) {
            $lines = $items->map(fn ($i) => '• '.$i->name.' '.qty($i->purchase_qty).' '.$i->unit)->implode("\n");
            $whatsapp->send(null, "PO HC Catering untuk {$supplier}:\n{$lines}\nKirim sebelum 17.00. Terima kasih.");
        }

        return back()->with('toast', 'Pesanan pembelian (PO) dibuat untuk '.$bySupplier->count().' pemasok');
    }
}
