<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['delivery_route_id', 'order_id', 'sequence', 'name', 'address', 'distance_km', 'tags', 'note', 'payment_label', 'collect_payment', 'eta', 'delivered_at', 'proof_photo'])]
class DeliveryStop extends Model
{
    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'collect_payment' => 'boolean',
            'delivered_at' => 'datetime',
        ];
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(DeliveryRoute::class, 'delivery_route_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
