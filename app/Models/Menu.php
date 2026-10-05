<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'selling_price', 'recipe_locked', 'rating'])]
class Menu extends Model
{
    protected function casts(): array
    {
        return ['recipe_locked' => 'boolean'];
    }

    public function recipeItems(): HasMany
    {
        return $this->hasMany(RecipeItem::class);
    }

    public function rotations(): HasMany
    {
        return $this->hasMany(MenuRotation::class);
    }

    public function getHppAttribute(): int
    {
        return (int) $this->recipeItems->sum('cost');
    }

    public function getMarginPercentAttribute(): ?float
    {
        if (! $this->selling_price) {
            return null;
        }

        return round(($this->selling_price - $this->hpp) / $this->selling_price * 100, 1);
    }
}
