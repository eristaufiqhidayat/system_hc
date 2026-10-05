<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(private WhatsAppService $whatsapp) {}

    public function nextCode(): string
    {
        $last = Order::query()->pluck('code')
            ->map(fn (string $code) => (int) substr($code, 3))
            ->max() ?? 2000;

        return 'HC-'.($last + 1);
    }

    /**
     * Membuat pesanan baru dari form admin atau web pemesanan.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $by = null): Order
    {
        return DB::transaction(function () use ($data, $by) {
            $customer = $data['customer'] instanceof Customer
                ? $data['customer']
                : Customer::firstOrCreate(
                    ['name' => trim($data['customer'])],
                    [
                        'segment' => $data['business_line'],
                        'area' => $data['area'] ?? null,
                        'address' => $data['address'] ?? null,
                        'whatsapp' => $data['whatsapp'] ?? null,
                    ],
                );

            [$method, $status] = $this->paymentFor($data['payment'] ?? 'qris');

            $order = Order::create([
                'code' => $this->nextCode(),
                'customer_id' => $customer->id,
                'business_line' => $data['business_line'],
                'item' => $data['item'] ?? $data['business_line'].' · '.$data['portions'].' porsi',
                'portions' => $data['portions'],
                'delivery_date' => $data['delivery_date'],
                'delivery_date_end' => $data['delivery_date_end'] ?? null,
                'delivery_time' => $data['delivery_time'] ?? null,
                'address' => $data['address'] ?? $customer->address,
                'area' => $data['area'] ?? $customer->area,
                'notes' => $data['notes'] ?? null,
                'channel' => $data['channel'] ?? 'Admin',
                'payment_method' => $method,
                'payment_status' => $data['payment_status'] ?? $status,
                'status' => 'Baru',
                'total' => $data['total'] ?? 0,
            ]);

            $order->histories()->create(['status' => 'Baru', 'user_id' => $by?->id, 'changed_at' => now()]);

            return $order;
        });
    }

    /**
     * Memajukan status pesanan satu langkah (Baru → Dikonfirmasi → ... → Selesai).
     */
    public function advance(Order $order, ?User $by = null): Order
    {
        $next = $order->next_status;
        if (! $next) {
            return $order;
        }

        DB::transaction(function () use ($order, $next, $by) {
            $order->update(['status' => $next]);
            $order->histories()->create(['status' => $next, 'user_id' => $by?->id, 'changed_at' => now()]);
        });

        if (in_array($next, ['Dikonfirmasi', 'Dikirim'], true)) {
            $this->sendConfirmation($order);
        }

        return $order->refresh();
    }

    public function sendConfirmation(Order $order): void
    {
        $order->loadMissing('customer');
        $this->whatsapp->sendToCustomer($order->customer, sprintf(
            "Halo %s, pesanan #%s (%s, %d porsi) berstatus *%s*.\nJadwal antar: %s.\nTerima kasih, HC Catering 🙏",
            $order->customer->name,
            $order->code,
            $order->item,
            $order->portions,
            $order->status,
            $order->schedule_label,
        ));
    }

    /**
     * @return array{0:string,1:string} [metode, status pembayaran]
     */
    private function paymentFor(string $option): array
    {
        return match ($option) {
            'transfer' => ['Transfer', 'menunggu'],
            'invoice' => ['Invoice', 'invoice'],
            'cod' => ['COD', 'menunggu'],
            'paid_qris' => ['QRIS', 'lunas'],
            default => ['QRIS', 'menunggu'],
        };
    }

    /**
     * Jumlah porsi yang harus dimasak pada tanggal tertentu, per lini bisnis.
     *
     * @return array<string, int>
     */
    public function portionsByLine(Carbon $date): array
    {
        return Order::query()
            ->whereIn('status', ['Dikonfirmasi', 'Diproses', 'Baru'])
            ->where('channel', '!=', 'Kontrak') // porsi kontrak dihitung dari tabel kontrak
            ->whereDate('delivery_date', '<=', $date)
            ->where(fn ($q) => $q->whereDate('delivery_date', '>=', $date)->orWhereDate('delivery_date_end', '>=', $date))
            ->selectRaw('business_line, SUM(portions) as total')
            ->groupBy('business_line')
            ->pluck('total', 'business_line')
            ->map(fn ($v) => (int) $v)
            ->all();
    }
}
