<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'customer_id', 'business_line', 'item', 'portions', 'delivery_date', 'delivery_date_end', 'delivery_time', 'address', 'area', 'notes', 'channel', 'payment_method', 'payment_status', 'status', 'total'])]
class Order extends Model
{
    use HasFactory;

    public const STATUSES = ['Baru', 'Dikonfirmasi', 'Diproses', 'Dikirim', 'Selesai'];

    public const LINES = ['Rantangan', 'Nasi box', 'Kantor', 'Rumah sakit', 'Event'];

    public const CHANNELS = ['WA bot', 'Web', 'Admin', 'Kontrak'];

    public const PAYMENT_OPTIONS = [
        'qris' => 'Kirim link QRIS via WhatsApp',
        'transfer' => 'Transfer bank',
        'invoice' => 'Invoice (klien kontrak)',
        'cod' => 'Bayar di tempat',
    ];

    protected function casts(): array
    {
        return [
            'delivery_date' => 'date',
            'delivery_date_end' => 'date',
            'portions' => 'integer',
            'total' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('changed_at');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('code', 'like', "%{$term}%")
                ->orWhere('area', 'like', "%{$term}%")
                ->orWhereHas('customer', fn (Builder $c) => $c->where('name', 'like', "%{$term}%"));
        });
    }

    public function getStatusToneAttribute(): string
    {
        return match ($this->status) {
            'Baru' => 'wait',
            'Dikonfirmasi', 'Diproses', 'Dikirim' => 'info',
            'Selesai' => 'ok',
            default => 'gray',
        };
    }

    public function getPaymentLabelAttribute(): string
    {
        return match ($this->payment_status) {
            'lunas' => 'Lunas '.$this->payment_method,
            'dp' => 'DP 50%',
            'invoice' => 'Invoice bulanan',
            default => 'Menunggu bayar',
        };
    }

    public function getPaymentToneAttribute(): string
    {
        return match ($this->payment_status) {
            'lunas' => 'ok',
            'dp' => 'wait',
            'invoice' => 'info',
            default => 'bad',
        };
    }

    public function getScheduleLabelAttribute(): string
    {
        $date = $this->delivery_date_end && ! $this->delivery_date_end->equalTo($this->delivery_date)
            ? date_range_id($this->delivery_date, $this->delivery_date_end)
            : date_id($this->delivery_date);

        return trim($date.' · '.$this->delivery_time, ' ·');
    }

    /**
     * Jumlah hari antar (Senin–Jumat) untuk pesanan berjangka seperti paket rantangan.
     */
    public function getServingDaysAttribute(): int
    {
        if (! $this->delivery_date_end || $this->delivery_date_end->lte($this->delivery_date)) {
            return 1;
        }

        return max(1, (int) $this->delivery_date->diffInWeekdays($this->delivery_date_end) + 1);
    }

    public function getTotalPortionsAttribute(): int
    {
        return $this->portions * $this->serving_days;
    }

    public function getNextStatusAttribute(): ?string
    {
        $index = array_search($this->status, self::STATUSES, true);

        return self::STATUSES[$index + 1] ?? null;
    }
}
