<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'segment', 'area', 'address', 'whatsapp', 'preference', 'birthday', 'customer_since', 'value_tier', 'portal_token'])]
class Customer extends Model
{
    use HasFactory;

    public const SEGMENTS = ['Rantangan', 'Nasi box', 'Kantor', 'Rumah sakit', 'Klinik', 'Pabrik', 'Sekolah', 'Event'];

    protected static function booted(): void
    {
        static::creating(function (Customer $customer) {
            $customer->portal_token ??= Str::random(40);
            $customer->customer_since ??= now();
        });
    }

    protected function casts(): array
    {
        return [
            'birthday' => 'date',
            'customer_since' => 'date',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(CateringEvent::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function getInitialsAttribute(): string
    {
        return initials($this->name);
    }

    public function getValueToneAttribute(): string
    {
        return match ($this->value_tier) {
            'Tinggi' => 'ok',
            'Sedang' => 'info',
            default => 'gray',
        };
    }

    /**
     * Ringkasan jumlah pesanan seperti "Kontrak", "7 paket", "3 pesanan".
     */
    public function getOrderSummaryAttribute(): string
    {
        if ($this->contracts_count ?? $this->contracts()->count()) {
            return 'Kontrak';
        }

        $count = $this->orders_count ?? $this->orders()->count();

        return match ($this->segment) {
            'Rantangan' => "{$count} paket",
            'Event' => "{$count} event",
            default => "{$count} pesanan",
        };
    }
}
