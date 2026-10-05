<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['number', 'customer_id', 'contract_id', 'catering_event_id', 'period', 'amount', 'issued_at', 'due_at', 'paid_at', 'last_reminded_at'])]
class Invoice extends Model
{
    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'due_at' => 'date',
            'paid_at' => 'datetime',
            'last_reminded_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(CateringEvent::class, 'catering_event_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->whereNull('paid_at');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->unpaid()->where('due_at', '<', today());
    }

    public function isOverdue(): bool
    {
        return $this->paid_at === null && $this->due_at->lt(today());
    }

    public function getClientLabelAttribute(): string
    {
        return $this->catering_event_id
            ? $this->customer->name.' (event)'
            : $this->customer->name.' · '.$this->customer->area;
    }

    /**
     * @return array{0:string,1:string}
     */
    public function getStateAttribute(): array
    {
        if ($this->paid_at) {
            return ['Lunas', 'ok'];
        }
        if ($this->isOverdue()) {
            return ['Lewat '.(int) $this->due_at->diffInDays(today()).' hari', 'bad'];
        }
        if ($this->catering_event_id) {
            return ['Menunggu', 'wait'];
        }

        return ['Belum jatuh tempo', 'info'];
    }
}
