<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function __construct(private WhatsAppService $whatsapp) {}

    /**
     * @return Collection<int, Ingredient>
     */
    public function shoppingList(): Collection
    {
        return Ingredient::orderBy('category')->orderBy('name')->get()
            ->filter(fn (Ingredient $i) => $i->needsPurchase())
            ->values();
    }

    public function shoppingEstimate(?Collection $list = null): int
    {
        return (int) ($list ?? $this->shoppingList())
            ->sum(fn (Ingredient $i) => $i->purchase_qty * $i->last_price);
    }

    public function stockValue(): int
    {
        return (int) Ingredient::all()->sum(fn (Ingredient $i) => $i->stock * $i->last_price);
    }

    public function record(Ingredient $ingredient, string $type, float $quantity, ?int $unitPrice = null, ?string $note = null, ?User $by = null): void
    {
        DB::transaction(function () use ($ingredient, $type, $quantity, $unitPrice, $note, $by) {
            $ingredient->movements()->create([
                'type' => $type,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'note' => $note,
                'user_id' => $by?->id,
            ]);

            $delta = $type === 'in' ? $quantity : -$quantity;
            $ingredient->stock = max(0, $ingredient->stock + $delta);
            if ($type === 'in' && $unitPrice) {
                $ingredient->last_price = $unitPrice;
            }
            $ingredient->save();
        });
    }

    /**
     * Persentase bahan terbuang 7 hari terakhir terhadap pemakaian.
     */
    public function wastePercent(): float
    {
        $since = now()->subDays(7);
        $waste = (float) DB::table('stock_movements')->where('type', 'waste')->where('created_at', '>=', $since)->sum('quantity');
        $used = (float) DB::table('stock_movements')->whereIn('type', ['out', 'waste'])->where('created_at', '>=', $since)->sum('quantity');

        return $used > 0 ? round($waste / $used * 100, 1) : 0.0;
    }

    public function sendShoppingList(?string $phone): int
    {
        $list = $this->shoppingList();
        $lines = $list->map(fn (Ingredient $i) => '• '.$i->name.' '.qty($i->purchase_qty).' '.$i->unit.' ('.$i->supplier.')');

        $this->whatsapp->send($phone, 'Daftar belanja '.date_id(today()->addDay()).":\n".$lines->implode("\n"));

        return $list->count();
    }
}
