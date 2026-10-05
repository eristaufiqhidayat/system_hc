<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['customer_id', 'type', 'summary', 'daily_portions', 'delivery_info', 'pic', 'price_per_portion', 'payment_term_days', 'starts_at', 'ends_at'])]
class Contract extends Model
{
    public const TYPES = ['Rumah sakit', 'Kantor', 'Klinik', 'Pabrik', 'Sekolah'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(ContractShift::class);
    }

    public function diets(): HasMany
    {
        return $this->hasMany(PatientDiet::class)->orderBy('room');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(ContractDelivery::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->customer->name.' · '.$this->customer->area;
    }

    public function hasDietTracking(): bool
    {
        return in_array($this->type, ['Rumah sakit', 'Klinik'], true);
    }
}
