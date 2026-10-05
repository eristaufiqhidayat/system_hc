<?php

namespace App\Services;

use App\Models\CateringEvent;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function __construct(private WhatsAppService $whatsapp) {}

    public function nextNumber(Carbon $date, string $suffix = ''): string
    {
        $prefix = 'INV-'.$date->format('ym').'-';
        if ($suffix) {
            $count = Invoice::where('number', 'like', $prefix.$suffix.'%')->count();

            return $prefix.$suffix.($count + 1);
        }

        $last = Invoice::where('number', 'like', $prefix.'%')
            ->pluck('number')
            ->map(fn ($n) => (int) substr($n, strlen($prefix)))
            ->max() ?? 0;

        return $prefix.str_pad((string) ($last + 1), 3, '0', STR_PAD_LEFT);
    }

    /**
     * Rekap porsi kontrak dalam satu bulan.
     *
     * @return array{portions:int, extra:int, amount:int}
     */
    public function contractRecap(Contract $contract, Carbon $month): array
    {
        $rows = $contract->deliveries()
            ->whereBetween('delivered_on', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->selectRaw('COALESCE(SUM(portions),0) as portions, COALESCE(SUM(extra_portions),0) as extra')
            ->first();

        $portions = (int) $rows->portions;
        $extra = (int) $rows->extra;

        return [
            'portions' => $portions,
            'extra' => $extra,
            'amount' => ($portions + $extra) * $contract->price_per_portion,
        ];
    }

    public function issueForContract(Contract $contract, Carbon $month): Invoice
    {
        $period = month_id($month->month, false).' '.$month->year;

        $existing = $contract->invoices()->where('period', $period)->first();
        if ($existing) {
            return $existing;
        }

        $recap = $this->contractRecap($contract, $month);

        $invoice = Invoice::create([
            'number' => $this->nextNumber($month),
            'customer_id' => $contract->customer_id,
            'contract_id' => $contract->id,
            'period' => $period,
            'amount' => $recap['amount'],
            'issued_at' => today(),
            'due_at' => today()->addDays($contract->payment_term_days),
        ]);

        $this->whatsapp->sendToCustomer($contract->customer, sprintf(
            'Invoice %s periode %s sebesar %s telah terbit. Jatuh tempo %s.',
            $invoice->number, $period, rupiah($invoice->amount), date_id($invoice->due_at, true),
        ));

        return $invoice;
    }

    /**
     * Membuat invoice DP 50% atau pelunasan untuk event.
     */
    public function issueForEvent(CateringEvent $event, string $kind): Invoice
    {
        $half = (int) round($event->total / 2);
        $isDp = $kind === 'dp';

        return Invoice::create([
            'number' => $this->nextNumber($event->event_date, 'EV'),
            'customer_id' => $event->customer_id,
            'catering_event_id' => $event->id,
            'period' => ($isDp ? 'DP 50%' : 'Pelunasan').' · '.date_id($event->event_date),
            'amount' => $isDp ? $half : $event->total - $half,
            'issued_at' => today(),
            'due_at' => $isDp ? today()->addDays(3) : $event->event_date->copy()->subDay(),
        ]);
    }

    public function markPaid(Invoice $invoice, string $method = 'Transfer'): Invoice
    {
        DB::transaction(function () use ($invoice, $method) {
            $invoice->update(['paid_at' => now()]);
            Payment::create([
                'customer_id' => $invoice->customer_id,
                'invoice_id' => $invoice->id,
                'method' => $method,
                'amount' => $invoice->amount,
                'description' => 'Pembayaran '.$invoice->number,
                'paid_at' => now(),
            ]);

            if ($invoice->catering_event_id && str_starts_with($invoice->period, 'DP')) {
                $invoice->event?->update(['dp_received' => true, 'stage' => max(2, $invoice->event->stage)]);
            }
        });

        return $invoice->refresh();
    }

    public function remindOverdue(): int
    {
        $invoices = Invoice::overdue()->with('customer')->get();

        foreach ($invoices as $invoice) {
            $this->whatsapp->sendToCustomer($invoice->customer, sprintf(
                'Pengingat: invoice %s (%s) sebesar %s telah lewat jatuh tempo %s. Mohon segera diselesaikan. Terima kasih.',
                $invoice->number, $invoice->period, rupiah($invoice->amount), date_id($invoice->due_at, true),
            ));
            $invoice->update(['last_reminded_at' => now()]);
        }

        return $invoices->count();
    }
}
