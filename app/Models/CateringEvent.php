<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['customer_id', 'name', 'venue_area', 'pax', 'event_date', 'event_time', 'stage', 'price_per_pax', 'equipment_cost', 'dp_received', 'menu'])]
class CateringEvent extends Model
{
    public const STAGES = ['Permintaan', 'Quotation dikirim', 'DP diterima', 'Persiapan', 'Selesai'];

    public const DEFAULT_MENU = ['Nasi putih & nasi liwet', 'Ayam bakar madu', 'Rendang daging', 'Gulai kambing', 'Capcay seafood', 'Sop kimlo', 'Kerupuk, sambal, buah', 'Es buah & air mineral'];

    public const DEFAULT_CHECKLIST = ['Chafing dish × 8', 'Meja prasmanan + taplak', 'Piring, sendok, gelas 130 set', 'Pramusaji 4 orang', 'Dekorasi gubukan', 'Tes rasa dengan klien', 'Konfirmasi jam loading'];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'dp_received' => 'boolean',
            'menu' => 'array',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function checklist(): HasMany
    {
        return $this->hasMany(EventChecklistItem::class)->orderBy('sort');
    }

    public function getStageLabelAttribute(): string
    {
        return self::STAGES[$this->stage] ?? '-';
    }

    public function getTotalAttribute(): int
    {
        return $this->pax * $this->price_per_pax + $this->equipment_cost;
    }

    public function getClientLabelAttribute(): string
    {
        return $this->customer->name.' · '.($this->venue_area ?: $this->customer->area);
    }
}
