<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['contract_id', 'room', 'diet_type', 'note', 'valid_until', 'is_new'])]
class PatientDiet extends Model
{
    public const TYPES = ['Rendah garam', 'Makanan lunak', 'Lunak', 'Diet DM', 'Cair', 'Rendah lemak'];

    protected function casts(): array
    {
        return [
            'valid_until' => 'date',
            'is_new' => 'boolean',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function getValidLabelAttribute(): string
    {
        return $this->valid_until ? 's/d '.date_id($this->valid_until) : 'Tetap';
    }
}
