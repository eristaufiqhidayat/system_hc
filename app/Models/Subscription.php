<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['customer_id', 'package', 'total_days', 'remaining_days', 'portions', 'preference', 'delivery_window', 'starts_at', 'paused_until', 'last_reminded_at'])]
class Subscription extends Model
{
    public const PACKAGES = [
        'Harian' => ['days' => 1, 'label' => 'Harian', 'hint' => 'Pesan per hari, H-1 sebelum 19.00'],
        'Mingguan' => ['days' => 5, 'label' => 'Mingguan · 5 hari', 'hint' => 'Senin–Jumat, menu berganti tiap hari'],
        'Bulanan' => ['days' => 20, 'label' => 'Bulanan · 20 hari', 'hint' => 'Paling hemat, bisa jeda saat libur'],
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'paused_until' => 'date',
            'last_reminded_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function skips(): HasMany
    {
        return $this->hasMany(SubscriptionSkip::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('remaining_days', '>', 0)
            ->where(fn (Builder $q) => $q->whereNull('paused_until')->orWhere('paused_until', '<', today()));
    }

    public function scopeExpiringSoon(Builder $query): Builder
    {
        return $query->active()->where('remaining_days', '<=', 3);
    }

    public function scopePaused(Builder $query): Builder
    {
        return $query->whereNotNull('paused_until')->where('paused_until', '>=', today());
    }

    public function isPaused(): bool
    {
        return $this->paused_until !== null && $this->paused_until->gte(today());
    }

    public function getPackageLabelAttribute(): string
    {
        return self::PACKAGES[$this->package]['label'] ?? $this->package;
    }

    /**
     * Status & warna badge seperti di mockup: Aktif / Habis besok / Habis ≤3 hari / Dijeda.
     *
     * @return array{0:string,1:string}
     */
    public function getStateAttribute(): array
    {
        if ($this->isPaused()) {
            return ['Dijeda s/d '.date_id($this->paused_until), 'gray'];
        }
        if ($this->remaining_days <= 0) {
            return ['Habis', 'gray'];
        }
        if ($this->remaining_days === 1) {
            return ['Habis besok', 'bad'];
        }
        if ($this->remaining_days <= 3) {
            return ['Habis ≤3 hari', 'wait'];
        }

        return ['Aktif', 'ok'];
    }

    public function getEndsAtAttribute()
    {
        $from = $this->starts_at && $this->starts_at->isFuture() ? $this->starts_at : today();

        return working_days_after($from, max($this->remaining_days - 1, 0));
    }
}
