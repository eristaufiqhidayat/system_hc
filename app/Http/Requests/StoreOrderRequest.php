<?php

namespace App\Http\Requests;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->canAccess('orders');
    }

    public function rules(): array
    {
        return [
            'customer' => ['required', 'string', 'max:120'],
            'business_line' => ['required', Rule::in(Order::LINES)],
            'portions' => ['required', 'integer', 'min:1', 'max:5000'],
            'delivery_date' => ['required', 'date'],
            'delivery_time' => ['required', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
            'payment' => ['required', Rule::in(array_keys(Order::PAYMENT_OPTIONS))],
        ];
    }

    public function attributes(): array
    {
        return [
            'customer' => 'pelanggan',
            'business_line' => 'lini bisnis',
            'portions' => 'jumlah porsi',
            'delivery_date' => 'tanggal antar',
            'delivery_time' => 'jam antar',
            'payment' => 'pembayaran',
        ];
    }
}
