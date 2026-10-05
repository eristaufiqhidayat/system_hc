<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['subscription_id', 'skip_date'])]
class SubscriptionSkip extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return ['skip_date' => 'date'];
    }
}
