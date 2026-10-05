<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['production_date', 'menu_name', 'office_portions', 'rantang_portions', 'hospital_portions', 'event_portions', 'diet_notes', 'cook', 'status'])]
class ProductionItem extends Model
{
    public const STATUSES = ['Belum' => 'gray', 'Persiapan' => 'wait', 'Dimasak' => 'info', 'Selesai' => 'ok'];

    protected function casts(): array
    {
        return ['production_date' => 'date'];
    }

    public function getTotalAttribute(): int
    {
        return $this->office_portions + $this->rantang_portions + $this->hospital_portions + $this->event_portions;
    }

    public function getStatusToneAttribute(): string
    {
        return self::STATUSES[$this->status] ?? 'gray';
    }
}
