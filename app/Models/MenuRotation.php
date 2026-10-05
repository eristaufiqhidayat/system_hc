<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['week', 'weekday', 'menu_id'])]
class MenuRotation extends Model
{
    public const WEEKDAYS = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat'];

    public $timestamps = false;

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }
}
