<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Subscription;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SubscriptionService
{
    public function __construct(private WhatsAppService $whatsapp) {}

    public function pause(Subscription $subscription, Carbon $until): void
    {
        $subscription->update(['paused_until' => $until]);
        $this->whatsapp->sendToCustomer($subscription->customer, sprintf(
            'Langganan %s Anda dijeda sampai %s. Sisa %d hari tidak hangus. 🙏',
            $subscription->package_label,
            date_id($until, true),
            $subscription->remaining_days,
        ));
    }

    public function resume(Subscription $subscription): void
    {
        $subscription->update(['paused_until' => null]);
    }

    public function remind(Subscription $subscription): void
    {
        $this->whatsapp->sendToCustomer($subscription->customer, sprintf(
            'Halo %s, langganan rantangan Anda tinggal %d hari lagi. Perpanjang sekarang agar menu tetap diantar tanpa jeda: %s',
            $subscription->customer->name,
            $subscription->remaining_days,
            route('portal.show', $subscription->customer->portal_token),
        ));
        $subscription->update(['last_reminded_at' => now()]);
    }

    /**
     * Kirim pengingat ke semua langganan yang habis ≤ 3 hari.
     */
    public function remindExpiring(): int
    {
        $subs = Subscription::query()->expiringSoon()->with('customer')->get();
        $subs->each(fn (Subscription $s) => $this->remind($s));

        return $subs->count();
    }

    /**
     * Pelanggan melewati satu hari antar: masa langganan bertambah 1 hari.
     */
    public function toggleSkip(Subscription $subscription, Carbon $date): bool
    {
        $existing = $subscription->skips()->whereDate('skip_date', $date)->first();
        if ($existing) {
            $existing->delete();

            return false;
        }

        $subscription->skips()->create(['skip_date' => $date]);

        return true;
    }

    public function renew(Subscription $subscription): Subscription
    {
        $days = Subscription::PACKAGES[$subscription->package]['days'] ?? $subscription->total_days;
        $subscription->update([
            'remaining_days' => $subscription->remaining_days + $days,
            'total_days' => $subscription->remaining_days + $days,
            'paused_until' => null,
        ]);

        return $subscription;
    }

    public function subscribe(Customer $customer, string $package, int $portions, Carbon $start, ?string $preference = null, ?string $window = null): Subscription
    {
        $days = Subscription::PACKAGES[$package]['days'];

        return $customer->subscriptions()->create([
            'package' => $package,
            'total_days' => $days,
            'remaining_days' => $days,
            'portions' => $portions,
            'preference' => $preference,
            'delivery_window' => $window ?? '11.00–12.00',
            'starts_at' => $start,
        ]);
    }

    /**
     * Jadwal 5 hari kerja berikutnya beserta menu rotasi dan status lewati.
     *
     * @return Collection<int, array{date:Carbon, menu:string, skipped:bool}>
     */
    public function upcomingSchedule(Subscription $subscription, MenuService $menus): Collection
    {
        $skips = $subscription->skips()->pluck('skip_date')->map(fn ($d) => $d->toDateString())->all();
        $date = today()->next(Carbon::MONDAY);

        return collect(range(0, 4))->map(function (int $i) use ($date, $skips, $menus) {
            $day = $date->copy()->addDays($i);

            return [
                'date' => $day,
                'menu' => $menus->menuForDate($day)?->name ?? '—',
                'skipped' => in_array($day->toDateString(), $skips, true),
            ];
        });
    }
}
