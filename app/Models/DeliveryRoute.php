<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['route_date', 'area', 'courier_id', 'color', 'map_points', 'is_late', 'late_reason'])]
class DeliveryRoute extends Model
{
    protected function casts(): array
    {
        return [
            'route_date' => 'date',
            'map_points' => 'array',
            'is_late' => 'boolean',
        ];
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'courier_id');
    }

    public function stops(): HasMany
    {
        return $this->hasMany(DeliveryStop::class)->orderBy('sequence');
    }

    public function getStopsCountValueAttribute(): int
    {
        return $this->stops_count ?? $this->stops->count();
    }

    public function getDoneCountAttribute(): int
    {
        return $this->delivered_count ?? $this->stops->whereNotNull('delivered_at')->count();
    }

    public function getProgressPercentAttribute(): int
    {
        $total = $this->stops_count_value;

        return $total ? (int) round($this->done_count / $total * 100) : 0;
    }

    /**
     * @return array{0:string,1:string}
     */
    public function getStateAttribute(): array
    {
        if ($this->stops_count_value && $this->done_count >= $this->stops_count_value) {
            return ['Selesai', 'ok'];
        }
        if ($this->is_late) {
            return ['Terlambat', 'bad'];
        }

        return $this->done_count > 0 ? ['Jalan', 'info'] : ['Belum berangkat', 'gray'];
    }
}
