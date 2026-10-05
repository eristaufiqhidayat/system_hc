<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'schedule', 'active'])]
class ScheduledMessage extends Model
{
    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }
}
