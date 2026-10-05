<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['catering_event_id', 'label', 'done', 'sort'])]
class EventChecklistItem extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return ['done' => 'boolean'];
    }
}
