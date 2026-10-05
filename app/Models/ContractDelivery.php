<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['contract_id', 'delivered_on', 'portions', 'extra_portions'])]
class ContractDelivery extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return ['delivered_on' => 'date'];
    }
}
