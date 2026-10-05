<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['contract_id', 'label', 'value', 'note'])]
class ContractShift extends Model
{
    public $timestamps = false;
}
