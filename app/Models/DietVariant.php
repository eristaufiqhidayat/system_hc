<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'rule', 'active'])]
class DietVariant extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }
}
