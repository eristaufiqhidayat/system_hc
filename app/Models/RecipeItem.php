<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['menu_id', 'component', 'amount', 'cost'])]
class RecipeItem extends Model
{
    public $timestamps = false;
}
