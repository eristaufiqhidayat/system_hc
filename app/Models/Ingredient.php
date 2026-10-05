<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'category', 'stock', 'min_stock', 'need_tomorrow', 'unit', 'supplier', 'last_price'])]
class Ingredient extends Model
{
    public const CATEGORIES = ['Protein', 'Pokok', 'Sayur', 'Bumbu', 'Kemasan'];

    protected function casts(): array
    {
        return [
            'stock' => 'float',
            'min_stock' => 'float',
            'need_tomorrow' => 'float',
        ];
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function isBelowMinimum(): bool
    {
        return $this->stock < $this->min_stock;
    }

    public function needsPurchase(): bool
    {
        return $this->stock < $this->need_tomorrow || $this->isBelowMinimum();
    }

    public function getPurchaseQtyAttribute(): float
    {
        return max($this->need_tomorrow - $this->stock, $this->min_stock - $this->stock, 0);
    }

    /**
     * @return array{0:string,1:string}
     */
    public function getStateAttribute(): array
    {
        if ($this->stock >= $this->need_tomorrow && $this->stock >= $this->min_stock) {
            return ['Cukup', 'ok'];
        }
        if ($this->stock < $this->need_tomorrow) {
            return ['Beli '.qty($this->need_tomorrow - $this->stock).' '.$this->unit, 'bad'];
        }

        return ['Mepet', 'wait'];
    }

    public function getLevelPercentAttribute(): float
    {
        return $this->min_stock > 0 ? min(100, $this->stock / ($this->min_stock * 2) * 100) : 100;
    }
}
